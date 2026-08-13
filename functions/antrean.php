<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';

/**
 * Determine letter code (A, B, C) based on Barber's assigned Chair ('Kursi A', 'Kursi B', etc.)
 */
if (!function_exists('determine_queue_letter')) {
    function determine_queue_letter($barber_id = null) {
        $pdo = get_koneksi();
        
        // Ensure tgl_kursi column exists
        try {
            $chkCol = $pdo->query("SHOW COLUMNS FROM barber LIKE 'tgl_kursi'");
            if (!$chkCol || $chkCol->rowCount() === 0) {
                $pdo->exec("ALTER TABLE barber ADD COLUMN tgl_kursi DATE DEFAULT NULL");
            }
        } catch (Exception $e) {}

        if ($barber_id) {
            $stmt = $pdo->prepare("SELECT kursi, tgl_kursi FROM barber WHERE id = ? LIMIT 1");
            $stmt->execute([$barber_id]);
            $barber = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($barber && !empty($barber['kursi'])) {
                if (preg_match('/kursi\s+([a-z])/i', $barber['kursi'], $m)) {
                    return strtoupper($m[1]);
                }
            }
        }
        
        // Auto allocation: prefer barbers who selected a chair today
        $stmt = $pdo->prepare("SELECT id, kursi FROM barber WHERE (status = 'aktif' OR status = 'Aktif') AND tgl_kursi = CURDATE() ORDER BY id ASC");
        $stmt->execute();
        $barbers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fallback if no barbers have confirmed chair today
        if (empty($barbers)) {
            $stmt = $pdo->prepare("SELECT id, kursi FROM barber WHERE (status = 'aktif' OR status = 'Aktif') ORDER BY id ASC");
            $stmt->execute();
            $barbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($barbers)) {
            return 'A';
        }

        $min_count = 999999;
        $chosen_letter = 'A';

        foreach ($barbers as $b) {
            $stmt_c = $pdo->prepare("SELECT COUNT(*) as total FROM antrian WHERE barber_id = ? AND status_antrean IN ('waiting', 'serving') AND DATE(waktu_dibuat) = CURDATE()");
            $stmt_c->execute([$b['id']]);
            $count = (int) $stmt_c->fetchColumn();

            if ($count < $min_count) {
                $min_count = $count;
                if (preg_match('/kursi\s+([a-z])/i', $b['kursi'], $m)) {
                    $chosen_letter = strtoupper($m[1]);
                } else {
                    $chosen_letter = 'A';
                }
            }
        }

        return $chosen_letter;
    }
}

/**
 * Generate unique ticket number with prefix letter (e.g. A-01, B-02)
 */
if (!function_exists('generate_ticket_number')) {
    function generate_ticket_number($letter = 'A') {
        $pdo = get_koneksi();
        $letter = strtoupper(trim($letter));
        if (empty($letter)) $letter = 'A';

        $prefix = $letter . '-%';
        $stmt = $pdo->prepare("SELECT no_antrean FROM antrian WHERE no_antrean LIKE ? AND DATE(waktu_dibuat) = CURDATE() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix]);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($last && !empty($last['no_antrean'])) {
            $parts = explode('-', $last['no_antrean']);
            $num = (int) end($parts);
            $next_num = $num + 1;
        } else {
            $next_num = 1;
        }

        return sprintf('%s-%02d', $letter, $next_num);
    }
}

/**
 * Ambil Tiket Antrean oleh Pelanggan
 */
if (!function_exists('take_queue_ticket')) {
    function take_queue_ticket($customer_name, $barber_id = null, $service_id = null, $pelanggan_id = null) {
        $pdo = get_koneksi();

        if (empty($customer_name)) {
            return ['status' => false, 'message' => 'Nama pelanggan wajib diisi!'];
        }

        if ($pelanggan_id) {
            $stmt_cek = $pdo->prepare("SELECT id, no_antrean FROM antrian WHERE pelanggan_id = ? AND DATE(waktu_dibuat) = CURDATE() AND status_antrean NOT IN ('skipped', 'completed', 'cancelled') LIMIT 1");
            $stmt_cek->execute([$pelanggan_id]);
            $existing = $stmt_cek->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                return ['status' => false, 'message' => 'Anda sudah memiliki antrean aktif untuk hari ini (Nomor: ' . $existing['no_antrean'] . '). Silakan selesaikan antrean terlebih dahulu!'];
            }
        }

        $letter = determine_queue_letter($barber_id);
        $ticket_number = generate_ticket_number($letter);

        try {
            $stmt = $pdo->prepare("INSERT INTO antrian (pelanggan_id, barber_id, layanan_id, no_antrean, status_antrean, waktu_dibuat) VALUES (?, ?, ?, ?, 'waiting', NOW())");
            $stmt->execute([$pelanggan_id, $barber_id, $service_id, $ticket_number]);
            return [
                'status' => true,
                'ticket_number' => $ticket_number,
                'message' => 'Berhasil mengambil nomor antrean.'
            ];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Gagal mengambil nomor antrean: ' . $e->getMessage()];
        }
    }
}

/**
 * Helper Queue Status
 */
if (!function_exists('get_current_serving_queue')) {
    function get_current_serving_queue() {
        $pdo = get_koneksi();
        $stmt = $pdo->query("SELECT a.*, l.nama_layanan, b.nama as barber_nama FROM antrian a LEFT JOIN layanan l ON a.layanan_id = l.id LEFT JOIN barber b ON a.barber_id = b.id WHERE a.status_antrean = 'serving' AND DATE(a.waktu_dibuat) = CURDATE() ORDER BY a.id ASC LIMIT 1");
        return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    }
}

if (!function_exists('get_active_queues')) {
    function get_active_queues() {
        $pdo = get_koneksi();
        $stmt = $pdo->query("SELECT a.*, l.nama_layanan, b.nama as barber_nama FROM antrian a LEFT JOIN layanan l ON a.layanan_id = l.id LEFT JOIN barber b ON a.barber_id = b.id WHERE a.status_antrean IN ('waiting', 'serving') AND DATE(a.waktu_dibuat) = CURDATE() ORDER BY a.id ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}

/**
 * Handle Panggil, Skip, Finish, Delete Antrean
 */
if (!function_exists('handle_antrean_actions')) {
    function handle_antrean_actions($action, $antrian_id, $user_id = null) {
        $pdo = get_koneksi();
        $user_id = $user_id ?? ($_SESSION['user_id'] ?? 0);

        if ($action === 'call' && $antrian_id > 0) {
            $stmt_b = $pdo->prepare("SELECT * FROM barber WHERE user_id = ? OR id = ? LIMIT 1");
            $stmt_b->execute([$user_id, $user_id]);
            $barber = $stmt_b->fetch(PDO::FETCH_ASSOC);
            $barber_id = $barber['id'] ?? null;
            $stmt = $pdo->prepare("UPDATE antrian SET status_antrean = 'serving', barber_id = ?, served_by_user_id = ? WHERE id = ?");
            $stmt->execute([$barber_id, $user_id, $antrian_id]);
            set_flash('success', 'Pelanggan berhasil dipanggil!');
        }
        elseif ($action === 'skip' && $antrian_id > 0) {
            $stmt_b = $pdo->prepare("SELECT * FROM barber WHERE user_id = ? OR id = ? LIMIT 1");
            $stmt_b->execute([$user_id, $user_id]);
            $barber = $stmt_b->fetch(PDO::FETCH_ASSOC);
            $barber_id = $barber['id'] ?? null;

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE antrian SET status_antrean = 'skipped' WHERE id = ?");
            $stmt->execute([$antrian_id]);
            $stmtNext = $pdo->prepare("SELECT id FROM antrian WHERE status_antrean = 'waiting' AND DATE(waktu_dibuat) = CURDATE() AND (barber_id = ? OR barber_id IS NULL) ORDER BY id ASC LIMIT 1");
            $stmtNext->execute([$barber_id]);
            $nextQueue = $stmtNext->fetch(PDO::FETCH_ASSOC);
            if ($nextQueue) {
                $stmtCall = $pdo->prepare("UPDATE antrian SET status_antrean = 'serving', barber_id = ?, served_by_user_id = ? WHERE id = ?");
                $stmtCall->execute([$barber_id, $user_id, $nextQueue['id']]);
                set_flash('warning', 'Antrean dilewati. Antrean berikutnya otomatis dipanggil.');
            } else {
                set_flash('warning', 'Antrean berhasil dilewati (Skip). Tidak ada antrean berikutnya.');
            }
            $pdo->commit();
        }
        elseif ($action === 'finish_service' && $antrian_id > 0) {
            $pdo->beginTransaction();
            $stmt1 = $pdo->prepare("UPDATE antrian SET status_antrean = 'payment' WHERE id = ?");
            $stmt1->execute([$antrian_id]);
            $pdo->commit();
            set_flash('success', 'Layanan selesai! Menunggu pelanggan memilih metode pembayaran.');
        }
        elseif ($action === 'delete_antrian' && $antrian_id > 0) {
            try {
                $pdo->beginTransaction();
                try { $pdo->prepare("DELETE FROM ulasan WHERE antrian_id = ?")->execute([$antrian_id]); } catch (Exception $e) {}
                try { $pdo->prepare("DELETE FROM transaksi WHERE antrian_id = ?")->execute([$antrian_id]); } catch (Exception $e) {}
                $stmt_del = $pdo->prepare("DELETE FROM antrian WHERE id = ?");
                $stmt_del->execute([$antrian_id]);
                $pdo->commit();
                set_flash('warning', 'Data antrean berhasil dihapus dari sistem!');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                set_flash('danger', 'Gagal menghapus antrean: ' . $e->getMessage());
            }
        }
    }
}
