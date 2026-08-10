<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helper.php';

$bookNowUrl = 'auth/login.php';
if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'pelanggan';
    if ($role === 'admin') {
        $bookNowUrl = 'petugas/admin.php';
    } elseif ($role === 'barber') {
        $bookNowUrl = 'petugas/barber.php';
    } else {
        $bookNowUrl = 'pelanggan/dashboard.php';
    }
}
$stmt_ulasan = $pdo->query("SELECT u.*, us.username FROM ulasan u JOIN users us ON u.pelanggan_id = us.id_user ORDER BY u.waktu DESC LIMIT 6");
$ulasan_list = $stmt_ulasan->fetchAll(PDO::FETCH_ASSOC);

$stmt_queue = $pdo->prepare("SELECT no_antrean FROM antrian WHERE status_antrean = 'serving' AND DATE(waktu_dibuat) = CURDATE() ORDER BY id ASC LIMIT 1");
$stmt_queue->execute();
$current_queue = $stmt_queue->fetch(PDO::FETCH_ASSOC);
$display_queue = $current_queue ? $current_queue['no_antrean'] : 'Kosong';

// Jumlah yang masih menunggu hari ini
$stmt_waiting = $pdo->prepare("SELECT COUNT(*) as total FROM antrian WHERE status_antrean = 'waiting' AND DATE(waktu_dibuat) = CURDATE()");
$stmt_waiting->execute();
$waiting_count = $stmt_waiting->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Antrian berikutnya
$stmt_next = $pdo->prepare("SELECT no_antrean FROM antrian WHERE status_antrean = 'waiting' AND DATE(waktu_dibuat) = CURDATE() ORDER BY id ASC LIMIT 1");
$stmt_next->execute();
$next_queue = $stmt_next->fetch(PDO::FETCH_ASSOC);
$display_next = $next_queue ? $next_queue['no_antrean'] : 'Kosong';

$stmt_layanan = $pdo->query("SELECT * FROM layanan ORDER BY harga DESC");
$all_layanan = $stmt_layanan->fetchAll(PDO::FETCH_ASSOC);

$paket_utama = [];
$layanan_tambahan = [];
foreach ($all_layanan as $l) {
    if (!empty(trim($l['deskripsi'] ?? ''))) {
        $paket_utama[] = $l;
    } else {
        $layanan_tambahan[] = $l;
    }
}

// Antrean per kursi hari ini
$stmt_chairs = $pdo->query("
    SELECT b.id, b.nama, b.kursi, b.status,
        MAX(CASE WHEN a.status_antrean='serving' THEN a.no_antrean ELSE NULL END) AS current_no,
        SUM(CASE WHEN a.status_antrean='waiting' THEN 1 ELSE 0 END) AS waiting_count,
        SUM(CASE WHEN a.status_antrean IN ('serving','waiting') THEN 1 ELSE 0 END) AS active_count
    FROM barber b
    LEFT JOIN antrian a ON a.barber_id = b.id AND DATE(a.waktu_dibuat) = CURDATE()
    WHERE b.status = 'Aktif'
    GROUP BY b.id, b.nama, b.kursi, b.status
    ORDER BY b.kursi
");
$chairs_data = $stmt_chairs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELITE BARBER | Barbershop Kelas Atas</title>
    <!-- Google Fonts for Professional Look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        gold: {
                            DEFAULT: '#d4af37',
                            50: '#fbf8f0',
                            100: '#f5efda',
                            200: '#eadab4',
                            300: '#dec286',
                            400: '#d2a758',
                            500: '#c68e37',
                            600: '#a9702b',
                            700: '#8c5525',
                            800: '#734424',
                            900: '#5e3720',
                        }
                    },
                    animation: {
                        "infinite-slider": "infinite-slider 40s linear infinite",
                    },
                    keyframes: {
                        "infinite-slider": {
                            "0%": { transform: "translateX(0)" },
                            "100%": { transform: "translateX(calc(-50% - 56px))" }
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Slider blur effects */
        .blur-left {
            background: linear-gradient(to right, #09090b, transparent);
        }
        .blur-right {
            background: linear-gradient(to left, #09090b, transparent);
        }
        
        .nav-active {
            background: rgba(9, 9, 11, 0.95);
            backdrop-filter: blur(16px);
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col overflow-x-hidden bg-black text-zinc-200">
    <div class="fixed inset-0 z-[-1] bg-gradient-to-br from-black via-[#0a0a0a] to-[#3e2723] pointer-events-none"></div>

    <!-- Header / Navbar -->
    <header class="fixed top-0 left-0 w-full z-50 transition-all duration-300 bg-zinc-950/95 backdrop-blur-md border-b border-zinc-800" id="navbar">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <a href="index.php" class="flex items-center space-x-2 font-bold text-xl tracking-tight text-white">
                    <span class="text-gold"><i data-lucide="scissors" class="w-6 h-6"></i></span>
                    <span>ELITE BARBER</span>
                </a>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="block lg:hidden text-zinc-300 hover:text-white p-2">
                    <i data-lucide="menu" id="menu-icon"></i>
                    <i data-lucide="x" id="close-icon" class="hidden"></i>
                </button>

                <!-- Desktop Links -->
                <div class="hidden lg:flex items-center gap-8 text-sm font-medium">
                    <a href="#services" class="text-zinc-300 hover:text-white transition-colors">Ulasan</a>
                    <a href="#gallery" class="text-zinc-300 hover:text-white transition-colors">Layanan</a>
                    <a href="#pricing" class="text-zinc-300 hover:text-white transition-colors flex items-center gap-2">
                        Antrian 
                        <span class="bg-gold/20 text-gold px-2 py-0.5 rounded-full text-xs font-bold border border-gold/30">
                            <?= htmlspecialchars($display_queue) ?>
                        </span>
                    </a>
                    <a href="#about" class="text-zinc-300 hover:text-white transition-colors">About</a>
                </div>

                <!-- Action Buttons -->
                <div class="hidden lg:flex items-center gap-4">
                    <?php if (is_logged_in()): ?>
                        <div class="flex items-center gap-2 text-sm text-zinc-300 font-medium">
                            <i data-lucide="user" class="w-4 h-4"></i>
                            <span><?= htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username'] ?? 'User') ?></span>
                        </div>
                        <a href="<?= $bookNowUrl ?>" class="inline-flex h-10 items-center justify-center rounded-lg bg-gold px-5 py-2 text-sm font-bold text-black shadow-lg transition-transform hover:scale-105 active:scale-95">
                            Dashboard
                        </a>
                    <?php else: ?>
                        <a href="auth/login.php" class="inline-flex h-10 items-center justify-center rounded-lg border border-zinc-600 bg-transparent px-5 py-2 text-sm font-medium transition-colors hover:bg-zinc-800 text-white">
                            Login
                        </a>
                        <a href="<?= $bookNowUrl ?>" class="inline-flex h-10 items-center justify-center rounded-lg bg-gold px-5 py-2 text-sm font-bold text-black shadow-lg transition-transform hover:scale-105 active:scale-95">
                            Book Now
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div id="mobile-menu" class="hidden lg:hidden w-full flex flex-col gap-4 mt-4 pt-4 border-t border-zinc-800">
                <a href="#services" class="text-zinc-300 hover:text-white transition-colors font-medium">Ulasan</a>
                <a href="#gallery" class="text-zinc-300 hover:text-white transition-colors font-medium">Layanan</a>
                <a href="#pricing" class="text-zinc-300 hover:text-white transition-colors flex items-center justify-between font-medium">
                    Antrian
                    <span class="bg-gold/20 text-gold px-2 py-0.5 rounded-full text-xs font-bold border border-gold/30">
                        <?= htmlspecialchars($display_queue) ?>
                    </span>
                </a>
                <a href="#about" class="text-zinc-300 hover:text-white transition-colors font-medium">About</a>
                
                <?php if (is_logged_in()): ?>
                    <div class="flex items-center gap-2 text-sm text-zinc-300 font-medium mt-2">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <span><?= htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username'] ?? 'User') ?></span>
                    </div>
                    <a href="<?= $bookNowUrl ?>" class="w-full inline-flex h-10 items-center justify-center rounded-lg bg-gold px-4 py-2 text-sm font-bold text-black transition-colors hover:bg-yellow-500 mt-2">
                        Dashboard
                    </a>
                <?php else: ?>
                    <a href="auth/login.php" class="w-full inline-flex h-10 items-center justify-center rounded-lg border border-zinc-700 bg-transparent px-4 py-2 text-sm font-medium transition-colors hover:bg-zinc-800 text-white mt-2">
                        Login
                    </a>
                    <a href="<?= $bookNowUrl ?>" class="w-full inline-flex h-10 items-center justify-center rounded-lg bg-gold px-4 py-2 text-sm font-bold text-black transition-colors hover:bg-yellow-500">
                        Book Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col pt-[72px]"> <!-- pt to offset fixed navbar -->
        
        <!-- Hero Section (Premium Layout) -->
        <div class="relative w-full bg-transparent overflow-hidden pb-12 lg:pb-20" data-aos="fade-in">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10 pt-10 lg:pt-20 pb-12 flex flex-col-reverse lg:flex-row items-center gap-12">
                <!-- Left: Text Content -->
                <div class="w-full lg:w-1/2 text-left z-20">
                    <h1 class="hero-parallax-text font-serif text-5xl md:text-6xl lg:text-7xl font-bold text-white leading-[1.1] mb-6 tracking-tight">
                        Pengalaman Perawatan <br/>Pria Terbaik
                        <span class="block text-gold italic font-light mt-2">dan Presisi.</span>
                    </h1>
                    
                    <!-- Search / Action Box -->
                    <div class="hero-parallax-text flex max-w-sm mt-8 shadow-[0_0_40px_rgba(212,175,55,0.15)] relative z-20">
                        <input type="text" id="search-input" placeholder="Layanan apa yang Anda cari?" class="w-full px-5 py-3.5 rounded-l-full bg-zinc-900/80 text-white border border-r-0 border-white/10 focus:ring-0 focus:border-gold outline-none text-sm font-medium backdrop-blur-md">
                        <button id="search-btn" class="bg-gold px-6 py-3.5 rounded-r-full text-zinc-950 font-bold hover:bg-yellow-500 transition-colors flex items-center justify-center shrink-0 border border-gold">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </button>
                    </div>
                    
                    <p class="hero-parallax-text mt-8 text-zinc-400 text-sm flex items-start gap-2 font-medium tracking-wide">
                        <i data-lucide="map-pin" class="w-4 h-4 text-gold shrink-0 mt-0.5"></i>
                        <span>Jl. Nawawi Gelar Dalom, Sumberjo, Rajabasa Jaya, Bandarlampung</span>
                    </p>
                </div>
                
                <!-- Right: Antrean Card - Multi Chair Horizontal Scroll -->
                <div class="w-full lg:w-1/2 flex flex-col items-center lg:items-end relative hero-parallax-img">

                    <!-- Scroll Container Wrapper -->
                    <div class="relative w-full max-w-[340px] md:max-w-[400px]">

                        <!-- Scroll track -->
                        <div id="hero-queue-scroll" class="flex gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-1"
                             style="scrollbar-width: none; -ms-overflow-style: none;">

                            <?php foreach ($chairs_data as $idx => $chair):
                                $is_busy  = !empty($chair['current_no']);
                                $waiting  = (int)$chair['waiting_count'];
                                $curNo    = $chair['current_no'] ?? 'Kosong';
                                $nextStmt = $pdo->prepare("SELECT no_antrean FROM antrian WHERE barber_id=? AND status_antrean='waiting' AND DATE(waktu_dibuat)=CURDATE() ORDER BY id ASC LIMIT 1");
                                $nextStmt->execute([$chair['id']]);
                                $nextNo = $nextStmt->fetchColumn() ?: 'Kosong';
                            ?>
                            <div class="snap-center flex-shrink-0 w-full rounded-2xl border overflow-hidden"
                                 style="background: rgba(24,18,8,0.85); backdrop-filter: blur(12px);
                                        border-color: <?= $is_busy ? 'rgba(212,175,55,0.35)' : 'rgba(255,255,255,0.07)' ?>;">

                                <!-- Header -->
                                <div class="px-6 py-4 flex items-center justify-between border-b" style="border-color: rgba(255,255,255,0.06);">
                                    <div class="flex items-center gap-2">
                                        <?php if ($is_busy): ?>
                                            <span class="relative flex h-2 w-2">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                                            </span>
                                            <span class="text-[11px] font-semibold text-green-400 uppercase tracking-widest">Sedang Dilayani</span>
                                        <?php else: ?>
                                            <span class="inline-flex rounded-full h-2 w-2 bg-zinc-600"></span>
                                            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-widest">Siap Melayani</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background: rgba(201,160,58,0.15); color:#c9a03a;">
                                        <?= htmlspecialchars($chair['kursi']) ?>
                                    </span>
                                </div>

                                <!-- Barber Name -->
                                <div class="px-6 pt-4 pb-0 text-center">
                                    <p class="text-[10px] uppercase tracking-[0.2em] text-zinc-600 mb-1">Barber</p>
                                    <p class="text-sm font-semibold text-zinc-300"><?= htmlspecialchars($chair['nama']) ?></p>
                                </div>

                                <!-- Queue Number -->
                                <div class="px-6 py-8 text-center">
                                    <p class="text-zinc-600 text-[10px] uppercase tracking-[0.25em] mb-4">Antrean Saat Ini</p>
                                    <?php if ($is_busy): ?>
                                        <div class="text-[5rem] md:text-[6rem] font-serif font-bold text-white leading-none">
                                            <?= htmlspecialchars($curNo) ?>
                                        </div>
                                        <p class="mt-3 text-xs font-medium tracking-widest uppercase" style="color:#c9a03a80;">Sedang Diproses</p>
                                    <?php else: ?>
                                        <div class="text-[2.5rem] md:text-[3rem] font-serif font-semibold text-zinc-600 leading-none">
                                            Kosong
                                        </div>
                                        <p class="mt-3 text-xs text-zinc-600 font-medium tracking-widest uppercase">Belum Ada Antrian</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Stats -->
                                <div class="grid grid-cols-2 border-t" style="border-color: rgba(255,255,255,0.06);">
                                    <div class="px-6 py-4 text-center border-r" style="border-color: rgba(255,255,255,0.06);">
                                        <p class="text-zinc-600 text-[10px] uppercase tracking-widest mb-1">Menunggu</p>
                                        <p class="text-2xl font-serif font-semibold <?= $waiting > 0 ? '' : 'text-zinc-600' ?>" style="<?= $waiting > 0 ? 'color:#c9a03a;' : '' ?>"><?= $waiting ?></p>
                                    </div>
                                    <div class="px-6 py-4 text-center">
                                        <p class="text-zinc-600 text-[10px] uppercase tracking-widest mb-1">Berikutnya</p>
                                        <p class="text-2xl font-serif font-semibold text-zinc-400"><?= htmlspecialchars($nextNo) ?></p>
                                    </div>
                                </div>

                                <!-- CTA -->
                                <div class="p-4 border-t" style="border-color: rgba(255,255,255,0.06);">
                                    <a href="<?= $bookNowUrl ?>" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl text-black text-sm font-bold transition-colors"
                                       style="background: linear-gradient(90deg, #c9a03a, #8a6010);" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                                        <i data-lucide="scissors" class="w-4 h-4"></i>
                                        Ambil Antrean
                                    </a>
                                </div>

                            </div>
                            <?php endforeach; ?>

                        </div><!-- end scroll track -->

                        <!-- Dot Indicators -->
                        <div id="hero-dots" class="flex justify-center gap-2 mt-3">
                            <?php foreach ($chairs_data as $i => $ch): ?>
                            <button onclick="heroScrollTo(<?= $i ?>)"
                                    class="hero-dot w-6 h-1.5 rounded-full transition-all duration-300 <?= $i === 0 ? 'active-dot' : '' ?>"
                                    style="background: <?= $i === 0 ? '#c9a03a' : 'rgba(255,255,255,0.15)' ?>;"></button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Chair Labels -->
                        <div class="flex justify-center gap-3 mt-2">
                            <?php foreach ($chairs_data as $i => $ch): ?>
                            <button onclick="heroScrollTo(<?= $i ?>)"
                                    class="text-[10px] uppercase tracking-widest font-semibold transition-colors hover:text-amber-400"
                                    style="color: rgba(255,255,255,0.3);">
                                <?= htmlspecialchars($ch['kursi']) ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div><!-- end wrapper -->

                </div>

            </div>
        </div>


        <!-- 2. About Us Section -->
        <section id="about" class="py-20 md:py-24 bg-transparent relative" data-aos="fade-up">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
                <div class="flex flex-col md:flex-row gap-16 items-center">
                    <div class="md:w-1/2 relative group">
                        <!-- Decorative element -->
                        <div class="absolute -top-6 -left-6 w-32 h-32 bg-gold/10 rounded-full blur-2xl pointer-events-none group-hover:bg-gold/20 transition-colors duration-500"></div>
                        <div class="relative rounded-[2rem] overflow-hidden shadow-2xl border border-white/10">
                            <img src="https://images.unsplash.com/photo-1585747860715-2ba37e788b70?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="Tentang Elite Barber" class="w-full h-auto object-cover hover:scale-105 transition-transform duration-1000">
                            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
                            <div class="absolute bottom-8 left-8 text-white">
                                <p class="font-serif text-2xl font-bold tracking-wide">Didirikan 2026</p>
                                <p class="text-gold/80 text-sm font-light mt-1 tracking-widest uppercase">Komitmen pada Kualitas</p>
                            </div>
                        </div>
                    </div>
                    <div class="md:w-1/2">
                        <h2 class="font-serif text-4xl md:text-5xl font-bold text-white tracking-tight mb-8">Tentang <span class="italic font-light text-gold">Elite Barber.</span></h2>
                        <p class="text-zinc-400 text-base md:text-lg mb-6 leading-relaxed font-light tracking-wide">
                            Elite Barber bukan sekadar tempat potong rambut biasa. Kami hadir untuk memberikan pengalaman <span class="text-white font-medium">grooming pria terbaik</span> dengan sentuhan seni, presisi, dan gaya kekinian.
                        </p>
                        <p class="text-zinc-400 text-base md:text-lg mb-10 leading-relaxed font-light tracking-wide">
                            Tempat yang menyediakan layanan potong rambut, penataan rambut, pencukuran jenggot dan kumis, serta berbagai perawatan penampilan khusus pria. Tempat ini mengutamakan pelayanan profesional, kenyamanan pelanggan, kebersihan, dan kualitas hasil perawatan sehingga pelanggan memperoleh pengalaman yang memuaskan.
                        </p>
                        
                        <div class="grid grid-cols-2 gap-6">
                            <div class="bg-zinc-900/30 p-6 rounded-2xl shadow-xl border border-white/5 flex flex-col gap-4 backdrop-blur-sm hover:bg-white/5 transition-colors group">
                                <div class="bg-white/5 p-3 rounded-full text-gold w-max border border-white/5 group-hover:scale-110 transition-transform">
                                    <i data-lucide="scissors" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-white text-lg">Profesional</h4>
                                    <p class="text-xs text-zinc-500 uppercase tracking-widest mt-1">Barber Ahli</p>
                                </div>
                            </div>
                            <div class="bg-zinc-900/30 p-6 rounded-2xl shadow-xl border border-white/5 flex flex-col gap-4 backdrop-blur-sm hover:bg-white/5 transition-colors group">
                                <div class="bg-white/5 p-3 rounded-full text-gold w-max border border-white/5 group-hover:scale-110 transition-transform">
                                    <i data-lucide="star" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-white text-lg">Premium</h4>
                                    <p class="text-xs text-zinc-500 uppercase tracking-widest mt-1">Kualitas Terbaik</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Services Section -->
        <section id="gallery" class="py-20 md:py-24 bg-transparent relative" data-aos="fade-up">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
                <div class="text-center mb-16">
                    <h2 class="font-serif text-4xl md:text-5xl lg:text-6xl font-bold text-white tracking-tight">Layanan <span class="italic font-light text-gold">Kami.</span></h2>
                    <p class="mt-6 text-zinc-400 text-lg font-light tracking-wide max-w-2xl mx-auto">Pilih layanan premium yang dirancang khusus untuk memenuhi kebutuhan penampilan terbaik Anda.</p>
                </div>
                
                <!-- Paket Utama (Dynamic Horizontal Scroll) -->
                <div class="relative group mb-16">
                    <div class="flex overflow-x-auto gap-8 pb-8 snap-x snap-mandatory hide-scrollbar" style="scrollbar-width: none; -ms-overflow-style: none;">
                        <?php foreach ($paket_utama as $p): ?>
                        <?php
                            $files = glob(__DIR__ . "/asset/image/layanan_{$p['id']}.*");
                            $nama_lower = strtolower($p['nama_layanan']);
                            $default_images = [
                                'pridecut' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                                'maxcut' => 'asset/image/maxcut.png',
                            ];
                            $img = !empty($files) ? 'asset/image/' . basename($files[0]) : ($default_images[$nama_lower] ?? 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80');
                            $features = array_filter(array_map('trim', explode("\n", $p['deskripsi'])));
                        ?>
                        <div class="snap-start shrink-0 w-[350px] md:w-[450px] group relative bg-zinc-900/30 rounded-[2rem] overflow-hidden border border-white/10 shadow-2xl hover:border-white/20 transition-all duration-500 flex flex-col backdrop-blur-sm">
                            <div class="h-64 w-full overflow-hidden relative shrink-0">
                                <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 opacity-90" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nama_layanan']) ?>">
                                <?php if(strtolower($p['nama_layanan']) === 'maxcut'): ?>
                                <div class="absolute top-6 right-6 bg-gold text-black text-[10px] font-bold px-4 py-1.5 rounded-full uppercase tracking-widest shadow-lg shadow-gold/20">Terbaik</div>
                                <?php endif; ?>
                                <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/40 to-transparent"></div>
                                <div class="absolute bottom-8 left-8 right-8 flex justify-between items-end">
                                    <div>
                                        <h3 class="font-serif text-3xl font-bold text-white"><?= htmlspecialchars($p['nama_layanan']) ?></h3>
                                    </div>
                                    <div class="text-2xl font-serif font-bold text-gold">Rp <?= number_format($p['harga']/1000, 0, ',', '.') ?>K</div>
                                </div>
                            </div>
                            <div class="p-8 flex flex-col flex-grow">
                                <ul class="space-y-4 flex-grow">
                                    <?php foreach($features as $feat): ?>
                                    <li class="flex items-center text-zinc-300 font-light"><i data-lucide="check" class="w-5 h-5 text-gold mr-4 shrink-0"></i> <span><?= htmlspecialchars($feat) ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="<?= $bookNowUrl ?>" class="mt-8 flex w-full items-center justify-center rounded-full bg-white/5 border border-white/10 hover:bg-gold hover:text-black hover:border-gold py-4 text-sm font-semibold text-white transition-all shadow-[0_0_20px_rgba(212,175,55,0.05)] hover:shadow-[0_0_20px_rgba(212,175,55,0.2)]">Pilih <?= htmlspecialchars($p['nama_layanan']) ?></a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Layanan Tambahan (Horizontally Scrollable) -->
                <div class="pt-8 mb-10 text-center">
                    <h3 class="font-serif text-3xl font-bold text-white mb-2">Layanan <span class="italic font-light text-gold">Tambahan.</span></h3>
                    <div class="w-12 h-1 bg-gold/50 mx-auto rounded-full"></div>
                </div>
                
                <!-- Horizontal Scroll Container -->
                <div class="relative group">
                    <div class="flex overflow-x-auto gap-4 lg:gap-6 pb-8 snap-x snap-mandatory hide-scrollbar" style="scrollbar-width: none; -ms-overflow-style: none;">
                        
                        <?php foreach ($layanan_tambahan as $l): ?>
                        <?php
                            $files = glob(__DIR__ . "/asset/image/layanan_{$l['id']}.*");
                            $nama_lower = strtolower($l['nama_layanan']);
                            $default_images = [
                                'hair coloring' => 'https://images.unsplash.com/photo-1620331311520-246422fd82f9?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                                'hairlight' => 'asset/image/hairlight.png',
                                'full hairlight' => 'asset/image/full_hairlight.png',
                                'hair tattoo' => 'https://images.unsplash.com/photo-1593702295094-aea22597af65?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                                'shave' => 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                                'korean wave' => 'https://images.unsplash.com/photo-1605497788044-5a32c7078486?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'
                            ];
                            $img = !empty($files) ? 'asset/image/' . basename($files[0]) : ($default_images[$nama_lower] ?? 'https://images.unsplash.com/photo-1620331311520-246422fd82f9?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80');
                        ?>
                        <div class="snap-start shrink-0 w-64 group/card relative rounded-2xl overflow-hidden bg-zinc-900/30 border border-white/10 hover:border-white/20 hover:shadow-xl hover:shadow-black/50 transition-all backdrop-blur-sm">
                            <div class="aspect-square overflow-hidden">
                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($l['nama_layanan']) ?>" class="w-full h-full object-cover group-hover/card:scale-110 transition-transform duration-700">
                            </div>
                            <div class="p-5 bg-transparent">
                                <h4 class="font-bold text-white text-sm md:text-base"><?= htmlspecialchars($l['nama_layanan']) ?></h4>
                                <p class="text-gold/80 font-medium text-sm mt-1">Rp <?= number_format($l['harga']/1000, 0, ',', '.') ?>K</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Fade edges for scroll indication -->
                    <div class="absolute top-0 right-0 bottom-8 w-16 bg-gradient-to-l from-black to-transparent pointer-events-none"></div>
                    <div class="absolute top-0 left-0 bottom-8 w-16 bg-gradient-to-r from-black to-transparent pointer-events-none"></div>
                </div>
            </div>
        </section>





        <!-- 4. Reviews Section -->
        <section id="services" class="py-20 md:py-24 bg-transparent relative" data-aos="fade-in">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
                <div class="text-center mb-16">
                    <h2 class="font-serif text-4xl md:text-5xl font-bold text-white tracking-tight">Apa Kata <span class="text-gold italic font-light">Pelanggan.</span></h2>
                    <p class="mt-6 text-zinc-400 text-lg font-light tracking-wide max-w-2xl mx-auto">Kami bangga dapat memberikan pelayanan terbaik dan memuaskan bagi setiap pria yang mempercayakan penampilannya kepada Elite Barber.</p>
                </div>
                
                <!-- Horizontal Scroll Container for Reviews -->
                <div class="relative group">
                    <div class="flex overflow-x-auto gap-6 pb-8 snap-x snap-mandatory hide-scrollbar" style="scrollbar-width: none; -ms-overflow-style: none;">
                        <?php if (empty($ulasan_list)): ?>
                            <div class="w-full text-center text-zinc-500 py-12 text-lg font-light">Belum ada ulasan saat ini.</div>
                        <?php else: ?>
                            <?php foreach ($ulasan_list as $u): ?>
                                <div class="snap-start shrink-0 w-[300px] md:w-[400px] bg-zinc-900/30 backdrop-blur-sm rounded-2xl p-8 border border-white/5 shadow-2xl hover:border-white/10 hover:bg-white/5 transition-all duration-300 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between mb-6">
                                            <div class="font-bold text-white text-lg tracking-wide"><?= htmlspecialchars($u['username']) ?></div>
                                            <div class="text-gold text-sm tracking-widest flex gap-1">
                                                <?= str_repeat('<i data-lucide="star" class="fill-gold stroke-none w-4 h-4"></i>', (int)$u['rating']) ?><span class="opacity-20"><?= str_repeat('<i data-lucide="star" class="fill-white stroke-none w-4 h-4"></i>', 5 - (int)$u['rating']) ?></span>
                                            </div>
                                        </div>
                                        <p class="text-zinc-400 text-sm md:text-base leading-relaxed italic font-light">"<?= nl2br(htmlspecialchars($u['komentar'] ?? '-')) ?>"</p>
                                    </div>
                                    <div class="mt-8 text-xs font-medium text-zinc-600 uppercase tracking-widest">
                                        <?= date('d M Y', strtotime($u['waktu'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <!-- Fade edges for scroll indication -->
                    <div class="absolute top-0 right-0 bottom-8 w-16 bg-gradient-to-l from-black to-transparent pointer-events-none"></div>
                    <div class="absolute top-0 left-0 bottom-8 w-16 bg-gradient-to-r from-black to-transparent pointer-events-none"></div>
                </div>


            </div>
        </section>

        <!-- Footer Section -->
        <footer class="bg-black text-zinc-400 pt-20 pb-10 mt-auto relative overflow-hidden">
            <!-- Glow effect -->

            <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
                <div class="flex flex-col md:flex-row justify-between gap-12 md:gap-24">
                    <!-- Left: Brand Info -->
                    <div class="max-w-md">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="bg-white/5 border border-white/10 p-2.5 rounded-xl text-gold">
                                <i data-lucide="scissors" class="w-6 h-6"></i>
                            </div>
                            <span class="text-2xl font-bold tracking-tight text-white font-serif">Elite<span class="font-light italic text-gold">Barber.</span></span>
                        </div>
                        <p class="text-zinc-500 mb-8 leading-relaxed font-light tracking-wide text-sm md:text-base">
                            Kami hadir untuk memberikan pengalaman grooming pria terbaik dengan sentuhan seni, presisi, dan gaya kekinian yang memadukan teknik klasik dengan estetika modern.
                        </p>
                        <div class="flex items-center gap-4 text-zinc-500 font-medium">
                            <i data-lucide="map-pin" class="w-5 h-5 shrink-0 text-gold"></i>
                            <span>Jl. Nawawi Gelar Dalom, Sumberjo, Rajabasa Jaya, Bandarlampung</span>
                        </div>
                    </div>
                    
                    <!-- Right: Contacts & Socials -->
                    <div>
                        <h4 class="text-white font-bold tracking-widest uppercase text-xs mb-6">Hubungi Kami</h4>
                        <ul class="space-y-4 mb-8 text-zinc-400 font-light text-sm">
                            <li class="flex items-center gap-4 hover:text-white transition-colors cursor-pointer">
                                <a href="https://wa.me/6285788942309" target="_blank" rel="noopener noreferrer" class="flex items-center gap-4 hover:text-gold transition-colors">
                                    <i data-lucide="phone" class="w-4 h-4 text-gold"></i>
                                    <span>+62 857 8894 2309</span>
                                </a>
                            </li>
                            <li class="flex items-center gap-4 hover:text-white transition-colors cursor-pointer">
                                <a href="https://mail.google.com/mail/?view=cm&fs=1&to=daffafaiz12309@gmail.com" target="_blank" rel="noopener noreferrer" class="flex items-center gap-4 hover:text-gold transition-colors">
                                    <i data-lucide="mail" class="w-4 h-4 text-gold"></i>
                                    <span>daffafaiz12309@gmail.com</span>
                                </a>
                            </li>
                        </ul>
                        <div class="flex items-center gap-4">
                            <!-- WhatsApp -->
                            <a href="https://wa.me/6285788942309" target="_blank" rel="noopener noreferrer" class="bg-white/5 p-3.5 rounded-full hover:bg-gold hover:text-black text-zinc-300 transition-all border border-white/10 hover:border-gold" title="WhatsApp">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            </a>
                            <!-- Email (Gmail) -->
                            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=daffafaiz12309@gmail.com" target="_blank" rel="noopener noreferrer" class="bg-white/5 p-3.5 rounded-full hover:bg-gold hover:text-black text-zinc-300 transition-all border border-white/10 hover:border-gold" title="Gmail">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                            </a>
                            <!-- Instagram -->
                            <a href="https://instagram.com/dappuulll67" target="_blank" rel="noopener noreferrer" class="bg-white/5 p-3.5 rounded-full hover:bg-gold hover:text-black text-zinc-300 transition-all border border-white/10 hover:border-gold" title="Instagram">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Separator line -->
                <div class="w-full h-px bg-gradient-to-r from-transparent via-white/10 to-transparent mt-16 mb-8"></div>

                <!-- Bottom Copyright -->
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs font-medium text-zinc-600 tracking-wider">
                    <p>&copy; <?= date('Y') ?> ELITE BARBER. ALL RIGHTS RESERVED.</p>
                    <div class="flex gap-8">
                        <a href="#" class="hover:text-white transition-colors">PRIVACY POLICY</a>
                        <a href="#" class="hover:text-white transition-colors">TERMS OF SERVICE</a>
                    </div>
                </div>
            </div>
        </footer>
        </div>
    </main>



    <!-- Script for interactivity -->
    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // ===== Hero Queue Horizontal Scroll =====
        const heroScroll = document.getElementById('hero-queue-scroll');
        const heroDots   = document.querySelectorAll('.hero-dot');

        function heroScrollTo(index) {
            if (!heroScroll) return;
            const cards = heroScroll.querySelectorAll('.snap-center');
            if (cards[index]) {
                cards[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
            heroDots.forEach((d, i) => {
                d.style.background = i === index ? '#c9a03a' : 'rgba(255,255,255,0.15)';
                d.style.width = i === index ? '1.75rem' : '1.5rem';
            });
        }

        // Auto-update dots on swipe
        if (heroScroll) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const cards = Array.from(heroScroll.querySelectorAll('.snap-center'));
                        const idx = cards.indexOf(entry.target);
                        if (idx >= 0) {
                            heroDots.forEach((d, i) => {
                                d.style.background = i === idx ? '#c9a03a' : 'rgba(255,255,255,0.15)';
                                d.style.width = i === idx ? '1.75rem' : '1.5rem';
                            });
                        }
                    }
                });
            }, { root: heroScroll, threshold: 0.6 });
            heroScroll.querySelectorAll('.snap-center').forEach(c => observer.observe(c));
        }

        // Navbar Scroll Effect (Kept for potential future use, but header is always dark now)
        window.addEventListener('scroll', () => {
            const navContainer = document.getElementById('navbar');
            if (window.scrollY > 20) {
                navContainer.classList.add('shadow-lg');
            } else {
                navContainer.classList.remove('shadow-lg');
            }
        });

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            menuIcon.classList.toggle('hidden');
            closeIcon.classList.toggle('hidden');
        });
        
        // Search Functionality
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');

        function performSearch(doScroll) {
            if (!searchInput) return;
            const query = searchInput.value.toLowerCase().trim();
            const serviceSections = document.querySelectorAll('#gallery .grid');
            
            serviceSections.forEach(section => {
                const cards = section.querySelectorAll(':scope > div.group');
                cards.forEach(card => {
                    // Check h3 and h4 tags inside the card
                    const titleElement = card.querySelector('h3, h4');
                    if (titleElement) {
                        const title = titleElement.textContent.toLowerCase();
                        if (title.includes(query)) {
                            card.style.display = ''; // Show
                        } else {
                            card.style.display = 'none'; // Hide
                        }
                    }
                });
            });

            if (doScroll) {
                const gallerySection = document.getElementById('gallery');
                if (gallerySection) {
                    gallerySection.scrollIntoView({ behavior: 'smooth' });
                }
            }
        }

        if (searchInput && searchBtn) {
            searchBtn.addEventListener('click', () => performSearch(true));
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch(true);
                }
            });
            searchInput.addEventListener('input', () => performSearch(false));
        }

        // Pause slider on hover
        const track = document.getElementById('slider-track');
        if (track) {
            track.addEventListener('mouseenter', () => {
                track.style.animationPlayState = 'paused';
            });
            track.addEventListener('mouseleave', () => {
                track.style.animationPlayState = 'running';
            });
        }
    </script>
    
    <!-- AOS JS & Init -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
      AOS.init({
        duration: 800,
        once: false,
        mirror: true,
        offset: 50
      });

      // 3D Depth Parallax Scroll
      window.addEventListener('scroll', () => {
          const scrollY = window.scrollY;
          
          // Hero Image scrolls slightly up relative to page
          const heroImg = document.querySelector('.hero-parallax-img');
          if (heroImg) {
              heroImg.style.transform = `translateY(${scrollY * 0.15}px)`;
          }

          // Hero Text scrolls at a different speed for depth
          const heroTexts = document.querySelectorAll('.hero-parallax-text');
          heroTexts.forEach(text => {
              text.style.transform = `translateY(${scrollY * 0.05}px)`;
          });
          
          // Background glows move at different speeds
          const glows = document.querySelectorAll('.bg-glow-parallax');
          glows.forEach(glow => {
              const speed = glow.getAttribute('data-speed') || 0.1;
              glow.style.transform = `translateY(${scrollY * parseFloat(speed)}px)`;
          });
      });
    </script>
</body>
</html>


