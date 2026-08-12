<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';

/**
 * Buat Notifikasi Admin
 */
if (!function_exists('create_admin_notification')) {
    function create_admin_notification($type, $title, $message, $link = '') {
        $pdo = get_koneksi();
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
 * Handle AJAX Notification Endpoint untuk Admin
 */
if (!function_exists('handle_admin_ajax_notifications')) {
    function handle_admin_ajax_notifications() {
        $pdo = get_koneksi();
        if (isset($_GET['action'])) {
            if ($_GET['action'] === 'get_unread_notif') {
                header('Content-Type: application/json');
                $stmt_n = $pdo->query("SELECT * FROM notifikasi WHERE is_read = 0 ORDER BY id DESC LIMIT 10");
                $unread_list = $stmt_n ? $stmt_n->fetchAll(PDO::FETCH_ASSOC) : [];
                
                $stmt_c = $pdo->query("SELECT COUNT(*) as unread_count FROM notifikasi WHERE is_read = 0");
                $unread_count = $stmt_c ? (int)$stmt_c->fetchColumn() : 0;
                
                echo json_encode([
                    'status' => true,
                    'unread_count' => $unread_count,
                    'notifications' => $unread_list
                ]);
                exit;
            }
            if ($_GET['action'] === 'mark_notif_read') {
                header('Content-Type: application/json');
                $id = (int)($_GET['id'] ?? 0);
                if ($id > 0) {
                    $stmt_m = $pdo->prepare("UPDATE notifikasi SET is_read = 1 WHERE id = ?");
                    $stmt_m->execute([$id]);
                } else {
                    $pdo->exec("UPDATE notifikasi SET is_read = 1 WHERE is_read = 0");
                }
                echo json_encode(['status' => true]);
                exit;
            }
        }
    }
}
