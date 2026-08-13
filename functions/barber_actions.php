<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/antrean.php';
require_once __DIR__ . '/transaksi.php';
require_once __DIR__ . '/profil.php';

/**
 * Handle POST Actions dari Barber
 */
function handle_barber_post_actions() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        $antrian_id = (int)($_POST['antrian_id'] ?? 0);
        $user_id = $_SESSION['user_id'] ?? 0;

        if ($action === 'select_kursi') {
            $pdo = get_koneksi();
            $kursi_pilihan = trim($_POST['kursi'] ?? '');
            
            $valid_kursi = ['Kursi A', 'Kursi B', 'Kursi C'];
            if (!in_array($kursi_pilihan, $valid_kursi)) {
                set_flash('danger', 'Kursi yang dipilih tidak valid! Pilihan: Kursi A, Kursi B, atau Kursi C.');
                redirect('barber.php');
                exit;
            }

            // Pastikan kolom tgl_kursi ada
            try {
                $chkCol = $pdo->query("SHOW COLUMNS FROM barber LIKE 'tgl_kursi'");
                if (!$chkCol || $chkCol->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE barber ADD COLUMN tgl_kursi DATE DEFAULT NULL");
                }
            } catch (Exception $e) {}

            // Cari ID Barber
            $stmt_b = $pdo->prepare("SELECT id, nama FROM barber WHERE user_id = ? OR id = ? LIMIT 1");
            $stmt_b->execute([$user_id, $user_id]);
            $barber = $stmt_b->fetch(PDO::FETCH_ASSOC);

            if (!$barber) {
                set_flash('danger', 'Data Barber tidak ditemukan!');
                redirect('barber.php');
                exit;
            }

            $barber_id = $barber['id'];

            // Cek apakah kursi sudah digunakan barber lain hari ini
            $stmt_cek = $pdo->prepare("SELECT id, nama FROM barber WHERE kursi = ? AND tgl_kursi = CURDATE() AND id != ? AND (status = 'aktif' OR status = 'Aktif')");
            $stmt_cek->execute([$kursi_pilihan, $barber_id]);
            $occupied = $stmt_cek->fetch(PDO::FETCH_ASSOC);

            if ($occupied) {
                set_flash('danger', "Gagal memilih kursi! <b>{$kursi_pilihan}</b> sudah dipilih oleh Barber <b>" . htmlspecialchars($occupied['nama']) . "</b> untuk hari ini.");
                redirect('barber.php');
                exit;
            }

            // Update kursi dan tgl_kursi untuk hari ini
            $stmt_upd = $pdo->prepare("UPDATE barber SET kursi = ?, tgl_kursi = CURDATE() WHERE id = ?");
            $stmt_upd->execute([$kursi_pilihan, $barber_id]);

            set_flash('success', "Berhasil! Anda menetapkan <b>{$kursi_pilihan}</b> sebagai kursi tugas melayani hari ini.");
            redirect('barber.php');
            exit;
        }
        elseif ($action === 'update_profil') {
            update_user_profile($user_id, 'barber.php?page=profil');
            exit;
        }
        elseif ($action === 'confirm_paid' && $antrian_id > 0) {
            $total_harga = (float)($_POST['total_harga'] ?? 0);
            konfirmasi_pembayaran_cash($antrian_id, $total_harga);
            redirect('barber.php');
            exit;
        }
        elseif (in_array($action, ['call', 'skip', 'finish_service']) && $antrian_id > 0) {
            handle_antrean_actions($action, $antrian_id, $user_id);
            redirect('barber.php');
            exit;
        }
    }
}
