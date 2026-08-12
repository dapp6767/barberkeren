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
        #sidebar.w-20 nav span, #sidebar.w-20 nav p { opacity: 0; max-width: 0; padding: 0; margin: 0; border: none; }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-transition { animation: fadeSlideUp 0.4s ease-out forwards; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #0e0a08; }
        ::-webkit-scrollbar-thumb { background: #3d2b1a; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #c9a03a; }
    </style>
</head>
<body class="text-amber-50 font-sans antialiased overflow-x-hidden flex h-screen">
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: linear-gradient(135deg, #0e0a08 0%, #120e06 30%, #1a0e04 60%, #0a0603 100%);"></div>
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: radial-gradient(ellipse 80% 60% at 70% 20%, rgba(90,50,15,0.15) 0%, transparent 60%), radial-gradient(ellipse 60% 40% at 20% 80%, rgba(60,30,5,0.1) 0%, transparent 50%);"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-adminlte-sidebar h-full flex flex-col shadow-xl flex-shrink-0 transition-all duration-300">
        <script>
            if(localStorage.getItem('sidebarMinimized') === 'true') {
                document.getElementById('sidebar').classList.replace('w-64', 'w-20');
            }
        </script>
        <!-- Brand Logo -->
        <div id="brand-logo-container" class="h-16 flex items-center px-6 overflow-hidden" style="border-bottom: 1px solid #3a2510;">
            <span id="brand-icon" class="text-2xl mr-3 shrink-0">💈</span>
            <span id="brand-text" class="text-xl font-bold tracking-tight whitespace-nowrap" style="color:#e8d5a3;">Dashboard <span class="font-normal" style="color:#8a6030;">Barber</span></span>
        </div>
        
        <!-- Sidebar Menu -->
        <div class="flex-1 overflow-y-auto py-4">
            <nav class="flex flex-col gap-1 px-3">
                <a href="?page=dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= ($current_page === 'dashboard' || !isset($_GET['page']) || empty($current_page)) ? 'bg-adminlte-primary text-amber-200 mt-4' : 'text-stone-400 hover:text-amber-200 mt-4' ?>">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span>Panel Kerja</span>
                </a>
                <a href="?page=charts" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $current_page === 'charts' ? 'bg-adminlte-primary text-white mt-1' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white mt-1' ?>">
                    <i data-lucide="pie-chart" class="w-5 h-5"></i>
                    <span>Statistik (Charts)</span>
                </a>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <p class="px-3 text-xs font-semibold uppercase tracking-wider mb-2 mt-4" style="color:#5c3d1a;">Sistem</p>
                <a href="admin.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-zinc-400 hover:bg-zinc-800 hover:text-white">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                    <span>Panel Admin</span>
                </a>
                <?php endif; ?>

                <p class="px-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2 mt-4">Lainnya</p>
                <a href="?page=profil" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= (in_array($current_page, ['profil', 'profile'])) ? 'bg-adminlte-primary text-white' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?>">
                    <i data-lucide="user" class="w-5 h-5"></i>
                    <span>Profil</span>
                </a>
                <a href="../auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-400 hover:bg-red-400/10 hover:text-red-300 transition-colors mt-1">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Top Navbar -->
        <header class="h-16 flex items-center justify-between px-6 shadow-lg z-10 shrink-0" style="background: linear-gradient(90deg, #1a1008 0%, #110d06 50%, #1a1008 100%); border-bottom: 1px solid rgba(90,55,15,0.4);">
            <div class="flex items-center gap-4">
                <button id="sidebar-toggle" class="transition-colors hover:text-amber-400" style="color:#8a6030;">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="text-xl font-semibold text-white capitalize">
                    <?= $current_page === 'dashboard' ? 'Panel Kerja Barber' : ($current_page === 'charts' ? 'Statistik & Analisis Performa' : ($current_page === 'profil' ? 'Profil Barber Saya' : str_replace('_', ' ', $current_page))) ?>
                </h1>
                
                <a href="../index.php" class="hidden sm:flex items-center gap-1.5 text-zinc-400 hover:text-blue-400 transition-colors duration-300 text-sm font-medium ml-4 group" title="Ke Home">
                    <i data-lucide="home" class="w-4 h-4 group-hover:scale-110 transition-transform duration-300"></i>
                    <span class="group-hover:underline underline-offset-4">Home</span>
                </a>
            </div>
            <div class="flex items-center gap-4">
                <div id="realtime-clock" class="hidden md:block text-sm text-zinc-300 font-medium tracking-wide"></div>
                <a href="javascript:void(0)" onclick="navigateToTab('tab-profil')" class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity" title="Profil Saya">
                    <?php 
                    $nav_avatar_name = !empty($user_data['fullname']) ? urlencode($user_data['fullname']) : urlencode($_SESSION['username']);
                    $nav_profile_files = glob(__DIR__ . '/../asset/image/profile_' . $_SESSION['user_id'] . '.*');
                    $nav_profile_url = !empty($nav_profile_files) ? '../asset/image/' . basename($nav_profile_files[0]) : "https://ui-avatars.com/api/?name={$nav_avatar_name}&background=random&color=fff&size=64&bold=true";
                    ?>
                    <img src="<?= $nav_profile_url ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover shadow-md border-2 border-zinc-700/50">
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6 page-transition">
            <?php if (function_exists('display_flash')) display_flash(); ?>

            <?php if ($current_page === 'charts'): ?>
                <!-- DEDICATED CHARTS VIEW -->
                <!-- Summary Stat Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="p-5 rounded-xl border shadow-md flex items-center justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <p class="text-xs font-semibold text-amber-200/80 uppercase tracking-wider">Pelanggan Bulan Ini</p>
                            <h3 class="text-3xl font-extrabold text-white mt-1"><?= number_format($barberCountMonth) ?> <span class="text-sm font-normal text-amber-400">Orang</span></h3>
                        </div>
                        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400">
                            <i data-lucide="users" class="w-6 h-6"></i>
                        </div>
                    </div>

                    <div class="p-5 rounded-xl border shadow-md flex items-center justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <p class="text-xs font-semibold text-emerald-200/80 uppercase tracking-wider">Nilai Layanan Bulan Ini</p>
                            <h3 class="text-2xl font-extrabold text-emerald-400 mt-1">Rp <?= number_format($barberOmsetMonth, 0, ',', '.') ?></h3>
                        </div>
                        <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
                            <i data-lucide="banknote" class="w-6 h-6"></i>
                        </div>
                    </div>

                    <div class="p-5 rounded-xl border shadow-md flex items-center justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <p class="text-xs font-semibold text-sky-200/80 uppercase tracking-wider">Layanan Terlaris</p>
                            <h3 class="text-xl font-bold text-sky-300 mt-1 truncate max-w-[140px]"><?= htmlspecialchars($topServiceName) ?></h3>
                        </div>
                        <div class="p-3.5 rounded-xl bg-sky-500/10 border border-sky-500/30 text-sky-400">
                            <i data-lucide="scissors" class="w-6 h-6"></i>
                        </div>
                    </div>

                    <div class="p-5 rounded-xl border shadow-md flex items-center justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <p class="text-xs font-semibold text-amber-200/80 uppercase tracking-wider">Rating & Kepuasan</p>
                            <h3 class="text-2xl font-extrabold text-amber-300 mt-1 flex items-center gap-1.5">
                                ⭐ <?= number_format($barberRating, 1) ?> 
                                <span class="text-xs font-normal text-stone-400">/ 5.0 (<?= $barberTotalUlasan ?> Ulasan)</span>
                            </h3>
                        </div>
                        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400">
                            <i data-lucide="star" class="w-6 h-6"></i>
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

            <?php endif; ?>
            
            <?php if (in_array($current_page, ['profil', 'profile'])): ?>
            <!-- PROFIL MODULE (IDENTICAL TO ADMIN & PELANGGAN) -->
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

            <?php endif; ?>
            
            <?php if ($current_page === 'dashboard' || empty($current_page)): ?>
                <!-- DEFAULT DASHBOARD WORK PANEL -->
                <!-- Dashboard Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-adminlte-info rounded-lg p-6 relative overflow-hidden text-white shadow-lg">
                        <div class="relative z-10">
                            <h3 class="text-4xl font-bold mb-1"><?= count($queues) ?></h3>
                            <p class="text-blue-50 font-medium">Total Antrean Hari Ini</p>
                        </div>
                        <i data-lucide="list" class="absolute -right-4 -bottom-4 w-32 h-32 text-black/10 z-0"></i>
                    </div>
                    <div class="bg-adminlte-warning rounded-lg p-6 relative overflow-hidden text-zinc-900 shadow-lg">
                        <div class="relative z-10">
                            <h3 class="text-4xl font-bold mb-1"><?= $total_waiting ?></h3>
                            <p class="text-yellow-900 font-medium">Antrean Menunggu</p>
                        </div>
                        <i data-lucide="clock" class="absolute -right-4 -bottom-4 w-32 h-32 text-black/10 z-0"></i>
                    </div>
                    <div class="bg-adminlte-success rounded-lg p-6 relative overflow-hidden text-white shadow-lg">
                        <div class="relative z-10">
                            <h3 class="text-4xl font-bold mb-1"><?= $total_served ?></h3>
                            <p class="text-green-100 font-medium">Pelanggan Selesai</p>
                        </div>
                        <i data-lucide="check-circle" class="absolute -right-4 -bottom-4 w-32 h-32 text-black/10 z-0"></i>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="bg-adminlte-card rounded-lg border border-zinc-700 shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-700 bg-[#30363d] flex justify-between items-center">
                        <h3 class="font-semibold text-white">Daftar Antrean Tugas Anda</h3>
                    </div>
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
            <?php endif; ?>
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

                new Chart(document.getElementById('barberChart1'), {
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

                new Chart(document.getElementById('barberPieChart'), {
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

        // Sidebar Toggle with Smooth State Persistence
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');

        function applySidebarState(isMinimized) {
            if (isMinimized) {
                sidebar.classList.remove('w-64'); 
                sidebar.classList.add('w-20');
            } else {
                sidebar.classList.remove('w-20'); 
                sidebar.classList.add('w-64');
            }
        }

        if (sidebarToggle && sidebar) {
            // Load state without transition glitch
            const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
            
            // Toggle on click
            sidebarToggle.addEventListener('click', () => {
                const willMinimize = sidebar.classList.contains('w-64');
                localStorage.setItem('sidebarMinimized', willMinimize);
                applySidebarState(willMinimize);
            });
        }</script>
</body>
</html>



