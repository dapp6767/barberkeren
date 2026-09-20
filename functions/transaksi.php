<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/notifikasi.php';

/**
 * Bayar Tiket oleh Pelanggan (Transfer / QRIS / E-Wallet)
 */
if (!function_exists('bayar_tiket_pelanggan')) {
    function bayar_tiket_pelanggan($antrian_id, $metode, $total) {
        $pdo = get_koneksi();
        if (!$pdo) {
            set_flash('danger', 'Koneksi database tidak tersedia.');
            return false;
        }

        try {
            $pdo->beginTransaction();
            $stmt2 = $pdo->prepare("INSERT INTO transaksi (antrian_id, total_harga, status_pembayaran, metode_pembayaran, waktu_bayar) VALUES (?, ?, 'lunas', ?, NOW())");
            $stmt2->execute([$antrian_id, $total, $metode]);
            $stmt1 = $pdo->prepare("UPDATE antrian SET status_antrean = 'paid' WHERE id = ?");
            $stmt1->execute([$antrian_id]);
            
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            $stmt_q = $pdo->prepare("SELECT no_antrean FROM antrian WHERE id = ? LIMIT 1");
            $stmt_q->execute([$antrian_id]);
            $q_info = $stmt_q->fetch(PDO::FETCH_ASSOC);
            $no_antrean = $q_info ? $q_info['no_antrean'] : "#$antrian_id";

            if (function_exists('create_admin_notification')) {
                create_admin_notification(
                    'new_transaction',
                    'Transaksi Baru Diterima',
                    "Pembayaran Rp " . number_format($total, 0, ',', '.') . " ({$metode}) dari antrean {$no_antrean} berhasil!",
                    'admin.php?page=transaksi'
                );
            }
            set_flash('success', 'Pembayaran berhasil! Menunggu Barber mencetak struk.');
            return true;
        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error in bayar_tiket_pelanggan: " . $e->getMessage());
            set_flash('danger', 'Gagal memproses pembayaran: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Konfirmasi Bayar Cash oleh Barber/Admin
 */
if (!function_exists('konfirmasi_pembayaran_cash')) {
    function konfirmasi_pembayaran_cash($antrian_id, $total_harga = 0) {
        $pdo = get_koneksi();
        if (!$pdo) {
            set_flash('danger', 'Koneksi database tidak tersedia.');
            return false;
        }

        try {
            // Ambil nomor antrean & fallback total_harga jika belum diisi atau bernilai 0
            $stmt_info = $pdo->prepare("
                SELECT a.no_antrean, a.layanan_id, l.harga 
                FROM antrian a 
                LEFT JOIN layanan l ON a.layanan_id = l.id 
                WHERE a.id = ? 
                LIMIT 1
            ");
            $stmt_info->execute([$antrian_id]);
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            $no_antrean = $info ? $info['no_antrean'] : "#$antrian_id";

            if ($total_harga <= 0 && !empty($info['harga'])) {
                $total_harga = (float)$info['harga'];
            }

            $pdo->beginTransaction();

            $stmt_cek = $pdo->prepare("SELECT id FROM transaksi WHERE antrian_id = ? LIMIT 1");
            $stmt_cek->execute([$antrian_id]);
            $existing_tx = $stmt_cek->fetch(PDO::FETCH_ASSOC);

            if (!$existing_tx) {
                $stmt2 = $pdo->prepare("INSERT INTO transaksi (antrian_id, total_harga, status_pembayaran, metode_pembayaran, waktu_bayar) VALUES (?, ?, 'lunas', 'Cash', NOW())");
                $stmt2->execute([$antrian_id, $total_harga]);
            } else {
                $stmt2 = $pdo->prepare("UPDATE transaksi SET status_pembayaran = 'lunas', metode_pembayaran = 'Cash', waktu_bayar = NOW() WHERE id = ?");
                $stmt2->execute([$existing_tx['id']]);
            }

            $stmt1 = $pdo->prepare("UPDATE antrian SET status_antrean = 'review' WHERE id = ?");
            $stmt1->execute([$antrian_id]);

            // PENTING: Lakukan commit database terlebih dahulu sebelum operasi notifikasi!
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            // Jalankan notifikasi admin setelah transaksi aman di-commit
            if (function_exists('create_admin_notification')) {
                create_admin_notification(
                    'new_transaction',
                    'Transaksi Baru Dikonfirmasi',
                    "Pembayaran Cash Rp " . number_format($total_harga, 0, ',', '.') . " untuk antrean {$no_antrean} telah dikonfirmasi!",
                    'admin.php?page=transaksi'
                );
            }

            set_flash('success', 'Pembayaran Dikonfirmasi! Struk dapat dicetak, dan pelanggan diminta memberi ulasan.');
            return true;
        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error in konfirmasi_pembayaran_cash: " . $e->getMessage());
            set_flash('danger', 'Gagal memproses pembayaran: ' . $e->getMessage());
            return false;
        }
    }
}
