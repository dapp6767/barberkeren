<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions/helper.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

if (!function_exists('is_logged_in') || !is_logged_in() || !in_array($_SESSION['user_role'], ['admin', 'barber'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt_b = $pdo->prepare("SELECT id FROM barber WHERE user_id = ? LIMIT 1");
$stmt_b->execute([$user_id]);
$barber = $stmt_b->fetch(PDO::FETCH_ASSOC);
$barber_id = $barber ? $barber['id'] : null;

$today = date('Y-m-d');
// Cari antrean berstatus 'paid' (pelanggan sudah klik bayar) yang belum diproses barber
$query = "SELECT a.*, l.nama_layanan, l.harga, u.username as pelanggan_nama, 
          b.multiplier, 
          (SELECT metode_pembayaran FROM transaksi t WHERE t.antrian_id = a.id LIMIT 1) as metode_bayar
          FROM antrian a
          LEFT JOIN layanan l ON a.layanan_id = l.id
          LEFT JOIN users u ON a.pelanggan_id = u.id_user
          LEFT JOIN barber b ON a.barber_id = b.id
          WHERE DATE(a.waktu_dibuat) = ? 
            AND a.status_antrean = 'paid' 
            AND (a.barber_id = ? OR a.barber_id IS NULL)
          ORDER BY a.id ASC LIMIT 1";

$stmt = $pdo->prepare($query);
$stmt->execute([$today, $barber_id]);
$queue = $stmt->fetch(PDO::FETCH_ASSOC);

if ($queue) {
    $base_price = (float)($queue['harga'] ?? 0);
    $final_price = $base_price;
    
    $queue['final_price'] = $final_price;
    echo json_encode(['status' => 'success', 'data' => $queue]);
} else {
    echo json_encode(['status' => 'not_found']);
}
