<?php
// Deteksi host server saat ini (contoh: localhost / 127.0.0.1 / keren.great-site.net)
$httpHost = $_SERVER['HTTP_HOST'] ?? '';

// Cek apakah script berjalan di lokal
$isLocal = (empty($httpHost) || php_sapi_name() === 'cli' || $httpHost === 'localhost' || $httpHost === '127.0.0.1' || str_contains($httpHost, 'localhost:') || str_contains($httpHost, '127.0.0.1:'));

if ($isLocal) {
    // === KONFIGURASI LOKAL (Laragon / XAMPP) ===
    $host     = '127.0.0.1'; // Menggunakan 127.0.0.1 agar koneksi TCP lancar tanpa error socket
    $dbname   = 'barber_db';
    $username = 'root';
    $password = '';
} else {
    // === KONFIGURASI HOSTING (INFINITYFREE) ===
    // ⚠️ Ganti 'sql308.infinityfree.com' di bawah ini dengan MySQL Hostname ASLI yang ada di vPanel InfinityFree Anda!
    $host     = 'sql308.infinityfree.com'; 
    $dbname   = 'if0_42597730_barber_db';  
    $username = 'if0_42597730';            
    $password = 'inigwdaffa2009';   
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    date_default_timezone_set('Asia/Jakarta');
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage() . "<br><br><b>Petunjuk:</b> Jika error ini muncul di hosting, dipastikan nama MySQL Hostname (contoh: <code>sql308.infinityfree.com</code>) belum sesuai dengan MySQL Hostname asli di vPanel InfinityFree Anda.");
}
?>