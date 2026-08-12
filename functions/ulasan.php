<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';

/**
 * Submit Ulasan & Rating Pelanggan
 */
if (!function_exists('submit_ulasan_pelanggan')) {
    function submit_ulasan_pelanggan($antrian_id, $rating, $komentar, $cust_id) {
        $pdo = get_koneksi();
        $pdo->beginTransaction();
        $stmt2 = $pdo->prepare("INSERT INTO ulasan (antrian_id, pelanggan_id, rating, komentar) VALUES (?, ?, ?, ?)");
        $stmt2->execute([$antrian_id, $cust_id, $rating, $komentar]);
        $stmt1 = $pdo->prepare("UPDATE antrian SET status_antrean = 'completed' WHERE id = ?");
        $stmt1->execute([$antrian_id]);
        $pdo->commit();
        set_flash('success', 'Terima kasih atas ulasan Anda!');
    }
}
