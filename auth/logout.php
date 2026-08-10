<?php
require_once __DIR__ . '/../functions/helper.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_online = 0, last_active = NOW() WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {}
}

// Hapus remember token di database
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    try {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL, is_online = 0, last_active = NOW() WHERE remember_token = ?");
        $stmt->execute([$token]);
    } catch (Exception $e) {
        // Abaikan error jika ada
    }
    
    // Hapus cookie
    setcookie('remember_me', '', time() - 3600, "/");
}

session_destroy();
redirect('login.php');
?>