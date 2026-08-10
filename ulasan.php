<?php
require_once __DIR__ . '/config/database.php';
$stmt_ulasan = $pdo->query("SELECT u.*, us.username as pelanggan_nama, b.nama as barber_nama, l.nama_layanan
                            FROM ulasan u 
                            JOIN users us ON u.pelanggan_id = us.id_user 
                            JOIN antrian a ON u.antrian_id = a.id
                            LEFT JOIN barber b ON a.barber_id = b.id
                            LEFT JOIN layanan l ON a.layanan_id = l.id
                            ORDER BY u.waktu DESC");
$ulasan_list = $stmt_ulasan->fetchAll(PDO::FETCH_ASSOC);

// Hitung Rata-rata
$avg_stmt = $pdo->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM ulasan");
$stats = $avg_stmt->fetch(PDO::FETCH_ASSOC);
$average_rating = $stats['avg_rating'] ? round((float)$stats['avg_rating'], 1) : 0;
$total_reviews = $stats['total_reviews'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan Pelanggan | ELITE BARBER</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="antialiased min-h-screen flex flex-col overflow-x-hidden bg-black text-zinc-200">
    <div class="fixed inset-0 z-[-1] bg-gradient-to-br from-black via-[#0a0a0a] to-[#3e2723] pointer-events-none"></div>

    <!-- Floating Header / Navbar -->
    <header class="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[95%] max-w-6xl transition-all duration-300" id="navbar">
        <div class="group relative p-[1px] rounded-[24px] bg-[radial-gradient(circle_80px_at_80%_-10%,_#ffffff,_#27272a)] shadow-2xl transition-all">
            <div class="absolute top-0 right-0 w-[65%] h-[60%] rounded-[120px] shadow-[0_0_20px_#ffffff38] -z-10"></div>
            <div class="absolute bottom-0 left-0 w-[50px] h-[50%] rounded-[17px] transition-all duration-300 ease-out bg-[radial-gradient(circle_60px_at_0%_100%,_#3fff75,_#00ff8050,_transparent)] shadow-[-2px_9px_40px_#00ff2d40] group-hover:w-[90px] group-hover:shadow-[-4px_1px_45px_#00ff2d60]"></div>
            
            <div class="relative px-6 lg:px-10 py-3 rounded-[23px] text-white bg-zinc-950 bg-[radial-gradient(circle_80px_at_80%_-50%,_#444444,_#09090b)] z-10 transition-all duration-300">
                <div class="absolute inset-0 rounded-[23px] bg-[radial-gradient(circle_60px_at_0%_100%,_#00e1ff1a,_#0000ff11,_transparent)] z-[-1]"></div>
                
                <div class="flex items-center justify-between relative z-20">
                    <a href="index.php" class="flex items-center space-x-2 font-bold text-xl tracking-tight text-white">
                        💈 ELITE BARBER
                    </a>
                    
                    <div class="hidden lg:flex items-center gap-4">
                        <a href="index.php" class="inline-flex h-9 items-center justify-center rounded-md border border-zinc-600 bg-transparent px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-zinc-800 text-white">
                            Kembali ke Utama
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow flex flex-col justify-start pt-32 pb-16">
        <section class="mx-auto w-full px-6 lg:px-12 xl:px-24">
            <div class="text-center mb-16">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-zinc-200 tracking-tight">Semua Ulasan</h1>
                <p class="mt-4 text-zinc-600 text-lg max-w-2xl mx-auto">Melihat semua tanggapan dan feedback pelanggan Elite Barber</p>
                
                <div class="mt-8 flex justify-center gap-8">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-[#f1c40f]"><?= number_format($average_rating, 1) ?> <span class="text-3xl">⭐</span></div>
                        <div class="text-zinc-500 text-sm font-medium mt-1 uppercase tracking-widest">Rata-rata</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-zinc-200"><?= $total_reviews ?></div>
                        <div class="text-zinc-500 text-sm font-medium mt-1 uppercase tracking-widest">Total Ulasan</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (empty($ulasan_list)): ?>
                    <div class="col-span-full text-center text-zinc-500 py-12 text-lg">Belum ada ulasan saat ini.</div>
                <?php else: ?>
                    <?php foreach ($ulasan_list as $u): ?>
                        <div class="bg-white rounded-3xl p-8 border border-zinc-200 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 transform hover:-translate-y-1">
                            <div class="flex items-center justify-between mb-4">
                                <div class="font-bold text-zinc-900 text-lg"><?= htmlspecialchars($u['pelanggan_nama']) ?></div>
                                <div class="text-[#f1c40f] text-xl tracking-widest">
                                    <?= str_repeat('★', (int)$u['rating']) ?><span class="text-zinc-200"><?= str_repeat('★', 5 - (int)$u['rating']) ?></span>
                                </div>
                            </div>
                            <div class="text-xs text-zinc-500 mb-4 font-medium">
                                <?= htmlspecialchars($u['nama_layanan']) ?> • oleh <?= htmlspecialchars($u['barber_nama'] ?? 'Barber') ?>
                            </div>
                            <p class="text-zinc-600 text-base leading-relaxed italic">"<?= nl2br(htmlspecialchars($u['komentar'] ?? '-')) ?>"</p>
                            <div class="mt-6 text-sm font-medium text-zinc-400">
                                <?= date('d M Y, H:i', strtotime($u['waktu'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <section class="pb-12 pt-12 mt-auto relative px-4 lg:px-8">
        <div class="group relative p-[1px] rounded-[32px] bg-[radial-gradient(circle_80px_at_80%_-10%,_#ffffff,_#27272a)] shadow-2xl transition-all max-w-7xl mx-auto">
            <div class="absolute top-0 right-0 w-[40%] h-[60%] rounded-[120px] shadow-[0_0_20px_#ffffff38] -z-10"></div>
            <div class="absolute bottom-0 left-0 w-[50px] h-[50%] rounded-[17px] transition-all duration-300 ease-out bg-[radial-gradient(circle_60px_at_0%_100%,_#3fff75,_#00ff8050,_transparent)] shadow-[-2px_9px_40px_#00ff2d40]"></div>
            
            <div class="relative py-8 rounded-[31px] text-white bg-zinc-950 bg-[radial-gradient(circle_80px_at_80%_-50%,_#333333,_#09090b)] z-10 transition-all duration-300">
                <div class="absolute inset-0 rounded-[31px] bg-[radial-gradient(circle_60px_at_0%_100%,_#00e1ff1a,_#0000ff11,_transparent)] z-[-1]"></div>
                <div class="text-center text-zinc-400 text-sm z-10 relative">
                    &copy; <?= date('Y') ?> Elite Barber. Hak Cipta Dilindungi.
                </div>
            </div>
        </div>
    </section>
</body>
</html>
