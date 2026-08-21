<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite Barber - Sistem Antrean Langsung</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        adminlte: {
                            sidebar: '#0e0a08',      // Deep dark brown-black
                            bg: '#0a0805',           // Almost black with brown tint
                            card: '#1a1208',         // Very dark warm card
                            primary: '#3d2b1a',      // Dark rich brown
                            success: '#1e3a1e',
                            warning: '#e8d5a3',
                            danger: '#4a1e1e',
                            info: '#1e2a3a',
                            accent: '#c9a03a',       // Gold accent
                        }
                    }
                }
            }
        };
    </script>
    <!-- FontAwesome 6 & Lucide Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- jQuery & DataTables CDN -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- HTML5 QR Code Scanner & SweetAlert2 CDN -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* DataTables Custom Dark Amber Theme for Barber Keren */
        .dataTables_wrapper {
            color: #d4c4a0 !important;
            padding: 1rem 1.5rem !important;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #d4c4a0 !important;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
        }
        .dataTables_wrapper .dataTables_filter input {
            background-color: #16120c !important;
            border: 1px solid rgba(245, 158, 11, 0.3) !important;
            color: #fde68a !important;
            border-radius: 0.5rem !important;
            padding: 0.375rem 0.75rem !important;
            margin-left: 0.5rem !important;
            outline: none !important;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #f59e0b !important;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.2) !important;
        }
        .dataTables_wrapper .dataTables_length select {
            background-color: #16120c !important;
            border: 1px solid rgba(245, 158, 11, 0.3) !important;
            color: #fde68a !important;
            border-radius: 0.5rem !important;
            padding: 0.25rem 0.5rem !important;
            outline: none !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #d4c4a0 !important;
            border-radius: 0.5rem !important;
            border: 1px solid transparent !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
            color: #ffffff !important;
            border: 1px solid #f59e0b !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: rgba(245, 158, 11, 0.15) !important;
            color: #fde68a !important;
            border-color: rgba(245, 158, 11, 0.3) !important;
        }
        table.dataTable tbody tr {
            background-color: transparent !important;
        }
        table.dataTable.no-footer {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
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
        #sidebar.w-20 nav a, #sidebar.w-20 .sidebar-footer a { justify-content: center; padding-left: 0; padding-right: 0; gap: 0; }
        #sidebar.w-20 nav span, #sidebar.w-20 nav p, #sidebar.w-20 .sidebar-footer span { opacity: 0; max-width: 0; padding: 0; margin: 0; border: none; }
        
        * {
            -webkit-tap-highlight-color: transparent;
        }
        body {
            background-color: #0F172A !important;
            color: #F8FAFC !important;
        }

        .page-transition, .tab-content, nav, .barber-card, .service-item {
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
        }

        /* Touch Friendly Action Buttons */
        button[type="submit"], .btn-touch {
            min-height: 48px !important;
            font-size: 16px !important;
            border-radius: 12px !important;
        }

        /* Form Input Touch & Anti-Zoom Safari */
        select, input[type="text"], input[type="email"], input[type="password"], input[type="number"], textarea {
            font-size: 16px !important;
            width: 100% !important;
            border-radius: 10px !important;
        }

        /* Mobile Layout Adjustments (< 768px) */
        @media (max-width: 768px) {
            body {
                flex-direction: column !important;
                padding-bottom: 65px !important;
                height: auto !important;
                min-height: 100vh !important;
            }

            #sidebar {
                position: fixed !important;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 60 !important;
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            #sidebar.open-mobile {
                transform: translateX(0) !important;
                width: 260px !important;
                box-shadow: 0 0 40px rgba(0,0,0,0.8) !important;
            }

            main {
                padding: 1rem !important;
            }
        }
        
        /* SPA Tab & View Transitions */
        .tab-content {
            display: none;
            width: 100%;
        }

        .tab-content.active {
            display: block;
        }

        ::view-transition-old(root) {
            animation: 200ms cubic-bezier(0.4, 0, 0.2, 1) both fade-out,
                       200ms cubic-bezier(0.4, 0, 0.2, 1) both scale-down;
        }

        ::view-transition-new(root) {
            animation: 250ms cubic-bezier(0, 0, 0.2, 1) both fade-in,
                       250ms cubic-bezier(0, 0, 0.2, 1) both slide-up;
        }

        @keyframes fade-out { from { opacity: 1; } to { opacity: 0; } }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes scale-down { from { transform: scale(1); } to { transform: scale(0.97); } }
        @keyframes slide-up { from { transform: translateY(12px); } to { transform: translateY(0); } }

        .fallback-anim-out {
            animation: fade-out 180ms ease forwards;
        }
        .fallback-anim-in {
            animation: fade-in 220ms ease forwards, slide-up 220ms ease forwards;
        }

        .nav-indicator {
            display: none;
        }

        /* Mobile Bottom Navigation Item Styles */
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
    </style>
</head>
<body class="text-amber-50 bg-adminlte-bg font-sans antialiased overflow-x-hidden flex h-screen">
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: linear-gradient(135deg, #0e0a08 0%, #120e06 30%, #1a0e04 60%, #0a0603 100%);"></div>

    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="w-64 bg-adminlte-sidebar h-full flex flex-col shadow-xl flex-shrink-0 transition-all duration-300">
        <script>
            if(localStorage.getItem('sidebarMinimized') === 'true') {
                document.getElementById('sidebar').classList.replace('w-64', 'w-20');
            }
        </script>
        <!-- Brand Logo -->
        <div id="brand-logo-container" class="h-16 flex items-center px-6 overflow-hidden" style="border-bottom: 1px solid #3a2510;">
            <span id="brand-icon" class="text-2xl mr-3 shrink-0">💈</span>
            <span id="brand-text" class="text-xl font-bold tracking-tight whitespace-nowrap" style="color:#e8d5a3;">Dashboard <span class="font-normal" style="color:#8a6030;">Pelanggan</span></span>
        </div>
        
        <!-- Sidebar Menu -->
        <div class="flex-1 overflow-y-auto py-4">
            <nav class="flex flex-col gap-1 px-3">
                <?php
                $current_page_param = $_GET['page'] ?? '';
                $is_dashboard = ($current_page_param === '');
                $is_profil = ($current_page_param === 'profil');
                $is_riwayat = ($current_page_param === 'riwayat');
                $is_layanan = ($current_page_param === 'layanan');
                $is_qris = ($current_page_param === 'qris');
                ?>
                <a href="javascript:void(0)" onclick="navigateToTab('tab-dashboard')" class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg mt-4 <?= $is_dashboard ? 'bg-adminlte-primary text-amber-200' : 'text-stone-400 hover:text-amber-200' ?>">
                    <i data-lucide="layout-dashboard" class="fa-solid fa-house w-5 h-5 text-amber-400 shrink-0"></i>
                    <span>Beranda</span>
                </a>
                <a href="javascript:void(0)" onclick="navigateToTab('tab-layanan')" class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg mt-1 <?= $is_layanan ? 'bg-adminlte-primary text-amber-200' : 'text-stone-400 hover:text-amber-200' ?>">
                    <i data-lucide="scissors" class="fa-solid fa-scissors w-5 h-5 text-amber-400 shrink-0"></i>
                    <span>Layanan</span>
                </a>
                <a href="javascript:void(0)" onclick="navigateToTab('tab-riwayat')" class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg mt-1 <?= $is_riwayat ? 'bg-adminlte-primary text-amber-200' : 'text-stone-400 hover:text-amber-200' ?>">
                    <i data-lucide="history" class="fa-solid fa-clock-rotate-left w-5 h-5 text-amber-400 shrink-0"></i>
                    <span>Riwayat Cukur</span>
                </a>
                <a href="javascript:void(0)" onclick="navigateToTab('tab-profil')" class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg mt-1 <?= $is_profil ? 'bg-adminlte-primary text-amber-200' : 'text-stone-400 hover:text-amber-200' ?>">
                    <i data-lucide="user-circle" class="fa-solid fa-user-gear w-5 h-5 text-amber-400 shrink-0"></i>
                    <span>Profil Saya</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer / Bottom Home Button -->
        <div class="sidebar-footer p-3 border-t border-amber-900/30 bg-zinc-950/40">
            <a href="../index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-zinc-400 hover:text-amber-200 hover:bg-amber-500/10 transition-colors">
                <i data-lucide="home" class="fa-solid fa-house w-5 h-5 text-zinc-400 shrink-0"></i>
                <span class="text-sm font-medium">Home</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Top Navbar -->
        <header class="h-16 flex items-center justify-between px-6 shadow-lg z-10 shrink-0" style="background: linear-gradient(90deg, #1a1008 0%, #110d06 50%, #1a1008 100%); border-bottom: 1px solid rgba(90,55,15,0.4);">
            <div class="flex items-center gap-4">
                <button id="sidebar-toggle" class="transition-colors hover:text-amber-400" style="color:#8a6030;">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="text-xl font-semibold text-white capitalize">
                    Pelanggan
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <div id="realtime-clock" class="hidden md:block text-sm text-zinc-300 font-medium tracking-wide"></div>
                <?php 
                $curr_fn = $user['fullname'] ?? $_SESSION['fullname'] ?? '';
                $curr_un = $user['username'] ?? $_SESSION['username'] ?? 'User';
                $nav_avatar_name = !empty($curr_fn) ? urlencode($curr_fn) : urlencode($curr_un);
                $nav_profile_files = glob(__DIR__ . '/../../asset/image/profile_' . $my_user_id . '.*');
                $nav_profile_url = !empty($nav_profile_files)
                    ? '../asset/image/' . basename($nav_profile_files[0]) . '?v=' . filemtime($nav_profile_files[0])
                    : "https://ui-avatars.com/api/?name={$nav_avatar_name}&background=random&color=fff&size=64&bold=true";
                ?>
                <div class="relative" id="user-profile-dropdown-container">
                    <button type="button" onclick="toggleProfileDropdown(event)" class="flex items-center gap-2.5 cursor-pointer hover:opacity-90 transition-all p-1.5 rounded-xl hover:bg-amber-500/10 focus:outline-none border border-transparent hover:border-amber-500/20 group" id="user-profile-dropdown-btn">
                        <img src="<?= $nav_profile_url ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover shadow-md border-2 border-amber-700/60 transition-transform group-hover:scale-105">
                        <span class="hidden md:block text-sm text-zinc-200 font-medium max-w-[130px] truncate"><?= htmlspecialchars($curr_fn ?: $curr_un) ?></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-amber-400 transition-transform duration-200" id="profile-dropdown-chevron"></i>
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <div id="user-profile-dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-[#161009] border border-amber-900/60 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-xl">
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

        <!-- Dynamic Main View Wrapper -->
        <main class="flex-1 overflow-y-auto p-6 relative page-transition">
            <?php if (function_exists('display_flash')) display_flash(); ?>
