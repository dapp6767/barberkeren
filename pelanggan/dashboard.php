<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/helper.php';
require_once __DIR__ . '/../functions/queue_functions.php';

// 1. PROTEKSI UTAMA: Wajib Login sebelum bisa mengakses Dashboard Antrean
if (!function_exists('is_logged_in') || !is_logged_in()) {
    redirect('../auth/login.php');
    exit;
}

$current_serving = get_current_serving_queue();
$active_queues   = get_active_queues();
$barbers        = get_all_barbers();
$services       = get_all_services();

$pdo = get_db_connection();
$my_user_id = $_SESSION['user_id'] ?? null;
$my_queue = null;
$user = [];
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
    
    // Assign current user for profile tab
    $current_user = $user;
}

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'take_ticket') {
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
    if ($_POST['action'] === 'cancel_my_ticket') {
        $antrian_id = (int)($_POST['antrian_id'] ?? 0);
        $cust_id = $_SESSION['user_id'] ?? 0;
        if ($antrian_id > 0 && $cust_id > 0) {
            try {
                $pdo->beginTransaction();
                try { $pdo->prepare("DELETE FROM ulasan WHERE antrian_id = ?")->execute([$antrian_id]); } catch (Exception $e) {}
                try { $pdo->prepare("DELETE FROM transaksi WHERE antrian_id = ?")->execute([$antrian_id]); } catch (Exception $e) {}
                $stmt_c = $pdo->prepare("DELETE FROM antrian WHERE id = ? AND pelanggan_id = ?");
                $stmt_c->execute([$antrian_id, $cust_id]);
                $pdo->commit();
                set_flash('warning', 'Antrean Anda berhasil dibatalkan.');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                set_flash('danger', 'Gagal membatalkan antrean: ' . $e->getMessage());
            }
        }
        redirect('dashboard.php');
        exit;
    }
    if ($_POST['action'] === 'pay_ticket') {
        $antrian_id = (int)$_POST['antrian_id'];
        $metode = $_POST['metode_pembayaran'];
        $total = (float)$_POST['total_harga'];
        $pdo->beginTransaction();
        $stmt2 = $pdo->prepare("INSERT INTO transaksi (antrian_id, total_harga, status_pembayaran, metode_pembayaran, waktu_bayar) VALUES (?, ?, 'lunas', ?, NOW())");
        $stmt2->execute([$antrian_id, $total, $metode]);
        $stmt1 = $pdo->prepare("UPDATE antrian SET status_antrean = 'paid' WHERE id = ?");
        $stmt1->execute([$antrian_id]);
        $pdo->commit();

        $stmt_q = $pdo->prepare("SELECT no_antrean FROM antrian WHERE id = ? LIMIT 1");
        $stmt_q->execute([$antrian_id]);
        $q_info = $stmt_q->fetch(PDO::FETCH_ASSOC);
        $no_antrean = $q_info ? $q_info['no_antrean'] : "#$antrian_id";

        if (function_exists('create_admin_notification')) {
            create_admin_notification(
                'new_transaction',
                'Transaksi Baru Diterima',
                "Pembayaran Rp " . number_format($total, 0, ',', '.') . " ({$metode}) dari antrean {$no_antrean} berhasil!",
                'admin.php?page=transaksi'
            );
        }
        set_flash('success', 'Pembayaran berhasil! Menunggu Barber mencetak struk.');
        redirect('dashboard.php');
        exit;
    }
    if ($_POST['action'] === 'submit_review') {
        $antrian_id = (int)$_POST['antrian_id'];
        $rating = (int)$_POST['rating'];
        $komentar = $_POST['komentar'];
        $cust_id = $_SESSION['user_id'];
        $pdo->beginTransaction();
        $stmt2 = $pdo->prepare("INSERT INTO ulasan (antrian_id, pelanggan_id, rating, komentar) VALUES (?, ?, ?, ?)");
        $stmt2->execute([$antrian_id, $cust_id, $rating, $komentar]);
        $stmt1 = $pdo->prepare("UPDATE antrian SET status_antrean = 'completed' WHERE id = ?");
        $stmt1->execute([$antrian_id]);
        $pdo->commit();
        set_flash('success', 'Terima kasih atas ulasan Anda!');
        redirect('dashboard.php');
        exit;
    }
    if ($_POST['action'] === 'update_profil') {
        $user_id = $_SESSION['user_id'];
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? ($_POST['password'] ?? '');
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        try {
            $stmt_cur = $pdo->prepare("SELECT * FROM users WHERE id_user = ? LIMIT 1");
            $stmt_cur->execute([$user_id]);
            $user_data = $stmt_cur->fetch(PDO::FETCH_ASSOC);

            if (!$user_data) {
                set_flash('danger', 'Akun tidak ditemukan!');
                redirect('dashboard.php?tab=profil');
                exit;
            }

            if (function_exists('contains_sara_words')) {
                if (contains_sara_words($fullname)) {
                    set_flash('danger', 'Nama Lengkap mengandung kata/unsur SARA yang dilarang!');
                    redirect('dashboard.php?tab=profil');
                    exit;
                }
                if (contains_sara_words($username)) {
                    set_flash('danger', 'Username mengandung kata/unsur SARA yang dilarang!');
                    redirect('dashboard.php?tab=profil');
                    exit;
                }
            }

            $stmt_dup = $pdo->prepare("SELECT id_user FROM users WHERE (LOWER(username) = LOWER(?) OR (email != '' AND LOWER(email) = LOWER(?))) AND id_user != ? LIMIT 1");
            $stmt_dup->execute([$username, $email, $user_id]);
            if ($stmt_dup->fetch()) {
                set_flash('danger', 'Username atau Email sudah terdaftar pada akun lain!');
                redirect('dashboard.php?tab=profil');
                exit;
            }

            $update_password_hash = null;

            if (!empty($old_password) || !empty($new_password) || !empty($confirm_password)) {
                if (empty($old_password)) {
                    set_flash('danger', 'Silakan masukkan Password Lama Anda untuk mengonfirmasi perubahan password!');
                    redirect('dashboard.php?tab=profil');
                    exit;
                }

                $password_correct = false;
                if (password_verify($old_password, $user_data['password'])) {
                    $password_correct = true;
                } elseif ($old_password === $user_data['password']) {
                    $password_correct = true;
                }

                if (!$password_correct) {
                    set_flash('danger', 'Password Lama Anda salah! Verifikasi pemilik akun gagal.');
                    redirect('dashboard.php?tab=profil');
                    exit;
                }

                if (empty($new_password) || empty($confirm_password)) {
                    set_flash('danger', 'Password Baru dan Konfirmasi Password wajib diisi!');
                    redirect('dashboard.php?tab=profil');
                    exit;
                }

                if ($new_password !== $confirm_password) {
                    set_flash('danger', 'Konfirmasi Password Baru tidak cocok dengan Password Baru!');
                    redirect('dashboard.php?tab=profil');
                    exit;
                }

                if (function_exists('validate_account_creation')) {
                    $val_p = validate_account_creation($fullname, $username, $new_password, $email, $user_id);
                    if (!$val_p['status'] && str_contains(strtolower($val_p['message']), 'password')) {
                        set_flash('danger', $val_p['message']);
                        redirect('dashboard.php?tab=profil');
                        exit;
                    }
                } else {
                    if (strlen($new_password) < 6) {
                        set_flash('danger', 'Password minimal harus 6-8 karakter!');
                        redirect('dashboard.php?tab=profil');
                        exit;
                    }
                    if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[\W_]/', $new_password)) {
                        set_flash('danger', 'Password baru wajib kombinasi Huruf Besar (A-Z), Huruf Kecil (a-z), Angka (0-9), dan Simbol Khusus (@, #, !, dll)!');
                        redirect('dashboard.php?tab=profil');
                        exit;
                    }
                }

                $update_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            }

            if ($update_password_hash) {
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, username=?, email=?, phone=?, password=? WHERE id_user=?");
                $stmt->execute([$fullname, $username, $email, $phone, $update_password_hash, $user_id]);
                set_flash('success', 'Profil dan Password Anda berhasil diperbarui!');
            } else {
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, username=?, email=?, phone=? WHERE id_user=?");
                $stmt->execute([$fullname, $username, $email, $phone, $user_id]);
                set_flash('success', 'Informasi profil berhasil diperbarui!');
            }

            if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $upload_dir = __DIR__ . '/../asset/image/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    $old_files = glob($upload_dir . "profile_{$user_id}.*");
                    foreach ($old_files as $of) unlink($of);
                    
                    $new_filename = "profile_{$user_id}.{$ext}";
                    move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_dir . $new_filename);
                }
            }

            $_SESSION['username'] = $username;
            $_SESSION['fullname'] = $fullname;
            set_flash('success', 'Profil berhasil diperbarui!');
            redirect('dashboard.php?tab=profil');
            exit;
        } catch (PDOException $e) {
            set_flash('danger', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            redirect('dashboard.php?tab=profil');
            exit;
        }
    }
}
?>
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
        }
                    }
                }
            }
        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
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
        /* Mobile-First Theme & Responsive Rules */
        body {
            background-color: #0F172A !important;
            color: #F8FAFC !important;
        }

        /* Touch Friendly Action Buttons (min-height 48px, rounded 12px) */
        button[type="submit"], .btn-touch {
            min-height: 48px !important;
            font-size: 16px !important;
            border-radius: 12px !important;
        }

        /* Form Input Touch & Anti-Zoom Safari (font-size >= 16px) */
        select, input[type="text"], input[type="email"], input[type="password"], input[type="number"], textarea {
            font-size: 16px !important;
            width: 100% !important;
            border-radius: 10px !important;
        }

        /* Mobile Layout Adjustments (< 768px) */
        @media (max-width: 768px) {
            body {
                flex-direction: column !important;
                padding-bottom: 65px !important; /* Extra space for Fixed Bottom Nav */
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
        
        /* ==========================================
           SPA Tab & View Transitions API CSS
           ========================================== */
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
    </style>
</head>
<body class="text-amber-50 bg-adminlte-bg font-sans antialiased overflow-x-hidden flex h-screen">
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: linear-gradient(135deg, #0e0a08 0%, #120e06 30%, #1a0e04 60%, #0a0603 100%);"></div>
    <!-- Light mode bg -->

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
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span>Beranda</span>
                </a>
                <a href="javascript:void(0)" onclick="navigateToTab('tab-layanan')" class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg mt-1 <?= $is_layanan ? 'bg-adminlte-primary text-amber-200' : 'text-stone-400 hover:text-amber-200' ?>">
                    <i data-lucide="scissors" class="w-5 h-5"></i>
                    <span>Layanan</span>
                </a>
                <a href="javascript:void(0)" onclick="navigateToTab('tab-riwayat')" class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg mt-1 <?= $is_riwayat ? 'bg-adminlte-primary text-amber-200' : 'text-stone-400 hover:text-amber-200' ?>">
                    <i data-lucide="history" class="w-5 h-5"></i>
                    <span>Riwayat Cukur</span>
                </a>
                <a href="javascript:void(0)" onclick="navigateToTab('tab-profil')" class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg mt-1 <?= $is_profil ? 'bg-adminlte-primary text-amber-200' : 'text-stone-400 hover:text-amber-200' ?>">
                    <i data-lucide="user-circle" class="w-5 h-5"></i>
                    <span>Profil Saya</span>
                </a>
                
                <p class="px-3 text-xs font-semibold uppercase tracking-wider mb-2 mt-4" style="color:#5c3d1a;">Lainnya</p>
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
                    Pelanggan
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
                    $curr_fn = $user['fullname'] ?? $_SESSION['fullname'] ?? '';
                    $curr_un = $user['username'] ?? $_SESSION['username'] ?? 'User';
                    $nav_avatar_name = !empty($curr_fn) ? urlencode($curr_fn) : urlencode($curr_un);
                    // Check for uploaded profile photo first
                    $nav_profile_files = glob(__DIR__ . '/../asset/image/profile_' . $my_user_id . '.*');
                    $nav_profile_url = !empty($nav_profile_files)
                        ? '../asset/image/' . basename($nav_profile_files[0]) . '?v=' . filemtime($nav_profile_files[0])
                        : "https://ui-avatars.com/api/?name={$nav_avatar_name}&background=random&color=fff&size=64&bold=true";
                    ?>
                    <img src="<?= $nav_profile_url ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover shadow-md border-2 border-amber-700/60">
                    <span class="hidden md:block text-sm text-zinc-300 font-medium"><?= htmlspecialchars($curr_fn ?: $curr_un) ?></span>
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6 relative page-transition">
            <?php if (function_exists('display_flash')) display_flash(); ?>
            
            <section id="tab-profil" class="tab-content <?= $is_profil ? 'active' : '' ?>">
            <!-- PROFIL MODULE -->
            <div class="max-w-4xl mx-auto">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Profil Saya</h2>
                        <p class="text-zinc-400 text-sm mt-1">Kelola informasi pribadi dan keamanan akun Anda</p>
                    </div>
                </div>

                <form action="dashboard.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <input type="hidden" name="action" value="update_profil">
                    <input type="hidden" name="current_page" value="profil">
                    <!-- Left Column: Avatar & Summary -->
                    <div class="col-span-1">
                        <div class="bg-adminlte-card border border-zinc-700 rounded-xl p-6 shadow-2xl flex flex-col items-center text-center relative overflow-hidden">
                            <!-- Background Decoration -->
                            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-amber-900/30 to-amber-950/20 z-0"></div>
                            
                            <div class="relative z-10 w-28 h-28 rounded-full border-4 border-zinc-700 shadow-xl mt-4 mb-4 overflow-hidden bg-zinc-900 group">
                                <?php 
                                $avatar_name = !empty($current_user['fullname']) ? urlencode($current_user['fullname']) : urlencode($current_user['username']);
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
                            
                            <h3 class="relative z-10 text-xl font-bold text-white mb-1"><?= !empty($current_user['fullname']) ? htmlspecialchars($current_user['fullname']) : htmlspecialchars($current_user['username']) ?></h3>
                            <span class="relative z-10 bg-amber-500/20 text-amber-300 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider mb-4 border border-amber-500/30">
                                <?= htmlspecialchars($current_user['role']) ?>
                            </span>
                            
                            <div class="relative z-10 w-full text-left space-y-3 mt-4 border-t border-zinc-700/80 pt-4">
                                <div class="flex items-center gap-3 text-sm text-zinc-300">
                                    <i data-lucide="mail" class="w-4 h-4 text-amber-400"></i>
                                    <span class="truncate"><?= !empty($current_user['email']) ? htmlspecialchars($current_user['email']) : '<em class="text-zinc-500">Belum diatur</em>' ?></span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-zinc-300">
                                    <i data-lucide="phone" class="w-4 h-4 text-amber-400"></i>
                                    <span><?= !empty($current_user['phone']) ? htmlspecialchars($current_user['phone']) : '<em class="text-zinc-500">Belum diatur</em>' ?></span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-zinc-300">
                                    <i data-lucide="user" class="w-4 h-4 text-amber-400"></i>
                                    <span>@<?= htmlspecialchars($current_user['username']) ?></span>
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
                            
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-400 mb-2">Nama Lengkap</label>
                                        <input type="text" name="fullname" value="<?= htmlspecialchars($current_user['fullname'] ?? '') ?>" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-amber-500 transition-all" placeholder="Nama Lengkap">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-400 mb-2">Username</label>
                                        <input type="text" name="username" value="<?= htmlspecialchars($current_user['username']) ?>" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-amber-500 transition-all" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-400 mb-2">Email</label>
                                        <input type="email" name="email" value="<?= htmlspecialchars($current_user['email'] ?? '') ?>" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-amber-500 transition-all" placeholder="email@contoh.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-400 mb-2">No. WhatsApp</label>
                                        <input type="text" name="phone" value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-amber-500 transition-all" placeholder="08123456789">
                                    </div>
                                </div>

                                <div class="border-t border-zinc-700/80 pt-6 mb-6">
                                    <h4 class="text-sm font-medium text-white mb-4 flex items-center gap-2">
                                        <i data-lucide="shield-check" class="w-4 h-4 text-amber-400"></i> Keamanan Akun & Ubah Password
                                    </h4>
                                    
                                    <div class="space-y-4 max-w-xl">
                                        <div>
                                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Password Lama Saat Ini</label>
                                            <div class="relative">
                                                <input type="password" id="old_pass_input" name="old_password" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-amber-500 transition-all text-sm" placeholder="Masukkan password lama Anda">
                                                <button type="button" onclick="togglePass('old_pass_input', 'eye_old')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                                    <i data-lucide="eye" id="eye_old" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                            <p class="text-[11px] text-zinc-500 mt-1">* Wajib diisi untuk memverifikasi bahwa Anda adalah pemilik sah akun ini.</p>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Password Baru</label>
                                                <div class="relative">
                                                    <input type="password" id="new_pass_input" name="new_password" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-amber-500 transition-all text-sm" placeholder="Min. 6-8 karakter">
                                                    <button type="button" onclick="togglePass('new_pass_input', 'eye_new')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                                        <i data-lucide="eye" id="eye_new" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Konfirmasi Password Baru</label>
                                                <div class="relative">
                                                    <input type="password" id="confirm_pass_input" name="confirm_password" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-amber-500 transition-all text-sm" placeholder="Ulangi password baru">
                                                    <button type="button" onclick="togglePass('confirm_pass_input', 'eye_conf')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                                        <i data-lucide="eye" id="eye_conf" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Password Strength Checklist Box -->
                                        <div class="bg-black/40 border border-white/10 rounded-xl p-3.5 space-y-2 text-xs">
                                            <p class="text-amber-200 font-semibold text-[11px] uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                                <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-amber-400"></i> Ketentuan Kombinasi Password Baru:
                                            </p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-zinc-400">
                                                <div id="prof_rule_len" class="flex items-center gap-1.5 transition-colors">
                                                    <i data-lucide="circle-dot" class="w-3 h-3 text-zinc-600"></i> Minimal 6-8 Karakter
                                                </div>
                                                <div id="prof_rule_case" class="flex items-center gap-1.5 transition-colors">
                                                    <i data-lucide="circle-dot" class="w-3 h-3 text-zinc-600"></i> Huruf Besar (A-Z) & Kecil (a-z)
                                                </div>
                                                <div id="prof_rule_num" class="flex items-center gap-1.5 transition-colors">
                                                    <i data-lucide="circle-dot" class="w-3 h-3 text-zinc-600"></i> Memiliki Angka (0-9)
                                                </div>
                                                <div id="prof_rule_sym" class="flex items-center gap-1.5 transition-colors">
                                                    <i data-lucide="circle-dot" class="w-3 h-3 text-zinc-600"></i> Memiliki Simbol (@, #, !, dll)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" id="profile_save_btn" class="bg-amber-600 hover:bg-amber-500 text-white font-medium px-6 py-2.5 rounded-lg transition-colors flex items-center gap-2 shadow-lg shadow-amber-900/40">
                                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            </section> <!-- End Profil -->

            <section id="tab-riwayat" class="tab-content <?= $is_riwayat ? 'active' : '' ?>">
            <!-- Riwayat Cukur Card -->
            <div class="bg-[#1E1B18] rounded-xl border border-white/10 shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-amber-900/30 bg-[#16120c] flex items-center justify-between flex-wrap gap-2">
                    <h3 class="font-bold text-[#e8d5a3] text-base tracking-wide flex items-center gap-2">
                        <i data-lucide="scroll-text" class="w-5 h-5 text-amber-400"></i>
                        Riwayat Cukur Anda
                    </h3>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                        <?= count($history) ?> Transaksi
                    </span>
                </div>

                <?php if(empty($history)): ?>
                    <div class="flex flex-col items-center justify-center py-14 px-6 text-center">
                        <div class="w-14 h-14 rounded-full bg-amber-500/10 border border-amber-500/20 flex items-center justify-center mb-4">
                            <i data-lucide="scissors" class="w-7 h-7 text-amber-400/60"></i>
                        </div>
                        <p class="text-zinc-400 font-medium mb-1">Belum ada riwayat cukur</p>
                        <p class="text-zinc-600 text-sm">Riwayat transaksi Anda akan muncul di sini setelah selesai cukur.</p>
                    </div>
                <?php else: ?>

                <!-- Desktop Table View (md+) -->
                <div class="hidden md:block overflow-x-auto custom-scroll">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-zinc-900/70 text-zinc-400 text-xs uppercase tracking-wider border-b border-white/10">
                                <th class="px-6 py-4 font-semibold">No. Tiket</th>
                                <th class="px-6 py-4 font-semibold">Layanan</th>
                                <th class="px-6 py-4 font-semibold">Kursi</th>
                                <th class="px-6 py-4 font-semibold">Tanggal & Waktu</th>
                                <th class="px-6 py-4 font-semibold text-right">Total Harga</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach($history as $i => $h): ?>
                                <tr class="hover:bg-amber-900/10 transition-colors group">
                                    <td class="px-6 py-4">
                                        <span class="text-amber-400 font-mono font-bold text-base tracking-wide"><?= htmlspecialchars($h['no_antrean']) ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-white font-semibold"><?= htmlspecialchars($h['nama_layanan'] ?? 'Standard Cut') ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-zinc-800/80 text-zinc-300 text-xs font-medium border border-white/10">
                                            <i data-lucide="armchair" class="w-3 h-3 text-amber-400/70"></i>
                                            Kursi <?= htmlspecialchars(substr($h['no_antrean'], 0, 1)) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-zinc-300 text-sm font-medium"><?= date('d M Y', strtotime($h['waktu_bayar'])) ?></div>
                                        <div class="text-zinc-500 text-xs mt-0.5"><?= date('H:i', strtotime($h['waktu_bayar'])) ?> WIB</div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-emerald-400 font-bold text-base">Rp <?= number_format($h['total_harga'], 0, ',', '.') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <!-- Tfoot: Total Keseluruhan -->
                        <tfoot>
                            <tr class="bg-amber-900/10 border-t border-amber-500/20">
                                <td colspan="4" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-amber-300/70">Total Keseluruhan</td>
                                <td class="px-6 py-3 text-right">
                                    <span class="text-amber-400 font-black text-lg">Rp <?= number_format(array_sum(array_column($history, 'total_harga')), 0, ',', '.') ?></span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Mobile Vertical Card Stack -->
                <div class="block md:hidden p-4 space-y-3">
                    <?php foreach($history as $h): ?>
                    <div class="bg-zinc-900/60 border border-white/10 rounded-xl p-4 flex flex-col gap-3 hover:border-amber-500/30 transition-all">
                        <!-- Top: Ticket & Price -->
                        <div class="flex justify-between items-start border-b border-white/5 pb-3">
                            <div>
                                <span class="text-[11px] text-zinc-400 uppercase font-medium block mb-0.5">No. Tiket</span>
                                <span class="text-amber-400 font-mono font-black text-xl tracking-wider"><?= htmlspecialchars($h['no_antrean']) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] text-zinc-400 uppercase font-medium block mb-0.5">Total Bayar</span>
                                <span class="text-emerald-400 font-bold text-base">Rp <?= number_format($h['total_harga'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                        <!-- Middle: Service & Chair -->
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-[11px] text-zinc-400 uppercase font-medium block mb-0.5">Layanan</span>
                                <span class="text-white font-semibold"><?= htmlspecialchars($h['nama_layanan'] ?? 'Standard Cut') ?></span>
                            </div>
                            <div>
                                <span class="text-[11px] text-zinc-400 uppercase font-medium block mb-0.5">Kursi</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-zinc-800 text-zinc-300 text-xs border border-white/10">
                                    <i data-lucide="armchair" class="w-3 h-3 text-amber-400/70"></i>
                                    Kursi <?= htmlspecialchars(substr($h['no_antrean'], 0, 1)) ?>
                                </span>
                            </div>
                        </div>
                        <!-- Bottom: Date & Time -->
                        <div class="flex items-center gap-1.5 text-xs text-zinc-500 pt-2 border-t border-white/5">
                            <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-amber-400/60"></i>
                            <span><?= date('d M Y', strtotime($h['waktu_bayar'])) ?></span>
                            <span class="text-zinc-700">·</span>
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-400/60"></i>
                            <span><?= date('H:i', strtotime($h['waktu_bayar'])) ?> WIB</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <!-- Mobile Total Summary -->
                    <div class="bg-amber-500/10 border border-amber-500/25 rounded-xl px-4 py-3 flex justify-between items-center">
                        <span class="text-xs font-semibold uppercase tracking-wider text-amber-300/70">Total Keseluruhan</span>
                        <span class="text-amber-400 font-black text-lg">Rp <?= number_format(array_sum(array_column($history, 'total_harga')), 0, ',', '.') ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </section> <!-- End Riwayat -->

            <section id="tab-layanan" class="tab-content <?= $is_layanan ? 'active' : '' ?>">
            <!-- LAYANAN MODULE -->
            <div class="w-full pb-32">
                <!-- Header & Search Row -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">
                            <span class="text-amber-400">Katalog</span> <span class="text-white">Layanan</span>
                        </h2>
                        <p class="text-zinc-500 text-sm mt-1">Pilih layanan yang kamu inginkan, lalu ambil antrean</p>
                    </div>
                    <!-- Search Bar -->
                    <div class="relative w-full md:max-w-xs">
                        <i data-lucide="search" class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" id="search-layanan" placeholder="Cari layanan..."
                            class="w-full bg-[#1A1612] border border-white/5 rounded-xl pl-9 pr-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500/50 transition-all placeholder:text-zinc-500">
                    </div>
                </div>

                <!-- Service Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="service-list-container">
                    <?php 
                    $default_images_layanan = [
                        'pridecut'      => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                        'maxcut'        => '../asset/image/maxcut.png',
                        'hair coloring' => 'https://images.unsplash.com/photo-1620331311520-246422fd82f9?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                        'hairlight'     => '../asset/image/hairlight.png',
                        'full hairlight'=> '../asset/image/full_hairlight.png',
                        'hair tattoo'   => 'https://images.unsplash.com/photo-1593702295094-aea22597af65?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                        'shave'         => 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                        'korean wave'   => 'https://images.unsplash.com/photo-1605497788044-5a32c7078486?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                    ];
                    $dummy_desc_arr = [
                        'color' => 'Pewarnaan rambut highlight full kepala',
                        'light' => 'Pewarnaan rambut highlight full kepala',
                        'default' => 'Potong Rambut + Cuci + Styling',
                    ];
                    
                    foreach($services as $i => $srv): 
                        $s_id = $srv['id'] ?? $srv['id_service'];
                        $files = glob(__DIR__ . "/../asset/image/layanan_{$s_id}.*");
                        $nama_lower = strtolower($srv['service_name']);
                        $img = !empty($files)
                            ? '../asset/image/' . basename($files[0])
                            : ($default_images_layanan[$nama_lower] ?? 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80');
                        $desc = $dummy_desc_arr['default'];
                        $est_time = '45 Menit';
                        if(stripos($srv['service_name'], 'color') !== false || stripos($srv['service_name'], 'light') !== false) {
                            $desc = $dummy_desc_arr['light'];
                            $est_time = '90 Menit';
                        }
                        $price_formatted = 'Rp ' . number_format($srv['price'], 0, ',', '.');
                    ?>
                    <!-- Service Card (Desktop Grid Style) -->
                    <div class="service-item group bg-[#1A1612] rounded-2xl border border-white/5 overflow-hidden shadow-lg transition-all duration-200 cursor-pointer hover:border-amber-500/40 hover:-translate-y-0.5 hover:shadow-amber-900/20 select-none"
                         data-id="<?= $s_id ?>"
                         data-name="<?= htmlspecialchars($srv['service_name']) ?>"
                         data-price="<?= $srv['price'] ?>"
                         data-price-fmt="<?= $price_formatted ?>"
                         onclick="selectLayanan(this)">

                        <!-- Image Section -->
                        <div class="relative w-full h-44 overflow-hidden bg-zinc-800">
                            <img src="<?= $img ?>" alt="<?= htmlspecialchars($srv['service_name']) ?>"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <!-- Gradient overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-[#1A1612] via-transparent to-transparent"></div>
                            <!-- Selected overlay -->
                            <div class="selected-overlay absolute inset-0 bg-amber-500/25 flex items-center justify-center opacity-0 transition-opacity duration-200">
                                <div class="bg-amber-400 rounded-full p-2 shadow-xl">
                                    <i data-lucide="check" class="w-5 h-5 text-amber-950"></i>
                                </div>
                            </div>
                            <!-- Price badge -->
                            <div class="absolute bottom-3 right-3 bg-black/60 backdrop-blur-sm text-emerald-400 font-bold text-sm px-3 py-1 rounded-full border border-emerald-500/30">
                                <?= $price_formatted ?>
                            </div>
                        </div>

                        <!-- Content Section -->
                        <div class="p-4 flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-bold text-white leading-tight truncate"><?= htmlspecialchars($srv['service_name']) ?></h3>
                                <p class="text-xs text-zinc-400 mt-1 line-clamp-2"><?= $desc ?></p>
                                <div class="flex items-center gap-1 text-xs text-zinc-500 mt-2">
                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                    <span><?= $est_time ?></span>
                                </div>
                            </div>
                            <!-- Selected checkmark (right side) -->
                            <div class="selected-tick hidden shrink-0 mt-1">
                                <div class="w-6 h-6 rounded-full bg-amber-400 flex items-center justify-center shadow-[0_0_10px_rgba(245,158,11,0.5)]">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-amber-950"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Floating Action Bar — hidden until service is selected, desktop-aware -->
            <div id="layanan-action-bar" class="fixed bottom-24 md:bottom-6 left-0 right-0 z-50 px-6 transition-all duration-300 translate-y-4 opacity-0 pointer-events-none">
                <!-- Offset for sidebar on desktop -->
                <div class="md:pl-64 lg:pl-64 transition-all duration-300" id="fab-inner-wrapper">
                    <div class="max-w-2xl mx-auto bg-gradient-to-r from-zinc-900 to-[#2a1c0a] border border-amber-500/50 rounded-2xl px-5 py-3.5 flex justify-between items-center shadow-[0_8px_30px_rgba(0,0,0,0.7)] relative overflow-hidden">
                        <div class="absolute inset-0 bg-amber-500/5 backdrop-blur-md"></div>
                        <div class="relative z-10 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-amber-400/20 border border-amber-500/30 flex items-center justify-center shrink-0">
                                <i data-lucide="scissors" class="w-5 h-5 text-amber-400"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-zinc-400 font-medium uppercase tracking-wider">Layanan Dipilih</p>
                                <p id="fab-name" class="text-white font-bold text-sm leading-tight truncate max-w-[200px] md:max-w-xs"></p>
                                <p id="fab-price" class="text-amber-400 font-black text-base leading-none"></p>
                            </div>
                        </div>
                        <a id="fab-link" href="#"
                           class="relative z-10 bg-amber-400 hover:bg-amber-300 text-amber-950 font-bold text-sm px-6 py-2.5 rounded-xl transition-colors shadow-[0_0_15px_rgba(245,158,11,0.3)] flex items-center gap-2 whitespace-nowrap">
                            <i data-lucide="ticket" class="w-4 h-4"></i>
                            Ambil Antrean
                        </a>
                    </div>
                </div>
            </div>

            <script>
            (function() {
                let selectedId = null;

                function selectLayanan(el) {
                    // Deselect previous
                    document.querySelectorAll('.service-item').forEach(function(card) {
                        card.classList.remove('border-amber-500', 'shadow-[0_0_20px_rgba(245,158,11,0.2)]');
                        card.classList.add('border-white/5');
                        card.querySelector('.selected-overlay').classList.replace('opacity-100', 'opacity-0');
                        card.querySelector('.selected-tick').classList.add('hidden');
                    });

                    // Select this card
                    el.classList.remove('border-white/5');
                    el.classList.add('border-amber-500', 'shadow-[0_0_20px_rgba(245,158,11,0.2)]');
                    el.querySelector('.selected-overlay').classList.replace('opacity-0', 'opacity-100');
                    el.querySelector('.selected-tick').classList.remove('hidden');

                    selectedId = el.dataset.id;
                    const name = el.dataset.name;
                    const priceFmt = el.dataset.priceFmt;

                    // Update FAB
                    document.getElementById('fab-name').textContent = name;
                    document.getElementById('fab-price').textContent = priceFmt;
                    document.getElementById('fab-link').href = 'dashboard.php?service_id=' + selectedId;

                    // Show FAB
                    const bar = document.getElementById('layanan-action-bar');
                    bar.classList.remove('translate-y-4', 'opacity-0', 'pointer-events-none');
                    bar.classList.add('translate-y-0', 'opacity-100');

                    lucide.createIcons();
                }

                // Expose globally
                window.selectLayanan = selectLayanan;

                // Search filter
                document.addEventListener('DOMContentLoaded', function() {
                    const searchInput = document.getElementById('search-layanan');
                    if (searchInput) {
                        searchInput.addEventListener('input', function() {
                            const q = this.value.toLowerCase();
                            document.querySelectorAll('.service-item').forEach(function(card) {
                                const name = card.dataset.name.toLowerCase();
                                card.style.display = name.includes(q) ? '' : 'none';
                            });
                        });
                    }
                    lucide.createIcons();
                });
            })();
            </script>
            </section> <!-- End Layanan -->

            <section id="tab-qris" class="tab-content <?= $is_qris ? 'active' : '' ?>">
            <!-- SCAN QRIS MODULE -->
            <div class="max-w-md mx-auto mt-4 pb-24">
                <div class="bg-[#1E1B18] rounded-2xl border border-amber-900/40 p-6 shadow-2xl relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-500/20 blur-3xl rounded-full"></div>
                    <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-amber-500/20 blur-3xl rounded-full"></div>
                    
                    <div class="text-center relative z-10 mb-6">
                        <h2 class="text-2xl font-bold text-white tracking-tight flex justify-center items-center gap-2">
                            <i data-lucide="qr-code" class="w-6 h-6 text-amber-400"></i> Scan QRIS
                        </h2>
                        <p class="text-zinc-400 text-sm mt-1">Pembayaran lebih mudah dan cepat</p>
                    </div>

                    <div class="bg-white p-4 rounded-xl shadow-inner relative z-10 mx-auto w-64 h-64 flex items-center justify-center mb-6">
                        <!-- Placeholder for QR Code, replace src with actual QR image or dynamic generated QR -->
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=EliteBarberQRIS&color=3d2b1a" alt="QRIS Code" class="w-full h-full object-contain">
                        <div class="absolute inset-0 border-4 border-amber-400/50 rounded-xl pointer-events-none"></div>
                        <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-amber-500 rounded-tl-xl pointer-events-none"></div>
                        <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-amber-500 rounded-tr-xl pointer-events-none"></div>
                        <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-amber-500 rounded-bl-xl pointer-events-none"></div>
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-amber-500 rounded-br-xl pointer-events-none"></div>
                    </div>

                    <div class="text-center relative z-10 bg-zinc-900/50 rounded-xl p-4 border border-white/5">
                        <p class="text-xs text-zinc-400 uppercase tracking-widest font-semibold mb-1">Merchant</p>
                        <p class="text-lg text-amber-400 font-bold mb-3">ELITE BARBER</p>
                        <p class="text-sm text-zinc-300">NMID: ID10203040506070809</p>
                    </div>
                </div>
            </div>
            </section> <!-- End QRIS -->

            <section id="tab-dashboard" class="tab-content <?= $is_dashboard ? 'active' : '' ?>">
            <!-- BERANDA (DASHBOARD HOME VIEW) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Box Sedang Dilayani -->
                <div class="lg:col-span-1 bg-[#1E1B18] rounded-xl border border-white/10 shadow-xl overflow-hidden flex flex-col justify-between">
                    <div class="px-6 py-4 border-b border-amber-900/30 bg-[#16120c] flex items-center justify-between">
                        <h3 class="font-bold text-[#e8d5a3] text-base tracking-wide flex items-center gap-2">
                            <i data-lucide="scissors" class="w-5 h-5 text-amber-400"></i>
                            Sedang Dilayani
                        </h3>

                    </div>
                    <div class="p-6 text-center flex flex-col items-center justify-center my-auto">
                        <?php if ($current_serving): ?>
                            <div class="w-full bg-amber-500/5 border border-amber-500/20 rounded-2xl p-6 mb-4 relative overflow-hidden">
                                <p class="text-xs uppercase font-medium text-amber-400/70 tracking-widest mb-1">Nomor Antrean</p>
                                <div class="text-5xl font-black text-amber-400 font-mono tracking-wider mb-2 drop-shadow-[0_0_15px_rgba(245,158,11,0.4)]">
                                    <?= htmlspecialchars($current_serving['ticket_number']) ?>
                                </div>
                                <p class="text-base text-white font-bold truncate max-w-[200px] mx-auto">
                                    <?= htmlspecialchars($current_serving['customer_name']) ?>
                                </p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                <i data-lucide="armchair" class="w-3.5 h-3.5"></i>
                                Kursi <?= htmlspecialchars(substr($current_serving['ticket_number'], 0, 1)) ?>
                            </span>
                        <?php else: ?>
                            <div class="w-14 h-14 rounded-full bg-zinc-800/80 border border-white/10 flex items-center justify-center mb-3 text-zinc-500">
                                <i data-lucide="users" class="w-7 h-7 opacity-40"></i>
                            </div>
                            <p class="text-zinc-400 font-medium text-sm">Belum ada antrean dilayani</p>
                            <p class="text-xs text-zinc-600 mt-1">Antrean berikutnya akan muncul di sini</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Aksi Antrean Pelanggan / Form Status -->
                <div class="lg:col-span-2">
                    <div class="bg-[#1E1B18] rounded-xl border border-white/10 shadow-xl overflow-hidden h-full flex flex-col">
                        <?php if ($my_queue && $my_queue['status_antrean'] === 'payment'): 
                            $base = (float)($my_queue['harga'] ?? 0);
                            $final_price = $base;
                        ?>
                            <div class="px-6 py-4 border-b border-amber-900/30 bg-[#16120c] flex items-center justify-between">
                                <h3 class="font-bold text-[#e8d5a3] text-base tracking-wide flex items-center gap-2">
                                    <i data-lucide="wallet" class="w-5 h-5 text-amber-400"></i>
                                    Pembayaran Layanan
                                </h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/15 text-blue-400 border border-blue-500/30 uppercase">
                                    Tagihan Aktif
                                </span>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between">
                                <div class="bg-emerald-500/10 border border-emerald-500/25 p-4 rounded-xl mb-4 text-zinc-200">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs text-zinc-400">Nomor Tiket:</span>
                                        <strong class="text-amber-400 font-mono font-bold text-lg"><?= htmlspecialchars($my_queue['no_antrean']) ?></strong>
                                    </div>
                                    <div class="flex justify-between items-center mb-2 text-sm">
                                        <span class="text-zinc-400">Layanan:</span>
                                        <span class="text-white font-semibold"><?= htmlspecialchars($my_queue['nama_layanan']) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center pt-2 border-t border-emerald-500/20">
                                        <span class="text-xs text-zinc-300 font-medium">Total Tagihan:</span>
                                        <strong class="text-xl text-emerald-400 font-bold">Rp <?= number_format($final_price, 0, ',', '.') ?></strong>
                                    </div>
                                </div>
                                <form action="dashboard.php" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="pay_ticket">
                                    <input type="hidden" name="antrian_id" value="<?= $my_queue['id'] ?>">
                                    <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
                                        <select name="metode_pembayaran" id="metode_pembayaran" class="w-full bg-zinc-900/90 border border-white/10 rounded-xl px-4 py-3 text-white text-base focus:outline-none focus:border-amber-500" required onchange="togglePaymentInfo()">
                                            <option value="">-- Pilih Metode Pembayaran --</option>
                                            <option value="QRIS">QRIS</option>
                                            <option value="Cash">Cash (Tunai)</option>
                                            <option value="Transfer Bank">Transfer Bank</option>
                                        </select>
                                    </div>

                                    <div id="info_qris" class="hidden bg-zinc-900/80 border border-white/10 p-4 rounded-xl text-center mt-3">
                                        <p class="font-bold text-white mb-2 text-sm">Scan QRIS Elite Barber</p>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="QRIS" class="w-32 h-32 mx-auto bg-white p-1.5 rounded-lg shadow-md">
                                        <p class="text-xs text-zinc-400 mt-2">Pastikan nama penerima adalah <strong>Elite Barber</strong></p>
                                    </div>

                                    <div id="info_transfer" class="hidden bg-zinc-900/80 border border-white/10 p-4 rounded-xl mt-3 text-zinc-200">
                                        <p class="font-bold mb-1 text-white text-sm">Transfer Bank BCA</p>
                                        <p class="text-xl font-mono mb-1 tracking-wider text-amber-400 font-bold">1234567890</p>
                                        <p class="text-xs text-zinc-300">a.n. Elite Barber</p>
                                    </div>

                                    <div id="info_cash" class="hidden bg-zinc-900/80 p-4 rounded-xl mt-3 text-center border border-dashed border-white/20 text-zinc-300 text-xs">
                                        Silakan serahkan uang tunai langsung ke kasir atau Barber Anda.
                                    </div>

                                    <script>
                                        function togglePaymentInfo() {
                                            var val = document.getElementById('metode_pembayaran').value;
                                            document.getElementById('info_qris').style.display = (val === 'QRIS') ? 'block' : 'none';
                                            document.getElementById('info_transfer').style.display = (val === 'Transfer Bank') ? 'block' : 'none';
                                            document.getElementById('info_cash').style.display = (val === 'Cash') ? 'block' : 'none';
                                        }
                                    </script>

                                    <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-bold py-3.5 px-6 rounded-xl transition-all shadow-lg border border-emerald-400/30 flex items-center justify-center gap-2 min-h-[48px] text-base active:scale-98">
                                        <i data-lucide="banknote" class="w-5 h-5"></i> Konfirmasi Bayar Sekarang
                                    </button>
                                </form>
                            </div>

                        <?php elseif ($my_queue && $my_queue['status_antrean'] === 'paid'): ?>
                            <div class="px-6 py-4 border-b border-amber-900/30 bg-[#16120c] flex items-center justify-between">
                                <h3 class="font-bold text-[#e8d5a3] text-base tracking-wide flex items-center gap-2">
                                    <i data-lucide="printer" class="w-5 h-5 text-amber-400"></i>
                                    Menunggu Struk
                                </h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/15 text-blue-400 border border-blue-500/30 uppercase">
                                    Lunas
                                </span>
                            </div>
                            <div class="p-8 text-center text-zinc-400 my-auto flex flex-col items-center">
                                <div class="w-14 h-14 rounded-full bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mb-3">
                                    <i data-lucide="printer" class="w-7 h-7 text-blue-400"></i>
                                </div>
                                <p class="text-white font-semibold mb-1">Pembayaran Berhasil!</p>
                                <p class="text-xs text-zinc-400">Menunggu Barber mencetak struk transaksi Anda...</p>
                            </div>

                        <?php elseif ($my_queue && $my_queue['status_antrean'] === 'review'): ?>
                            <div class="px-6 py-4 border-b border-amber-900/30 bg-[#16120c] flex items-center justify-between">
                                <h3 class="font-bold text-[#e8d5a3] text-base tracking-wide flex items-center gap-2">
                                    <i data-lucide="star" class="w-5 h-5 text-amber-400"></i>
                                    Berikan Ulasan Anda
                                </h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 uppercase">
                                    Selesai
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="text-center text-zinc-300 text-sm mb-4">Bagaimana pengalaman cukur Anda hari ini?</p>
                                <form action="dashboard.php" method="POST" class="max-w-md mx-auto space-y-4">
                                    <input type="hidden" name="action" value="submit_review">
                                    <input type="hidden" name="antrian_id" value="<?= $my_queue['id'] ?>">
                                    
                                    <style>
                                        .rating-stars { display: flex; flex-direction: row-reverse; justify-content: center; gap: 8px; font-size: 32px; }
                                        .rating-stars input { display: none; }
                                        .rating-stars label { color: #444; cursor: pointer; transition: color 0.2s; }
                                        .rating-stars input:checked ~ label,
                                        .rating-stars label:hover,
                                        .rating-stars label:hover ~ label { color: #F59E0B; }
                                    </style>
                                    <div class="rating-stars mb-3">
                                        <input type="radio" id="star5" name="rating" value="5" required /><label for="star5">★</label>
                                        <input type="radio" id="star4" name="rating" value="4" /><label for="star4">★</label>
                                        <input type="radio" id="star3" name="rating" value="3" /><label for="star3">★</label>
                                        <input type="radio" id="star2" name="rating" value="2" /><label for="star2">★</label>
                                        <input type="radio" id="star1" name="rating" value="1" /><label for="star1">★</label>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-1.5">Komentar (Opsional)</label>
                                        <textarea name="komentar" class="w-full bg-zinc-900/90 border border-white/10 rounded-xl px-4 py-2.5 text-white text-base focus:outline-none focus:border-amber-500 resize-none" rows="3" placeholder="Tulis masukan atau kesan Anda..."></textarea>
                                    </div>
                                    <button type="submit" class="w-full bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-white font-bold py-3.5 px-6 rounded-xl transition-all shadow-lg border border-amber-400/30 flex items-center justify-center gap-2 min-h-[48px] text-base active:scale-98">
                                        <i data-lucide="send" class="w-5 h-5"></i> Kirim Ulasan
                                    </button>
                                </form>
                            </div>

                        <?php elseif ($my_queue && in_array($my_queue['status_antrean'], ['waiting', 'serving'])): ?>
                            <div class="px-6 py-4 border-b border-amber-900/30 bg-[#16120c] flex items-center justify-between">
                                <h3 class="font-bold text-[#e8d5a3] text-base tracking-wide flex items-center gap-2">
                                    <i data-lucide="ticket" class="w-5 h-5 text-amber-400"></i>
                                    Status Antrean Anda
                                </h3>
                                <?php if ($my_queue['status_antrean'] === 'serving'): ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/35 flex items-center gap-1.5 uppercase">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Melayani
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/35 flex items-center gap-1.5 uppercase">
                                        <span class="w-2 h-2 rounded-full bg-amber-400"></span> Menunggu
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="p-6 text-center my-auto">
                                <div class="bg-amber-500/5 border border-amber-500/20 rounded-2xl p-6 max-w-sm mx-auto shadow-inner">
                                    <p class="text-xs uppercase font-medium text-amber-400/70 tracking-widest mb-1">Nomor Tiket Anda</p>
                                    <p class="text-5xl font-black text-amber-400 font-mono tracking-wider mb-4 drop-shadow-[0_0_15px_rgba(245,158,11,0.4)]"><?= htmlspecialchars($my_queue['no_antrean']) ?></p>
                                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-zinc-800 border border-white/10 text-zinc-300">
                                        <span>Status:</span>
                                        <strong class="<?= $my_queue['status_antrean'] === 'serving' ? 'text-emerald-400' : 'text-amber-400' ?> uppercase">
                                            <?= $my_queue['status_antrean'] === 'serving' ? 'Sedang Dilayani' : 'Menunggu Giliran' ?>
                                        </strong>
                                    </div>
                                    <?php if(!empty($my_queue['barber_nama'])): ?>
                                    <div class="mt-4 pt-4 border-t border-white/10 flex justify-between items-center text-xs text-zinc-300">
                                        <span class="text-zinc-400">Barber:</span>
                                        <span class="font-bold text-white"><?= htmlspecialchars($my_queue['barber_nama']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" action="" class="mt-4" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan antrean Anda?');">
                                    <input type="hidden" name="action" value="cancel_my_ticket">
                                    <input type="hidden" name="antrian_id" value="<?= $my_queue['id'] ?>">
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 text-rose-300 border border-rose-500/30 text-xs font-semibold transition-all inline-flex items-center gap-1.5 shadow-md">
                                        <i data-lucide="x-circle" class="w-4 h-4 text-rose-400"></i> Batalkan / Hapus Antrean Saya
                                    </button>
                                </form>
                                <p class="text-xs text-zinc-400 mt-3 max-w-md mx-auto">
                                    Silakan tunggu giliran Anda dipanggil. Tiket baru dapat diambil kembali setelah giliran Anda selesai.
                                </p>
                            </div>

                        <?php else: ?>
                            <!-- Form Ambil Tiket Baru -->
                            <div class="px-6 py-4 border-b border-amber-900/30 bg-[#16120c] flex items-center justify-between">
                                <h3 class="font-bold text-[#e8d5a3] text-base tracking-wide flex items-center gap-2">
                                    <i data-lucide="ticket" class="w-5 h-5 text-amber-400"></i>
                                    Ambil Antrean Baru
                                </h3>

                            </div>
                            <div class="p-6">
                                <form action="dashboard.php" method="POST" class="space-y-4 max-w-md mx-auto">
                                    <input type="hidden" name="action" value="take_ticket">
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-1.5">Pelanggan</label>
                                        <input type="text" value="<?= htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']) ?>" readonly class="w-full bg-zinc-900/80 border border-white/10 rounded-xl px-4 py-3 text-zinc-300 font-medium cursor-not-allowed text-base">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-1.5">Layanan Terpilih</label>
                                        <select name="service_id" id="main_service_select" class="w-full bg-zinc-900/80 border border-white/10 rounded-xl px-4 py-3 text-white font-semibold focus:outline-none pointer-events-none appearance-none cursor-not-allowed text-base" required tabindex="-1">
                                            <option value="" disabled <?= empty($_GET['service_id']) ? 'selected' : '' ?>>-- Pilih dari Menu Layanan --</option>
                                            <?php foreach ($services as $s): 
                                                $s_id = $s['id'] ?? $s['id_service'];
                                                $is_selected = (isset($_GET['service_id']) && $_GET['service_id'] == $s_id) ? 'selected' : '';
                                            ?>
                                                <option value="<?= $s_id ?>" <?= $is_selected ?>>
                                                    <?= htmlspecialchars($s['service_name']) ?> - Rp <?= number_format($s['price'], 0, ',', '.') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="text-[11px] text-amber-400/80 mt-1 flex items-center gap-1">
                                            <i data-lucide="info" class="w-3.5 h-3.5"></i> Pilih layanan melalui menu <a href="javascript:void(0)" onclick="navigateToTab('tab-layanan')" class="underline hover:text-amber-300">Layanan</a>
                                        </p>
                                    </div>
                                    <button type="submit" <?= empty($_GET['service_id']) ? 'disabled' : '' ?> class="w-full bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-white font-bold py-3.5 px-6 rounded-xl transition-all shadow-lg border border-amber-400/30 flex items-center justify-center gap-2 min-h-[48px] text-base active:scale-98 mt-4 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-amber-600 disabled:hover:to-amber-500">
                                        <i data-lucide="scissors" class="w-5 h-5"></i> AMBIL TIKET ANTREAN
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tabel Daftar Antrean Aktif Hari Ini -->
            <div class="bg-[#1E1B18] rounded-xl border border-white/10 shadow-xl overflow-hidden mt-6">
                <div class="px-6 py-4 border-b border-amber-900/30 bg-[#16120c] flex items-center justify-between flex-wrap gap-2">
                    <h3 class="font-bold text-[#e8d5a3] text-base tracking-wide flex items-center gap-2">
                        <i data-lucide="list-ordered" class="w-5 h-5 text-amber-400"></i>
                        Daftar Antrean Aktif Hari Ini
                    </h3>

                </div>
                <!-- Desktop Table View (hidden on mobile, visible md+) -->
                <div class="hidden md:block overflow-x-auto custom-scroll">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-zinc-900/70 text-zinc-400 text-xs uppercase tracking-wider border-b border-white/10">
                                <th class="px-6 py-4 font-semibold">No. Tiket</th>
                                <th class="px-6 py-4 font-semibold">Nama Pelanggan</th>
                                <th class="px-6 py-4 font-semibold">Layanan</th>
                                <th class="px-6 py-4 font-semibold">Kursi / Barber</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold">Est. Tunggu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($active_queues)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-zinc-400 text-sm">Belum ada antrean aktif saat ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($active_queues as $q): 
                                    $is_my_row = (!empty($my_user_id) && isset($q['pelanggan_id']) && (int)$q['pelanggan_id'] === (int)$my_user_id);
                                    $row_bg = $is_my_row ? 'bg-amber-500/10 border-l-4 border-amber-500' : 'hover:bg-amber-900/15 transition-colors';
                                ?>
                                    <tr class="<?= $row_bg ?>">
                                        <td class="px-6 py-4">
                                            <span class="text-amber-400 font-mono font-bold text-lg tracking-wide"><?= htmlspecialchars($q['ticket_number']) ?></span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-white">
                                            <?= htmlspecialchars($q['customer_name']) ?>
                                            <?php if ($is_my_row): ?>
                                                <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-500 text-black uppercase tracking-wider">Anda</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <div class="text-zinc-200 font-medium"><?= htmlspecialchars($q['service_name'] ?? 'Standard Cut') ?></div>
                                            <?php 
                                                $base = (float)($q['base_price'] ?? 0);
                                                echo "<div class='text-emerald-400 mt-0.5 font-semibold text-xs'>Rp " . number_format($base, 0, ',', '.') . "</div>";
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-zinc-300 text-sm">
                                            Kursi <?= htmlspecialchars(substr($q['ticket_number'], 0, 1)) ?>
                                            <?php if (!empty($q['barber_nama'])): ?>
                                                <span class="text-xs text-zinc-400 block mt-0.5">(<?= htmlspecialchars($q['barber_nama']) ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($q['status'] === 'serving'): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/35 flex items-center gap-1.5 w-fit uppercase">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Melayani
                                                </span>
                                            <?php elseif (in_array($q['status'], ['payment', 'paid'])): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/15 text-blue-400 border border-blue-500/35 flex items-center gap-1.5 w-fit uppercase">
                                                    <span class="w-2 h-2 rounded-full bg-blue-400"></span> Pembayaran
                                                </span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/35 flex items-center gap-1.5 w-fit uppercase">
                                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span> Menunggu
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-zinc-400 text-sm font-mono">
                                            <?= (int)$q['estimated_wait_min'] ?> Menit
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Vertical Card Stack (No Horizontal Scrolling, Stacks Downwards Vertically) -->
                <div class="block md:hidden p-4 space-y-3">
                    <?php if (empty($active_queues)): ?>
                        <div class="text-center text-zinc-400 py-8 text-sm">Belum ada antrean aktif saat ini.</div>
                    <?php else: ?>
                        <?php foreach ($active_queues as $q): 
                            $is_my_row = (!empty($my_user_id) && isset($q['pelanggan_id']) && (int)$q['pelanggan_id'] === (int)$my_user_id);
                            $card_border = $is_my_row ? 'border-2 border-amber-500 bg-amber-500/10 shadow-[0_0_20px_rgba(245,158,11,0.25)]' : 'border border-white/10 bg-zinc-900/60 hover:border-amber-500/30';
                        ?>
                        <div class="p-4 rounded-xl <?= $card_border ?> transition-all flex flex-col gap-3">
                            <!-- Top Row: Ticket Number & Status -->
                            <div class="flex justify-between items-center border-b border-white/5 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-amber-400 font-mono font-black text-xl tracking-wider"><?= htmlspecialchars($q['ticket_number']) ?></span>
                                    <?php if ($is_my_row): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-500 text-black uppercase tracking-wider">Anda</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($q['status'] === 'serving'): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/35 flex items-center gap-1.5 uppercase">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Melayani
                                    </span>
                                <?php elseif (in_array($q['status'], ['payment', 'paid'])): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-500/15 text-blue-400 border border-blue-500/35 flex items-center gap-1.5 uppercase">
                                        <span class="w-2 h-2 rounded-full bg-blue-400"></span> Pembayaran
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/35 flex items-center gap-1.5 uppercase">
                                        <span class="w-2 h-2 rounded-full bg-amber-400"></span> Menunggu
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Middle Row: Customer Name & Service -->
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-[11px] text-zinc-400 uppercase font-medium block">Pelanggan</span>
                                    <span class="font-bold text-white"><?= htmlspecialchars($q['customer_name']) ?></span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-zinc-400 uppercase font-medium block">Layanan & Harga</span>
                                    <span class="font-semibold text-zinc-200"><?= htmlspecialchars($q['service_name'] ?? 'Standard Cut') ?></span>
                                    <span class="text-xs text-emerald-400 font-bold block">Rp <?= number_format((float)($q['base_price'] ?? 0), 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <!-- Bottom Row: Chair / Barber & Estimated Wait Time -->
                            <div class="flex justify-between items-center text-xs text-zinc-400 pt-2 border-t border-white/5">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="scissors" class="w-3.5 h-3.5 text-amber-400"></i>
                                    <span>Kursi <?= htmlspecialchars(substr($q['ticket_number'], 0, 1)) ?></span>
                                    <?php if (!empty($q['barber_nama'])): ?>
                                        <span class="text-zinc-300 font-medium">(<?= htmlspecialchars($q['barber_nama']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-1 text-amber-300/90 font-mono">
                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                    <span><?= (int)$q['estimated_wait_min'] ?> Menit Est.</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            </section> <!-- End Dashboard -->
        </main>
    </div>


<script>
        lucide.createIcons();

        // Real-time Clock
        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            const dayName = days[now.getDay()];
            const day = String(now.getDate()).padStart(2, '0');
            const month = months[now.getMonth()];
            const year = now.getFullYear();
            
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            const clockString = `${dayName}, ${day} ${month} ${year} | ${hours}:${minutes}:${seconds}`;
            const clockEl = document.getElementById('realtime-clock');
            if (clockEl) {
                clockEl.textContent = clockString;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Sidebar Toggle with Smooth State Persistence
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');

        function applySidebarState(isMinimized) {
            const fouc = document.getElementById('fouc-style');
            if (fouc) fouc.remove();
            
            if (isMinimized) {
                sidebar.classList.remove('w-64'); 
                sidebar.classList.add('w-20');
            } else {
                sidebar.classList.remove('w-20'); 
                sidebar.classList.add('w-64');
            }
        }

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                if (window.innerWidth < 768) {
                    sidebar.classList.toggle('open-mobile');
                } else {
                    const willMinimize = sidebar.classList.contains('w-64');
                    localStorage.setItem('sidebarMinimized', willMinimize);
                    applySidebarState(willMinimize);
                }
            });

            document.addEventListener('click', (e) => {
                if (window.innerWidth < 768 && sidebar.classList.contains('open-mobile') && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('open-mobile');
                }
            });
        }
    </script>
    <style>
        .nav-item { color: #78716c; }
        .nav-item:hover { color: #fcd34d; }
        .nav-item.active { color: #F59E0B; }
        .nav-item .solid-icon { display: none; }
        .nav-item .active-pulse { display: none; }
        .nav-item.active .solid-icon { display: block; }
        .nav-item.active .active-pulse { display: block; }
        .nav-item.active .outline-icon { display: none; }
        
        .nav-item.active .nav-label { color: #F59E0B; }
        
        /* Profile Image Styles */
        .nav-item.active .profile-img { border-color: #F59E0B; box-shadow: 0 0 12px rgba(245,158,11,0.7); opacity: 1; }
        .nav-item:not(.active) .profile-img { border-color: #57534e; opacity: 0.8; }
    </style>

    <!-- Mobile Fixed Bottom Navigation Bar — SPA Nav -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-black backdrop-blur-xl border-t border-amber-900/40 flex justify-around items-center shadow-[0_-8px_30px_rgba(0,0,0,0.85)]"
         style="padding-bottom: env(safe-area-inset-bottom, 8px); padding-top: 8px;">

        <!-- Beranda -->
        <a href="javascript:void(0)" onclick="switchTab('tab-dashboard', this)" class="nav-item flex flex-col items-center gap-0.5 py-1 px-2 min-w-[60px] rounded-xl transition-colors duration-200 relative <?= $is_dashboard ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 drop-shadow-[0_0_8px_rgba(245,158,11,0.6)]">
                <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z"/>
                <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z"/>
            </svg>
            <!-- Outline (Inactive) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            <span class="active-pulse w-1.5 h-1.5 rounded-full bg-[#F59E0B] shadow-[0_0_8px_#F59E0B] animate-pulse absolute top-1 right-3"></span>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Beranda</span>
        </a>

        <!-- Layanan -->
        <a href="javascript:void(0)" onclick="switchTab('tab-layanan', this)" class="nav-item flex flex-col items-center gap-0.5 py-1 px-2 min-w-[60px] rounded-xl transition-colors duration-200 relative <?= $is_layanan ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 transform -rotate-45 drop-shadow-[0_0_8px_rgba(245,158,11,0.6)]">
                <path d="M9.64 7.64c.23-.5.36-1.05.36-1.64 0-2.21-1.79-4-4-4S2 3.79 2 6s1.79 4 4 4c.59 0 1.14-.13 1.64-.36L10 12l-2.36 2.36C7.14 14.13 6.59 14 6 14c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4c0-.59-.13-1.14-.36-1.64L12 14l7 7h3v-1L9.64 7.64zm-3.64 12c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm0-10c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM19 3l-6 6 2 2 7-7V3h-3z"/>
            </svg>
            <!-- Outline (Inactive) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="outline-icon w-6 h-6 transform -rotate-45">
                <circle cx="6" cy="6" r="3"></circle>
                <circle cx="6" cy="18" r="3"></circle>
                <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
                <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
            </svg>
            <span class="active-pulse w-1.5 h-1.5 rounded-full bg-[#F59E0B] shadow-[0_0_8px_#F59E0B] animate-pulse absolute top-1 right-3"></span>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Layanan</span>
        </a>

        <!-- Scan QRIS -->
        <a href="javascript:void(0)" onclick="switchTab('tab-qris', this)" class="nav-item <?= $is_qris ? 'active' : '' ?> relative -top-5 flex flex-col items-center group">
            <div class="flex items-center justify-center w-14 h-14 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full shadow-[0_4px_15px_rgba(245,158,11,0.5)] border-4 border-[#140f09] transition-transform duration-200 group-hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="w-7 h-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                </svg>
            </div>
            <span class="nav-label absolute -bottom-5 text-[10px] font-semibold text-amber-500 whitespace-nowrap">Scan QRIS</span>
        </a>

        <!-- Riwayat -->
        <a href="javascript:void(0)" onclick="switchTab('tab-riwayat', this)" class="nav-item flex flex-col items-center gap-0.5 py-1 px-2 min-w-[60px] rounded-xl transition-colors duration-200 relative <?= $is_riwayat ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 drop-shadow-[0_0_8px_rgba(245,158,11,0.6)]">
                <path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75-6.75a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd"/>
                <path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z"/>
            </svg>
            <!-- Outline (Inactive) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <span class="active-pulse w-1.5 h-1.5 rounded-full bg-[#F59E0B] shadow-[0_0_8px_#F59E0B] animate-pulse absolute top-1 right-3"></span>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Riwayat</span>
        </a>

        <!-- Profil -->
        <a href="javascript:void(0)" onclick="switchTab('tab-profil', this)" class="nav-item flex flex-col items-center gap-0.5 py-1 px-2 min-w-[60px] rounded-xl transition-colors duration-200 relative <?= $is_profil ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <?php
                $bn_avatar_name = !empty($curr_fn) ? urlencode($curr_fn) : urlencode($curr_un ?? 'User');
                $bn_profile_files = glob(__DIR__ . '/../asset/image/profile_' . $my_user_id . '.*');
                $bn_profile_url = !empty($bn_profile_files)
                    ? '../asset/image/' . basename($bn_profile_files[0]) . '?v=' . filemtime($bn_profile_files[0])
                    : "https://ui-avatars.com/api/?name={$bn_avatar_name}&background=3d2b1a&color=F59E0B&size=64&bold=true";
            ?>
            <img src="<?= $bn_profile_url ?>" alt="Foto Profil" class="profile-img w-7 h-7 rounded-full object-cover border-2 transition-all">
            <span class="active-pulse w-1.5 h-1.5 rounded-full bg-[#F59E0B] shadow-[0_0_8px_#F59E0B] animate-pulse absolute top-0 right-3"></span>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Profil</span>
        </a>
    </nav>

    <script>
        function switchTab(targetTabId, navElement) {
            const currentTab = document.querySelector('.tab-content.active');
            const targetTab = document.getElementById(targetTabId);

            if (currentTab && currentTab.id === targetTabId) return;

            updateNavState(navElement);

            if (document.startViewTransition) {
                document.startViewTransition(() => {
                    executeDOMSwitch(currentTab, targetTab);
                });
            } else {
                executeFallbackSwitch(currentTab, targetTab);
            }
        }

        function executeDOMSwitch(currentTab, targetTab) {
            if (currentTab) currentTab.classList.remove('active');
            if (targetTab) {
                targetTab.classList.add('active');
                // Scroll to top of main element
                const mainArea = document.querySelector('main');
                if(mainArea) mainArea.scrollTop = 0;
            }
        }

        function executeFallbackSwitch(currentTab, targetTab) {
            if (currentTab) {
                currentTab.classList.add('fallback-anim-out');
                setTimeout(() => {
                    currentTab.classList.remove('active', 'fallback-anim-out');
                    if (targetTab) {
                        targetTab.classList.add('active', 'fallback-anim-in');
                        const mainArea = document.querySelector('main');
                        if(mainArea) mainArea.scrollTop = 0;
                        setTimeout(() => targetTab.classList.remove('fallback-anim-in'), 220);
                    }
                }, 180);
            } else {
                executeDOMSwitch(currentTab, targetTab);
            }
        }

        function updateNavState(activeNav) {
            if (!activeNav) return;
            // Update bottom nav items
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            activeNav.classList.add('active');

            // Find the target tab id from the onclick attribute
            const onclickAttr = activeNav.getAttribute('onclick');
            const match = onclickAttr ? onclickAttr.match(/switchTab\('([^']+)'/) || onclickAttr.match(/navigateToTab\('([^']+)'/) : null;
            if (match && match[1]) {
                const targetTabId = match[1];
                // Update sidebar items
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    item.classList.remove('bg-adminlte-primary', 'text-amber-200');
                    item.classList.add('text-stone-400');
                });
                const sidebarLink = document.querySelector(`.sidebar-item[onclick*="${targetTabId}"]`);
                if (sidebarLink) {
                    sidebarLink.classList.remove('text-stone-400');
                    sidebarLink.classList.add('bg-adminlte-primary', 'text-amber-200');
                }
            }
        }
        
        // Expose a helper to switch from any link (like the desktop sidebar)
        window.navigateToTab = function(tabId) {
            const navLink = document.querySelector(`.nav-item[onclick*="${tabId}"]`);
            if (navLink) switchTab(tabId, navLink);
        };

        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.setAttribute('data-lucide', 'eye-off');
                } else {
                    input.type = 'password';
                    icon.setAttribute('data-lucide', 'eye');
                }
                if (window.lucide) lucide.createIcons();
            }
        }

        const profPassInput = document.getElementById('new_pass_input');
        if (profPassInput) {
            profPassInput.addEventListener('input', function() {
                const val = this.value;
                const rLen = document.getElementById('prof_rule_len');
                const rCase = document.getElementById('prof_rule_case');
                const rNum = document.getElementById('prof_rule_num');
                const rSym = document.getElementById('prof_rule_sym');

                if (rLen) rLen.className = val.length >= 6 ? 'flex items-center gap-1.5 text-emerald-400 font-medium' : 'flex items-center gap-1.5 text-zinc-400';
                if (rCase) rCase.className = (/[A-Z]/.test(val) && /[a-z]/.test(val)) ? 'flex items-center gap-1.5 text-emerald-400 font-medium' : 'flex items-center gap-1.5 text-zinc-400';
                if (rNum) rNum.className = /[0-9]/.test(val) ? 'flex items-center gap-1.5 text-emerald-400 font-medium' : 'flex items-center gap-1.5 text-zinc-400';
                if (rSym) rSym.className = /[\W_]/.test(val) ? 'flex items-center gap-1.5 text-emerald-400 font-medium' : 'flex items-center gap-1.5 text-zinc-400';
            });
        }
    </script>
</body>
</html>
