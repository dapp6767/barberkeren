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
            @page { margin: 0; }
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

        .mobile-bottom-nav {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 50;
            background: rgba(14, 10, 8, 0.95); backdrop-filter: blur(12px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; justify-content: space-around; align-items: center;
            padding: 8px 0; box-shadow: 0 -4px 20px rgba(0,0,0,0.5);
        }
        .mobile-nav-item {
            display: flex; flex-direction: column; align-items: center; gap: 2px;
            color: #9ca3af; font-size: 10px; font-weight: 500; text-decoration: none;
            transition: all 0.2s ease; padding: 4px 12px; border-radius: 8px;
        }
        .mobile-nav-item.active, .mobile-nav-item:hover { color: #f59e0b; }
        .mobile-nav-item i { font-size: 18px; }
    </style>
</head>
<body class="bg-[#0a0805] text-zinc-100 font-sans min-h-screen flex antialiased selection:bg-amber-900 selection:text-amber-100 pb-16 md:pb-0">

    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="w-64 min-h-screen flex flex-col fixed md:relative z-40">
        <!-- Brand Logo Header -->
        <div id="brand-logo-container" class="h-16 flex items-center px-4">
            <div id="brand-icon" class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-600 via-amber-700 to-amber-900 border border-amber-500/40 flex items-center justify-center text-amber-200 font-bold text-xl shadow-lg shrink-0 mr-3">
                <i data-lucide="scissors" class="w-5 h-5 text-amber-300"></i>
            </div>
            <div id="brand-text" class="flex flex-col">
                <span class="font-bold text-white text-base tracking-wider uppercase font-serif" style="color:#e8d5a3;">ELITE BARBER</span>
                <span class="text-[10px] text-amber-400/80 tracking-widest font-semibold uppercase -mt-0.5">Workstation Panel</span>
            </div>
        </div>

        <!-- Navigation Menu Links -->
        <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
            <p class="px-3 text-[10px] font-bold text-amber-700/80 uppercase tracking-widest mb-2">MODUL kerja</p>
            
            <a href="barber.php?page=dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-zinc-300 transition-colors <?= $current_page === 'dashboard' ? 'bg-adminlte-primary' : '' ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5 text-amber-400 shrink-0"></i>
                <span class="font-medium">Workstation Barber</span>
            </a>
            
            <a href="barber.php?page=profil" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-zinc-300 transition-colors <?= $current_page === 'profil' ? 'bg-adminlte-primary' : '' ?>">
                <i data-lucide="user-cog" class="w-5 h-5 text-amber-400 shrink-0"></i>
                <span class="font-medium">Profil & Keamanan</span>
            </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-3 border-t border-zinc-800/80 bg-zinc-950/40">
            <a href="../auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-colors">
                <i data-lucide="log-out" class="w-5 h-5 shrink-0"></i>
                <span class="font-medium">Keluar System</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navigation Bar -->
        <header class="h-16 bg-[#18120b] border-b border-white/10 flex items-center justify-between px-4 md:px-6 sticky top-0 z-30 shadow-md">
            <div class="flex items-center gap-3">
                <button id="sidebar-toggle" class="text-zinc-400 hover:text-white p-2 rounded-lg hover:bg-white/5 transition-colors">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="hidden sm:flex flex-col">
                    <span class="text-xs text-amber-300 font-semibold" id="realtime-clock">Memuat jam...</span>
                    <span class="text-[10px] text-zinc-400">Shift Kerja Barber Specialist</span>
                </div>
            </div>

            <!-- Right Profile Info -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <?php
                    $user_photo_files = glob(__DIR__ . '/../../asset/image/profile_' . $user_id . '.*');
                    $has_b_photo = !empty($user_photo_files);
                    $b_photo_url = $has_b_photo ? '../asset/image/' . basename($user_photo_files[0]) . '?v=' . filemtime($user_photo_files[0]) : null;
                    ?>
                    <?php if ($has_b_photo): ?>
                        <img src="<?= $b_photo_url ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-amber-500/50">
                    <?php else: ?>
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-600 to-amber-900 border border-amber-500/40 flex items-center justify-center text-amber-100 font-bold text-sm shadow">
                            <?= strtoupper(substr($user_data['fullname'] ?? $user_data['username'] ?? 'B', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="hidden md:flex flex-col">
                        <span class="text-xs font-bold text-white"><?= htmlspecialchars($user_data['fullname'] ?? $user_data['username'] ?? 'Barber') ?></span>
                        <span class="text-[10px] text-amber-400 capitalize">Barber Specialist</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Main View Wrapper -->
        <main class="flex-1 p-4 md:p-6 overflow-y-auto page-transition">
