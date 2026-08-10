<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (file_exists(__DIR__ . '/helper.php')) {
    require_once __DIR__ . '/helper.php';
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

    if (empty($username) || empty($email) || empty($password)) {
        return ['status' => false, 'message' => 'Semua kolom wajib diisi!'];
    }

    try {
        // Cek keberadaan username atau email
        $stmt = $pdo->prepare("SELECT id_user FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['status' => false, 'message' => 'Username atau Email sudah terdaftar!'];
        }

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