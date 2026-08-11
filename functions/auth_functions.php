<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (file_exists(__DIR__ . '/helper.php')) {
    require_once __DIR__ . '/helper.php';
}

/**
 * Helper Pemeriksa Kata/Unsur SARA & Profanitas Dilarang
 */
function contains_sara_words($text) {
    if (empty($text)) return false;
    $text = strtolower($text);
    
    $forbidden_words = [
        'cina', 'pribumi', 'kafir', 'kontol', 'memek', 'jancok', 'asu',
        'anjing', 'babi', 'monyet', 'kintil', 'pantek', 'puki', 'goblok', 'tolol',
        'itil', 'lonte', 'pelacur', 'nigger', 'nigga', 'peler', 'pepek', 'bajingan',
        'sara', 'rasis', 'teroris', 'nazi', 'fasis', 'pki'
    ];

    foreach ($forbidden_words as $word) {
        $pattern = '/\b' . preg_quote(strtolower($word), '/') . '\b/i';
        if (preg_match($pattern, $text) || str_contains($text, strtolower($word))) {
            return true;
        }
    }
    return false;
}

/**
 * Validasi Lengkap Akun Baru (SARA, Duplikat Nama/Username, Password Kompleks)
 */
function validate_account_creation($fullname, $username, $password, $email = '', $exclude_user_id = null) {
    global $pdo;

    $fullname = trim($fullname);
    $username = trim($username);
    $email    = trim($email);

    // 1. Cek Unsur SARA pada Nama Lengkap & Username
    if (contains_sara_words($fullname)) {
        return ['status' => false, 'message' => 'Nama Lengkap mengandung kata/unsur SARA atau profanitas yang dilarang! Silakan gunakan nama yang sopan.'];
    }
    if (contains_sara_words($username)) {
        return ['status' => false, 'message' => 'Username mengandung kata/unsur SARA atau profanitas yang dilarang! Silakan gunakan username lain.'];
    }

    // 2. Cek Duplikat Username, Email, atau Nama Lengkap di Database
    try {
        if ($exclude_user_id) {
            $stmt = $pdo->prepare("SELECT id_user, username, fullname, email FROM users WHERE (LOWER(username) = LOWER(?) OR (email != '' AND LOWER(email) = LOWER(?)) OR (fullname != '' AND LOWER(fullname) = LOWER(?))) AND id_user != ? LIMIT 1");
            $stmt->execute([$username, $email, $fullname, $exclude_user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id_user, username, fullname, email FROM users WHERE LOWER(username) = LOWER(?) OR (email != '' AND LOWER(email) = LOWER(?)) OR (fullname != '' AND LOWER(fullname) = LOWER(?)) LIMIT 1");
            $stmt->execute([$username, $email, $fullname]);
        }

        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            if (strtolower($existing['username']) === strtolower($username)) {
                return ['status' => false, 'message' => "Username '@{$username}' sudah digunakan! Silakan pilih username lain."];
            }
            if (!empty($email) && strtolower($existing['email'] ?? '') === strtolower($email)) {
                return ['status' => false, 'message' => "Email '{$email}' sudah terdaftar! Gunakan email lain."];
            }
            if (!empty($fullname) && strtolower($existing['fullname'] ?? '') === strtolower($fullname)) {
                return ['status' => false, 'message' => "Nama lengkap '{$fullname}' sudah terdaftar dalam sistem! Silakan gunakan nama lain."];
            }
        }
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Error pengecekan database: ' . $e->getMessage()];
    }

    // 3. Validasi Kekuatan Password Kompleks (Panjang min. 6-8 Karakter + Kombinasi)
    if (strlen($password) < 6) {
        return ['status' => false, 'message' => 'Password minimal harus terdiri dari 6-8 karakter!'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['status' => false, 'message' => 'Password wajib mengandung minimal satu Huruf Besar (A-Z)!'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['status' => false, 'message' => 'Password wajib mengandung minimal satu Huruf Kecil (a-z)!'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['status' => false, 'message' => 'Password wajib mengandung minimal satu Angka (0-9)!'];
    }
    if (!preg_match('/[\W_]/', $password)) {
        return ['status' => false, 'message' => 'Password wajib mengandung minimal satu Simbol Khusus (misal: @, #, !, $, %, dll)!'];
    }

    return ['status' => true, 'message' => 'Validasi berhasil!'];
}

/**
 * Registrasi User Baru
 */
function register_user($fullname, $username, $email, $phone, $password) {
    global $pdo;

    // Sanitasi input dasar
    $fullname = trim($fullname);
    $username = trim($username);
    $email    = trim($email);
    $phone    = trim($phone);

    if (empty($username) || empty($password)) {
        return ['status' => false, 'message' => 'Username dan Password wajib diisi!'];
    }

    // Jalankan Validasi Ketat (SARA, Duplikat Nama/Username, Password Kompleks)
    $val = validate_account_creation($fullname, $username, $password, $email);
    if (!$val['status']) {
        return $val;
    }

    try {
        // Hash password demi keamanan
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Simpan data user baru (Role default: pelanggan)
        $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, 'pelanggan')");
        $success = $stmt->execute([$fullname, $username, $email, $phone, $hashed_password]);

        if ($success) {
            $displayName = !empty($fullname) ? $fullname : $username;
            if (function_exists('create_admin_notification')) {
                create_admin_notification(
                    'user_register',
                    'Pendaftaran Pelanggan Baru',
                    "Pelanggan baru \"{$displayName}\" (@{$username}) baru saja mendaftar!",
                    "admin.php?page=akun#card-pendaftaran-baru"
                );
            }
            return ['status' => true, 'message' => 'Registrasi berhasil! Silakan login.'];
        }
        return ['status' => false, 'message' => 'Terjadi kesalahan sistem saat mendaftar.'];
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Error Database: ' . $e->getMessage()];
    }
}

/**
 * Login User (Mendukung Validasi Role)
 */
function login_user($username_email, $password, $selected_role = null) {
    global $pdo;

    $username_email = trim($username_email);

    if (empty($username_email) || empty($password)) {
        return ['status' => false, 'message' => 'Username/Email dan Password wajib diisi!'];
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username_email, $username_email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Validasi Role jika user memilih role pada form login
            if (!empty($selected_role) && $user['role'] !== $selected_role) {
                return [
                    'status'  => false,
                    'message' => 'Role/Akses yang Anda pilih (' . strtoupper($selected_role) . ') tidak sesuai dengan akun ini!'
                ];
            }

            $password_valid = false;
            $needs_rehash   = false;

            // 1. Cek dengan password_verify standard
            if (password_verify($password, $user['password'])) {
                $password_valid = true;
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $needs_rehash = true;
                }
            } 
            // 2. Fallback untuk Plain Text (Data lama/testing)
            elseif ($password === $user['password']) {
                $password_valid = true;
                $needs_rehash   = true;
            }

            // Jika Password Valid
            if ($password_valid) {
                // Auto-upgrade password jika masih plain text / hash lama
                if ($needs_rehash) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                    $update_stmt->execute([$new_hash, $user['id_user']]);
                }

                // Mencegah Session Fixation
                session_regenerate_id(true);

                // Set Session User
                $_SESSION['user_id']   = $user['id_user'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['fullname']  = !empty($user['fullname']) ? $user['fullname'] : $user['username'];
                $_SESSION['user_role'] = $user['role'];

                // Persistent Login / Remember Me Token & Real-Time Online Status
                $token = bin2hex(random_bytes(32));
                $update_token = $pdo->prepare("UPDATE users SET remember_token = ?, is_online = 1, last_active = NOW() WHERE id_user = ?");
                $update_token->execute([$token, $user['id_user']]);
                
                // Set cookie for 30 days
                setcookie('remember_me', $token, time() + (86400 * 30), "/", "", false, true);

                return [
                    'status' => true,
                    'role'   => $user['role'],
                    'message'=> 'Login berhasil!'
                ];
            }
        }

        return ['status' => false, 'message' => 'Username/Email atau Password salah!'];
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Error Database: ' . $e->getMessage()];
    }
}

/**
 * Forgot Password - Generate Reset Token
 */
function generate_password_reset($email) {
    global $pdo;

    $email = trim($email);

    try {
        $stmt = $pdo->prepare("SELECT id_user, fullname FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['status' => false, 'message' => 'Email tidak ditemukan dalam sistem!'];
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $update->execute([$token, $expires, $email]);

        return [
            'status'  => true,
            'token'   => $token,
            'message' => 'Token reset password telah dibuat. Gunakan link tersebut untuk mereset password Anda.'
        ];
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Error Database: ' . $e->getMessage()];
    }
}

/**
 * Reset Password dengan Token
 */
function reset_password_with_token($token, $new_password) {
    global $pdo;

    if (empty($token) || empty($new_password)) {
        return ['status' => false, 'message' => 'Token dan Password baru wajib diisi!'];
    }

    try {
        $stmt = $pdo->prepare("SELECT id_user FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['status' => false, 'message' => 'Token reset tidak valid atau sudah kadaluwarsa.'];
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id_user = ?");
        $update->execute([$hashed_password, $user['id_user']]);

        return ['status' => true, 'message' => 'Password berhasil diperbarui! Silakan login kembali.'];
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Error Database: ' . $e->getMessage()];
    }
}