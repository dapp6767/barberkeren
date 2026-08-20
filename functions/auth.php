<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/notifikasi.php';

/**
 * Helper Pemeriksa Kata/Unsur SARA & Profanitas Dilarang
 */
if (!function_exists('contains_sara_words')) {
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
}

/**
 * Validasi Lengkap Akun Baru (SARA, Duplikat Nama/Username, Password Kompleks)
 */
if (!function_exists('validate_account_creation')) {
    function validate_account_creation($fullname, $username, $password, $email = '', $exclude_user_id = null) {
        $pdo = get_koneksi();

        $fullname = trim($fullname);
        $username = trim($username);
        $email    = trim($email);

        if (contains_sara_words($fullname)) {
            return ['status' => false, 'message' => 'Nama Lengkap mengandung kata/unsur SARA atau profanitas yang dilarang! Silakan gunakan nama yang sopan.'];
        }
        if (contains_sara_words($username)) {
            return ['status' => false, 'message' => 'Username mengandung kata/unsur SARA atau profanitas yang dilarang! Silakan gunakan username lain.'];
        }

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
}

/**
 * Registrasi User Baru
 */
if (!function_exists('register_user')) {
    function register_user($fullname, $username, $email, $phone, $password) {
        $pdo = get_koneksi();

        $fullname = trim($fullname);
        $username = trim($username);
        $email    = trim($email);
        $phone    = trim($phone);

        if (empty($username) || empty($password)) {
            return ['status' => false, 'message' => 'Username dan Password wajib diisi!'];
        }

        $val = validate_account_creation($fullname, $username, $password, $email);
        if (!$val['status']) {
            return $val;
        }

        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
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
}

/**
 * Login User (Mendukung Validasi Role)
 */
if (!function_exists('login_user')) {
    function login_user($username_email, $password, $selected_role = null) {
        $pdo = get_koneksi();

        $username_email = trim($username_email);

        if (empty($username_email) || empty($password)) {
            return ['status' => false, 'message' => 'Username/Email dan Password wajib diisi!'];
        }

        // --- BRUTE-FORCE RATE LIMITING (ANTI TAHAPAN PEMBOBOLAN) ---
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['last_login_attempt_time'] ?? 0;
        $lockoutSeconds = 60; // Waktu tunggu 60 detik jika 5x gagal

        if ($attempts >= 5) {
            $timePassed = time() - $lastAttempt;
            if ($timePassed < $lockoutSeconds) {
                $remaining = $lockoutSeconds - $timePassed;
                return [
                    'status'  => false,
                    'message' => "🛑 Terlalu banyak percobaan login yang gagal! Demi keamanan akun Anda, silakan tunggu {$remaining} detik lagi sebelum mencoba kembali."
                ];
            } else {
                // Waktu pendinginan habis, reset ulang penghitung
                $_SESSION['login_attempts'] = 0;
            }
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username_email, $username_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (!empty($selected_role) && $user['role'] !== $selected_role) {
                    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                    $_SESSION['last_login_attempt_time'] = time();
                    return [
                        'status'  => false,
                        'message' => 'Role/Akses yang Anda pilih (' . strtoupper($selected_role) . ') tidak sesuai dengan akun ini!'
                    ];
                }

                $password_valid = false;
                $needs_rehash   = false;

                if (password_verify($password, $user['password'])) {
                    $password_valid = true;
                    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                        $needs_rehash = true;
                    }
                } elseif ($password === $user['password']) {
                    $password_valid = true;
                    $needs_rehash   = true;
                }

                if ($password_valid) {
                    if ($needs_rehash) {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                        $update_stmt->execute([$new_hash, $user['id_user']]);
                    }

                    session_regenerate_id(true);

                    // Reset penghitung percobaan gagal jika login berhasil
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['last_login_attempt_time']);

                    $_SESSION['user_id']   = $user['id_user'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['fullname']  = !empty($user['fullname']) ? $user['fullname'] : $user['username'];
                    $_SESSION['user_role'] = $user['role'];

                    $token = bin2hex(random_bytes(32));
                    $update_token = $pdo->prepare("UPDATE users SET remember_token = ?, is_online = 1, last_active = NOW() WHERE id_user = ?");
                    $update_token->execute([$token, $user['id_user']]);
                    
                    setcookie('remember_me', $token, time() + (86400 * 30), "/", "", false, true);

                    if (function_exists('touch_user_activity')) {
                        touch_user_activity();
                    }

                    return [
                        'status' => true,
                        'role'   => $user['role'],
                        'message'=> 'Login berhasil!'
                    ];
                }
            }

            // Gagal login: tambahkan hitungan percobaan
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['last_login_attempt_time'] = time();

            $currentCount = $_SESSION['login_attempts'];
            $remaining = 5 - $currentCount;
            
            if ($remaining > 0) {
                return ['status' => false, 'message' => "Username/Email atau Password salah! (Sisa kesempatan mencoba: {$remaining}x)"];
            } else {
                return ['status' => false, 'message' => '🛑 Terlalu banyak percobaan gagal! Akses login dikunci sementara selama 60 detik demi keamanan.'];
            }
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Error Database: ' . $e->getMessage()];
        }
    }
}
