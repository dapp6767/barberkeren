<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/notifikasi.php';
require_once __DIR__ . '/crud_barber.php';
require_once __DIR__ . '/crud_layanan.php';
require_once __DIR__ . '/crud_user.php';
require_once __DIR__ . '/antrean.php';
require_once __DIR__ . '/transaksi.php';
require_once __DIR__ . '/profil.php';

/**
 * Handle Form POST Actions untuk Admin
 */
function handle_admin_post_actions() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type = $_POST['form_type'] ?? '';

        if (in_array($type, ['add_barber', 'toggle_barber_status', 'delete_barber'])) {
            handle_crud_barber($type);
        }
        elseif (in_array($type, ['add_layanan', 'edit_layanan', 'delete_layanan'])) {
            handle_crud_layanan($type);
        }
        elseif (in_array($type, ['add_user', 'edit_user', 'delete_user'])) {
            handle_crud_user($type);
        }
        elseif ($type === 'delete_antrian') {
            $antrian_id = (int)($_POST['antrian_id'] ?? 0);
            handle_antrean_actions('delete_antrian', $antrian_id);
        }
        elseif ($type === 'confirm_paid') {
            $antrian_id = (int)($_POST['antrian_id'] ?? 0);
            $total_harga = (float)($_POST['total_harga'] ?? 0);
            konfirmasi_pembayaran_cash($antrian_id, $total_harga);
        }
        elseif (in_array($type, ['call', 'skip', 'finish_service'])) {
            $antrian_id = (int)($_POST['antrian_id'] ?? 0);
            handle_antrean_actions($type, $antrian_id);
        }
        elseif ($type === 'update_profil') {
            $user_id = $_SESSION['user_id'];
            update_user_profile($user_id, 'admin.php?page=profil');
            exit;
        }
        elseif ($type === 'save_wa_config') {
            $api_key = trim($_POST['wa_api_key']);
            file_put_contents(__DIR__ . '/../config/wa_config.json', json_encode(['api_key' => $api_key]));
            set_flash('success', 'API Key WhatsApp Gateway (Fonnte) berhasil disimpan!');
        }

        $redirect_page = $_POST['current_page'] ?? 'dashboard';
        redirect("admin.php?page=$redirect_page");
        exit;
    }
}
