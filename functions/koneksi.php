<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * File koneksi database terpusat
 */
require_once __DIR__ . '/../config/database.php';

if (!function_exists('get_koneksi')) {
    function get_koneksi() {
        global $pdo;
        return $pdo;
    }
}

if (!function_exists('get_db_connection')) {
    function get_db_connection() {
        global $pdo;
        return $pdo;
    }
}
