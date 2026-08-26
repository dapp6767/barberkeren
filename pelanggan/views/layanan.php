<section id="tab-layanan" class="tab-content <?= $is_layanan ? 'active' : '' ?>">
<!-- LAYANAN MODULE -->
<div id="layanan-main-content" class="w-full pb-32">
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
            'pangkas rambut biasa'      => '../asset/image/keren.jpg',
            'pangkas rambut luar biasa' => '../asset/image/keren.jpg',
            'pridecut'                  => '../asset/image/keren.jpg',
            'maxcut'                    => '../asset/image/keren.jpg',
            'paket cukur sultan'        => '../asset/image/keren.jpg',
            'paket cukur segar'         => '../asset/image/keren.jpg',
            'hair coloring'             => 'https://images.unsplash.com/photo-1620331311520-246422fd82f9?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
            'hairlight'                 => '../asset/image/hairlight.png',
            'full hairlight'            => '../asset/image/full_hairlight.png',
            'hair tattoo'               => 'https://images.unsplash.com/photo-1593702295094-aea22597af65?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
            'shave'                     => 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
            'korean wave'               => 'https://images.unsplash.com/photo-1605497788044-5a32c7078486?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
        ];
        $dummy_desc_arr = [
            'pangkas rambut biasa'      => 'Potong Rambut Rapi + Cuci + Styling Sederhana',
            'pangkas rambut luar biasa' => 'Potong Rambut Spesial + Pijat + Styling Eksklusif',
            'hair coloring'             => 'Pewarnaan rambut full kepala dengan cat berkualitas tinggi',
            'hairlight'                 => 'Pewarnaan highlight aksen rambut',
            'full hairlight'            => 'Pewarnaan highlight full kepala',
            'hair tattoo'               => 'Seni ukir pola rambut presisi tinggi',
            'shave'                     => 'Cukur jenggot & kumis bersih dengan perawatan handuk hangat',
            'korean wave'               => 'Perming keriting gaya Korea modern & stylish',
        ];
        
        foreach($services as $i => $srv): 
            $s_id = $srv['id'] ?? $srv['id_service'] ?? 0;
            $s_name = $srv['nama_layanan'] ?? $srv['service_name'] ?? '';
            $s_price = (float)($srv['harga'] ?? $srv['price'] ?? 0);
            $nama_lower = strtolower(trim($s_name));

            $s_desc = !empty($srv['deskripsi']) 
                ? $srv['deskripsi'] 
                : ($dummy_desc_arr[$nama_lower] ?? 'Potong Rambut + Cuci + Styling');

            if (!empty($srv['durasi'])) {
                $s_durasi = $srv['durasi'] . ' Menit';
            } else if (stripos($s_name, 'color') !== false || stripos($s_name, 'light') !== false || stripos($s_name, 'wave') !== false) {
                $s_durasi = '90 Menit';
            } else {
                $s_durasi = '45 Menit';
            }

            $files = glob(__DIR__ . "/../../asset/image/layanan_{$s_id}.*");
            $img = !empty($files)
                ? '../asset/image/' . basename($files[0])
                : ($default_images_layanan[$nama_lower] ?? '../asset/image/keren.jpg');

            $price_formatted = 'Rp ' . number_format($s_price, 0, ',', '.');
        ?>
        <!-- Service Card (Desktop Grid Style) -->
        <div class="service-item group bg-[#1A1612] rounded-2xl border border-white/5 overflow-hidden shadow-lg transition-all duration-200 cursor-pointer hover:border-amber-500/40 hover:-translate-y-0.5 hover:shadow-amber-900/20 select-none"
             data-id="<?= $s_id ?>"
             data-name="<?= htmlspecialchars($s_name) ?>"
             data-price="<?= $s_price ?>"
             data-price-fmt="<?= $price_formatted ?>"
             onclick="selectLayanan(this)">

            <!-- Image Section -->
            <div class="relative w-full h-44 overflow-hidden bg-zinc-800">
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($s_name) ?>"
                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-t from-[#1A1612] via-transparent to-transparent"></div>
                <div class="selected-overlay absolute inset-0 bg-amber-500/25 flex items-center justify-center opacity-0 transition-opacity duration-200">
                    <div class="bg-amber-400 rounded-full p-2 shadow-xl">
                        <i data-lucide="check" class="w-5 h-5 text-amber-950"></i>
                    </div>
                </div>
                <div class="absolute bottom-3 right-3 bg-black/60 backdrop-blur-sm text-emerald-400 font-bold text-sm px-3 py-1 rounded-full border border-emerald-500/30">
                    <?= $price_formatted ?>
                </div>
            </div>

            <!-- Content Section -->
            <div class="p-4 flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold text-white leading-tight truncate"><?= htmlspecialchars($s_name) ?></h3>
                    <p class="text-xs text-zinc-400 mt-1 line-clamp-2"><?= htmlspecialchars($s_desc) ?></p>
                    <div class="flex items-center gap-1 text-xs text-zinc-500 mt-2">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                        <span><?= htmlspecialchars($s_durasi) ?></span>
                    </div>
                </div>
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

<!-- Floating Action Bar — hidden until service is selected -->
<div id="layanan-action-bar" class="fixed bottom-24 md:bottom-6 left-0 right-0 z-50 px-6 transition-all duration-300 translate-y-4 opacity-0 pointer-events-none transform-gpu">
    <div class="md:pl-64 lg:pl-64 transition-all duration-300" id="fab-inner-wrapper">
        <div class="max-w-2xl mx-auto bg-[#1a1209]/95 border border-amber-500/50 rounded-2xl px-5 py-3.5 flex justify-between items-center shadow-2xl relative overflow-hidden">
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
            <button id="fab-next-btn" onclick="openBarberStep()"
               class="relative z-10 bg-amber-400 hover:bg-amber-300 text-amber-950 font-bold text-sm px-6 py-2.5 rounded-xl transition-colors shadow-lg flex items-center gap-2 whitespace-nowrap active:scale-95">
                <i data-lucide="user-check" class="w-4 h-4"></i>
                Pilih Barber
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</div>

<!-- STEP 2: PILIH BARBER -->
<div id="step-pilih-barber" class="hidden w-full pb-36">
    <!-- Header Navigation & Stepper -->
    <div class="mb-6 bg-[#16120C] border border-amber-900/30 rounded-2xl p-5 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <button onclick="backToLayanan()" class="flex items-center justify-center w-10 h-10 rounded-xl bg-zinc-800/80 hover:bg-amber-500/20 text-zinc-300 hover:text-amber-300 border border-white/10 hover:border-amber-500/30 transition-all duration-200 group shadow-md" title="Kembali ke Pilih Layanan">
                    <i data-lucide="arrow-left" class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform"></i>
                </button>
                <div>
                    <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <span>Pilih Barber & Kursi</span>
                    </h2>
                    <p class="text-xs text-zinc-400">Pilih barber favorit Anda atau biarkan sistem memilih otomatis antrean terpendek</p>
                </div>
            </div>

            <div class="flex items-center gap-2 bg-zinc-900/80 px-3.5 py-1.5 rounded-xl border border-white/5 self-start sm:self-auto">
                <span class="flex items-center gap-1.5 text-xs text-emerald-400 font-semibold cursor-pointer" onclick="backToLayanan()">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    <span>Layanan</span>
                </span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-zinc-600"></i>
                <span class="flex items-center gap-1.5 text-xs text-amber-400 font-bold bg-amber-500/10 px-2.5 py-1 rounded-lg border border-amber-500/20">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>2. Barber</span>
                </span>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-white/5 flex flex-wrap items-center justify-between gap-3 bg-zinc-900/40 px-4 py-3 rounded-xl border border-amber-500/10">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-lg bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                    <i data-lucide="scissors" class="w-4 h-4"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold tracking-wider block">Layanan Terpilih</span>
                    <span id="step2-service-name" class="text-white font-bold text-sm truncate block"></span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span id="step2-service-price" class="text-emerald-400 font-black text-base"></span>
                <button onclick="backToLayanan()" class="text-xs text-amber-400 hover:text-amber-300 font-semibold underline underline-offset-2">Ubah</button>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4 px-1">
        <h3 class="text-sm font-bold uppercase tracking-wider text-amber-200/80 flex items-center gap-2">
            <i data-lucide="users" class="w-4 h-4 text-amber-400"></i>
            Daftar Barber Tersedia Hari Ini
        </h3>
        <span class="text-xs text-zinc-500">Klik salah satu untuk memilih</span>
    </div>

    <!-- Barber Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8" id="barber-grid">

        <!-- Option: Bebas / Otomatis -->
        <div class="barber-card group relative cursor-pointer rounded-2xl border-2 border-amber-500/30 bg-gradient-to-b from-[#241a0e] to-[#161009] p-5 shadow-lg transition-all duration-300 hover:border-amber-400 hover:shadow-[0_0_20px_rgba(245,158,11,0.2)] hover:-translate-y-1 select-none"
             data-barber-id="0"
             data-barber-name="Bebas / Pilih Otomatis"
             data-barber-kursi="Otomatis"
             data-barber-letter="Auto"
             onclick="selectBarber(this)">

            <div class="absolute -top-3 right-4 bg-gradient-to-r from-amber-500 to-amber-600 text-amber-950 font-black text-[10px] uppercase tracking-wider px-3 py-0.5 rounded-full shadow-md flex items-center gap-1 border border-amber-300/40">
                <i data-lucide="sparkles" class="w-3 h-3"></i> Rekomendasi Tercepat
            </div>

            <div class="flex flex-col gap-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="w-13 h-13 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 text-2xl shadow-inner shrink-0 group-hover:scale-105 transition-transform">
                            ⚡
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-base leading-tight group-hover:text-amber-300 transition-colors">Bebas / Otomatis</h4>
                            <p class="text-xs text-zinc-400 mt-1">Antrean paling sepi &amp; cepat</p>
                        </div>
                    </div>
                    <div class="barber-selected-tick hidden shrink-0">
                        <div class="w-7 h-7 rounded-full bg-amber-400 flex items-center justify-center shadow-[0_0_12px_rgba(245,158,11,0.6)]">
                            <i data-lucide="check" class="w-4 h-4 text-amber-950 font-black"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-black/30 rounded-xl p-3 border border-white/5 text-xs text-zinc-300 space-y-1.5">
                    <div class="flex items-center gap-2 text-amber-300 font-medium">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-400 shrink-0"></i>
                        <span>Waktu tunggu paling singkat</span>
                    </div>
                    <div class="flex items-center gap-2 text-zinc-400">
                        <i data-lucide="shuffle" class="w-3.5 h-3.5 text-zinc-500 shrink-0"></i>
                        <span>Dialokasikan otomatis oleh sistem</span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-white/10 text-xs">
                    <span class="text-zinc-400 font-medium">Kursi: <strong class="text-amber-300 font-semibold">Semua Kursi</strong></span>
                    <span class="px-2.5 py-1 rounded-lg bg-amber-400/20 text-amber-300 font-bold border border-amber-500/30">
                        Tiket Auto
                    </span>
                </div>
            </div>
        </div>

        <!-- Barber Cards Loop -->
        <?php
        $barber_letter_colors = [
            'A' => ['bg' => 'from-amber-600 to-amber-800',   'border' => 'border-amber-500/30',  'text' => 'text-amber-300',  'badge' => 'bg-amber-500/15 text-amber-400 border-amber-500/30'],
            'B' => ['bg' => 'from-blue-600 to-blue-800',     'border' => 'border-blue-500/30',    'text' => 'text-blue-300',   'badge' => 'bg-blue-500/15 text-blue-400 border-blue-500/30'],
            'C' => ['bg' => 'from-emerald-600 to-emerald-800','border' => 'border-emerald-500/30','text' => 'text-emerald-300','badge' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'],
            'D' => ['bg' => 'from-purple-600 to-purple-800', 'border' => 'border-purple-500/30',  'text' => 'text-purple-300', 'badge' => 'bg-purple-500/15 text-purple-400 border-purple-500/30'],
            'E' => ['bg' => 'from-rose-600 to-rose-800',     'border' => 'border-rose-500/30',    'text' => 'text-rose-300',   'badge' => 'bg-rose-500/15 text-rose-400 border-rose-500/30'],
        ];
        foreach ($barbers_detail as $br):
            $kursi_str = strtoupper($br['kursi'] ?? '');
            $br_letter = 'A';
            if (preg_match('/KURSI\s*([A-Z])/', $kursi_str, $m_br)) {
                $br_letter = $m_br[1];
            }
            $br_queue_count = $barber_queue_counts[$br['id']] ?? 0;
            $colors = $barber_letter_colors[$br_letter] ?? $barber_letter_colors['A'];
            
            $b_user_id = $br['user_id'] ?? 0;
            $b_profile_files = glob(__DIR__ . '/../../asset/image/profile_' . $b_user_id . '.*');
            $b_photo_url = !empty($b_profile_files) ? '../asset/image/' . basename($b_profile_files[0]) : null;

            $stmt_serv = $pdo_early->prepare("SELECT COUNT(*) FROM antrian WHERE barber_id = ? AND status_antrean = 'serving'");
            $stmt_serv->execute([$br['id']]);
            $is_serving = ($stmt_serv->fetchColumn() > 0);

            $nama_parts = explode(' ', trim($br['nama']));
            $initials = strtoupper(substr($nama_parts[0], 0, 1)) . (isset($nama_parts[1]) ? strtoupper(substr($nama_parts[1], 0, 1)) : '');
            $br_has_selected_chair = (!empty($br['tgl_kursi']) && $br['tgl_kursi'] === date('Y-m-d'));
        ?>
        <div class="barber-card group relative cursor-pointer rounded-2xl border-2 border-white/10 bg-[#1A1612] p-5 shadow-lg transition-all duration-300 hover:border-amber-500/50 hover:shadow-[0_0_20px_rgba(245,158,11,0.15)] hover:-translate-y-1 select-none"
             data-barber-id="<?= $br['id'] ?>"
             data-barber-name="<?= htmlspecialchars($br['nama']) ?>"
             data-barber-kursi="<?= htmlspecialchars($br['kursi']) ?>"
             data-barber-letter="<?= $br_letter ?>"
             onclick="selectBarber(this)">

            <div class="flex flex-col gap-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <?php if ($b_photo_url): ?>
                            <img src="<?= $b_photo_url ?>" alt="<?= htmlspecialchars($br['nama']) ?>" class="w-13 h-13 rounded-2xl object-cover border-2 border-amber-500/40 shadow-md shrink-0 group-hover:scale-105 transition-transform">
                        <?php else: ?>
                            <div class="w-13 h-13 rounded-2xl bg-gradient-to-br <?= $colors['bg'] ?> flex items-center justify-center shadow-lg text-white font-black text-base shrink-0 group-hover:scale-105 transition-transform border border-white/10">
                                <?= $initials ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <h4 class="text-white font-bold text-base leading-tight group-hover:text-amber-300 transition-colors"><?= htmlspecialchars($br['nama']) ?></h4>
                            <p class="text-xs text-zinc-400 mt-1"><?= htmlspecialchars($br['spesialisasi'] ?? 'Hair Stylist') ?> · <span class="text-amber-400/80 font-medium"><?= htmlspecialchars($br['tingkatan'] ?? 'Professional') ?></span></p>
                        </div>
                    </div>

                    <div class="barber-selected-tick hidden shrink-0">
                        <div class="w-7 h-7 rounded-full bg-amber-400 flex items-center justify-center shadow-[0_0_12px_rgba(245,158,11,0.6)]">
                            <i data-lucide="check" class="w-4 h-4 text-amber-950 font-black"></i>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <?php if ($br_has_selected_chair): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30" title="Kursi aktif tugas hari ini">
                            <i data-lucide="armchair" class="w-3.5 h-3.5 text-amber-400"></i>
                            <?= htmlspecialchars($br['kursi']) ?> (Hari Ini)
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-medium bg-zinc-800/80 text-zinc-400 border border-white/10" title="Barber belum mengonfirmasi kursi bertugas hari ini">
                            <i data-lucide="armchair" class="w-3.5 h-3.5 text-zinc-500"></i>
                            <?= htmlspecialchars($br['kursi']) ?> (Belum Siap)
                        </span>
                    <?php endif; ?>

                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold <?= $colors['badge'] ?> border">
                        <i data-lucide="ticket" class="w-3.5 h-3.5"></i>
                        No Tiket: <strong><?= $br_letter ?>-xx</strong>
                    </span>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-white/10 text-xs">
                    <div class="flex items-center gap-1.5 font-medium">
                        <i data-lucide="users" class="w-3.5 h-3.5 text-zinc-400"></i>
                        <?php if ($br_queue_count === 0): ?>
                            <span class="text-emerald-400 font-semibold">Tidak ada antrean</span>
                        <?php else: ?>
                            <span class="text-zinc-300"><strong><?= $br_queue_count ?></strong> orang dalam antrean</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_serving): ?>
                        <span class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>Melayani
                        </span>
                    <?php elseif ($br_queue_count === 0): ?>
                        <span class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>Tersedia
                        </span>
                    <?php else: ?>
                        <span class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>Menunggu
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Sticky Bottom Action Bar — Submit Form -->
    <div class="fixed bottom-24 md:bottom-6 left-0 right-0 z-50 px-6 transition-all duration-300 translate-y-4 opacity-0 pointer-events-none transform-gpu" id="barber-submit-bar">
        <div class="md:pl-64 lg:pl-64 transition-all duration-300">
            <div class="max-w-2xl mx-auto bg-[#18120b]/95 border border-amber-500/60 rounded-2xl px-5 py-3.5 flex justify-between items-center shadow-2xl relative overflow-hidden">
                <div class="relative z-10 flex items-center gap-3.5 min-w-0">
                    <div class="w-11 h-11 rounded-xl bg-amber-400/20 border border-amber-500/40 flex items-center justify-center shrink-0 shadow-inner text-amber-400">
                        <i data-lucide="ticket" class="w-6 h-6"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-zinc-400 font-semibold uppercase tracking-wider">Konfirmasi Pilihan Barber</p>
                        <p id="submit-barber-name" class="text-white font-bold text-sm truncate leading-tight"></p>
                        <p id="submit-barber-kursi" class="text-amber-400 text-xs font-semibold leading-none mt-0.5 truncate"></p>
                    </div>
                </div>

                <form id="form-ambil-antrian" action="dashboard.php" method="POST" class="relative z-10 shrink-0 ml-3">
                    <input type="hidden" name="action" value="take_ticket">
                    <input type="hidden" name="service_id" id="hidden-service-id" value="">
                    <input type="hidden" name="barber_id" id="hidden-barber-id" value="">
                    <button type="submit" id="btn-final-submit"
                        class="bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 active:scale-95 text-amber-950 font-extrabold text-sm px-6 py-3 rounded-xl transition-all shadow-lg flex items-center gap-2 whitespace-nowrap border border-amber-300/50">
                        <span>Ambil Antrean</span>
                        <i data-lucide="arrow-right" class="w-4 h-4 stroke-[3]"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let selectedServiceId = null;
    let selectedServiceName = '';
    let selectedServicePrice = '';
    let selectedBarberId = null;

    function selectLayanan(el) {
        document.querySelectorAll('.service-item').forEach(function(card) {
            card.classList.remove('border-amber-500', 'shadow-[0_0_20px_rgba(245,158,11,0.2)]');
            card.classList.add('border-white/5');
            card.querySelector('.selected-overlay').classList.replace('opacity-100', 'opacity-0');
            card.querySelector('.selected-tick').classList.add('hidden');
        });

        el.classList.remove('border-white/5');
        el.classList.add('border-amber-500', 'shadow-[0_0_20px_rgba(245,158,11,0.2)]');
        el.querySelector('.selected-overlay').classList.replace('opacity-0', 'opacity-100');
        el.querySelector('.selected-tick').classList.remove('hidden');

        selectedServiceId = el.dataset.id;
        selectedServiceName = el.dataset.name;
        selectedServicePrice = el.dataset.priceFmt;

        document.getElementById('fab-name').textContent = selectedServiceName;
        document.getElementById('fab-price').textContent = selectedServicePrice;

        const bar = document.getElementById('layanan-action-bar');
        bar.classList.remove('translate-y-4', 'opacity-0', 'pointer-events-none');
        bar.classList.add('translate-y-0', 'opacity-100');

        lucide.createIcons();
    }

    function openBarberStep() {
        if (!selectedServiceId) return;

        document.getElementById('layanan-main-content').style.display = 'none';
        document.getElementById('layanan-action-bar').style.display = 'none';

        const step2 = document.getElementById('step-pilih-barber');
        step2.classList.remove('hidden');
        step2.style.opacity = '0';
        step2.style.transform = 'translateX(30px)';
        setTimeout(() => {
            step2.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            step2.style.opacity = '1';
            step2.style.transform = 'translateX(0)';
        }, 10);

        document.getElementById('step2-service-name').textContent = selectedServiceName;
        document.getElementById('step2-service-price').textContent = selectedServicePrice;

        selectedBarberId = null;
        document.querySelectorAll('.barber-card').forEach(c => {
            c.classList.remove('border-amber-400', 'bg-amber-500/10', 'ring-2', 'ring-amber-400/50', 'scale-[1.01]');
            c.classList.add('border-white/10');
            c.querySelector('.barber-selected-tick').classList.add('hidden');
        });

        const submitBar = document.getElementById('barber-submit-bar');
        submitBar.classList.remove('translate-y-0', 'opacity-100');
        submitBar.classList.add('translate-y-4', 'opacity-0', 'pointer-events-none');

        const mainArea = document.querySelector('main');
        if (mainArea) mainArea.scrollTop = 0;

        lucide.createIcons();
    }

    function backToLayanan() {
        const step2 = document.getElementById('step-pilih-barber');
        step2.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        step2.style.opacity = '0';
        step2.style.transform = 'translateX(30px)';

        setTimeout(() => {
            step2.classList.add('hidden');
            step2.style.opacity = '';
            step2.style.transform = '';
            step2.style.transition = '';

            document.getElementById('layanan-main-content').style.display = '';
            document.getElementById('layanan-action-bar').style.display = '';
        }, 200);

        const mainArea = document.querySelector('main');
        if (mainArea) mainArea.scrollTop = 0;
    }

    function selectBarber(el) {
        document.querySelectorAll('.barber-card').forEach(function(card) {
            card.classList.remove('border-amber-400', 'bg-amber-500/10', 'ring-2', 'ring-amber-400/50', 'scale-[1.01]');
            card.classList.add('border-white/10');
            card.querySelector('.barber-selected-tick').classList.add('hidden');
        });

        el.classList.remove('border-white/10');
        el.classList.add('border-amber-400', 'bg-amber-500/10', 'ring-2', 'ring-amber-400/50', 'scale-[1.01]');
        el.querySelector('.barber-selected-tick').classList.remove('hidden');

        selectedBarberId = el.dataset.barberId;
        const barberName = el.dataset.barberName;
        const barberKursi = el.dataset.barberKursi;
        const barberLetter = el.dataset.barberLetter;

        document.getElementById('hidden-service-id').value = selectedServiceId;
        document.getElementById('hidden-barber-id').value = (selectedBarberId === '0') ? '' : selectedBarberId;

        document.getElementById('submit-barber-name').textContent = barberName;
        if (selectedBarberId === '0') {
            document.getElementById('submit-barber-kursi').textContent = 'Sistem akan otomatis memilih antrean paling singkat';
        } else {
            document.getElementById('submit-barber-kursi').textContent = barberKursi + ' · Estimasi Tiket ' + barberLetter + '-xx';
        }

        const submitBar = document.getElementById('barber-submit-bar');
        submitBar.classList.remove('translate-y-4', 'opacity-0', 'pointer-events-none');
        submitBar.classList.add('translate-y-0', 'opacity-100');

        lucide.createIcons();
    }

    function onMainServiceChange(selectEl) {
        const btn = document.getElementById('btn_submit_antrean');
        if (btn) {
            if (selectEl && selectEl.value) {
                btn.removeAttribute('disabled');
            } else {
                btn.setAttribute('disabled', 'disabled');
            }
        }
    }

    window.selectLayanan = selectLayanan;
    window.onMainServiceChange = onMainServiceChange;
    window.openBarberStep = openBarberStep;
    window.backToLayanan = backToLayanan;
    window.selectBarber = selectBarber;

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
</section>
