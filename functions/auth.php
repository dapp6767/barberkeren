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
            return [
                'status'  => false,
                'message' => 'Nama Lengkap mengandung kata/unsur SARA atau profanitas yang dilarang! Silakan gunakan nama yang sopan.',
                'field'   => 'fullname'
            ];
        }
        if (contains_sara_words($username)) {
            return [
                'status'  => false,
                'message' => 'Username mengandung kata/unsur SARA atau profanitas yang dilarang! Silakan gunakan username lain.',
                'field'   => 'username'
            ];
        }

        try {
            // 1. Cek duplikasi username (prioritas utama)
            if ($exclude_user_id) {
                $stmtUser = $pdo->prepare("SELECT id_user FROM users WHERE LOWER(username) = LOWER(?) AND id_user != ? LIMIT 1");
                $stmtUser->execute([$username, $exclude_user_id]);
            } else {
                $stmtUser = $pdo->prepare("SELECT id_user FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
                $stmtUser->execute([$username]);
            }

            if ($stmtUser->fetch()) {
                return [
                    'status'  => false,
                    'message' => 'Username sudah digunakan, silakan gunakan username lain.',
                    'field'   => 'username'
                ];
            }

            // 2. Cek duplikasi email jika diisi
            if (!empty($email)) {
                if ($exclude_user_id) {
                    $stmtEmail = $pdo->prepare("SELECT id_user FROM users WHERE email != '' AND LOWER(email) = LOWER(?) AND id_user != ? LIMIT 1");
                    $stmtEmail->execute([$email, $exclude_user_id]);
                } else {
                    $stmtEmail = $pdo->prepare("SELECT id_user FROM users WHERE email != '' AND LOWER(email) = LOWER(?) LIMIT 1");
                    $stmtEmail->execute([$email]);
                }

                if ($stmtEmail->fetch()) {
                    return [
                        'status'  => false,
                        'message' => "Email '{$email}' sudah terdaftar! Gunakan email lain.",
                        'field'   => 'email'
                    ];
                }
            }
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Error pengecekan database: ' . $e->getMessage()];
        }

        if (strlen($password) < 6) {
            return ['status' => false, 'message' => 'Password minimal harus terdiri dari 6-8 karakter!', 'field' => 'password'];
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return ['status' => false, 'message' => 'Password wajib mengandung minimal satu Huruf Besar (A-Z)!', 'field' => 'password'];
        }
        if (!preg_match('/[a-z]/', $password)) {
            return ['status' => false, 'message' => 'Password wajib mengandung minimal satu Huruf Kecil (a-z)!', 'field' => 'password'];
        }
        if (!preg_match('/[0-9]/', $password)) {
            return ['status' => false, 'message' => 'Password wajib mengandung minimal satu Angka (0-9)!', 'field' => 'password'];
        }
        if (!preg_match('/[\W_]/', $password)) {
            return ['status' => false, 'message' => 'Password wajib mengandung minimal satu Simbol Khusus (misal: @, #, !, $, %, dll)!', 'field' => 'password'];
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

        if (empty($fullname)) {
            return ['status' => false, 'message' => 'Nama lengkap wajib diisi!', 'field' => 'fullname'];
        }
        if (empty($username)) {
            return ['status' => false, 'message' => 'Username wajib diisi!', 'field' => 'username'];
        }
        if (empty($email)) {
            return ['status' => false, 'message' => 'Email wajib diisi!', 'field' => 'email'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => false, 'message' => 'Format email tidak valid! Silakan masukkan email yang benar.', 'field' => 'email'];
        }
        if (empty($phone)) {
            return ['status' => false, 'message' => 'Nomor HP / WhatsApp wajib diisi!', 'field' => 'phone'];
        }
        if (empty($password)) {
            return ['status' => false, 'message' => 'Password wajib diisi!', 'field' => 'password'];
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
            // Tangani duplicate entry constraint jika ada
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry')) {
                if (str_contains(strtolower($e->getMessage()), 'username')) {
                    return ['status' => false, 'message' => 'Username sudah digunakan, silakan gunakan username lain.', 'field' => 'username'];
                }
                if (str_contains(strtolower($e->getMessage()), 'email')) {
                    return ['status' => false, 'message' => "Email '{$email}' sudah terdaftar! Gunakan email lain.", 'field' => 'email'];
                }
            }
            return ['status' => false, 'message' => 'Error Database: ' . $e->getMessage()];
        }
    }
}

/**
 * Login User (Mendukung Validasi Role)
 */
if (!function_exists('login_user')) {
    function login_user($username_email, $password, $selected_role = null, $remember = false) {
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

                    // Remember Me: hanya buat token & cookie jika user mencentang checkbox
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $update_token = $pdo->prepare("UPDATE users SET remember_token = ?, is_online = 1, last_active = NOW() WHERE id_user = ?");
                        $update_token->execute([$token, $user['id_user']]);
                        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                        setcookie('remember_me', $token, time() + (86400 * 30), '/', '', $secure, true);
                    } else {
                        // Hapus token lama jika ada (login tanpa remember)
                        $pdo->prepare("UPDATE users SET is_online = 1, last_active = NOW() WHERE id_user = ?")->execute([$user['id_user']]);
                    }

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
