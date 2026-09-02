<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Elite Barber</title>
    <!-- Google Fonts: Playfair Display (Serif) & Plus Jakarta Sans (Sans-Serif) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS via CDN -->
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
                            sidebar: '#0d0805',      // Espresso mahogany black
                            bg: '#080503',           // Dark mahogany
                            card: '#160e08',         // Dark warm mahogany card
                            primary: '#4a321a',      // Rich mahogany brown
                            success: '#1e3a1e',
                            warning: '#e5c158',
                            danger: '#4a1e1e',
                            info: '#1e2a3a',
                            accent: '#d4af37',       // Antique brass accent
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        /* DataTables Custom Dark Amber Theme for Barber Keren */
        .dataTables_wrapper {
            color: #d4c4a0 !important;
            padding: 1rem 1.5rem !important;
        }
        .dataTables_wrapper .dataTables_header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .dataTables_wrapper .dataTables_filter {
            float: none !important;
            margin-bottom: 0 !important;
        }
        .dataTables_wrapper .dataTables_filter label {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            white-space: nowrap !important;
            margin: 0 !important;
            color: #d4c4a0 !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
        }
        .dataTables_wrapper .dataTables_footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding-top: 1rem;
            margin-top: 0.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .dataTables_wrapper .dataTables_footer_right {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
            margin-left: auto;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #d4c4a0 !important;
            font-size: 0.875rem;
        }
        .dataTables_wrapper .dataTables_length {
            float: none !important;
            margin-bottom: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            white-space: nowrap !important;
        }
        .dataTables_wrapper .dataTables_length label {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
            white-space: nowrap !important;
            margin: 0 !important;
            color: #d4c4a0 !important;
            font-size: 0.875rem !important;
        }
        .dataTables_wrapper .dataTables_info {
            float: none !important;
            margin-bottom: 0 !important;
            padding-top: 0 !important;
            color: #a1a1aa !important;
        }
        .dataTables_wrapper .dataTables_paginate {
            float: none !important;
            margin-bottom: 0 !important;
            padding-top: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
        }
        .dataTables_wrapper .dataTables_filter input {
            background-color: #16120c !important;
            border: 1px solid rgba(245, 158, 11, 0.3) !important;
            color: #fde68a !important;
            border-radius: 0.5rem !important;
            padding: 0.375rem 0.75rem !important;
            outline: none !important;
            transition: all 0.2s ease !important;
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
            margin: 0 0.25rem !important;
            outline: none !important;
            display: inline-block !important;
            width: auto !important;
            cursor: pointer !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #d4c4a0 !important;
            border-radius: 0.5rem !important;
            border: 1px solid transparent !important;
            padding: 0.35rem 0.75rem !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            transition: all 0.2s ease !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
            color: #ffffff !important;
            border: 1px solid #f59e0b !important;
            box-shadow: 0 2px 8px rgba(217, 119, 6, 0.3) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: rgba(245, 158, 11, 0.15) !important;
            color: #fde68a !important;
            border-color: rgba(245, 158, 11, 0.3) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            opacity: 0.35 !important;
            cursor: not-allowed !important;
            background: transparent !important;
            border-color: transparent !important;
        }
        table.dataTable tbody tr {
            background-color: transparent !important;
        }
        table.dataTable.no-footer {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
    </style>

    <!-- Tabulator CSS & JS -->
    <link href="https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
    <!-- SheetJS for XLSX -->
    <script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>
    <!-- jsPDF & html2pdf for PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>window.jsPDF = window.jspdf ? window.jspdf.jsPDF : window.jsPDF;</script>
    <style>
        /* === PREMIUM BROWN-BLACK THEME === */

        /* Custom Tabulator Dark Theme Styles */
        .tabulator-wrapper {
            background: linear-gradient(135deg, #18120b 0%, #120e06 100%);
            padding: 1.5rem; border-radius: 0.75rem; color: #d4d4d8;
            font-size: 14px; border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 30px rgba(0,0,0,0.5), inset 0 1px 0 rgba(245,158,11,0.08);
        }
        .tabulator {
            border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 0.5rem;
            background-color: #120e06; overflow: hidden;
        }
        .tabulator .tabulator-header {
            background: linear-gradient(135deg, #2a1c0a 0%, #1e1408 100%) !important;
            color: #fde68a; border-bottom: 2px solid rgba(245, 158, 11, 0.2); font-weight: 600;
        }
        .tabulator .tabulator-header .tabulator-col {
            background: linear-gradient(135deg, #2a1c0a 0%, #1e1408 100%) !important;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }
        .tabulator-col-title {
            padding: 0.875rem 1.25rem !important; font-size: 0.875rem;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: #f59e0b; background: transparent !important;
        }
        .tabulator-cell { padding: 0.875rem 1.25rem !important; display: flex; align-items: center; }
        .tabulator-row {
            background-color: #120e06; border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #d4d4d8 !important;
            transition: all 0.2s ease;
        }
        .tabulator-row:nth-child(even) { background-color: #18120b; }
        .tabulator-row:hover {
            background: linear-gradient(90deg, #3d2b1a 0%, #2a1c0a 100%) !important;
            border-left: 3px solid #f59e0b;
            box-shadow: inset 0 0 20px rgba(245,158,11,0.06);
        }
        .tabulator-footer {
            background: linear-gradient(135deg, #2a1c0a 0%, #1e1408 100%) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08); color: #d4d4d8; padding: 0.75rem 1rem;
        }
        .tabulator-page {
            background-color: #18120b !important; color: #d4d4d8 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important; padding: 0.25rem 0.5rem;
            border-radius: 0.25rem; margin: 0 0.125rem; transition: all 0.2s;
        }
        .tabulator-page:not(.disabled):hover {
            background: linear-gradient(135deg, #3d2b1a, #2a1c0a) !important;
            color: #fde68a !important; border-color: #f59e0b !important;
        }
        .tabulator-page.active {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            color: #0e0a08 !important; border-color: #f59e0b !important;
            font-weight: 700;
        }
        .tabulator-page.disabled { opacity: 0.5; cursor: not-allowed; }
        .tabulator-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .tabulator-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #3d2b1a, #2a1c0a);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.375rem;
            color: #fde68a; cursor: pointer; display: inline-flex; align-items: center;
            gap: 0.5rem; font-size: 13px; transition: all 0.25s;
        }
        .tabulator-btn:hover {
            background: linear-gradient(135deg, #5c3d1a, #3d2b1a);
            border-color: #f59e0b; color: #f59e0b;
            box-shadow: 0 0 12px rgba(245,158,11,0.2);
        }
        .tabulator-search {
            padding: 0.4rem 0.75rem; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 4px;
            outline: none; width: 250px; background: #120e06; color: #fde68a;
            transition: border-color 0.2s;
        }
        .tabulator-search:focus { border-color: #f59e0b; box-shadow: 0 0 0 2px rgba(245,158,11,0.15); }
        .tabulator-search::placeholder { color: #a1a1aa; }

        @media (max-width: 640px) {
            .tabulator-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }
            .tabulator-controls > div {
                flex-wrap: wrap;
                justify-content: space-between;
            }
            .tabulator-btn {
                flex: 1 1 calc(50% - 0.25rem);
                justify-content: center;
            }
            .tabulator-search {
                width: 100% !important;
            }
            .tabulator-wrapper {
                padding: 0.75rem;
            }
        }

        /* Fix table cell text colors */
        .tabulator-row .text-white { color: #fde68a !important; }
        .tabulator-row .text-zinc-400 { color: #d4d4d8 !important; }

        /* Custom Luxury Scrollbar for Cards */
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: rgba(18, 14, 6, 0.6); border-radius: 8px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.35); border-radius: 8px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(212, 175, 55, 0.7); }

        /* Smooth Table Loading */
        .tabulator { opacity: 0; transition: opacity 0.5s ease-in-out; }
        .tabulator.table-loaded { opacity: 1; }

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

        /* Sidebar nav links */
        #sidebar nav a {
            position: relative; transition: all 0.25s ease;
            white-space: nowrap; overflow: hidden;
            border: 1px solid transparent;
            border-radius: 0.5rem;
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

        /* Active sidebar link */
        #sidebar nav a.active-link,
        #sidebar nav a.bg-adminlte-primary {
            background: rgba(245, 158, 11, 0.15) !important;
            border-color: rgba(245, 158, 11, 0.35) !important;
            color: #F59E0B !important;
        }
        #sidebar nav a.active-link i,
        #sidebar nav a.bg-adminlte-primary i {
            color: #F59E0B !important;
        }
        #sidebar nav a.active-link::before,
        #sidebar nav a.bg-adminlte-primary::before { opacity: 1; background: #F59E0B; }

        #sidebar nav span, #sidebar nav p { transition: opacity 0.2s, max-width 0.3s; max-width: 250px; overflow: hidden; white-space: nowrap; }
        #sidebar nav p { color: #6b4c20 !important; }

        /* Minimized State */
        #sidebar.w-20 #brand-logo-container { padding-left: 0; padding-right: 0; justify-content: center; }
        #sidebar.w-20 #brand-icon { margin-right: 0; }
        #sidebar.w-20 #brand-text { opacity: 0; max-width: 0; margin: 0; }
        #sidebar.w-20 nav a { justify-content: center; padding-left: 0; padding-right: 0; gap: 0; }
        #sidebar.w-20 nav span, #sidebar.w-20 nav p { opacity: 0; max-width: 0; padding: 0; margin: 0; border: none; }

        /* ============ PAGE TRANSITION ============ */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-transition { animation: fadeSlideUp 0.4s ease-out forwards; }

        /* Mobile Bottom Navigation Item Styles (Matching Pelanggan & Barber) */
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

        @keyframes iconPop {
            0% { transform: scale(0.6); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* ============ CARDS ============ */
        .stat-card {
            background: linear-gradient(135deg, #1e1408 0%, #120e06 100%);
            border: 1px solid #3d2b1a;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-color: #c9a03a;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 0 20px rgba(201,160,58,0.1);
            transform: translateY(-2px);
        }

        /* ============ SCROLLBAR ============ */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #0e0a08; }
        ::-webkit-scrollbar-thumb { background: #3d2b1a; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #c9a03a; }

        /* Mobile Utility & Bottom Sheet Adjustments */
        @media (max-width: 767px) {
            .mobile-bottom-sheet {
                align-items: flex-end !important;
                padding: 0 !important;
            }
            .mobile-bottom-sheet > div {
                border-bottom-left-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
                border-top-left-radius: 1.25rem !important;
                border-top-right-radius: 1.25rem !important;
                max-height: 88vh !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .tabulator-wrapper {
                padding: 0.5rem !important;
            }
            .stat-card {
                padding: 0.875rem !important;
            }
        }
    </style>
</head>
<body class="text-amber-50 font-sans antialiased overflow-x-hidden flex h-screen">
    <!-- Premium Brown-Black Gradient Background -->
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: linear-gradient(135deg, #0e0a08 0%, #120e06 30%, #1a0e04 60%, #0a0603 100%);"></div>
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: radial-gradient(ellipse 80% 60% at 70% 20%, rgba(90,50,15,0.15) 0%, transparent 60%), radial-gradient(ellipse 60% 40% at 20% 80%, rgba(60,30,5,0.1) 0%, transparent 50%);"></div>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-40 hidden md:hidden transition-opacity duration-300"></div>

    <!-- Sidebar (Off-canvas Drawer on Mobile, Collapsible Sidebar on Desktop) -->
    <aside id="sidebar" class="w-64 bg-adminlte-sidebar h-full flex flex-col shadow-2xl flex-shrink-0 fixed inset-y-0 left-0 z-50 transform -translate-x-full md:translate-x-0 md:static md:z-auto transition-transform md:transition-all duration-300">
        <script>
            if(window.innerWidth >= 768 && localStorage.getItem('sidebarMinimized') === 'true') {
                document.getElementById('sidebar').classList.replace('w-64', 'w-20');
            }
        </script>
        <!-- Brand Logo -->
        <div id="brand-logo-container" class="h-16 flex items-center justify-between md:justify-start px-6 overflow-hidden shrink-0" style="border-bottom: 1px solid #3a2510;">
            <div class="flex items-center">
                <span id="brand-icon" class="text-2xl mr-3 shrink-0">💈</span>
                <span id="brand-text" class="text-xl font-serif font-extrabold tracking-wide whitespace-nowrap" style="color:#f0d375;">Dashboard <span class="font-sans font-normal text-amber-500/80">Admin</span></span>
            </div>
            <!-- Mobile Close Button -->
            <button id="sidebar-close-btn" class="md:hidden text-amber-400/80 hover:text-amber-200 p-1 focus:outline-none">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <!-- Sidebar Menu -->
        <div class="flex-1 overflow-y-auto py-4 custom-scroll">
            <nav class="flex flex-col gap-1 px-3">
                <a href="?page=dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= ($page === 'dashboard' || empty($page)) ? 'bg-adminlte-primary text-amber-200 mt-2' : 'text-stone-400 hover:text-amber-200 mt-2' ?>">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 shrink-0"></i>
                    <span>Admin</span>
                </a>
                <a href="?page=antrean" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'antrean' ? 'bg-adminlte-primary text-amber-200 mt-1' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white mt-1' ?>">
                    <i data-lucide="monitor" class="w-5 h-5 shrink-0"></i>
                    <span>Antrean</span>
                </a>
                
                <p class="px-3 text-xs font-semibold uppercase tracking-wider mb-2 mt-4" style="color:#5c3d1a;">Kelola Data</p>
                <a href="?page=layanan" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= $page === 'layanan' ? 'bg-adminlte-primary text-amber-200' : 'text-stone-400 hover:text-amber-200' ?>">
                    <i data-lucide="scissors" class="w-5 h-5 shrink-0"></i>
                    <span>Layanan</span>
                </a>
                <a href="?page=transaksi" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'transaksi' ? 'bg-adminlte-primary text-amber-200' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?>">
                    <i data-lucide="receipt-text" class="w-5 h-5 shrink-0"></i>
                    <span>Transaksi</span>
                </a>
                <a href="?page=akun" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'akun' ? 'bg-adminlte-primary text-amber-200' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?>">
                    <i data-lucide="users" class="w-5 h-5 shrink-0"></i>
                    <span>Akun</span>
                </a>

                <p class="px-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2 mt-4">Sistem</p>
                <a href="?page=pengaturan" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'pengaturan' ? 'bg-adminlte-primary text-amber-200' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?>">
                    <i data-lucide="settings" class="w-5 h-5 shrink-0"></i>
                    <span>Pengaturan WA</span>
                </a>

                <p class="px-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2 mt-4">Lainnya</p>
                <a href="?page=profil" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'profil' ? 'bg-adminlte-primary text-amber-200' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?>">
                    <i data-lucide="user" class="w-5 h-5 shrink-0"></i>
                    <span>Profil</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer / Bottom Home Button -->
        <div class="sidebar-footer p-3 border-t border-amber-900/30 bg-zinc-950/40 shrink-0">
            <a href="../index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-zinc-400 hover:text-amber-200 hover:bg-amber-500/10 transition-colors">
                <i data-lucide="home" class="fa-solid fa-house w-5 h-5 text-zinc-400 shrink-0"></i>
                <span class="text-sm font-medium">Home</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden min-w-0">
        
        <!-- Top Navbar -->
        <header class="h-16 flex items-center justify-between px-3 sm:px-4 md:px-6 shadow-lg z-10 shrink-0" style="background: linear-gradient(90deg, #1a1008 0%, #110d06 50%, #1a1008 100%); border-bottom: 1px solid rgba(90,55,15,0.4);">
            <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                <button id="sidebar-toggle" class="p-1.5 rounded-lg transition-colors hover:text-amber-400 active:scale-95 focus:outline-none shrink-0" style="color:#8a6030;">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="text-sm sm:text-base md:text-xl font-serif font-bold text-[#f8f4e9] tracking-wide capitalize truncate max-w-[130px] sm:max-w-xs md:max-w-none">
                    <?= $page === 'dashboard' ? 'Dashboard Overview' : str_replace('_', ' ', $page) ?>
                </h1>
            </div>
            <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                <div id="realtime-clock" class="hidden lg:block text-xs md:text-sm text-zinc-300 font-medium tracking-wide"></div>

                <!-- NOTIFICATION BELL & DROPDOWN -->
                <div class="relative" id="admin-notif-container">
                    <button type="button" id="notif-bell-btn" onclick="toggleNotifDropdown(event)" class="relative p-2 sm:px-3 sm:py-2 text-amber-300 hover:text-amber-200 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/40 rounded-xl transition-all shadow-lg focus:outline-none flex items-center gap-2 cursor-pointer group" title="Notifikasi Sistem">
                        <i data-lucide="bell" class="w-5 h-5 text-amber-400 group-hover:rotate-12 transition-transform"></i>
                        <span class="text-xs font-bold hidden sm:inline">Notif</span>
                        <span id="notif-badge" class="hidden bg-rose-600 text-white font-extrabold text-xs px-1.5 py-0.5 sm:px-2 rounded-full border border-rose-400 flex items-center justify-center animate-bounce shadow-md">0</span>
                    </button>

                    <!-- Dropdown Content -->
                    <div id="notif-dropdown-menu" class="hidden absolute right-[-50px] sm:right-0 mt-3 w-[calc(100vw-1.5rem)] max-w-sm sm:w-96 bg-[#18120b] border border-amber-900/50 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-xl transition-all">
                        <div class="p-3.5 border-b border-amber-900/30 bg-[#22180f] flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="bell-ring" class="w-4 h-4 text-amber-400"></i>
                                <span class="font-bold text-sm text-amber-100">Notifikasi Pendaftaran</span>
                            </div>
                            <button type="button" onclick="markAllNotifRead()" class="text-xs text-amber-400 hover:text-amber-300 hover:underline cursor-pointer">Tandai Semua Terbaca</button>
                        </div>
                        <div id="notif-list-container" class="max-h-80 overflow-y-auto divide-y divide-white/5 custom-scroll p-1">
                            <div class="p-4 text-center text-xs text-zinc-400">Memuat notifikasi...</div>
                        </div>
                    </div>
                </div>

                <div class="relative" id="user-profile-dropdown-container">
                    <button type="button" onclick="toggleProfileDropdown(event)" class="flex items-center gap-2 cursor-pointer hover:opacity-90 transition-all p-1 sm:p-1.5 rounded-xl hover:bg-amber-500/10 focus:outline-none border border-transparent hover:border-amber-500/20 group" id="user-profile-dropdown-btn">
                        <?php 
                        $nav_avatar_name = !empty($current_user['fullname']) ? urlencode($current_user['fullname']) : urlencode($current_user['username']);
                        $nav_profile_files = glob(__DIR__ . '/../../asset/image/profile_' . $_SESSION['user_id'] . '.*');
                        $nav_profile_url = !empty($nav_profile_files) ? '../asset/image/' . basename($nav_profile_files[0]) : "https://ui-avatars.com/api/?name={$nav_avatar_name}&background=random&color=fff&size=64&bold=true";
                        ?>
                        <img src="<?= $nav_profile_url ?>" alt="Avatar" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover shadow-md border-2 border-amber-700/60 transition-transform group-hover:scale-105">
                        <span class="hidden md:block text-sm text-zinc-200 font-medium max-w-[130px] truncate"><?= htmlspecialchars($current_user['fullname'] ?: $current_user['username']) ?></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-amber-400 transition-transform duration-200 hidden sm:block" id="profile-dropdown-chevron"></i>
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <div id="user-profile-dropdown-menu" class="hidden absolute right-0 mt-2 w-52 bg-[#161009] border border-amber-900/60 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-xl divide-y divide-amber-900/40">
                        <div class="p-3 bg-[#1e1408]">
                            <span class="text-xs font-bold text-amber-200 block truncate"><?= htmlspecialchars($current_user['fullname'] ?: $current_user['username']) ?></span>
                            <span class="text-[10px] text-amber-400/80 font-mono capitalize">Role: <?= htmlspecialchars($_SESSION['role'] ?? 'admin') ?></span>
                        </div>
                        <div class="py-1">
                            <a href="?page=profil" class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-amber-200 hover:bg-amber-500/20 hover:text-amber-100 transition-colors">
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
        <main class="flex-1 overflow-y-auto p-3 sm:p-4 md:p-6 pb-24 md:pb-6 page-transition w-full max-w-full min-w-0">
            <?php if (function_exists('display_flash')) display_flash(); ?>
