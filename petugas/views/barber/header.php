<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Barber - Elite Barber</title>
    <!-- Google Fonts: Playfair Display (Serif) & Plus Jakarta Sans (Sans-Serif) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        adminlte: {
                            sidebar: '#0d0805',
                            bg: '#080503',
                            card: '#160e08',
                            primary: '#4a321a',
                            success: '#1e3a1e',
                            warning: '#e5c158',
                            danger: '#4a1e1e',
                            info: '#1e2a3a',
                            accent: '#d4af37',
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

        /* Sidebar collapse — identik pelanggan */
        #sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; }
        #sidebar.w-20 #brand-text { opacity: 0; width: 0; overflow: hidden; }
        #sidebar.w-20 nav a span,
        #sidebar.w-20 nav p,
        #sidebar.w-20 .sidebar-footer a span {
            opacity: 0; max-width: 0; overflow: hidden; white-space: nowrap;
        }
        #sidebar.w-20 nav a { justify-content: center; padding-left: 0; padding-right: 0; }

        /* Mobile sidebar — slide in from left */
        @media (max-width: 767px) {
            #sidebar { position: fixed; left: -300px; top: 0; height: 100vh; z-index: 50; transition: left 0.3s ease; }
            #sidebar.open-mobile { left: 0; }
        }
        #sidebar-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 49; }

        .nav-item {
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;
            color: #9ca3af; text-decoration: none; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; padding: 6px 12px; border-radius: 12px;
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
        .nav-item .solid-icon { display: none; color: #f59e0b; filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.8)); }
        .nav-item .outline-icon { display: block; color: #9ca3af; transition: color 0.2s ease, transform 0.2s ease; }
        .nav-item:hover .outline-icon { color: #fcd34d; transform: translateY(-1px); }
        .nav-item.active { color: #f59e0b; }
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
        
        .nav-item .profile-img { border-color: rgba(255, 255, 255, 0.15); transition: all 0.25s ease; }
        .nav-item.active .profile-img { border-color: #f59e0b; box-shadow: 0 0 12px rgba(245, 158, 11, 0.7); }

        @keyframes iconPop {
            0% { transform: scale(0.8); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }

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
<body class="text-amber-50 bg-adminlte-bg font-sans antialiased overflow-x-hidden flex h-screen">
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: linear-gradient(135deg, #0e0a08 0%, #120e06 30%, #1a0e04 60%, #0a0603 100%);"></div>

    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="w-72 bg-adminlte-sidebar h-full flex flex-col shadow-xl flex-shrink-0 transition-all duration-300">
        <div id="brand-logo-container" class="h-16 md:h-18 flex items-center px-5 overflow-hidden" style="border-bottom: 1px solid #3a2510;">
            <span id="brand-icon" class="text-2xl mr-3 shrink-0">💈</span>
            <div id="brand-text" class="flex flex-col overflow-hidden">
                <span class="font-bold text-base tracking-wider whitespace-nowrap" style="color:#e8d5a3;">Dashboard <span style="color:#8a6030;font-weight:400;">Barber</span></span>
            </div>
        </div>

        <!-- Navigation Menu Links -->
        <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
            <p class="px-3 text-[10px] font-bold text-amber-700/80 uppercase tracking-widest mb-2">MODUL kerja</p>
            
            <a href="barber.php?page=dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-zinc-300 transition-colors <?= ($current_page === 'dashboard' || empty($current_page)) ? 'bg-adminlte-primary' : '' ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5 text-amber-400 shrink-0"></i>
                <span class="font-medium">Workstation Barber</span>
            </a>
            
            <a href="barber.php?page=kursi" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-zinc-300 transition-colors <?= $current_page === 'kursi' ? 'bg-adminlte-primary' : '' ?>">
                <i data-lucide="armchair" class="w-5 h-5 text-amber-400 shrink-0"></i>
                <span class="font-medium">Stasiun Kursi</span>
            </a>

            <a href="barber.php?page=profil" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-zinc-300 transition-colors <?= $current_page === 'profil' ? 'bg-adminlte-primary' : '' ?>">
                <i data-lucide="user-cog" class="w-5 h-5 text-amber-400 shrink-0"></i>
                <span class="font-medium">Profil & Keamanan</span>
            </a>
        </nav>

        <!-- Sidebar Footer / Bottom Home Button -->
        <div class="sidebar-footer p-3.5 border-t border-amber-900/30 bg-zinc-950/40">
            <a href="../index.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-stone-300 hover:text-amber-200 hover:bg-amber-500/10 transition-colors text-[15px] sm:text-base font-semibold">
                <i data-lucide="home" class="w-5 h-5 text-amber-400/80 shrink-0"></i>
                <span id="sidebar-home-label">Home</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Top Navigation Bar -->
        <header class="h-16 md:h-18 flex items-center justify-between px-4 sm:px-6 shadow-lg z-10 shrink-0" style="background: linear-gradient(90deg, #1a1008 0%, #110d06 50%, #1a1008 100%); border-bottom: 1px solid rgba(90,55,15,0.4);">
            <div class="flex items-center gap-3 sm:gap-4">
                <button id="sidebar-toggle" class="p-2 rounded-xl text-amber-500/80 hover:text-amber-300 hover:bg-amber-500/10 transition-colors cursor-pointer" title="Menu Sidebar">
                    <i data-lucide="menu" class="w-6 h-6 sm:w-7 sm:h-7"></i>
                </button>
                <h1 class="text-xl sm:text-2xl font-bold text-white capitalize tracking-tight flex items-center gap-2">
                    Panel Kerja Barber
                </h1>
            </div>
            <div class="flex items-center gap-3 sm:gap-4">
                <div id="realtime-clock" class="hidden md:block text-sm md:text-base text-zinc-300 font-semibold tracking-wide"></div>
                <div class="relative" id="user-profile-dropdown-container">
                    <button type="button" onclick="toggleProfileDropdown(event)" class="flex items-center gap-2.5 sm:gap-3 cursor-pointer hover:opacity-90 transition-all p-1 sm:p-1.5 rounded-xl hover:bg-amber-500/10 focus:outline-none border border-transparent hover:border-amber-500/20 group" id="user-profile-dropdown-btn">
                        <?php
                        $b_photo_url = get_user_avatar_url($user_id, $user_data['fullname'] ?? 'Barber', '../');
                        ?>
                        <img src="<?= $b_photo_url ?>" alt="Avatar" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full object-cover shadow-md border-2 border-amber-700/60 transition-transform group-hover:scale-105">
                        <span class="hidden md:block text-sm sm:text-base text-zinc-200 font-semibold max-w-[150px] truncate"><?= htmlspecialchars($user_data['fullname'] ?? $user_data['username'] ?? 'Barber') ?></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-amber-400 transition-transform duration-200" id="profile-dropdown-chevron"></i>
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <div id="user-profile-dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-[#161009] border border-amber-900/60 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-xl">
                        <div class="py-1 bg-rose-950/10">
                            <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-xs sm:text-sm font-bold text-rose-400 hover:bg-rose-500/20 hover:text-rose-300 transition-colors">
                                <i data-lucide="log-out" class="w-4 h-4 text-rose-400"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Main View Wrapper -->
        <main class="flex-1 overflow-y-auto p-6 relative page-transition">
