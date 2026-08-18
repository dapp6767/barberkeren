<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/koneksi.php';
require_once __DIR__ . '/../functions/helper.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/barber_actions.php';

// Proteksi Multi-Role: Barber & Admin bisa akses
if (!function_exists('is_logged_in') || !is_logged_in() || !in_array($_SESSION['user_role'], ['admin', 'barber'])) {
    set_flash('danger', 'Akses ditolak! Halaman ini khusus Barber.');
    redirect('../auth/login.php');
    exit;
}

// Handle Aksi Antrean & Profil via functions/barber_actions.php
handle_barber_post_actions();

$user_id = $_SESSION['user_id'];

// Ambil data User dari database
$stmt_u = $pdo->prepare("SELECT * FROM users WHERE id_user = ? LIMIT 1");
$stmt_u->execute([$user_id]);
$user_data = $stmt_u->fetch(PDO::FETCH_ASSOC) ?: [];

// Ambil ID Barber berdasarkan user_id
$stmt_b = $pdo->prepare("SELECT * FROM barber WHERE user_id = ? OR id = ? LIMIT 1");
$stmt_b->execute([$user_id, $user_id]);
$barber = $stmt_b->fetch(PDO::FETCH_ASSOC);
$barber_id = $barber['id'] ?? null;

// Ensure tgl_kursi column exists
try {
    $chkCol = $pdo->query("SHOW COLUMNS FROM barber LIKE 'tgl_kursi'");
    if (!$chkCol || $chkCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE barber ADD COLUMN tgl_kursi DATE DEFAULT NULL");
    }
} catch (Exception $e) {}

// Check daily chair status
$has_selected_chair_today = false;
if ($barber && !empty($barber['tgl_kursi']) && $barber['tgl_kursi'] === date('Y-m-d')) {
    $has_selected_chair_today = true;
}

// Fetch occupied chairs today by other barbers
$stmt_occ = $pdo->prepare("SELECT kursi, nama, id FROM barber WHERE tgl_kursi = CURDATE() AND (status = 'aktif' OR status = 'Aktif')");
$stmt_occ->execute();
$occ_list = $stmt_occ->fetchAll(PDO::FETCH_ASSOC);
$occupied_chairs = [];
foreach ($occ_list as $oc) {
    if ($barber && $oc['id'] != $barber['id']) {
        $occupied_chairs[$oc['kursi']] = $oc['nama'];
    }
}

// Ambil Daftar Antrean Hari Ini
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
$queues = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

// Count queues by status
$total_waiting = 0;
$total_served = 0;
foreach ($queues as $q) {
    if ($q['status_antrean'] === 'waiting') $total_waiting++;
    if (in_array($q['status_antrean'], ['review', 'completed'])) $total_served++;
}

$current_page = $_GET['page'] ?? 'dashboard';

// Fetch barber statistics data for 30 days
$barberChartQuery = "
    SELECT DATE(a.waktu_dibuat) as tanggal, COUNT(*) as jumlah 
    FROM antrian a 
    WHERE a.waktu_dibuat >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
      AND a.status_antrean IN ('review', 'completed', 'paid', 'serving', 'payment')
      " . ($barber_id ? "AND (a.barber_id = " . (int)$barber_id . " OR a.served_by_user_id = " . (int)$user_id . ")" : "") . "
    GROUP BY DATE(a.waktu_dibuat) 
    ORDER BY DATE(a.waktu_dibuat) ASC
";
$barberChartStmt = $pdo->query($barberChartQuery);
$barberChartRaw = $barberChartStmt ? $barberChartStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$barberDataMap = [];
foreach ($barberChartRaw as $row) {
    $barberDataMap[$row['tanggal']] = (int)$row['jumlah'];
}

$barberLabels = [];
$barberDataVals = [];
$barberTotal30 = 0;
$barberPeakValue = -1;
$barberPeakIndex = 0;

for ($i = 29; $i >= 0; $i--) {
    $tglStr = date('Y-m-d', strtotime("-$i days"));
    $val = $barberDataMap[$tglStr] ?? 0;
    $barberLabels[] = date('d M', strtotime($tglStr));
    $barberDataVals[] = $val;
    $barberTotal30 += $val;

    if ($val > $barberPeakValue) {
        $barberPeakValue = $val;
        $barberPeakIndex = 29 - $i;
    }
}
$barberAverage = round($barberTotal30 / 30, 1);

// Service breakdown query for barber
$servicePieQuery = "
    SELECT l.nama_layanan, COUNT(a.id) as total_layanan, SUM(l.harga) as total_omset
    FROM antrian a
    JOIN layanan l ON a.layanan_id = l.id
    WHERE a.status_antrean IN ('review', 'completed', 'paid', 'serving', 'payment')
      " . ($barber_id ? "AND (a.barber_id = " . (int)$barber_id . " OR a.served_by_user_id = " . (int)$user_id . ")" : "") . "
    GROUP BY l.id, l.nama_layanan
    ORDER BY total_layanan DESC
";
$servicePieStmt = $pdo->query($servicePieQuery);
$servicePieData = $servicePieStmt ? $servicePieStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$pieLabels = [];
$pieCounts = [];
$topServiceName = 'Belum Ada';
if (!empty($servicePieData)) {
    $topServiceName = $servicePieData[0]['nama_layanan'];
}
foreach ($servicePieData as $sp) {
    $pieLabels[] = $sp['nama_layanan'];
    $pieCounts[] = (int)$sp['total_layanan'];
}

// Calculate total revenue / Omset of barber (this month)
$revenueQuery = "
    SELECT COALESCE(SUM(l.harga), 0) as total_omset, COUNT(a.id) as total_bulan_ini
    FROM antrian a
    JOIN layanan l ON a.layanan_id = l.id
    WHERE MONTH(a.waktu_dibuat) = MONTH(CURDATE()) AND YEAR(a.waktu_dibuat) = YEAR(CURDATE())
      AND a.status_antrean IN ('review', 'completed', 'paid', 'serving', 'payment')
      " . ($barber_id ? "AND (a.barber_id = " . (int)$barber_id . " OR a.served_by_user_id = " . (int)$user_id . ")" : "");
$revenueStmt = $pdo->query($revenueQuery);
$revenueData = $revenueStmt ? $revenueStmt->fetch(PDO::FETCH_ASSOC) : ['total_omset' => 0, 'total_bulan_ini' => 0];
$barberOmsetMonth = (float)($revenueData['total_omset'] ?? 0);
$barberCountMonth = (int)($revenueData['total_bulan_ini'] ?? 0);

// Barber rating & review statistics
$ratingQuery = "
    SELECT AVG(u.rating) as avg_rating, COUNT(u.id) as total_ulasan
    FROM ulasan u
    JOIN antrian a ON u.antrian_id = a.id
    WHERE 1=1 " . ($barber_id ? "AND (a.barber_id = " . (int)$barber_id . " OR a.served_by_user_id = " . (int)$user_id . ")" : "");
$ratingStmt = $pdo->query($ratingQuery);
$ratingData = $ratingStmt ? $ratingStmt->fetch(PDO::FETCH_ASSOC) : ['avg_rating' => null, 'total_ulasan' => 0];
$barberRating = $ratingData['avg_rating'] ? round((float)$ratingData['avg_rating'], 1) : 5.0;
$barberTotalUlasan = (int)($ratingData['total_ulasan'] ?? 0);

// Render Views
require_once __DIR__ . '/views/barber/header.php';
require_once __DIR__ . '/views/barber/dashboard.php';
require_once __DIR__ . '/views/barber/modals.php';
require_once __DIR__ . '/views/barber/footer.php';
