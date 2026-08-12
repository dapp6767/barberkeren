<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/antrean.php';
require_once __DIR__ . '/transaksi.php';
require_once __DIR__ . '/ulasan.php';
require_once __DIR__ . '/profil.php';

/**
 * Handle POST Actions dari Pelanggan
 */
function handle_customer_post_actions() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'take_ticket') {
            $cust_name  = $_SESSION['fullname'] ?? $_SESSION['username'] ?? '';
            $barber_id  = !empty($_POST['barber_id']) ? (int)$_POST['barber_id'] : null;
            $service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
            $cust_id    = $_SESSION['user_id'] ?? null;

            if (empty($cust_name)) {
                set_flash('danger', "Data profil akun Anda belum lengkap.");
                redirect('dashboard.php');
                exit;
            }

            $result = take_queue_ticket($cust_name, $barber_id, $service_id, $cust_id);
            if ($result['status']) {
                set_flash('success', "Nomor Tiket Anda: " . $result['ticket_number'] . " berhasil diambil!");
            } else {
                set_flash('danger', $result['message']);
            }
            redirect('dashboard.php');
            exit;
        }

        if ($action === 'cancel_my_ticket') {
            $antrian_id = (int)($_POST['antrian_id'] ?? 0);
            $cust_id = $_SESSION['user_id'] ?? 0;
            if ($antrian_id > 0 && $cust_id > 0) {
                handle_antrean_actions('delete_antrian', $antrian_id, $cust_id);
            }
            redirect('dashboard.php');
            exit;
        }

        if ($action === 'pay_ticket') {
            $antrian_id = (int)$_POST['antrian_id'];
            $metode = $_POST['metode_pembayaran'];
            $total = (float)$_POST['total_harga'];
            bayar_tiket_pelanggan($antrian_id, $metode, $total);
            redirect('dashboard.php');
            exit;
        }

        if ($action === 'submit_review') {
            $antrian_id = (int)$_POST['antrian_id'];
            $rating = (int)$_POST['rating'];
            $komentar = $_POST['komentar'];
            $cust_id = $_SESSION['user_id'];
            submit_ulasan_pelanggan($antrian_id, $rating, $komentar, $cust_id);
            redirect('dashboard.php');
            exit;
        }

        if ($action === 'update_profil') {
            $user_id = $_SESSION['user_id'];
            update_user_profile($user_id, 'dashboard.php?tab=profil');
            exit;
        }
    }
}
