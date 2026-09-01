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

// Ambil ID Barber berdasarkan user_id atau nama
$stmt_b = $pdo->prepare("SELECT * FROM barber WHERE user_id = ? OR id = ? LIMIT 1");
$stmt_b->execute([$user_id, $user_id]);
$barber = $stmt_b->fetch(PDO::FETCH_ASSOC);

if (!$barber && !empty($user_data['fullname'])) {
    $stmt_b_name = $pdo->prepare("SELECT * FROM barber WHERE LOWER(nama) = LOWER(?) LIMIT 1");
    $stmt_b_name->execute([$user_data['fullname']]);
    $barber = $stmt_b_name->fetch(PDO::FETCH_ASSOC);
}
if (!$barber && !empty($user_data['username'])) {
    $stmt_b_name = $pdo->prepare("SELECT * FROM barber WHERE LOWER(nama) = LOWER(?) LIMIT 1");
    $stmt_b_name->execute([$user_data['username']]);
    $barber = $stmt_b_name->fetch(PDO::FETCH_ASSOC);
}

$barber_id = $barber ? (int)$barber['id'] : null;

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
$isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

$barber_kursi_letter = '';
if ($barber && !empty($barber['kursi'])) {
    if (preg_match('/kursi\s*([a-z])/i', $barber['kursi'], $m)) {
        $barber_kursi_letter = strtoupper($m[1]);
    }
}
$chairPrefix = $barber_kursi_letter ? ($barber_kursi_letter . '-%') : '';

if ($isAdmin && !$barber_id) {
    // Admin mode: tampilkan semua antrean hari ini
    $query = "SELECT a.*, l.nama_layanan, l.harga, u.username as pelanggan_nama, b.multiplier, b.nama as barber_nama,
              (SELECT metode_pembayaran FROM transaksi t WHERE t.antrian_id = a.id LIMIT 1) as metode_bayar
              FROM antrian a 
              LEFT JOIN layanan l ON a.layanan_id = l.id 
              LEFT JOIN users u ON a.pelanggan_id = u.id_user
              LEFT JOIN barber b ON a.barber_id = b.id
              WHERE DATE(a.waktu_dibuat) = ?
              ORDER BY a.id ASC";
    $stmt_q = $pdo->prepare($query);
    $stmt_q->execute([$today]);
} else {
    // Barber mode: tampilkan antrean khusus barber ini, antrean tanpa barber (bebas/otomatis), antrean kursi yang sama, dan yang sedang dilayani
    $query = "SELECT a.*, l.nama_layanan, l.harga, u.username as pelanggan_nama, b.multiplier, b.nama as barber_nama,
              (SELECT metode_pembayaran FROM transaksi t WHERE t.antrian_id = a.id LIMIT 1) as metode_bayar
              FROM antrian a 
              LEFT JOIN layanan l ON a.layanan_id = l.id 
              LEFT JOIN users u ON a.pelanggan_id = u.id_user
              LEFT JOIN barber b ON a.barber_id = b.id
              WHERE DATE(a.waktu_dibuat) = ? 
                AND (
                    (a.barber_id IS NOT NULL AND a.barber_id = ?)
                    OR a.barber_id IS NULL
                    OR a.barber_id = 0
                    OR a.served_by_user_id = ?
                    " . ($chairPrefix ? "OR a.no_antrean LIKE ?" : "") . "
                )
              ORDER BY a.id ASC";
    $stmt_q = $pdo->prepare($query);
    $params = [$today, (int)$barber_id, (int)$user_id];
    if ($chairPrefix) {
        $params[] = $chairPrefix;
    }
    $stmt_q->execute($params);
}
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
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Barber - Elite Barber</title>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',

            theme: {
                extend: {
                    colors: {
                        adminlte: {
                            sidebar: '#0e0a08',
                            bg: '#0a0805',
                            card: '#1a1208',
                            primary: '#3d2b1a',
                            success: '#1e3a1e',
                            warning: '#e8d5a3',
                            danger: '#4a1e1e',
                            info: '#1e2a3a',
                            accent: '#c9a03a',
                        }
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome 6 & Lucide Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* SPA Transitions */
        ::view-transition-old(root) { animation: fade-out 0.2s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        ::view-transition-new(root) { animation: fade-in 0.2s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        @keyframes fade-out { 0% { opacity: 1; transform: translateY(0); } 100% { opacity: 0; transform: translateY(-10px); } }
        @keyframes fade-in { 0% { opacity: 0; transform: translateY(10px); } 100% { opacity: 1; transform: translateY(0); } }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .fallback-anim-out { animation: fade-out 0.2s forwards; }
        .fallback-anim-in { animation: fade-in 0.2s forwards; }
        .receipt-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 9999;
        }
        .receipt-card {
            background: #fff; color: #000; padding: 20px; width: 300px; 
            font-family: 'Courier New', Courier, monospace; 
            border-radius: 8px; font-size: 13px;
        }
        .receipt-card p { margin: 4px 0; display: flex; justify-content: space-between; }
        .receipt-card hr { border: none; border-top: 1px dashed #000; margin: 10px 0; }
        .r-title { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 5px; display: block; }
        .r-subtitle { text-align: center; font-size: 11px; margin-top: 0; display: block; }
        @media print {
            @page { margin: 0; } /* Menghilangkan header & footer bawaan browser saat print */
            body * { visibility: hidden; }
            #printable-receipt, #printable-receipt * { visibility: visible; }
            #printable-receipt { 
                position: absolute; left: 0; top: 0; 
                width: 100% !important; height: 100% !important; 
                margin: 0; padding: 40px !important; 
                font-size: 24px !important; box-sizing: border-box; 
                transform: none !important;
            }
            #printable-receipt .r-title { font-size: 48px !important; margin-bottom: 20px !important; }
            #printable-receipt .r-subtitle { font-size: 24px !important; margin-bottom: 10px !important; }
            #printable-receipt hr { margin: 30px 0 !important; border-top: 2px dashed #000 !important; }
            #printable-receipt p { margin: 20px 0 !important; font-size: 24px !important; }
            #printable-receipt .no-print { display: none !important; }
        }

        /* ============ SIDEBAR ============ */
        #sidebar {
            background: linear-gradient(180deg, #0e0a08 0%, #120e06 40%, #0a0603 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-x: hidden;
        }
        #brand-logo-container {
            background: linear-gradient(135deg, #1e1408 0%, #2a1c0a 100%);
            border-bottom: 1px solid #4a3020;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #brand-icon { transition: margin 0.3s ease; }
        #brand-text { transition: opacity 0.2s, max-width 0.3s; max-width: 250px; white-space: nowrap; overflow: hidden; }
        #sidebar nav a {
            position: relative; transition: all 0.25s ease;
            white-space: nowrap; overflow: hidden;
            border: 1px solid transparent; border-radius: 0.5rem;
        }
        #sidebar nav a::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 3px; background: linear-gradient(180deg, #c9a03a, #8a6010);
            border-radius: 2px; opacity: 0; transition: opacity 0.25s ease;
        }
        #sidebar nav a:hover {
            background: linear-gradient(90deg, rgba(61,43,26,0.9) 0%, rgba(42,28,10,0.6) 100%) !important;
            border-color: rgba(90,60,26,0.6);
            color: #e8d5a3 !important;
            box-shadow: 0 2px 12px rgba(0,0,0,0.3), inset 0 1px 0 rgba(201,160,58,0.08);
        }
        #sidebar nav a:hover::before { opacity: 1; }
        #sidebar nav a:hover i { transform: scale(1.15); color: #c9a03a; }
        #sidebar nav a i { transition: all 0.25s ease; }
        #sidebar nav a.bg-adminlte-primary {
            background: linear-gradient(90deg, #3d2b1a 0%, #2a1c0a 100%) !important;
            border-color: #5c3d1a !important; color: #e8d5a3 !important;
        }
        #sidebar nav a.bg-adminlte-primary::before { opacity: 1; }
        #sidebar nav span, #sidebar nav p { transition: opacity 0.2s, max-width 0.3s; max-width: 250px; overflow: hidden; white-space: nowrap; }
        #sidebar nav p { color: #6b4c20 !important; }
        #sidebar.w-20 #brand-logo-container { padding-left: 0; padding-right: 0; justify-content: center; }
        #sidebar.w-20 #brand-icon { margin-right: 0; }
        #sidebar.w-20 #brand-text { opacity: 0; max-width: 0; margin: 0; }
        #sidebar.w-20 nav a { justify-content: center; padding-left: 0; padding-right: 0; gap: 0; }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-transition { animation: fadeSlideUp 0.4s ease-out forwards; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #0e0a08; }
        ::-webkit-scrollbar-thumb { background: #3d2b1a; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #c9a03a; }

        /* Mobile Bottom Nav Bar — Premium Modern Dark Gold Theme */
        .nav-item {
            color: #9ca3af;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            -webkit-tap-highlight-color: transparent !important;
            -webkit-touch-callout: none !important;
            user-select: none !important;
            -webkit-user-select: none !important;
            outline: none !important;
            background-color: transparent !important;
        }
        .nav-item:focus,
        .nav-item:active,
        .nav-item:focus-visible,
        .nav-item:focus-within {
            outline: none !important;
            box-shadow: none !important;
            background-color: transparent !important;
            -webkit-tap-highlight-color: transparent !important;
        }
        .nav-item:hover { color: #fcd34d; }
        .nav-item.active { color: #F59E0B; }
        .nav-item .solid-icon { display: none; color: #f59e0b; filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.8)); }
        .nav-item .outline-icon { display: block; color: #9ca3af; transition: color 0.2s ease, transform 0.2s ease; }
        .nav-item:hover .outline-icon { color: #fcd34d; transform: translateY(-1px); }
        .nav-item.active .solid-icon { display: block; animation: iconPop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .nav-item.active .outline-icon { display: none; }
        .nav-item.active .nav-label { color: #fbbf24; font-weight: 700; text-shadow: 0 0 8px rgba(245, 158, 11, 0.5); }
        
        .nav-item .nav-indicator {
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 0px;
            height: 3px;
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
            border-radius: 9999px;
            box-shadow: 0 2px 10px rgba(245, 158, 11, 0.9);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .nav-item.active .nav-indicator {
            opacity: 1;
            width: 24px;
        }
        
        /* Profile Image Styles */
        .nav-item.active .profile-img { border-color: #F59E0B; box-shadow: 0 0 12px rgba(245,158,11,0.7); opacity: 1; }
        .nav-item:not(.active) .profile-img { border-color: #57534e; opacity: 0.8; }

        @keyframes iconPop {
            0% { transform: scale(0.8); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="text-amber-50 font-sans antialiased overflow-x-hidden flex h-screen">
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: linear-gradient(135deg, #0e0a08 0%, #120e06 30%, #1a0e04 60%, #0a0603 100%);"></div>
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: radial-gradient(ellipse 80% 60% at 70% 20%, rgba(90,50,15,0.15) 0%, transparent 60%), radial-gradient(ellipse 60% 40% at 20% 80%, rgba(60,30,5,0.1) 0%, transparent 50%);"></div>

    <!-- Mobile Drawer Overlay Backdrop -->
    <div id="sidebar-overlay" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-40 hidden md:hidden transition-opacity duration-300"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-adminlte-sidebar h-full flex flex-col shadow-2xl transition-transform duration-300 -translate-x-full md:translate-x-0 md:static md:z-auto flex-shrink-0">
        <script>
            if(localStorage.getItem('sidebarMinimized') === 'true' && window.innerWidth >= 768) {
                document.getElementById('sidebar').classList.replace('w-64', 'w-20');
            }
        </script>
        <!-- Brand Logo -->
        <div id="brand-logo-container" class="h-16 flex items-center justify-between px-6 overflow-hidden" style="border-bottom: 1px solid #3a2510;">
            <div class="flex items-center">
                <span id="brand-icon" class="text-2xl mr-3 shrink-0">💈</span>
                <span id="brand-text" class="text-xl font-bold tracking-tight whitespace-nowrap" style="color:#e8d5a3;">Dashboard <span class="font-normal" style="color:#8a6030;">Barber</span></span>
            </div>
            <!-- Close Button for Mobile -->
            <button type="button" onclick="toggleMobileSidebar()" class="md:hidden text-stone-400 hover:text-white p-1 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <!-- Sidebar Menu -->
        <div class="flex-1 overflow-y-auto py-4">
            <nav class="flex flex-col gap-1 px-3">
                <a href="javascript:void(0)" onclick="switchBarberTab('tab-dashboard', 'dashboard', this); if(window.innerWidth<768) toggleMobileSidebar();" class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= ($current_page === 'dashboard' || !isset($_GET['page']) || empty($current_page)) ? 'bg-adminlte-primary text-amber-200 mt-4' : 'text-stone-400 hover:text-amber-200 mt-4' ?>">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span>Panel Kerja</span>
                </a>
                <a href="javascript:void(0)" onclick="switchBarberTab('tab-charts', 'charts', this); if(window.innerWidth<768) toggleMobileSidebar();" class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $current_page === 'charts' ? 'bg-adminlte-primary text-white mt-1' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white mt-1' ?>">
                    <i data-lucide="pie-chart" class="w-5 h-5"></i>
                    <span>Statistik (Charts)</span>
                </a>
                <a href="javascript:void(0)" onclick="switchBarberTab('tab-kursi', 'kursi', this); if(window.innerWidth<768) toggleMobileSidebar();" class="sidebar-item flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg transition-colors <?= $current_page === 'kursi' ? 'bg-adminlte-primary text-white mt-1' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white mt-1' ?>">
                    <div class="flex items-center gap-3 min-w-0">
                        <i data-lucide="armchair" class="w-5 h-5 shrink-0"></i>
                        <span class="truncate">Kursi (Stasiun Kerja)</span>
                    </div>
                    <?php if ($barber): ?>
                        <?php if ($has_selected_chair_today): ?>
                            <span class="inline-flex items-center gap-1 bg-amber-500/20 border border-amber-500/40 px-2 py-0.5 rounded-md text-amber-300 text-[10px] font-bold shrink-0">
                                <?= htmlspecialchars($barber['kursi']) ?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 bg-rose-500/20 border border-rose-500/40 px-2 py-0.5 rounded-md text-rose-300 text-[10px] font-bold shrink-0 animate-pulse">
                                Belum Pilih
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </a>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <p class="px-3 text-xs font-semibold uppercase tracking-wider mb-2 mt-4" style="color:#5c3d1a;">Sistem</p>
                <a href="admin.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-zinc-400 hover:bg-zinc-800 hover:text-white">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                    <span>Panel Admin</span>
                </a>
                <?php endif; ?>

                <p class="px-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2 mt-4">Lainnya</p>
                <a href="javascript:void(0)" onclick="switchBarberTab('tab-profil', 'profil', this); if(window.innerWidth<768) toggleMobileSidebar();" class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= (in_array($current_page, ['profil', 'profile'])) ? 'bg-adminlte-primary text-white' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?>">
                    <i data-lucide="user" class="w-5 h-5"></i>
                    <span>Profil</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer / Bottom Home & Logout Buttons -->
        <div class="sidebar-footer p-3 border-t border-amber-900/30 bg-zinc-950/40 space-y-1">
            <a href="../index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-zinc-400 hover:text-amber-200 hover:bg-amber-500/10 transition-colors">
                <i data-lucide="home" class="fa-solid fa-house w-5 h-5 text-zinc-400 shrink-0"></i>
                <span class="text-sm font-medium">Home</span>
            </a>
            <a href="../auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-400 hover:text-rose-200 hover:bg-rose-500/20 transition-colors font-medium">
                <i data-lucide="log-out" class="w-5 h-5 text-rose-400 shrink-0"></i>
                <span class="text-sm font-medium">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Top Navbar -->
        <header class="h-16 flex items-center justify-between px-4 sm:px-6 shadow-lg z-10 shrink-0" style="background: linear-gradient(90deg, #1a1008 0%, #110d06 50%, #1a1008 100%); border-bottom: 1px solid rgba(90,55,15,0.4);">
            <div class="flex items-center gap-3">
                <button id="sidebar-toggle" class="p-1.5 rounded-lg text-amber-400 hover:bg-amber-950/50 transition-colors focus:outline-none" title="Menu Navigasi">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="text-base sm:text-xl font-semibold text-white capitalize truncate max-w-[150px] sm:max-w-none">
                    <?= $current_page === 'dashboard' ? 'Panel Kerja Barber' : ($current_page === 'charts' ? 'Statistik & Analisis Performa' : ($current_page === 'kursi' ? 'Stasiun & Manajemen Kursi' : ($current_page === 'profil' ? 'Profil Barber Saya' : str_replace('_', ' ', $current_page)))) ?>
                </h1>
            </div>
            <div class="flex items-center gap-3 sm:gap-4">
                <div id="realtime-clock" class="hidden md:block text-xs sm:text-sm text-zinc-300 font-medium tracking-wide"></div>
                <div class="relative" id="user-profile-dropdown-container">
                    <button type="button" onclick="toggleProfileDropdown(event)" class="flex items-center gap-2 cursor-pointer hover:opacity-90 transition-all p-1 sm:p-1.5 rounded-xl hover:bg-amber-500/10 focus:outline-none border border-transparent hover:border-amber-500/20 group" id="user-profile-dropdown-btn">
                        <?php 
                        $nav_avatar_name = !empty($user_data['fullname']) ? urlencode($user_data['fullname']) : urlencode($_SESSION['username']);
                        $nav_profile_files = glob(__DIR__ . '/../asset/image/profile_' . $_SESSION['user_id'] . '.*');
                        $nav_profile_url = !empty($nav_profile_files) ? '../asset/image/' . basename($nav_profile_files[0]) : "https://ui-avatars.com/api/?name={$nav_avatar_name}&background=random&color=fff&size=64&bold=true";
                        ?>
                        <img src="<?= $nav_profile_url ?>" alt="Avatar" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover shadow-md border-2 border-amber-700/60 transition-transform group-hover:scale-105">
                        <span class="hidden md:block text-sm text-zinc-200 font-medium max-w-[130px] truncate"><?= htmlspecialchars(!empty($user_data['fullname']) ? $user_data['fullname'] : $_SESSION['username']) ?></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-amber-400 transition-transform duration-200" id="profile-dropdown-chevron"></i>
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <div id="user-profile-dropdown-menu" class="hidden absolute right-0 mt-2 w-52 bg-[#161009] border border-amber-900/60 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-xl divide-y divide-amber-900/40">
                        <div class="p-3 bg-[#1e1408]">
                            <span class="text-xs font-bold text-amber-200 block truncate"><?= htmlspecialchars(!empty($user_data['fullname']) ? $user_data['fullname'] : $_SESSION['username']) ?></span>
                            <span class="text-[10px] text-amber-400/80 font-mono capitalize">Role: Barber</span>
                        </div>
                        <div class="py-1">
                            <a href="javascript:void(0)" onclick="switchBarberTab('tab-profil', 'profil', this); closeProfileDropdown();" class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-amber-200 hover:bg-amber-500/20 hover:text-amber-100 transition-colors">
                                <i data-lucide="user" class="w-4 h-4 text-amber-400"></i>
                                <span>Profil Saya</span>
                            </a>
                        </div>
                        <div class="py-1 bg-rose-950/10">
                            <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-rose-400 hover:bg-rose-500/20 hover:text-rose-300 transition-colors">
                                <i data-lucide="log-out" class="w-4 h-4 text-rose-400"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 pb-24 md:pb-6 page-transition">
            <?php if (function_exists('display_flash')) display_flash(); ?>

            <!-- TAB 1: DASHBOARD / PANEL KERJA -->
            <div id="tab-dashboard" class="tab-content <?= ($current_page === 'dashboard' || empty($current_page)) ? 'active' : '' ?>">
                <!-- Daily Chair Selection Banner -->
                <?php if (!$has_selected_chair_today): ?>
                    <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-amber-950/80 via-amber-900/40 to-amber-950/80 border-2 border-amber-500/50 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 shrink-0 text-xl">
                                💈
                            </div>
                            <div>
                                <h4 class="font-bold text-amber-200 text-base">Anda Belum Memilih Kursi Tugas Hari Ini</h4>
                                <p class="text-xs text-zinc-300">Silakan pilih Kursi A, B, atau C untuk hari ini agar pelanggan dapat memesan layanan Anda.</p>
                            </div>
                        </div>
                        <button type="button" onclick="openSelectKursiModal()" class="bg-amber-500 hover:bg-amber-400 text-amber-950 font-bold text-xs px-5 py-2.5 rounded-lg transition-all duration-300 shadow-[0_0_15px_rgba(245,158,11,0.4)] shrink-0 flex items-center gap-1.5">
                            <i data-lucide="armchair" class="w-4 h-4"></i> Pilih Kursi Sekarang
                        </button>
                    </div>
                <?php else: ?>
                    <div class="mb-6 p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between text-xs text-amber-300">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
                            <span>Kursi bertugas Anda hari ini: <strong class="text-amber-200 font-bold"><?= htmlspecialchars($barber['kursi']) ?></strong> (Berlaku s/d Akhir Hari Ini)</span>
                        </div>
                        <button type="button" onclick="openSelectKursiModal()" class="text-amber-400 hover:text-amber-200 font-semibold underline underline-offset-2">
                            Ubah Kursi Tugas
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Stats -->
                <div class="grid grid-cols-3 gap-3 sm:gap-6 mb-6">
                    <div class="bg-adminlte-info rounded-xl p-3.5 sm:p-6 relative overflow-hidden text-white shadow-lg border border-blue-500/30">
                        <div class="relative z-10">
                            <h3 class="text-2xl sm:text-4xl font-black mb-0.5 sm:mb-1"><?= count($queues) ?></h3>
                            <p class="text-blue-100 text-xs sm:text-sm font-medium">Total Antrean</p>
                        </div>
                        <i data-lucide="list" class="absolute -right-3 -bottom-3 sm:-right-4 sm:-bottom-4 w-16 h-16 sm:w-32 sm:h-32 text-white/10 z-0"></i>
                    </div>
                    <div class="bg-adminlte-warning rounded-xl p-3.5 sm:p-6 relative overflow-hidden text-zinc-950 shadow-lg border border-amber-500/40">
                        <div class="relative z-10">
                            <h3 class="text-2xl sm:text-4xl font-black mb-0.5 sm:mb-1"><?= $total_waiting ?></h3>
                            <p class="text-amber-950 text-xs sm:text-sm font-semibold">Menunggu</p>
                        </div>
                        <i data-lucide="clock" class="absolute -right-3 -bottom-3 sm:-right-4 sm:-bottom-4 w-16 h-16 sm:w-32 sm:h-32 text-black/10 z-0"></i>
                    </div>
                    <div class="bg-adminlte-success rounded-xl p-3.5 sm:p-6 relative overflow-hidden text-white shadow-lg border border-emerald-500/30">
                        <div class="relative z-10">
                            <h3 class="text-2xl sm:text-4xl font-black mb-0.5 sm:mb-1"><?= $total_served ?></h3>
                            <p class="text-emerald-100 text-xs sm:text-sm font-medium">Selesai</p>
                        </div>
                        <i data-lucide="check-circle" class="absolute -right-3 -bottom-3 sm:-right-4 sm:-bottom-4 w-16 h-16 sm:w-32 sm:h-32 text-black/10 z-0"></i>
                    </div>
                </div>

                <!-- Antrean Queue Header -->
                <div class="flex items-center justify-between mb-3 px-1">
                    <h3 class="font-bold text-white text-base sm:text-lg flex items-center gap-2">
                        <i data-lucide="list-ordered" class="w-5 h-5 text-amber-400"></i>
                        Daftar Antrean Tugas Anda
                    </h3>
                    <span class="text-xs text-stone-400 bg-stone-900 px-2.5 py-1 rounded-full border border-stone-800">
                        Total: <strong class="text-amber-300"><?= count($queues) ?></strong>
                    </span>
                </div>

                <!-- Mobile Queue Cards View (Visible on Mobile < md) -->
                <div class="block md:hidden space-y-3 mb-6">
                    <?php if (empty($queues)): ?>
                        <div class="p-8 text-center bg-adminlte-card rounded-xl border border-zinc-800 text-stone-500 text-sm">
                            <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 text-stone-600"></i>
                            Belum ada antrean masuk untuk Anda hari ini.
                        </div>
                    <?php else: ?>
                        <?php foreach ($queues as $q): 
                            $base_price = (float)($q['harga'] ?? 0);
                            $final_price = $base_price;
                            $status = $q['status_antrean'];

                            $card_border = 'border-amber-900/40';
                            $badge_bg = 'bg-amber-500/20 text-amber-300 border-amber-500/40';
                            $status_label = 'MENUNGGU';

                            if ($status === 'serving') {
                                $card_border = 'border-blue-500/60 shadow-[0_0_15px_rgba(59,130,246,0.25)]';
                                $badge_bg = 'bg-blue-500/20 text-blue-300 border-blue-500/40 animate-pulse';
                                $status_label = 'SEDANG DILAYANI';
                            } elseif ($status === 'payment') {
                                $card_border = 'border-amber-500/60 shadow-[0_0_15px_rgba(245,158,11,0.25)]';
                                $badge_bg = 'bg-amber-500/20 text-amber-300 border-amber-500/40';
                                $status_label = 'MENUNGGU BAYAR';
                            } elseif (in_array($status, ['paid', 'review', 'completed'])) {
                                $card_border = 'border-emerald-500/40';
                                $badge_bg = 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40';
                                $status_label = 'SELESAI';
                            }
                        ?>
                        <div class="p-4 rounded-xl border bg-gradient-to-b from-[#1a1208] to-[#120d07] <?= $card_border ?> shadow-lg space-y-3 transition-all duration-300">
                            <!-- Header: Tiket & Status Badge -->
                            <div class="flex items-center justify-between pb-2 border-b border-amber-900/30">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-stone-400">Tiket:</span>
                                    <span class="text-xl font-black text-amber-200 tracking-wider bg-amber-950/80 px-2.5 py-0.5 rounded-lg border border-amber-800/50">
                                        <?= htmlspecialchars($q['no_antrean']) ?>
                                    </span>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider <?= $badge_bg ?>">
                                    <?= $status_label ?>
                                </span>
                            </div>

                            <!-- Details: Pelanggan & Layanan -->
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-[10px] text-stone-400 block uppercase tracking-wider">Pelanggan</span>
                                    <span class="font-bold text-white truncate block"><?= htmlspecialchars($q['pelanggan_nama'] ?? 'Guest') ?></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-stone-400 block uppercase tracking-wider">Layanan</span>
                                    <span class="font-bold text-amber-100 truncate block"><?= htmlspecialchars($q['nama_layanan'] ?? 'Cukur Standar') ?></span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <div class="text-xs text-stone-400">
                                    <span>Harga:</span>
                                    <span class="text-emerald-400 font-extrabold text-base ml-1">Rp <?= number_format($final_price, 0, ',', '.') ?></span>
                                </div>
                                <?php if ($status === 'paid' && !empty($q['metode_bayar'])): ?>
                                    <span class="text-[10px] text-stone-300 bg-stone-900 px-2 py-0.5 rounded border border-stone-800">
                                        Via: <?= htmlspecialchars($q['metode_bayar']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Mobile Action Buttons -->
                            <div class="pt-2 border-t border-amber-900/30 flex flex-wrap gap-2">
                                <?php if ($status === 'waiting'): ?>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="action" value="call">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs py-2.5 px-3 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-md active:scale-95">
                                            <i data-lucide="megaphone" class="w-4 h-4"></i> Panggil
                                        </button>
                                    </form>
                                    <form method="POST" class="shrink-0">
                                        <input type="hidden" name="action" value="skip">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <button type="submit" class="bg-red-950/60 hover:bg-red-900/80 text-red-300 border border-red-800/40 text-xs py-2.5 px-3 rounded-lg flex items-center justify-center gap-1 transition-all active:scale-95" onclick="return confirm('Lewati antrean ini?')">
                                            <i data-lucide="skip-forward" class="w-4 h-4"></i> Skip
                                        </button>
                                    </form>
                                <?php elseif ($status === 'serving'): ?>
                                    <form method="POST" class="w-full">
                                        <input type="hidden" name="action" value="finish_service">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-md active:scale-95">
                                            <i data-lucide="check" class="w-4 h-4"></i> Selesai Layani
                                        </button>
                                    </form>
                                <?php elseif ($status === 'payment'): ?>
                                    <form method="POST" class="w-full">
                                        <input type="hidden" name="action" value="confirm_paid">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                                        <button type="submit" class="w-full bg-cyan-700 hover:bg-cyan-600 text-white font-bold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-md active:scale-95" onclick="return confirm('Konfirmasi bayar cash langsung?')">
                                            <i data-lucide="banknote" class="w-4 h-4"></i> Terima Cash
                                        </button>
                                    </form>
                                <?php elseif ($status === 'paid'): ?>
                                    <form method="POST" class="w-full">
                                        <input type="hidden" name="action" value="confirm_paid">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-md active:scale-95">
                                            <i data-lucide="printer" class="w-4 h-4"></i> Cetak & Selesai
                                        </button>
                                    </form>
                                <?php elseif (in_array($status, ['review', 'completed'])): ?>
                                    <button type="button" class="w-full bg-stone-800 hover:bg-stone-700 text-stone-300 font-semibold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-1.5 transition-all active:scale-95" onclick="printStruk('<?= $q['no_antrean'] ?>', '<?= htmlspecialchars($q['pelanggan_nama'] ?? 'Guest') ?>', '<?= htmlspecialchars($q['nama_layanan'] ?? 'Layanan') ?>', '<?= $final_price ?>', '<?= htmlspecialchars($q['metode_bayar'] ?? 'Cash') ?>')">
                                        <i data-lucide="printer" class="w-4 h-4"></i> Cetak Ulang Struk
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Desktop Table View (Visible on Desktop >= md) -->
                <div class="hidden md:block bg-adminlte-card rounded-xl border border-zinc-700 shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-zinc-800/50 text-zinc-400 text-sm border-b border-zinc-700">
                                    <th class="px-6 py-3 font-medium">No. Tiket</th>
                                    <th class="px-6 py-3 font-medium">Pelanggan</th>
                                    <th class="px-6 py-3 font-medium">Layanan & Harga</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                    <th class="px-6 py-3 font-medium text-right">Aksi Kerja</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-700/50">
                                <?php if (empty($queues)): ?>
                                <tr><td colspan="5" class="px-6 py-8 text-center text-zinc-500">Belum ada antrean masuk untuk Anda hari ini.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($queues as $q): 
                                        $base_price = (float)($q['harga'] ?? 0);
                                        $final_price = $base_price;
                                    ?>
                                    <tr class="hover:bg-zinc-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-lg text-white"><?= htmlspecialchars($q['no_antrean']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 font-medium"><?= htmlspecialchars($q['pelanggan_nama'] ?? 'Guest') ?></td>
                                        <td class="px-6 py-4">
                                            <div class="text-white"><?= htmlspecialchars($q['nama_layanan'] ?? 'Cukur Standar') ?></div>
                                            <div class="text-green-400 text-sm">Rp <?= number_format($final_price, 0, ',', '.') ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $badge_class = 'bg-zinc-600 text-white';
                                            if ($q['status_antrean'] === 'serving') $badge_class = 'bg-adminlte-primary text-white';
                                            if ($q['status_antrean'] === 'payment') $badge_class = 'bg-adminlte-warning text-zinc-900';
                                            if (in_array($q['status_antrean'], ['paid', 'review', 'completed'])) $badge_class = 'bg-adminlte-success text-white';
                                            ?>
                                            <span class="<?= $badge_class ?> px-2 py-1 rounded text-xs font-semibold uppercase">
                                                <?= htmlspecialchars($q['status_antrean']) ?>
                                            </span>
                                            <?php if ($q['status_antrean'] === 'paid' && !empty($q['metode_bayar'])): ?>
                                                <div class="text-xs text-zinc-400 mt-1">Via: <?= htmlspecialchars($q['metode_bayar']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <?php if ($q['status_antrean'] === 'waiting'): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="call">
                                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                        <button type="submit" class="bg-adminlte-primary hover:bg-blue-600 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors">
                                                            <i data-lucide="megaphone" class="w-3 h-3"></i> Panggil
                                                        </button>
                                                    </form>
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="skip">
                                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                        <button type="submit" class="bg-adminlte-danger hover:bg-red-700 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors" onclick="return confirm('Lewati antrean ini?')">
                                                            <i data-lucide="skip-forward" class="w-3 h-3"></i> Skip
                                                        </button>
                                                    </form>
                                                <?php elseif ($q['status_antrean'] === 'serving'): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="finish_service">
                                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                        <button type="submit" class="bg-adminlte-success hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors">
                                                            <i data-lucide="check" class="w-3 h-3"></i> Selesai Layani
                                                        </button>
                                                    </form>
                                                <?php elseif ($q['status_antrean'] === 'payment'): ?>
                                                    <div class="flex flex-col items-end gap-1">
                                                        <span class="text-xs text-zinc-400">Menunggu Bayar...</span>
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="confirm_paid">
                                                            <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                            <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                                                            <button type="submit" class="bg-adminlte-info hover:bg-cyan-600 text-white text-xs px-3 py-1.5 rounded transition-colors" onclick="return confirm('Konfirmasi bayar cash langsung?')">
                                                                Terima Cash
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php elseif ($q['status_antrean'] === 'paid'): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="confirm_paid">
                                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                        <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                                                        <button type="submit" class="bg-adminlte-success hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded transition-colors">
                                                            Cetak & Selesai
                                                        </button>
                                                    </form>
                                                <?php elseif (in_array($q['status_antrean'], ['review', 'completed'])): ?>
                                                    <button type="button" class="bg-zinc-700 hover:bg-zinc-600 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors" onclick="printStruk('<?= $q['no_antrean'] ?>', '<?= htmlspecialchars($q['pelanggan_nama'] ?? 'Guest') ?>', '<?= htmlspecialchars($q['nama_layanan'] ?? 'Layanan') ?>', '<?= $final_price ?>', '<?= htmlspecialchars($q['metode_bayar'] ?? 'Cash') ?>')">
                                                        <i data-lucide="printer" class="w-3 h-3"></i> Cetak Ulang
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div><!-- /tab-dashboard -->

            <!-- TAB 2: STATISTIK & CHARTS -->
            <div id="tab-charts" class="tab-content <?= $current_page === 'charts' ? 'active' : '' ?>">
                <!-- Summary Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-6">
                    <div class="p-3.5 sm:p-5 rounded-xl border shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <p class="text-[10px] sm:text-xs font-semibold text-amber-200/80 uppercase tracking-wider">Pelanggan Bulan Ini</p>
                            <h3 class="text-lg sm:text-3xl font-extrabold text-white mt-1"><?= number_format($barberCountMonth) ?> <span class="text-xs font-normal text-amber-400">Orang</span></h3>
                        </div>
                        <div class="p-2 sm:p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 self-end sm:self-auto">
                            <i data-lucide="users" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                        </div>
                    </div>

                    <div class="p-3.5 sm:p-5 rounded-xl border shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <p class="text-[10px] sm:text-xs font-semibold text-emerald-200/80 uppercase tracking-wider">Omset Bulan Ini</p>
                            <h3 class="text-base sm:text-2xl font-extrabold text-emerald-400 mt-1">Rp <?= number_format($barberOmsetMonth, 0, ',', '.') ?></h3>
                        </div>
                        <div class="p-2 sm:p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 self-end sm:self-auto">
                            <i data-lucide="banknote" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                        </div>
                    </div>

                    <div class="p-3.5 sm:p-5 rounded-xl border shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <p class="text-[10px] sm:text-xs font-semibold text-sky-200/80 uppercase tracking-wider">Terlaris</p>
                            <h3 class="text-sm sm:text-xl font-bold text-sky-300 mt-1 truncate max-w-[100px] sm:max-w-[140px]"><?= htmlspecialchars($topServiceName) ?></h3>
                        </div>
                        <div class="p-2 sm:p-3.5 rounded-xl bg-sky-500/10 border border-sky-500/30 text-sky-400 self-end sm:self-auto">
                            <i data-lucide="scissors" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                        </div>
                    </div>

                    <div class="p-3.5 sm:p-5 rounded-xl border shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <p class="text-[10px] sm:text-xs font-semibold text-amber-200/80 uppercase tracking-wider">Rating</p>
                            <h3 class="text-base sm:text-2xl font-extrabold text-amber-300 mt-1 flex items-center gap-1">
                                ⭐ <?= number_format($barberRating, 1) ?> 
                                <span class="text-[10px] sm:text-xs font-normal text-stone-400">(<?= $barberTotalUlasan ?>)</span>
                            </h3>
                        </div>
                        <div class="p-2 sm:p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 self-end sm:self-auto">
                            <i data-lucide="star" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                        </div>
                    </div>
                </div>

                <!-- Grid 2 Chart Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Line Chart (Tren 30 Hari Terakhir - Scrollable) -->
                    <div class="lg:col-span-2 p-6 rounded-2xl border shadow-md flex flex-col justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4 pb-3 border-b border-amber-900/30">
                                <h3 class="text-xl font-bold tracking-wide flex items-center gap-2" style="color:#e8d5a3;">
                                    <i data-lucide="trending-up" class="w-5 h-5 text-amber-400"></i>
                                    Tren Pelanggan Dilayani (30 Hari Terakhir)
                                </h3>
                                <span class="text-[11px] text-amber-300 bg-amber-950/60 border border-amber-800/40 px-2.5 py-1 rounded-full font-medium flex items-center gap-1 shrink-0">
                                    ↔ Geser Kiri / Kanan (Max 30 Hari)
                                </span>
                            </div>
                            <div id="barberChartScrollContainer" class="overflow-x-auto custom-scroll pb-2">
                                <div style="height: 330px; min-width: 1250px;">
                                    <canvas id="barberChart1"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pie/Doughnut Chart (Proporsi Layanan) -->
                    <div class="p-6 rounded-2xl border shadow-md flex flex-col justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <h3 class="text-xl font-bold mb-4 pb-3 border-b border-amber-900/30 tracking-wide flex items-center gap-2" style="color:#e8d5a3;">
                                <i data-lucide="pie-chart" class="w-5 h-5 text-sky-400"></i>
                                Proporsi Layanan Dikerjakan
                            </h3>
                            <div style="height: 330px; width: 100%;" class="flex items-center justify-center">
                                <?php if (empty($pieCounts)): ?>
                                    <div class="text-center text-stone-400 py-12">Belum ada data layanan selesai.</div>
                                <?php else: ?>
                                    <canvas id="barberPieChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: PROFIL SAYA -->
            <div id="tab-profil" class="tab-content <?= in_array($current_page, ['profil', 'profile']) ? 'active' : '' ?>">
                <div class="max-w-4xl mx-auto">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white tracking-tight">Profil Saya</h2>
                            <p class="text-zinc-400 text-sm mt-1">Kelola informasi pribadi dan keamanan akun Anda</p>
                        </div>
                    </div>

                    <form action="barber.php?page=profil" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <input type="hidden" name="action" value="update_profil">
                        
                        <!-- Left Column: Avatar & Summary -->
                        <div class="col-span-1">
                            <div class="bg-adminlte-card border border-zinc-700 rounded-xl p-6 shadow-2xl flex flex-col items-center text-center relative overflow-hidden">
                                <!-- Background Decoration -->
                                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-amber-900/30 to-amber-950/20 z-0"></div>
                                
                                <div class="relative z-10 w-28 h-28 rounded-full border-4 border-zinc-700 shadow-xl mt-4 mb-4 overflow-hidden bg-zinc-900 group">
                                    <?php 
                                    $avatar_name = !empty($user_data['fullname']) ? urlencode($user_data['fullname']) : urlencode($_SESSION['username']);
                                    $profile_files = glob(__DIR__ . '/../asset/image/profile_' . $_SESSION['user_id'] . '.*');
                                    $profile_url = !empty($profile_files) ? '../asset/image/' . basename($profile_files[0]) : "https://ui-avatars.com/api/?name={$avatar_name}&background=random&color=fff&size=128&bold=true";
                                    ?>
                                    <img src="<?= $profile_url ?>" alt="Avatar" class="w-full h-full object-cover">
                                    <label for="foto_profil_input" class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white text-xs font-semibold backdrop-blur-sm">
                                        <i data-lucide="camera" class="w-6 h-6 mb-1 text-amber-400"></i>
                                        Ubah Foto
                                    </label>
                                    <input type="file" name="foto_profil" id="foto_profil_input" class="hidden" accept="image/*" onchange="document.getElementById('profile_save_btn').click();">
                                </div>
                                
                                <h3 class="relative z-10 text-xl font-bold text-white mb-1"><?= !empty($user_data['fullname']) ? htmlspecialchars($user_data['fullname']) : htmlspecialchars($_SESSION['username']) ?></h3>
                                <span class="relative z-10 bg-amber-500/20 text-amber-300 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider mb-4 border border-amber-500/30">
                                    Barber
                                </span>
                                
                                <div class="relative z-10 w-full text-left space-y-3 mt-4 border-t border-zinc-700/80 pt-4">
                                    <div class="flex items-center gap-3 text-sm text-zinc-300">
                                        <i data-lucide="mail" class="w-4 h-4 text-amber-400"></i>
                                        <span class="truncate"><?= !empty($user_data['email']) ? htmlspecialchars($user_data['email']) : '<em class="text-zinc-500">Belum diatur</em>' ?></span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm text-zinc-300">
                                        <i data-lucide="phone" class="w-4 h-4 text-amber-400"></i>
                                        <span><?= !empty($user_data['phone']) ? htmlspecialchars($user_data['phone']) : '<em class="text-zinc-500">Belum diatur</em>' ?></span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm text-zinc-300">
                                        <i data-lucide="user" class="w-4 h-4 text-amber-400"></i>
                                        <span>@<?= htmlspecialchars($user_data['username'] ?? $_SESSION['username']) ?></span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm text-amber-300 bg-amber-950/40 p-2.5 rounded-lg border border-amber-800/40">
                                        <i data-lucide="star" class="w-4 h-4 text-amber-400 shrink-0"></i>
                                        <span>Rating: <strong>⭐ <?= number_format($barberRating, 1) ?> / 5.0</strong></span>
                                    </div>
                                    <div class="pt-2">
                                        <a href="../auth/logout.php" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/40 text-rose-400 hover:text-rose-300 font-bold text-xs transition-colors shadow">
                                            <i data-lucide="log-out" class="w-4 h-4 text-rose-400"></i>
                                            <span>Logout dari Akun</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Edit Form -->
                        <div class="col-span-1 md:col-span-2">
                            <div class="bg-adminlte-card border border-zinc-700 rounded-xl shadow-2xl overflow-hidden">
                                <div class="border-b border-zinc-700 px-6 py-4 bg-[#30363d] flex items-center gap-3">
                                    <i data-lucide="settings-2" class="w-5 h-5 text-amber-400"></i>
                                    <h3 class="text-lg font-semibold text-white">Pengaturan Akun</h3>
                                </div>
                                
                                <div class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-zinc-400 mb-2">Nama Lengkap</label>
                                            <input type="text" name="fullname" value="<?= htmlspecialchars($user_data['fullname'] ?? '') ?>" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2.5 text-white focus:outline-none focus:border-amber-500" placeholder="Nama lengkap Anda" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-zinc-400 mb-2">Username</label>
                                            <input type="text" name="username" value="<?= htmlspecialchars($user_data['username'] ?? $_SESSION['username']) ?>" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2.5 text-white focus:outline-none focus:border-amber-500" placeholder="Username" required>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-zinc-400 mb-2">Email</label>
                                            <input type="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2.5 text-white focus:outline-none focus:border-amber-500" placeholder="alamat@email.com">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-zinc-400 mb-2">No. WhatsApp / Telepon</label>
                                            <input type="text" name="phone" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2.5 text-white focus:outline-none focus:border-amber-500" placeholder="08xxxxxxxxxx">
                                        </div>
                                    </div>

                                    <div class="border-t border-zinc-700/80 pt-6">
                                        <h4 class="text-sm font-medium text-white mb-4 flex items-center gap-2">
                                            <i data-lucide="shield-check" class="w-4 h-4 text-amber-400"></i> Keamanan Akun & Ubah Password
                                        </h4>
                                        
                                        <div class="space-y-4 max-w-xl">
                                            <div>
                                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Password Lama Saat Ini</label>
                                                <div class="relative">
                                                    <input type="password" id="barber_old_pass" name="old_password" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-amber-500 transition-all text-sm" placeholder="Masukkan password lama Anda">
                                                    <button type="button" onclick="togglePass('barber_old_pass', 'b_eye_old')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                                        <i data-lucide="eye" id="b_eye_old" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                                <p class="text-[11px] text-zinc-500 mt-1">* Wajib diisi untuk memverifikasi bahwa Anda adalah pemilik sah akun ini.</p>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Password Baru</label>
                                                    <div class="relative">
                                                        <input type="password" id="barber_new_pass" name="new_password" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-amber-500 transition-all text-sm" placeholder="Min. 6-8 karakter">
                                                        <button type="button" onclick="togglePass('barber_new_pass', 'b_eye_new')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                                            <i data-lucide="eye" id="b_eye_new" class="w-4 h-4"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Konfirmasi Password Baru</label>
                                                    <div class="relative">
                                                        <input type="password" id="barber_conf_pass" name="confirm_password" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-amber-500 transition-all text-sm" placeholder="Ulangi password baru">
                                                        <button type="button" onclick="togglePass('barber_conf_pass', 'b_eye_conf')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                                            <i data-lucide="eye" id="b_eye_conf" class="w-4 h-4"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-end pt-2">
                                        <button type="submit" id="profile_save_btn" class="bg-amber-600 hover:bg-amber-500 text-white font-medium px-6 py-2.5 rounded-md transition-colors shadow-lg flex items-center gap-2">
                                            <i data-lucide="save" class="w-4 h-4"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            <!-- TAB 4: KURSI & STASIUN KERJA BARBER -->
            <div id="tab-kursi" class="tab-content <?= $current_page === 'kursi' ? 'active' : '' ?>">
                <div class="max-w-6xl mx-auto space-y-6">
                    <!-- Header Banner -->
                    <div class="p-6 rounded-2xl bg-gradient-to-r from-[#1e1408] via-[#2a1c0a] to-[#120e06] border border-amber-500/30 shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-300 text-2xl shadow-inner">
                                    💈
                                </div>
                                <div>
                                    <h2 class="text-xl sm:text-2xl font-bold text-amber-100 tracking-tight">Manajemen Stasiun Kursi Barber</h2>
                                    <p class="text-xs sm:text-sm text-zinc-300">Pilih atau ubah kursi tugas harian Anda agar antrean pelanggan langsung terhubung ke stasiun Anda.</p>
                                </div>
                            </div>
                        </div>
                        <!-- Current Status Badge Card -->
                        <div class="relative z-10 shrink-0 bg-black/40 border border-amber-500/30 rounded-xl p-4 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-400">
                                <i data-lucide="armchair" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase tracking-wider font-semibold text-zinc-400">Status Tugas Hari Ini</span>
                                <?php if ($has_selected_chair_today && $barber): ?>
                                    <div class="text-sm font-bold text-amber-300 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#10B981]"></span>
                                        <?= htmlspecialchars($barber['kursi']) ?> <span class="text-xs text-emerald-400 font-medium">(Aktif)</span>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm font-bold text-rose-400 flex items-center gap-1.5 animate-pulse">
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                        Belum Memilih Kursi
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Chair Cards Grid (Kursi A, B, C) -->
                    <?php
                    $chairs_info = [
                        'Kursi A' => ['letter' => 'A', 'station' => 'Stasiun 1 (Kiri)', 'desc' => 'Stasiun Kiri dekat cermin utama & pencahayaan LED warm.'],
                        'Kursi B' => ['letter' => 'B', 'station' => 'Stasiun 2 (Tengah)', 'desc' => 'Stasiun Utama (Tengah) dilengkapi dudukan premium hydraulic.'],
                        'Kursi C' => ['letter' => 'C', 'station' => 'Stasiun 3 (Kanan)', 'desc' => 'Stasiun Kanan dengan akses stopkontak langsung & perlengkapan steril.']
                    ];
                    ?>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php foreach ($chairs_info as $k_name => $k_info): 
                            $is_my_chair = ($has_selected_chair_today && isset($barber['kursi']) && $barber['kursi'] === $k_name);
                            $is_occupied = isset($occupied_chairs[$k_name]);
                            $occupant_name = $occupied_chairs[$k_name] ?? '';
                            
                            // Count queue on this chair today
                            $chair_letter = $k_info['letter'];
                            $chair_queue_count = 0;
                            $chair_waiting_count = 0;
                            if (isset($queues) && is_array($queues)) {
                                foreach ($queues as $q_item) {
                                    if (substr($q_item['ticket_number'] ?? '', 0, 1) === $chair_letter) {
                                        $chair_queue_count++;
                                        if (($q_item['status_antrean'] ?? '') === 'waiting') $chair_waiting_count++;
                                    }
                                }
                            }
                        ?>
                            <div class="rounded-2xl p-6 transition-all duration-300 relative flex flex-col justify-between overflow-hidden shadow-xl border <?= $is_my_chair ? 'bg-gradient-to-b from-[#2a1c0a] to-[#18120b] border-amber-500 shadow-amber-500/10 ring-2 ring-amber-500/30' : ($is_occupied ? 'bg-[#120e06]/80 border-white/5 opacity-80' : 'bg-[#18120b] border-white/10 hover:border-amber-500/50 hover:-translate-y-1') ?>">
                                <div>
                                    <!-- Header Card -->
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-xl flex items-center justify-center font-extrabold text-2xl shadow-lg border <?= $is_my_chair ? 'bg-gradient-to-br from-amber-500 to-amber-700 text-amber-950 border-amber-300' : 'bg-zinc-800 text-amber-400 border-white/10' ?>">
                                                <?= $k_info['letter'] ?>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-white tracking-tight"><?= $k_name ?></h3>
                                                <p class="text-xs text-amber-400/90 font-medium"><?= $k_info['station'] ?></p>
                                            </div>
                                        </div>

                                        <!-- Status Badge -->
                                        <?php if ($is_my_chair): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-[10px] font-bold tracking-wide uppercase flex items-center gap-1 shadow">
                                                ✓ Kursi Anda
                                            </span>
                                        <?php elseif ($is_occupied): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-rose-500/20 border border-rose-500/40 text-rose-400 text-[10px] font-bold tracking-wide uppercase flex items-center gap-1">
                                                Terisi
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[10px] font-semibold tracking-wide uppercase">
                                                Tersedia
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <p class="text-xs text-zinc-400 mb-4 leading-relaxed"><?= $k_info['desc'] ?></p>

                                    <!-- Metrics for this chair -->
                                    <div class="grid grid-cols-2 gap-2 mb-5 p-3 rounded-xl bg-black/40 border border-white/5 text-xs">
                                        <div>
                                            <span class="text-zinc-500 block text-[10px]">Total Antrean</span>
                                            <strong class="text-amber-200 font-bold text-sm"><?= $chair_queue_count ?> Tiket</strong>
                                        </div>
                                        <div>
                                            <span class="text-zinc-500 block text-[10px]">Menunggu Saat Ini</span>
                                            <strong class="text-emerald-400 font-bold text-sm"><?= $chair_waiting_count ?> Orang</strong>
                                        </div>
                                    </div>

                                    <!-- Occupant Info -->
                                    <?php if ($is_occupied && !$is_my_chair): ?>
                                        <div class="p-3 rounded-xl bg-rose-950/30 border border-rose-800/30 text-xs text-rose-200 mb-4 flex items-center gap-2">
                                            <i data-lucide="user-x" class="w-4 h-4 text-rose-400 shrink-0"></i>
                                            <span>Saat ini dipakai oleh <strong class="text-white"><?= htmlspecialchars($occupant_name) ?></strong></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Form / Button -->
                                <div>
                                    <?php if ($is_my_chair): ?>
                                        <div class="w-full py-2.5 px-4 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-300 text-xs font-bold text-center flex items-center justify-center gap-2">
                                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i> Stasiun Bertugas Anda Saat Ini
                                        </div>
                                    <?php elseif ($is_occupied): ?>
                                        <button type="button" disabled class="w-full py-2.5 px-4 rounded-xl bg-zinc-800/80 text-zinc-500 text-xs font-bold cursor-not-allowed border border-zinc-700/50">
                                            Kursi Sedang Digunakan
                                        </button>
                                    <?php else: ?>
                                        <form action="barber.php?page=kursi" method="POST">
                                            <input type="hidden" name="action" value="select_kursi">
                                            <input type="hidden" name="kursi" value="<?= $k_name ?>">
                                            <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-amber-950 font-bold text-xs transition-all shadow-lg shadow-amber-600/30 flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                                                <i data-lucide="check" class="w-4 h-4"></i> Pilih <?= $k_name ?> Sekarang
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Info & Guidelines Card -->
                    <div class="p-5 rounded-2xl bg-[#18120b] border border-white/10 shadow-lg text-xs text-zinc-300 space-y-2">
                        <div class="flex items-center gap-2 text-amber-400 font-bold text-sm mb-1">
                            <i data-lucide="info" class="w-4 h-4"></i> Petunjuk Stasiun Kerja & Tiket Antrean
                        </div>
                        <p>• Pemilihan kursi bertugas bersifat harian (berlaku 24 jam s/d pergantian hari).</p>
                        <p>• Kode tiket pelanggan dibuat berdasarkan huruf depan kursi: <strong>A-xxx</strong> untuk Kursi A, <strong>B-xxx</strong> untuk Kursi B, dan <strong>C-xxx</strong> untuk Kursi C.</p>
                        <p>• Jika Anda ingin berpindah stasiun kerja, Anda dapat memilih kursi lain yang tersedia kapan saja dari halaman ini.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Cetak Struk -->
    <div id="receiptModal" class="receipt-modal">
        <div class="receipt-card" id="printable-receipt">
            <span class="r-title">ELITE BARBERSHOP</span>
            <span class="r-subtitle">Jl. Nawawi Gelar Dalom, Sumberjo, Rajabasa Jaya, Bandarlampung </span>
            <span class="r-subtitle">Telp: 0857-8894-2309</span>
            <hr>
            <p><span>No. Tiket</span> <span id="r_tiket"></span></p>
            <p><span>Pelanggan</span> <span id="r_nama"></span></p>
            <p><span>Layanan</span> <span id="r_layanan"></span></p>
            <hr>
            <p><span>TOTAL</span> <span>Rp <span id="r_total"></span></span></p>
            <p><span>PEMBAYARAN</span> <span id="r_metode"></span></p>
            <p><span>STATUS</span> <span>LUNAS</span></p>
            <hr>
            <span class="r-subtitle" style="margin-top:10px;">Terima kasih atas kunjungan Anda!</span>
            <span class="r-subtitle">IG: @</span>
            
            <button onclick="window.print()" class="no-print bg-adminlte-primary text-white w-full py-2 mt-4 rounded-md text-sm hover:bg-blue-600 transition-colors">🖨️ Cetak</button>
            <button onclick="closeStruk()" class="no-print bg-zinc-600 text-white w-full py-2 mt-2 rounded-md text-sm hover:bg-zinc-700 transition-colors">Tutup</button>
            <form id="form_confirm_paid" method="POST" style="display:none;" class="no-print mt-2">
                <input type="hidden" name="action" value="confirm_paid">
                <input type="hidden" name="antrian_id" id="r_antrian_id" value="">
                <input type="hidden" name="total_harga" id="r_total_input" value="">
                <button type="submit" id="btn_confirm_paid" class="bg-adminlte-success text-white w-full py-2 rounded-md text-sm hover:bg-green-700 transition-colors">Konfirmasi Selesai</button>
            </form>
        </div>
    </div>


<script>
        lucide.createIcons();

        document.addEventListener("DOMContentLoaded", function() {
            // Barber Performance Line Chart
            if (document.getElementById('barberChart1')) {
                const labels = <?php echo json_encode($barberLabels); ?>;
                const dataVals = <?php echo json_encode($barberDataVals); ?>;
                const peakIndex = <?php echo $barberPeakIndex; ?>;
                const averageVal = <?php echo $barberAverage; ?>;

                const pointColors = dataVals.map((_, i) => i === peakIndex ? '#ffffff' : '#c9a03a');
                const pointRadii = dataVals.map((_, i) => i === peakIndex ? 7 : 4);

                const peakCalloutPlugin = {
                    id: 'peakCalloutBarber',
                    afterDraw: (chart) => {
                        const ctx = chart.ctx;
                        const meta = chart.getDatasetMeta(0);
                        if (!meta || !meta.data || peakIndex < 0 || !meta.data[peakIndex]) return;
                        const point = meta.data[peakIndex];
                        const x = point.x;
                        const y = point.y;

                        ctx.save();
                        let boxX = x + 20;
                        let lineEndX = x + 15;
                        if (boxX + 185 > chart.width) {
                            boxX = x - 195;
                            lineEndX = x - 15;
                        }

                        ctx.beginPath();
                        ctx.moveTo(x, y);
                        ctx.lineTo(lineEndX, y - 15);
                        ctx.lineTo(boxX + (boxX < x ? 185 : 0), y - 15);
                        ctx.strokeStyle = '#c9a03a';
                        ctx.lineWidth = 2;
                        ctx.stroke();

                        ctx.fillStyle = '#18120a';
                        ctx.strokeStyle = '#c9a03a';
                        ctx.lineWidth = 2;
                        ctx.beginPath();
                        ctx.roundRect(boxX, y - 32, 185, 26, 6);
                        ctx.fill();
                        ctx.stroke();

                        ctx.fillStyle = '#e8d5a3';
                        ctx.font = 'bold 12px sans-serif';
                        ctx.fillText(`Puncak: ${dataVals[peakIndex]} orang (${labels[peakIndex]})`, boxX + 8, y - 14);
                        ctx.restore();
                    }
                };

                window.barberChart1 = new Chart(document.getElementById('barberChart1'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Pelanggan Dilayani',
                                data: dataVals,
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.18)',
                                fill: true,
                                tension: 0.3,
                                pointBackgroundColor: pointColors,
                                pointBorderColor: '#18120b',
                                pointBorderWidth: 2,
                                pointRadius: pointRadii,
                                borderWidth: 2
                            },
                            {
                                label: `Rata-rata (${averageVal})`,
                                data: Array(dataVals.length).fill(averageVal),
                                borderColor: '#d97706',
                                borderDash: [5, 5],
                                borderWidth: 2,
                                pointRadius: 0,
                                fill: false,
                                tension: 0
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { labels: { color: '#d4d4d8', usePointStyle: true, boxWidth: 8 } },
                            tooltip: {
                                backgroundColor: 'rgba(24, 18, 11, 0.95)',
                                titleColor: '#fde68a',
                                bodyColor: '#d4d4d8',
                                borderColor: '#f59e0b',
                                borderWidth: 1
                            }
                        },
                        scales: {
                            x: {
                                title: { display: true, text: 'Tanggal', color: '#f59e0b', font: { size: 12, weight: 'bold' } },
                                grid: { color: 'rgba(245, 158, 11, 0.08)' },
                                ticks: { color: '#d4d4d8', font: { size: 11 } }
                            },
                            y: {
                                title: { display: true, text: 'Jumlah Pelanggan', color: '#f59e0b', font: { size: 12, weight: 'bold' } },
                                beginAtZero: true,
                                min: 0,
                                grid: { color: 'rgba(245, 158, 11, 0.08)', borderDash: [4, 4] },
                                ticks: { color: '#d4d4d8', stepSize: 1 }
                            }
                        }
                    },
                    plugins: [peakCalloutPlugin]
                });

                // Auto scroll to right & enable smooth horizontal wheel scroll
                const scrollContainer = document.getElementById('barberChartScrollContainer');
                if (scrollContainer) {
                    scrollContainer.scrollLeft = scrollContainer.scrollWidth;
                    scrollContainer.addEventListener('wheel', (evt) => {
                        if (evt.deltaY !== 0) {
                            evt.preventDefault();
                            scrollContainer.scrollBy({
                                left: evt.deltaY > 0 ? 220 : -220,
                                behavior: 'smooth'
                            });
                        }
                    }, { passive: false });
                }
            }

            // Barber Doughnut Chart
            if (document.getElementById('barberPieChart')) {
                const pieLabelsRaw = <?php echo json_encode($pieLabels); ?>;
                const pieCountsRaw = <?php echo json_encode($pieCounts); ?>;

                let items = pieLabelsRaw.map((lbl, idx) => ({ label: lbl, count: parseInt(pieCountsRaw[idx] || 0) }));
                items.sort((a, b) => b.count - a.count);

                let finalPieLabels = [];
                let finalPieCounts = [];

                if (items.length > 4) {
                    const topItems = items.slice(0, 4);
                    const otherItems = items.slice(4);
                    const otherCount = otherItems.reduce((sum, item) => sum + item.count, 0);

                    finalPieLabels = topItems.map(item => item.label);
                    finalPieCounts = topItems.map(item => item.count);

                    if (otherCount > 0) {
                        finalPieLabels.push('Lainnya');
                        finalPieCounts.push(otherCount);
                    }
                } else {
                    finalPieLabels = items.map(item => item.label);
                    finalPieCounts = items.map(item => item.count);
                }

                const amberPieColors = ['#F59E0B', '#D97706', '#B45309', '#FDE68A', '#78350F'];
                const totalPie = finalPieCounts.reduce((a, b) => a + b, 0);

                window.barberPieChart = new Chart(document.getElementById('barberPieChart'), {
                    type: 'doughnut',
                    data: {
                        labels: finalPieLabels,
                        datasets: [{
                            data: finalPieCounts,
                            backgroundColor: finalPieLabels.map((_, i) => amberPieColors[i % amberPieColors.length]),
                            borderColor: '#18120b',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: '#d4d4d8', boxWidth: 10, usePointStyle: true, font: { size: 11 } }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(24, 18, 11, 0.95)',
                                titleColor: '#fde68a',
                                bodyColor: '#d4d4d8',
                                borderColor: 'rgba(245, 158, 11, 0.4)',
                                borderWidth: 1,
                                padding: 10,
                                callbacks: {
                                    label: function(context) {
                                        const val = context.raw || 0;
                                        const pct = totalPie > 0 ? Math.round((val / totalPie) * 100) : 0;
                                        return ` ${context.label}: ${val} (${pct}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });

        function printStruk(tiket, nama, layanan, total, metode, antrian_id = null, is_new_payment = false) {

            document.getElementById('r_tiket').innerText = tiket;
            document.getElementById('r_nama').innerText = nama;
            document.getElementById('r_layanan').innerText = layanan;
            document.getElementById('r_total').innerText = parseInt(total).toLocaleString('id-ID');
            document.getElementById('r_metode').innerText = metode;
            
            if (is_new_payment && antrian_id) {
                document.getElementById('r_antrian_id').value = antrian_id;
                document.getElementById('r_total_input').value = total;
                document.getElementById('form_confirm_paid').style.display = 'block';
            } else {
                document.getElementById('form_confirm_paid').style.display = 'none';
            }

            document.getElementById('receiptModal').style.display = 'flex';
        }
        
        function closeStruk() {
            document.getElementById('receiptModal').style.display = 'none';
        }

        // Polling payment
        let notifiedTickets = [];
        function checkNewPayments() {
            fetch('../api_check_payment.php')
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success' && res.data) {
                        let q = res.data;
                        if (!notifiedTickets.includes(q.id)) {
                            notifiedTickets.push(q.id);
                            let audio = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
                            audio.play().catch(e => {});
                            printStruk(
                                q.no_antrean, 
                                q.pelanggan_nama || 'Guest', 
                                q.nama_layanan || 'Layanan', 
                                q.final_price, 
                                q.metode_bayar || 'Cash/QRIS',
                                q.id,
                                true
                            );
                        }
                    }
                }).catch(err => console.error(err));
        }
        setInterval(checkNewPayments, 5000);

        // Real-time Clock
        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const clockString = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()} | ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
            const clockEl = document.getElementById('realtime-clock');
            if (clockEl) clockEl.textContent = clockString;
        }
        setInterval(updateClock, 1000); 
        updateClock();

        // Toggle Mobile Sidebar Drawer & Overlay
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar || !overlay) return;
            
            const isClosed = sidebar.classList.contains('-translate-x-full');
            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        // Sidebar Toggle with Smooth State Persistence
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');

        function applySidebarState(isMinimized) {
            if (!sidebar) return;
            if (isMinimized) {
                sidebar.classList.remove('w-64'); 
                sidebar.classList.add('w-20');
            } else {
                sidebar.classList.remove('w-20'); 
                sidebar.classList.add('w-64');
            }
        }

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    toggleMobileSidebar();
                } else {
                    const willMinimize = sidebar.classList.contains('w-64');
                    localStorage.setItem('sidebarMinimized', willMinimize);
                    applySidebarState(willMinimize);
                }
            });
        }

        // Profile Dropdown Toggle
        function toggleProfileDropdown(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const dropdown = document.getElementById('user-profile-dropdown-menu');
            const chevron = document.getElementById('profile-dropdown-chevron');
            if (dropdown) {
                const isHidden = dropdown.classList.contains('hidden');
                dropdown.classList.toggle('hidden');
                if (chevron) {
                    chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }
        }

        function closeProfileDropdown() {
            const dropdown = document.getElementById('user-profile-dropdown-menu');
            const chevron = document.getElementById('profile-dropdown-chevron');
            if (dropdown && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            }
        }

        document.addEventListener('click', function(e) {
            const profileContainer = document.getElementById('user-profile-dropdown-container');
            if (profileContainer && !profileContainer.contains(e.target)) {
                closeProfileDropdown();
            }
        });
    </script>

    <!-- Modal Pilih Kursi Tugas Harian -->
    <div id="selectKursiModal" class="fixed inset-0 z-40 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 pb-20 md:pb-4 transition-all duration-300 <?= ($current_page === 'dashboard' && !$has_selected_chair_today) ? '' : 'hidden' ?>">
        <div class="bg-gradient-to-b from-[#1c140b] to-[#120d07] border-2 border-amber-500/40 rounded-2xl max-w-xl w-full p-5 sm:p-6 shadow-[0_0_50px_rgba(245,158,11,0.25)] text-white relative max-h-[85vh] overflow-y-auto">
            
            <button type="button" onclick="closeSelectKursiModal()" class="absolute top-4 right-4 text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors" title="Tutup Modal">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="text-center mb-6">
                <div class="w-14 h-14 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 text-3xl mx-auto mb-3 shadow-inner">
                    💈
                </div>
                <h3 class="text-xl sm:text-2xl font-bold text-amber-200 tracking-tight">Pilih Kursi Tugas Hari Ini</h3>
                <p class="text-xs text-zinc-400 mt-1">Silakan tentukan kursi layanan Anda untuk hari ini (<strong><?= date('d F Y') ?></strong>). Pilihan ini berlaku selama 1 hari.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <?php
                $chairs_options = [
                    'Kursi A' => ['letter' => 'A', 'desc' => 'Stasiun 1 (Kiri)', 'color' => 'amber'],
                    'Kursi B' => ['letter' => 'B', 'desc' => 'Stasiun 2 (Tengah)', 'color' => 'blue'],
                    'Kursi C' => ['letter' => 'C', 'desc' => 'Stasiun 3 (Kanan)', 'color' => 'emerald'],
                ];
                foreach ($chairs_options as $k_name => $k_info):
                    $is_current = ($barber && $barber['kursi'] === $k_name && $has_selected_chair_today);
                    $occupied_by = $occupied_chairs[$k_name] ?? null;
                ?>
                    <div class="relative rounded-xl border-2 p-4 transition-all duration-300 flex flex-col justify-between text-center select-none
                        <?= $is_current 
                            ? 'border-emerald-500 bg-emerald-950/40 text-emerald-300 shadow-[0_0_15px_rgba(16,185,129,0.3)]' 
                            : ($occupied_by 
                                ? 'border-zinc-800 bg-zinc-900/60 opacity-60 cursor-not-allowed' 
                                : 'border-amber-500/40 bg-black/40 hover:border-amber-400 hover:bg-amber-950/40 hover:-translate-y-1 cursor-pointer') ?>">

                        <div class="mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 block mb-1"><?= $k_info['desc'] ?></span>
                            <h4 class="text-xl font-black text-white"><?= $k_name ?></h4>
                        </div>

                        <?php if ($is_current): ?>
                            <div class="my-2">
                                <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 font-bold text-[11px] border border-emerald-500/40 inline-block">
                                    ✓ Kursi Anda
                                </span>
                            </div>
                            <button type="button" disabled class="w-full mt-2 py-2 px-3 rounded-lg bg-emerald-600/40 text-emerald-300 font-bold text-xs cursor-default">
                                Aktif Hari Ini
                            </button>
                        <?php elseif ($occupied_by): ?>
                            <div class="my-2">
                                <span class="px-2.5 py-1 rounded-full bg-red-500/20 text-red-400 font-semibold text-[11px] border border-red-500/30 inline-block truncate max-w-full" title="Diisi oleh <?= htmlspecialchars($occupied_by) ?>">
                                    🔒 <?= htmlspecialchars($occupied_by) ?>
                                </span>
                            </div>
                            <button type="button" disabled class="w-full mt-2 py-2 px-3 rounded-lg bg-zinc-800 text-zinc-500 font-semibold text-xs cursor-not-allowed">
                                Terisi
                            </button>
                        <?php else: ?>
                            <div class="my-2">
                                <span class="px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-300 font-semibold text-[11px] border border-amber-500/30 inline-block">
                                    Tersedia
                                </span>
                            </div>
                            <form method="POST" action="barber.php">
                                <input type="hidden" name="action" value="select_kursi">
                                <input type="hidden" name="kursi" value="<?= $k_name ?>">
                                <button type="submit" class="w-full mt-2 py-2 px-3 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs transition-colors shadow-md">
                                    Pilih <?= $k_name ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center text-xs text-zinc-400 pt-3 border-t border-white/10 flex items-center justify-between">
                <span>* Pilihan berlaku selama 24 jam (1 hari).</span>
                <?php if ($has_selected_chair_today): ?>
                    <button type="button" onclick="closeSelectKursiModal()" class="text-zinc-400 hover:text-white underline">Tutup Window</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
        $b_has_custom_pic = false;
        $b_profile_pic_url = '';
        if (isset($user_id)) {
            $b_profile_files = glob(__DIR__ . '/../asset/image/profile_' . $user_id . '.*');
            if (!empty($b_profile_files)) {
                $b_has_custom_pic = true;
                $b_profile_pic_url = '../asset/image/' . basename($b_profile_files[0]) . '?v=' . filemtime($b_profile_files[0]);
            }
        }
    ?>
    <!-- Mobile Fixed Bottom Navigation Bar — Premium Modern Dark Gold Theme -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-[#0e0a08]/95 backdrop-blur-md border-t border-amber-500/20 flex justify-around items-center shadow-[0_-4px_25px_rgba(0,0,0,0.8)] transform-gpu"
         style="padding-bottom: env(safe-area-inset-bottom, 8px); padding-top: 8px;">

        <!-- Panel -->
        <a href="?page=dashboard" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= ($current_page === 'dashboard' || empty($current_page)) ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active Scissors) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M6 2a4 4 0 1 0 2.828 6.828l3.172 3.172-3.172 3.172A4 4 0 1 0 6 22a4 4 0 0 0 2.828-6.828L12 12l5.5-5.5a1 1 0 0 1 1.414 0l1.586 1.586a1 1 0 0 0 1.414-1.414L20.5 5.25a1 1 0 0 0-1.414 0L14 10.343l-2.828-2.828A4 4 0 0 0 6 2zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zm0 14a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/>
            </svg>
            <!-- Outline (Inactive Scissors) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="outline-icon w-6 h-6">
                <circle cx="6" cy="6" r="3"></circle>
                <circle cx="6" cy="18" r="3"></circle>
                <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
                <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
            </svg>
            <?php if ($total_waiting > 0): ?>
                <span class="absolute top-0 right-1 bg-gradient-to-r from-amber-500 to-amber-600 text-amber-950 text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center shadow-lg border border-amber-300/40 animate-pulse">
                    <?= $total_waiting ?>
                </span>
            <?php endif; ?>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Panel</span>
        </a>

        <!-- Statistik -->
        <a href="?page=charts" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= $current_page === 'charts' ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active Charts) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c-1.036 0-1.875.84-1.875 1.875v9.375c0 1.036.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V10.5c0-1.036-.84-1.875-1.875-1.875h-.75zM3 15c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v3c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 18v-3z"/>
            </svg>
            <!-- Outline (Inactive Charts) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Statistik</span>
        </a>

        <!-- Kursi -->
        <a href="javascript:void(0)" id="btn-nav-kursi" onclick="switchBarberTab('tab-kursi', 'kursi', this)" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= ($current_page === 'kursi' || (!$has_selected_chair_today && ($current_page === 'dashboard' || empty($current_page)))) ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active Barber Chair) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path d="M7 4a2 2 0 0 0-2 2v3h14V6a2 2 0 0 0-2-2H7z"/>
                <path d="M3 11a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5z"/>
                <path d="M6 18v2a1 1 0 1 0 2 0v-2H6zm10 0v2a1 1 0 1 0 2 0v-2h-2z"/>
            </svg>
            <!-- Outline (Inactive Barber Chair) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="outline-icon w-6 h-6">
                <path d="M19 9V6a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v3"></path>
                <path d="M3 16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v5z"></path>
                <path d="M5 18v2"></path>
                <path d="M19 18v2"></path>
            </svg>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">
                <span>Kursi</span>
                <?php if ($has_selected_chair_today && $barber): ?>
                    <span class="font-extrabold text-amber-400 text-[9px] uppercase">(<?= str_replace('Kursi ', '', $barber['kursi']) ?>)</span>
                <?php endif; ?>
            </span>
        </a>

        <!-- Profil -->
        <a href="javascript:void(0)" onclick="switchBarberTab('tab-profil', 'profil', this)" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= in_array($current_page, ['profil', 'profile']) ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <?php if ($b_has_custom_pic): ?>
                <img src="<?= $b_profile_pic_url ?>" alt="Foto Profil" class="profile-img w-6 h-6 rounded-full object-cover border-2 transition-all">
            <?php else: ?>
                <!-- Solid Profile (Active) -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm-7 18a7 7 0 0 1 14 0 1 1 0 0 1-1 1H6a1 1 0 0 1-1-1z" />
                </svg>
                <!-- Outline Profile (Inactive) -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="outline-icon w-6 h-6">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            <?php endif; ?>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Profil</span>
        </a>
    </nav>

    <script>
        function switchBarberTab(targetTabId, pageName, navElement) {
            // Always close select kursi modal overlay when switching tabs
            closeSelectKursiModal();

            const currentTab = document.querySelector('.tab-content.active');
            const targetTab = document.getElementById(targetTabId);

            if (currentTab && currentTab.id === targetTabId) return;

            // Scroll main area to top
            const mainArea = document.querySelector('main');
            if (mainArea) mainArea.scrollTop = 0;

            if (currentTab) currentTab.classList.remove('active');
            if (targetTab) targetTab.classList.add('active');

            // Update browser URL query string without reloading page
            if (pageName && history.pushState) {
                const newUrl = window.location.pathname + '?page=' + pageName;
                history.pushState({ page: pageName }, '', newUrl);
            }

            // Update active states for bottom nav and sidebar
            updateBarberNavState(targetTabId, pageName);

            // Auto resize & scroll chart canvas when tab-charts is opened
            if (targetTabId === 'tab-charts') {
                setTimeout(function() {
                    if (window.barberChart1) {
                        window.barberChart1.resize();
                        window.barberChart1.update();
                    }
                    if (window.barberPieChart) {
                        window.barberPieChart.resize();
                        window.barberPieChart.update();
                    }
                    const scrollContainer = document.getElementById('barberChartScrollContainer');
                    if (scrollContainer) scrollContainer.scrollLeft = scrollContainer.scrollWidth;
                }, 50);
            }
        }

        function updateBarberNavState(targetTabId, pageName) {
            // Update bottom nav items
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            
            if (targetTabId === 'tab-dashboard') {
                const nav = document.querySelector('.nav-item[onclick*="tab-dashboard"]');
                if (nav) nav.classList.add('active');
            } else if (targetTabId === 'tab-charts') {
                const nav = document.querySelector('.nav-item[onclick*="tab-charts"]');
                if (nav) nav.classList.add('active');
            } else if (targetTabId === 'tab-kursi') {
                const nav = document.getElementById('btn-nav-kursi');
                if (nav) nav.classList.add('active');
            } else if (targetTabId === 'tab-profil') {
                const nav = document.querySelector('.nav-item[onclick*="tab-profil"]');
                if (nav) nav.classList.add('active');
            }

            // Update sidebar items active state
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.classList.remove('bg-adminlte-primary', 'text-amber-200');
                item.classList.add('text-stone-400');
            });
            const sidebarNav = document.querySelector('.sidebar-item[onclick*="' + targetTabId + '"]');
            if (sidebarNav) {
                sidebarNav.classList.remove('text-stone-400');
                sidebarNav.classList.add('bg-adminlte-primary', 'text-amber-200');
            }

            // Update header title
            const headerTitle = document.querySelector('header h1');
            if (headerTitle) {
                if (pageName === 'dashboard') headerTitle.textContent = 'Panel Kerja Barber';
                else if (pageName === 'charts') headerTitle.textContent = 'Statistik & Analisis Performa';
                else if (pageName === 'kursi') headerTitle.textContent = 'Stasiun & Manajemen Kursi';
                else if (pageName === 'profil') headerTitle.textContent = 'Profil Barber Saya';
            }
        }

        window.addEventListener('popstate', function(event) {
            const params = new URLSearchParams(window.location.search);
            const page = params.get('page') || 'dashboard';
            const tabId = page === 'charts' ? 'tab-charts' : (page === 'kursi' ? 'tab-kursi' : (page === 'profil' ? 'tab-profil' : 'tab-dashboard'));
            
            const currentTab = document.querySelector('.tab-content.active');
            const targetTab = document.getElementById(tabId);
            if (currentTab) currentTab.classList.remove('active');
            if (targetTab) targetTab.classList.add('active');
            updateBarberNavState(tabId, page);
        });

    function openSelectKursiModal() {
        const modal = document.getElementById('selectKursiModal');
        const kursiBtn = document.getElementById('btn-nav-kursi');
        if (modal) modal.classList.remove('hidden');
        if (kursiBtn) {
            kursiBtn.classList.add('active');
        }
    }
    function closeSelectKursiModal() {
        const modal = document.getElementById('selectKursiModal');
        const kursiBtn = document.getElementById('btn-nav-kursi');
        if (modal) modal.classList.add('hidden');
    }
    document.addEventListener("DOMContentLoaded", function() {
        if (window.lucide) lucide.createIcons();
    });
    </script>
</body>
</html>



