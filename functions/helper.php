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
// Touch User Last Active Timestamp
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
            if (!isset($_SESSION['last_active_updated']) || (time() - $_SESSION['last_active_updated']) > 300) {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET is_online = 1, last_active = NOW() WHERE id_user = ?");
                    $stmt->execute([$_SESSION['user_id']]);
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

// Record Website Visitor
function record_website_visit() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Only record once per session hour to avoid spamming database
    $last_recorded = $_SESSION['last_visit_recorded'] ?? 0;
    if (time() - $last_recorded > 1800) { // 30 minutes
        global $pdo;
        if (!isset($pdo)) {
            $db_path = __DIR__ . '/../config/database.php';
            if (file_exists($db_path)) {
                require_once $db_path;
            }
        }
        if (isset($pdo)) {
            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $user_id = $_SESSION['user_id'] ?? null;
                $stmt = $pdo->prepare("INSERT INTO kunjungan_website (ip_address, user_id) VALUES (?, ?)");
                $stmt->execute([$ip, $user_id]);
                $_SESSION['last_visit_recorded'] = time();
            } catch (Exception $e) {
                // Silently ignore if table doesn't exist yet
            }
        }
    }
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
?>