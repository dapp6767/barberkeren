<?php
require_once __DIR__ . '/../functions/helper.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sess_id = session_id();
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id && isset($pdo)) {
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

        // Delete ONLY THIS DEVICE'S session from user_sessions
        if ($sess_id) {
            $stmtDel = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?");
            $stmtDel->execute([$sess_id]);
        }

        // Check if user still has OTHER active sessions on other devices (active within last 15 minutes)
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user_sessions WHERE user_id = ? AND last_activity >= NOW() - INTERVAL 15 MINUTE");
        $checkStmt->execute([$user_id]);
        $remainingActive = (int)$checkStmt->fetchColumn();

        if ($remainingActive > 0) {
            // User is STILL logged in and active on another device (e.g. HP)! Keep is_online = 1
            $stmtUpdate = $pdo->prepare("UPDATE users SET is_online = 1, last_active = NOW() WHERE id_user = ?");
            $stmtUpdate->execute([$user_id]);
        } else {
            // No other active sessions left anywhere. Mark user as offline
            $stmtUpdate = $pdo->prepare("UPDATE users SET is_online = 0, last_active = NOW() WHERE id_user = ?");
            $stmtUpdate->execute([$user_id]);
        }
    } catch (Exception $e) {}
}

// Hapus cookie remember me
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    try {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = ?");
            $stmt->execute([$token]);
        }
    } catch (Exception $e) {}
    
    setcookie('remember_me', '', time() - 3600, "/");
}

// Bersihkan session secara total
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

redirect('login.php');
?>