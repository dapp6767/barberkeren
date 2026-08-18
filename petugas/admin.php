<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/koneksi.php';
require_once __DIR__ . '/../functions/helper.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/queue_functions.php';
require_once __DIR__ . '/../functions/admin_functions.php';

// Proteksi Multi-Role: Hanya Admin
if (!function_exists('is_logged_in') || !is_logged_in() || $_SESSION['user_role'] !== 'admin') {
    set_flash('danger', 'Akses ditolak! Halaman ini khusus Administrator.');
    redirect('../auth/login.php');
    exit;
}

// Auto-Ensure table notifikasi exists & created_at column in users table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifikasi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT '',
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $chkUserCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    if (!$chkUserCol || $chkUserCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
    }
    $chkBarberKursi = $pdo->query("SHOW COLUMNS FROM barber LIKE 'kursi'");
    if (!$chkBarberKursi || $chkBarberKursi->rowCount() === 0) {
        $pdo->exec("ALTER TABLE barber ADD COLUMN kursi VARCHAR(20) DEFAULT 'Kursi A'");
    }
    $chkTglKursi = $pdo->query("SHOW COLUMNS FROM barber LIKE 'tgl_kursi'");
    if (!$chkTglKursi || $chkTglKursi->rowCount() === 0) {
        $pdo->exec("ALTER TABLE barber ADD COLUMN tgl_kursi DATE DEFAULT NULL");
    }
} catch (Exception $e) {}

// Handle AJAX Notification Endpoint & Form POST Actions via admin_functions.php
handle_admin_ajax_notifications();
handle_admin_post_actions();

// Fetch Data Master
$barbers = $pdo->query("SELECT * FROM barber ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$layanan = $pdo->query("SELECT * FROM layanan ORDER BY harga DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$transaksi = $pdo->query("SELECT t.*, a.no_antrean, u.username as pelanggan FROM transaksi t JOIN antrian a ON t.antrian_id = a.id LEFT JOIN users u ON a.pelanggan_id = u.id_user ORDER BY t.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT * FROM users ORDER BY id_user DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch current logged in user
$session_user_id = $_SESSION['user_id'] ?? 0;
$stmt_curr_u = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt_curr_u->execute([$session_user_id]);
$current_user = $stmt_curr_u->fetch(PDO::FETCH_ASSOC);

// Fetch Stats for Dashboard
$total_layanan = count($layanan);
$total_transaksi = count($transaksi);
$total_users = count($users);
$total_barbers = count($barbers);

// Sales Metrics Connected to Database
$sales_total_val = (float)$pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE status_pembayaran = 'lunas'")->fetchColumn();
$sales_today_val = (float)$pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE status_pembayaran = 'lunas' AND DATE(waktu_bayar) = CURDATE()")->fetchColumn();
$sales_today_trx_count = (int)$pdo->query("SELECT COUNT(*) FROM transaksi WHERE status_pembayaran = 'lunas' AND DATE(waktu_bayar) = CURDATE()")->fetchColumn();

$sales_this_week = (float)$pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE status_pembayaran = 'lunas' AND YEARWEEK(waktu_bayar, 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn();
$sales_last_week = (float)$pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE status_pembayaran = 'lunas' AND YEARWEEK(waktu_bayar, 1) = YEARWEEK(CURDATE(), 1) - 1")->fetchColumn();

$week_ratio = 0;
if ($sales_last_week > 0) {
    $week_ratio = round((($sales_this_week - $sales_last_week) / $sales_last_week) * 100);
}

$sales_yesterday = (float)$pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE status_pembayaran = 'lunas' AND DATE(waktu_bayar) = SUBDATE(CURDATE(), 1)")->fetchColumn();
$day_ratio = 0;
if ($sales_yesterday > 0) {
    $day_ratio = round((($sales_today_val - $sales_yesterday) / $sales_yesterday) * 100);
}

// Daily Revenue Metrics Connected to Database
$avg_daily_revenue = (float)$pdo->query("SELECT COALESCE(AVG(daily_total), 0) FROM (SELECT SUM(total_harga) as daily_total FROM transaksi WHERE status_pembayaran = 'lunas' GROUP BY DATE(waktu_bayar)) t")->fetchColumn();

// Transaksi & Conversion Connected to Database
$total_transaksi_lunas = (int)$pdo->query("SELECT COUNT(*) FROM transaksi WHERE status_pembayaran = 'lunas'")->fetchColumn();
$total_antrean_count = (int)$pdo->query("SELECT COUNT(*) FROM antrian")->fetchColumn();
$conversion_rate = $total_antrean_count > 0 ? round(($total_transaksi_lunas / $total_antrean_count) * 100, 1) : 100.0;

// Antrean Hari Ini Metrics Connected to Database
$today_antrian_total = (int)$pdo->query("SELECT COUNT(*) FROM antrian WHERE DATE(waktu_dibuat) = CURDATE()")->fetchColumn();
$today_antrian_waiting = (int)$pdo->query("SELECT COUNT(*) FROM antrian WHERE DATE(waktu_dibuat) = CURDATE() AND status_antrean = 'waiting'")->fetchColumn();
$today_antrian_serving = (int)$pdo->query("SELECT COUNT(*) FROM antrian WHERE DATE(waktu_dibuat) = CURDATE() AND status_antrean = 'serving'")->fetchColumn();
$today_antrian_completed = (int)$pdo->query("SELECT COUNT(*) FROM antrian WHERE DATE(waktu_dibuat) = CURDATE() AND status_antrean IN ('completed', 'review')")->fetchColumn();

// Users & Barbers Connected to Database
$total_users_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_barbers_active = (int)$pdo->query("SELECT COUNT(*) FROM barber WHERE status = 'aktif' OR status = 'Aktif'")->fetchColumn();
$total_layanan_count = (int)$pdo->query("SELECT COUNT(*) FROM layanan")->fetchColumn();

// Modal Detail Queries
$modal_pay_methods = $pdo->query("SELECT COALESCE(NULLIF(metode_pembayaran, ''), 'Cash') as metode, COUNT(*) as count_trx, SUM(total_harga) as total_rev FROM transaksi WHERE status_pembayaran = 'lunas' GROUP BY metode ORDER BY total_rev DESC")->fetchAll(PDO::FETCH_ASSOC);
$modal_top_layanan = $pdo->query("SELECT l.nama_layanan, l.harga, COUNT(t.id) as count_trx, SUM(t.total_harga) as total_rev FROM transaksi t JOIN antrian a ON t.antrian_id = a.id JOIN layanan l ON a.layanan_id = l.id WHERE t.status_pembayaran = 'lunas' GROUP BY l.id ORDER BY total_rev DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$modal_revenue_daily = $pdo->query("SELECT DATE(waktu_bayar) as tgl, SUM(total_harga) as total FROM transaksi WHERE status_pembayaran = 'lunas' GROUP BY DATE(waktu_bayar) ORDER BY tgl DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$modal_queue_status = $pdo->query("SELECT status_antrean, COUNT(*) as jml FROM antrian GROUP BY status_antrean")->fetchAll(PDO::FETCH_KEY_PAIR);
$modal_barbers_detail = $pdo->query("SELECT b.*, u.username FROM barber b LEFT JOIN users u ON b.user_id = u.id_user ORDER BY b.id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch WA Key
$wa_key = '';
if (file_exists(__DIR__ . '/../config/wa_config.json')) {
    $wa_conf = json_decode(file_get_contents(__DIR__ . '/../config/wa_config.json'), true);
    $wa_key = $wa_conf['api_key'] ?? '';
}

$page = $_GET['page'] ?? 'dashboard';

// Fetch Data for Charts
$chartDataLayanan = [];
if ($page === 'layanan') {
    $stmt3 = $pdo->query("SELECT l.nama_layanan, COUNT(t.id) as c FROM transaksi t JOIN antrian a ON t.antrian_id = a.id JOIN layanan l ON a.layanan_id = l.id GROUP BY l.id");
    $chartDataLayanan = $stmt3->fetchAll(PDO::FETCH_ASSOC);
}

$chartDataTransaksi = [];
$chartDataLayananTransaksi = [];
if ($page === 'transaksi') {
    $stmtChartT = $pdo->query("SELECT metode_pembayaran, COUNT(*) as c FROM transaksi GROUP BY metode_pembayaran");
    $chartDataTransaksi = $stmtChartT->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtChartLT = $pdo->query("SELECT l.nama_layanan, COUNT(t.id) as c FROM transaksi t JOIN antrian a ON t.antrian_id = a.id JOIN layanan l ON a.layanan_id = l.id GROUP BY l.id ORDER BY c DESC");
    $chartDataLayananTransaksi = $stmtChartLT->fetchAll(PDO::FETCH_ASSOC);
}

$chartDataAkun = [];
if ($page === 'akun') {
    $stmtChartA = $pdo->query("SELECT role, COUNT(*) as c FROM users GROUP BY role");
    $chartDataAkun = $stmtChartA->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Data for Antrean Module
if ($page === 'antrean') {
    $current_serving = get_current_serving_queue();
    $active_queues   = get_active_queues();
}

// Fetch Data for Barber Module
$barber_queues = [];
$total_b_waiting = 0;
$total_b_served = 0;
if ($page === 'barber') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $stmt_b = $pdo->prepare("SELECT * FROM barber WHERE user_id = ? OR id = ? LIMIT 1");
    $stmt_b->execute([$user_id, $user_id]);
    $barber = $stmt_b->fetch(PDO::FETCH_ASSOC);
    $barber_id = $barber['id'] ?? null;

    $today = date('Y-m-d');
    $query = "SELECT a.*, l.nama_layanan, l.harga, u.username as pelanggan_nama, b.multiplier,
              (SELECT metode_pembayaran FROM transaksi t WHERE t.antrian_id = a.id LIMIT 1) as metode_bayar
              FROM antrian a 
              LEFT JOIN layanan l ON a.layanan_id = l.id 
              LEFT JOIN users u ON a.pelanggan_id = u.id_user
              LEFT JOIN barber b ON a.barber_id = b.id
              WHERE DATE(a.waktu_dibuat) = ? AND (a.barber_id = ? OR a.barber_id IS NULL)
              ORDER BY a.id ASC";
    $stmt_q = $pdo->prepare($query);
    $stmt_q->execute([$today, $barber_id]);
    $barber_queues = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

    foreach ($barber_queues as $q) {
        if ($q['status_antrean'] === 'waiting') $total_b_waiting++;
        if (in_array($q['status_antrean'], ['review', 'completed'])) $total_b_served++;
    }
}

// Render Modular Views
require_once __DIR__ . '/views/admin/header.php';

switch ($page) {
    case 'antrean':
        require_once __DIR__ . '/views/admin/antrean.php';
        break;
    case 'layanan':
        require_once __DIR__ . '/views/admin/layanan.php';
        break;
    case 'transaksi':
        require_once __DIR__ . '/views/admin/transaksi.php';
        break;
    case 'akun':
        require_once __DIR__ . '/views/admin/akun.php';
        break;
    case 'barber':
        require_once __DIR__ . '/views/admin/barber.php';
        break;
    case 'pengaturan':
        require_once __DIR__ . '/views/admin/pengaturan.php';
        break;
    case 'profil':
        require_once __DIR__ . '/views/admin/profil.php';
        break;
    case 'dashboard':
    default:
        require_once __DIR__ . '/views/admin/dashboard.php';
        break;
}

require_once __DIR__ . '/views/admin/modals.php';
require_once __DIR__ . '/views/admin/footer.php';
