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

        if ($action === 'update_profil') {
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
