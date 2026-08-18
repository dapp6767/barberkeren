<?php
/**
 * Controller Dashboard Pelanggan
 * Mengelola otentikasi, penanganan action, data fetching, dan pemanggilan view modular.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/koneksi.php';
require_once __DIR__ . '/../functions/helper.php';
require_once __DIR__ . '/../functions/queue_functions.php';
require_once __DIR__ . '/../functions/customer_functions.php';

// 1. PROTEKSI UTAMA: Wajib Login sebelum bisa mengakses Dashboard Antrean
if (!function_exists('is_logged_in') || !is_logged_in()) {
    redirect('../auth/login.php');
    exit;
}

// 2. Handle POST Actions via customer_functions.php
handle_customer_post_actions();

// 3. Fetch Master Data Antrean & Layanan
$current_serving = get_current_serving_queue();
$active_queues   = get_active_queues();
$barbers        = get_all_barbers();
$services       = get_all_services();

// Fetch queue count per barber (untuk step pilih barber)
$pdo_early = get_db_connection();
$barber_queue_counts = [];
$stmt_bc = $pdo_early->query("SELECT barber_id, COUNT(*) as cnt FROM antrian WHERE status_antrean IN ('waiting','serving') AND DATE(waktu_dibuat) = CURDATE() GROUP BY barber_id");
if ($stmt_bc) {
    foreach ($stmt_bc->fetchAll(PDO::FETCH_ASSOC) as $bcrow) {
        $barber_queue_counts[(int)$bcrow['barber_id']] = (int)$bcrow['cnt'];
    }
}

// Pastikan kolom tgl_kursi ada pada tabel barber
try {
    $chkCol = $pdo_early->query("SHOW COLUMNS FROM barber LIKE 'tgl_kursi'");
    if (!$chkCol || $chkCol->rowCount() === 0) {
        $pdo_early->exec("ALTER TABLE barber ADD COLUMN tgl_kursi DATE DEFAULT NULL");
    }
} catch (Exception $e) {}

// Fetch detail barber dengan kursi untuk step pilih barber
$barbers_detail = [];
$stmt_bd = $pdo_early->query("SELECT id, user_id, nama, kursi, tgl_kursi, spesialisasi, status, tingkatan FROM barber WHERE status = 'Aktif' OR status = 'aktif' ORDER BY kursi ASC");
if ($stmt_bd) {
    $barbers_detail = $stmt_bd->fetchAll(PDO::FETCH_ASSOC);
}

// 4. Fetch Data Pelanggan Login (Status Antrean & Riwayat)
$pdo = get_db_connection();
$my_user_id = $_SESSION['user_id'] ?? null;
$my_queue = null;
$user = [];
$history = [];
$current_user = [];

if ($my_user_id) {
    $stmt_u = $pdo->prepare("SELECT * FROM users WHERE id_user = ? LIMIT 1");
    $stmt_u->execute([$my_user_id]);
    $user = $stmt_u->fetch(PDO::FETCH_ASSOC) ?: [];
    
    $stmt_my = $pdo->prepare("SELECT a.*, l.nama_layanan, l.harga, b.nama as barber_nama, b.multiplier 
                              FROM antrian a 
                              LEFT JOIN layanan l ON a.layanan_id = l.id 
                              LEFT JOIN barber b ON a.barber_id = b.id 
                              WHERE a.pelanggan_id = ? AND DATE(a.waktu_dibuat) = CURDATE() 
                              AND a.status_antrean NOT IN ('skipped', 'completed', 'cancelled')
                              ORDER BY a.id DESC LIMIT 1");
    $stmt_my->execute([$my_user_id]);
    $my_queue = $stmt_my->fetch(PDO::FETCH_ASSOC);

    // Fetch History (Riwayat) unconditionally for SPA
    $stmt_hist = $pdo->prepare("SELECT t.*, a.no_antrean, l.nama_layanan, b.nama as barber_name 
                               FROM transaksi t 
                               JOIN antrian a ON t.antrian_id = a.id 
                               LEFT JOIN layanan l ON a.layanan_id = l.id 
                               LEFT JOIN barber b ON a.barber_id = b.id 
                               WHERE a.pelanggan_id = ? ORDER BY t.waktu_bayar DESC");
    $stmt_hist->execute([$my_user_id]);
    $history = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
    
    // Assign current user untuk modul profil
    $current_user = $user;
}

// Determine active tabs
$current_page_param = $_GET['page'] ?? '';
$is_dashboard = ($current_page_param === '');
$is_profil = ($current_page_param === 'profil');
$is_riwayat = ($current_page_param === 'riwayat');
$is_layanan = ($current_page_param === 'layanan');
$is_qris = ($current_page_param === 'qris');

// 5. Render Views Modular (Header, Tabs, & Footer)
require_once __DIR__ . '/views/header.php';
require_once __DIR__ . '/views/antrean_saya.php';
require_once __DIR__ . '/views/layanan.php';
require_once __DIR__ . '/views/qris.php';
require_once __DIR__ . '/views/riwayat.php';
require_once __DIR__ . '/views/profil.php';
require_once __DIR__ . '/views/footer.php';
