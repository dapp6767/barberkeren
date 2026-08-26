<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sanitize user inputs
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Set Flash Messages
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Display Flash Messages
function display_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $type = $flash['type']; // 'success', 'danger', 'warning', 'info'
        $icon = ($type === 'danger') ? 'error' : $type;
        $title = ($type === 'success') ? 'Berhasil' : (($type === 'danger') ? 'Gagal' : 'Perhatian');
        $msg = addslashes($flash['message']);
        
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '{$icon}',
                    title: '{$title}',
                    html: '{$msg}',
                    confirmButtonColor: '#d4af37',
                    background: '#18181b',
                    color: '#fff',
                    customClass: {
                        popup: 'border border-zinc-700 shadow-2xl rounded-xl'
                    }
                });
            });
        </script>";
        unset($_SESSION['flash']);
    }
}

// Redirect Helper
function redirect($url) {
    header("Location: " . $url);
    exit;
}
// Touch User Last Active Timestamp & Multi-Device Active Session Tracker
function touch_user_activity() {
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        if (!isset($pdo)) {
            $db_path = __DIR__ . '/../config/database.php';
            if (file_exists($db_path)) {
                require_once $db_path;
            }
        }
        if (isset($pdo)) {
            $user_id = (int)$_SESSION['user_id'];
            $sess_id = session_id();
            if (!$sess_id) return;

            $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
            $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);

            // Update every 30 seconds or on first page hit
            if (!isset($_SESSION['last_active_updated']) || (time() - $_SESSION['last_active_updated']) > 30) {
                try {
                    // Auto-ensure user_sessions table exists
                    $pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
                        session_id VARCHAR(128) PRIMARY KEY,
                        user_id INT NOT NULL,
                        user_agent VARCHAR(255) DEFAULT '',
                        ip_address VARCHAR(45) DEFAULT '',
                        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        KEY idx_user (user_id),
                        KEY idx_last_activity (last_activity)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                    // Insert or update current device session
                    $stmtSess = $pdo->prepare("INSERT INTO user_sessions (session_id, user_id, user_agent, ip_address, last_activity) 
                                               VALUES (?, ?, ?, ?, NOW()) 
                                               ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), user_agent = VALUES(user_agent), ip_address = VALUES(ip_address), last_activity = NOW()");
                    $stmtSess->execute([$sess_id, $user_id, $agent, $ip]);

                    // Update main users table is_online flag & timestamp
                    $stmtUsers = $pdo->prepare("UPDATE users SET is_online = 1, last_active = NOW() WHERE id_user = ?");
                    $stmtUsers->execute([$user_id]);

                    $_SESSION['last_active_updated'] = time();
                } catch (Exception $e) {}
            }
        }
    }
}

// Check if user is logged in
function is_logged_in() {
    if (isset($_SESSION['user_id'])) {
        touch_user_activity();
        return true;
    }

    // Persistent login check
    if (isset($_COOKIE['remember_me'])) {
        global $pdo;
        
        // Ensure $pdo is available
        if (!isset($pdo)) {
            $db_path = __DIR__ . '/../config/database.php';
            if (file_exists($db_path)) {
                require_once $db_path;
            } else {
                return false;
            }
        }

        $token = $_COOKIE['remember_me'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? LIMIT 1");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Auto login
                $_SESSION['user_id']   = $user['id_user'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['fullname']  = !empty($user['fullname']) ? $user['fullname'] : $user['username'];
                $_SESSION['user_role'] = $user['role'];
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    return false;
}

// Require Login
function require_login() {
    if (!is_logged_in()) {
        set_flash('danger', 'Silakan login terlebih dahulu.');
        redirect('../auth/login.php');
    }
}

// Require Specific Role
function require_role($role) {
    require_login();
    if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
        set_flash('danger', 'Anda tidak memiliki hak akses ke halaman ini.');
        redirect('../pelanggan/dashboard.php');
    }
}

// Record Website Visitor (Deprecated)
function record_website_visit() {
    // Feature disabled
}

/**
 * Buat Notifikasi Admin
 */
if (!function_exists('create_admin_notification')) {
    function create_admin_notification($type, $title, $message, $link = '') {
        global $pdo;
        if (!isset($pdo)) {
            $db_path = __DIR__ . '/../config/database.php';
            if (file_exists($db_path)) {
                require_once $db_path;
            }
        }
        if (isset($pdo)) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS notifikasi (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    type VARCHAR(50) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    link VARCHAR(255) DEFAULT '',
                    is_read TINYINT(1) DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                $chkUserCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
                if (!$chkUserCol || $chkUserCol->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                }

                $stmt = $pdo->prepare("INSERT INTO notifikasi (type, title, message, link, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
                return $stmt->execute([$type, $title, $message, $link]);
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
}

/**
 * Get Service Image URL consistently across Index, Pelanggan, and Admin
 */
if (!function_exists('get_service_image_url')) {
    function get_service_image_url($layanan, $prefix = '') {
        $id = (int)($layanan['id'] ?? $layanan['id_service'] ?? 0);
        $name = trim(strtolower($layanan['nama_layanan'] ?? $layanan['service_name'] ?? ''));
        $db_gambar = trim($layanan['gambar'] ?? '');

        // 1. DB gambar column
        if (!empty($db_gambar)) {
            $local_path = __DIR__ . '/../asset/image/' . $db_gambar;
            if (is_file($local_path)) {
                return $prefix . 'asset/image/' . $db_gambar;
            }
        }

        // 2. Check glob for layanan_{id}.*
        if ($id > 0) {
            $files = glob(__DIR__ . '/../asset/image/layanan_' . $id . '.*');
            if (!empty($files)) {
                return $prefix . 'asset/image/' . basename($files[0]);
            }
        }

        // 3. Fallback default images mapping
        $default_images = [
            'pangkas rambut biasa' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
            'pangkas rambut luar biasa' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
            'pridecut' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
            'maxcut' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
            'paket cukur sultan' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
            'paket cukur segar' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
            'hair coloring' => 'https://images.unsplash.com/photo-1620331311520-246422fd82f9?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
            'hairlight' => $prefix . 'asset/image/hairlight.png',
            'full hairlight' => $prefix . 'asset/image/full_hairlight.png',
            'hair tattoo' => 'https://images.unsplash.com/photo-1593702295094-aea22597af65?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
            'shave' => 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
            'korean wave' => 'https://images.unsplash.com/photo-1605497788044-5a32c7078486?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'
        ];

        return $default_images[$name] ?? 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
    }
}
?>