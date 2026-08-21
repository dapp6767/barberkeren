<?php if ($page === 'dashboard' || empty($page)): ?>

<!-- DASHBOARD METRIC CARDS (LUXURY DARK GOLD THEME CONNECTED TO DB) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6 mb-6">
    <!-- Card 1: Antrean Hari Ini -->
    <div class="bg-[#18120b] border border-white/10 hover:border-amber-500/50 rounded-xl p-5 shadow-xl flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/60 group relative overflow-hidden">
        <div class="relative z-10">
            <!-- Header -->
            <div class="flex items-center justify-between text-zinc-300 mb-2">
                <span class="text-sm font-medium tracking-wide group-hover:text-[#fde68a] transition-colors">Antrean Hari Ini</span>
                <button type="button" onclick="openCardModal('todayQueueModal')" class="w-6 h-6 rounded-full border border-amber-500/40 flex items-center justify-center text-xs font-serif text-amber-300 hover:text-amber-200 hover:border-amber-400 hover:bg-amber-400/10 cursor-pointer transition-all duration-200" title="Buka Detail Antrean Hari Ini">i</button>
            </div>
            <!-- Big Metric Value -->
            <div class="text-2xl lg:text-3xl font-bold text-white tracking-tight mb-3">
                <?= number_format($today_antrian_total) ?> <span class="text-sm font-normal text-amber-400/90">Antrean</span>
            </div>
            <!-- Status Indicators -->
            <div class="space-y-1.5 text-xs text-zinc-300 mb-2">
                <div class="flex items-center justify-between">
                    <span>Menunggu (Waiting)</span>
                    <span class="text-amber-400 font-bold"><?= $today_antrian_waiting ?> Orang</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Sedang Dilayani / Selesai</span>
                    <span class="text-emerald-400 font-bold"><?= $today_antrian_serving + $today_antrian_completed ?> Orang</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Pendapatan Perhari -->
    <div class="bg-[#18120b] border border-white/10 hover:border-amber-500/50 rounded-xl p-5 shadow-xl flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/60 group relative overflow-hidden">
        <div class="relative z-10">
            <!-- Header -->
            <div class="flex items-center justify-between text-zinc-300 mb-1">
                <span class="text-sm font-medium tracking-wide group-hover:text-[#fde68a] transition-colors">Pendapatan Perhari</span>
                <button type="button" onclick="openCardModal('dailyRevenueModal')" class="w-6 h-6 rounded-full border border-amber-500/40 flex items-center justify-center text-xs font-serif text-amber-300 hover:text-amber-200 hover:border-amber-400 hover:bg-amber-400/10 cursor-pointer transition-all duration-200" title="Buka Detail Pendapatan Perhari">i</button>
            </div>
            <!-- Big Metric Value -->
            <div class="text-2xl lg:text-3xl font-bold text-white tracking-tight mb-2">
                Rp <?= number_format($sales_today_val, 0, ',', '.') ?>
            </div>
        </div>
    </div>

    <!-- Card 3: Top Layanan Terlaris -->
    <div class="bg-[#18120b] border border-white/10 hover:border-amber-500/50 rounded-xl p-5 shadow-xl flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/60 group relative overflow-hidden">
        <div class="relative z-10">
            <!-- Header -->
            <div class="flex items-center justify-between text-zinc-300 mb-1">
                <span class="text-sm font-medium tracking-wide group-hover:text-[#fde68a] transition-colors">Top Layanan Terlaris</span>
                <button type="button" onclick="openCardModal('topLayananModal')" class="w-6 h-6 rounded-full border border-amber-500/40 flex items-center justify-center text-xs font-serif text-amber-300 hover:text-amber-200 hover:border-amber-400 hover:bg-amber-400/10 cursor-pointer transition-all duration-200" title="Buka Detail Top Layanan">i</button>
            </div>
            <?php 
            $top_single = $modal_top_layanan[0] ?? null;
            $top_name = $top_single['nama_layanan'] ?? 'Gentleman Cut';
            $top_count = (int)($top_single['count_trx'] ?? 0);
            ?>
            <!-- Big Metric Value: Nama Layanan Terfavorit -->
            <div class="text-lg lg:text-xl font-bold text-amber-300 tracking-tight truncate my-1" title="<?= htmlspecialchars($top_name) ?>">
                <?= htmlspecialchars($top_name) ?>
            </div>
            <div class="text-xs text-zinc-400 mb-2 flex items-center gap-1">
                <span class="font-bold text-emerald-400"><?= number_format($top_count) ?>x</span> dipesan oleh pelanggan
            </div>
            
            <!-- Top 3 Layanan Mini Progress Bar -->
            <div class="space-y-1.5 pt-2 border-t border-white/5">
                <?php 
                $top_3_items = array_slice($modal_top_layanan, 0, 3);
                $max_cnt = !empty($top_3_items) ? max(array_column($top_3_items, 'count_trx')) : 1;
                if (empty($top_3_items)):
                ?>
                    <div class="text-[11px] text-zinc-500 italic">Belum ada data transaksi</div>
                <?php 
                else:
                    foreach ($top_3_items as $titem): 
                        $pct = $max_cnt > 0 ? round(($titem['count_trx'] / $max_cnt) * 100) : 0;
                ?>
                    <div class="flex flex-col gap-0.5">
                        <div class="flex justify-between items-center text-[11px] text-zinc-300">
                            <span class="truncate max-w-[140px]"><?= htmlspecialchars($titem['nama_layanan']) ?></span>
                            <span class="text-amber-400 font-semibold text-[10px]"><?= $titem['count_trx'] ?>x</span>
                        </div>
                        <div class="w-full bg-zinc-800 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-gradient-to-r from-amber-600 to-amber-400 h-full rounded-full" style="width: <?= max(10, $pct) ?>%;"></div>
                        </div>
                    </div>
                <?php 
                    endforeach; 
                endif;
                ?>
            </div>
        </div>
    </div>

    <!-- Card 4: Users & Barber -->
    <div class="bg-[#18120b] border border-white/10 hover:border-amber-500/50 rounded-xl p-5 shadow-xl flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/60 group relative overflow-hidden">
        <div class="relative z-10">
            <!-- Header -->
            <div class="flex items-center justify-between text-zinc-300 mb-1">
                <span class="text-sm font-medium tracking-wide group-hover:text-[#fde68a] transition-colors">Users & Barber</span>
                <button type="button" onclick="openCardModal('usersModal')" class="w-6 h-6 rounded-full border border-amber-500/40 flex items-center justify-center text-xs font-serif text-amber-300 hover:text-amber-200 hover:border-amber-400 hover:bg-amber-400/10 cursor-pointer transition-all duration-200" title="Buka Detail Users & Barber">i</button>
            </div>
            <!-- Big Metric Value -->
            <div class="text-2xl lg:text-3xl font-bold text-white tracking-tight mb-2">
                <?= number_format($total_users_count) ?>
            </div>
            <!-- Status Gauge / Activity Sparkline -->
            <div class="h-12 w-full flex flex-col justify-center gap-1.5 px-1">
                <div class="flex justify-between items-center text-[11px] text-zinc-300 font-medium">
                    <span>Barber Aktif</span>
                    <span class="text-emerald-400 font-bold"><?= $total_barbers_active ?> Active</span>
                </div>
                <div class="w-full bg-zinc-800/90 h-2 rounded-full overflow-hidden p-0.5 border border-zinc-700/50">
                    <div class="bg-gradient-to-r from-amber-500 via-amber-400 to-emerald-400 h-full rounded-full transition-all duration-500 shadow-sm" style="width: 100%;"></div>
                </div>
                <div class="flex justify-between items-center text-[10px] text-zinc-300">
                    <span>Kapasitas Layanan</span>
                    <span class="text-amber-300 font-semibold">100% Ready</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- METRIC DETAIL MODALS OVERLAY -->
<div id="cardModalBackdrop" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden transition-opacity duration-300 items-center justify-center p-4 overflow-y-auto">

    <!-- 1. Today Queue Modal -->
    <div id="todayQueueModal" class="card-modal-content hidden bg-[#18120c] border border-amber-600/40 rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative text-white my-auto">
        <div class="flex items-center justify-between border-b border-amber-900/40 pb-4 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <i data-lucide="list-ordered" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-amber-100">Detail Antrean Hari Ini (<?= date('d M Y') ?>)</h3>
                    <p class="text-xs text-zinc-400">Rincian status antrean pelanggan hari ini</p>
                </div>
            </div>
            <button type="button" onclick="closeCardModal()" class="text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-zinc-800 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Summary Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Total Antrean</p>
                <p class="text-base font-bold text-white"><?= number_format($today_antrian_total) ?></p>
            </div>
            <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Menunggu (Waiting)</p>
                <p class="text-base font-bold text-amber-400"><?= number_format($today_antrian_waiting) ?></p>
            </div>
            <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Sedang Dilayani</p>
                <p class="text-base font-bold text-sky-400"><?= number_format($today_antrian_serving) ?></p>
            </div>
            <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Selesai</p>
                <p class="text-base font-bold text-emerald-400"><?= number_format($today_antrian_completed) ?></p>
            </div>
        </div>

        <div class="text-center pt-2">
            <a href="admin.php?page=antrean" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 border border-amber-500/40 text-xs font-semibold transition-all">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                Buka Halaman Kelola Antrean Selengkapnya
            </a>
        </div>
    </div>

    <!-- 2. Daily Revenue Modal -->
    <div id="dailyRevenueModal" class="card-modal-content hidden bg-[#18120c] border border-amber-600/40 rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative text-white my-auto">
        <div class="flex items-center justify-between border-b border-amber-900/40 pb-4 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-amber-100">Detail Pendapatan Perhari</h3>
                    <p class="text-xs text-zinc-400">Statistik dan rincian omset harian barbershop</p>
                </div>
            </div>
            <button type="button" onclick="closeCardModal()" class="text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-zinc-800 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Hari Ini (<?= date('d M Y') ?>)</p>
                <p class="text-base font-bold text-emerald-400">Rp <?= number_format($sales_today_val, 0, ',', '.') ?></p>
                <p class="text-[10px] text-zinc-400 mt-0.5"><?= $sales_today_trx_count ?> Transaksi Lunas</p>
            </div>
            <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Kemarin (<?= date('d M Y', strtotime('-1 day')) ?>)</p>
                <p class="text-base font-bold text-amber-300">Rp <?= number_format($sales_yesterday, 0, ',', '.') ?></p>
                <p class="text-[10px] text-zinc-400 mt-0.5">Pendapatan Harian Kemarin</p>
            </div>
            <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Rata-rata Harian</p>
                <p class="text-base font-bold text-amber-400">Rp <?= number_format($avg_daily_revenue, 0, ',', '.') ?></p>
                <p class="text-[10px] text-zinc-400 mt-0.5">Per Hari Aktif</p>
            </div>
        </div>

        <h4 class="text-xs font-semibold text-amber-200 uppercase tracking-wider mb-2">Riwayat Pendapatan Harian (7 Hari Terakhir)</h4>
        <div class="space-y-2 mb-4">
            <?php 
            $max_rev_day = !empty($modal_revenue_daily) ? max(array_column($modal_revenue_daily, 'total')) : 1;
            foreach($modal_revenue_daily as $rd):
                $pct_r = round(($rd['total'] / max(1, $max_rev_day)) * 100);
            ?>
            <div class="bg-[#100b07] border border-zinc-800/80 rounded-lg p-2.5 flex items-center justify-between text-xs gap-3">
                <span class="text-zinc-400 w-24 shrink-0"><?= date('d M Y', strtotime($rd['tgl'])) ?></span>
                <div class="flex-1 bg-zinc-900 h-2 rounded-full overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-500 to-yellow-400 h-full rounded-full" style="width: <?= $pct_r ?>%;"></div>
                </div>
                <span class="text-amber-300 font-bold text-right shrink-0">Rp <?= number_format($rd['total'], 0, ',', '.') ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 3. Payments Modal -->
    <div id="paymentsModal" class="card-modal-content hidden bg-[#18120c] border border-blue-600/40 rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative text-white my-auto">
        <div class="flex items-center justify-between border-b border-blue-900/40 pb-4 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-blue-100">Detail Transaksi & Pembayaran</h3>
                    <p class="text-xs text-zinc-400">Statistik transaksi lunas, status antrean, dan rasio konversi</p>
                </div>
            </div>
            <button type="button" onclick="closeCardModal()" class="text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-zinc-800 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <div class="bg-[#100b07] border border-blue-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Transaksi Lunas</p>
                <p class="text-base font-bold text-blue-400"><?= number_format($total_transaksi_lunas) ?></p>
            </div>
            <div class="bg-[#100b07] border border-blue-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Total Antrean Dibuat</p>
                <p class="text-base font-bold text-amber-400"><?= number_format($total_antrean_count) ?></p>
            </div>
            <div class="bg-[#100b07] border border-blue-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Conversion Rate</p>
                <p class="text-base font-bold text-emerald-400"><?= $conversion_rate ?>%</p>
            </div>
        </div>

        <h4 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-2">Breakdown Status Antrean</h4>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-5 text-xs">
            <div class="bg-[#100b07] border border-zinc-800 p-3 rounded-lg text-center">
                <span class="text-emerald-400 font-bold text-lg block"><?= $modal_queue_status['completed'] ?? 0 ?></span>
                <span class="text-zinc-400 text-[11px]">Selesai</span>
            </div>
            <div class="bg-[#100b07] border border-zinc-800 p-3 rounded-lg text-center">
                <span class="text-sky-400 font-bold text-lg block"><?= $modal_queue_status['review'] ?? 0 ?></span>
                <span class="text-zinc-400 text-[11px]">Ulasan</span>
            </div>
            <div class="bg-[#100b07] border border-zinc-800 p-3 rounded-lg text-center">
                <span class="text-amber-400 font-bold text-lg block"><?= $modal_queue_status['payment'] ?? 0 ?></span>
                <span class="text-zinc-400 text-[11px]">Proses Bayar</span>
            </div>
            <div class="bg-[#100b07] border border-zinc-800 p-3 rounded-lg text-center">
                <span class="text-rose-400 font-bold text-lg block"><?= $modal_queue_status['skipped'] ?? 0 ?></span>
                <span class="text-zinc-400 text-[11px]">Dilewati</span>
            </div>
        </div>
    </div>

    <!-- 4. Users Modal -->
    <div id="usersModal" class="card-modal-content hidden bg-[#18120c] border border-amber-600/40 rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative text-white my-auto">
        <div class="flex items-center justify-between border-b border-amber-900/40 pb-4 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-amber-100">Detail Pengguna & Barber</h3>
                    <p class="text-xs text-zinc-400">Rincian user terdaftar berdasarkan role & status tim barber</p>
                </div>
            </div>
            <button type="button" onclick="closeCardModal()" class="text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-zinc-800 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Total Users</p>
                <p class="text-base font-bold text-amber-300"><?= number_format($total_users_count) ?></p>
            </div>
            <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Barber Aktif</p>
                <p class="text-base font-bold text-emerald-400"><?= $total_barbers_active ?> Barber</p>
            </div>
            <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                <p class="text-xs text-zinc-400 mb-1">Total Layanan</p>
                <p class="text-base font-bold text-sky-400"><?= $total_layanan_count ?? $total_layanan ?? 0 ?> Layanan</p>
            </div>
        </div>

        <h4 class="text-xs font-semibold text-amber-200 uppercase tracking-wider mb-2">Tim Barber Saat Ini</h4>
        <div class="overflow-x-auto mb-3">
            <table class="w-full text-xs text-left text-zinc-300 border-collapse">
                <thead>
                    <tr class="border-b border-zinc-800 text-zinc-400 bg-[#100b07]">
                        <th class="p-2.5 rounded-l-lg">Nama Barber</th>
                        <th class="p-2.5">Kursi Layanan</th>
                        <th class="p-2.5 text-center">Status</th>
                        <th class="p-2.5 text-center rounded-r-lg">Aksi Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/50">
                    <?php foreach($modal_barbers_detail as $mb): 
                        $is_active = in_array(strtolower($mb['status']), ['aktif']);
                        $status_badge_class = $is_active 
                            ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/50' 
                            : 'bg-rose-950/60 text-rose-400 border border-rose-800/50';
                    ?>
                    <tr class="hover:bg-amber-950/20 transition-colors">
                        <td class="p-2.5 font-medium text-white"><?= htmlspecialchars($mb['nama']) ?></td>
                        <td class="p-2.5"><span class="px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-300 border border-amber-500/30 font-semibold text-[11px]"><?= htmlspecialchars($mb['kursi'] ?? 'Kursi A') ?></span></td>
                        <td class="p-2.5 text-center"><span class="px-2 py-0.5 rounded <?= $status_badge_class ?> text-[11px] capitalize"><?= htmlspecialchars($mb['status']) ?></span></td>
                        <td class="p-2.5 text-center">
                            <form method="POST" action="" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status keaktifan barber <?= htmlspecialchars(addslashes($mb['nama'])) ?>?');">
                                <input type="hidden" name="form_type" value="toggle_barber_status">
                                <input type="hidden" name="barber_id" value="<?= $mb['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $is_active ? 'Nonaktif' : 'Aktif' ?>">
                                <?php if ($is_active): ?>
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/15 text-rose-300 hover:bg-rose-500/25 border border-rose-500/30 text-[11px] font-medium transition-all flex items-center gap-1 mx-auto">
                                        <i data-lucide="power" class="w-3 h-3 text-rose-400"></i> Nonaktifkan
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25 border border-emerald-500/30 text-[11px] font-medium transition-all flex items-center gap-1 mx-auto">
                                        <i data-lucide="power" class="w-3 h-3 text-emerald-400"></i> Aktifkan
                                    </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Fetch daily customer trend for the last 30 days (All 30 days populated)
$chartQuery = "
    SELECT DATE(waktu_dibuat) as tanggal, COUNT(*) as jumlah 
    FROM antrian 
    WHERE waktu_dibuat >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(waktu_dibuat) 
    ORDER BY DATE(waktu_dibuat) ASC
";
$chartStmt = $pdo->query($chartQuery);
$chartDataRaw = $chartStmt ? $chartStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$dbDataMap = [];
foreach ($chartDataRaw as $row) {
    $dbDataMap[$row['tanggal']] = (int)$row['jumlah'];
}

$phpLabels = [];
$phpDataVals = [];
$phpPeakIndex = 0;
$phpPeakValue = -1;
$phpTotal = 0;

for ($i = 29; $i >= 0; $i--) {
    $tglStr = date('Y-m-d', strtotime("-$i days"));
    $val = $dbDataMap[$tglStr] ?? 0;
    $phpLabels[] = date('d M', strtotime($tglStr));
    $phpDataVals[] = $val;
    $phpTotal += $val;

    if ($val > $phpPeakValue) {
        $phpPeakValue = $val;
        $phpPeakIndex = 29 - $i;
    }
}
$phpAverage = round($phpTotal / 30, 1);

// Fetch Top Customers by Total Money Spent (Total Pengeluaran)
$topSpendingStmt = $pdo->query("
    SELECT 
        u.id_user, 
        u.username, 
        COALESCE(NULLIF(u.fullname, ''), u.username) as nama,
        COUNT(t.id) as total_transaksi,
        COALESCE(SUM(t.total_harga), 0) as total_pengeluaran,
        MAX(t.waktu_bayar) as transaksi_terakhir
    FROM users u
    LEFT JOIN antrian a ON a.pelanggan_id = u.id_user
    LEFT JOIN transaksi t ON t.antrian_id = a.id
    WHERE u.role NOT IN ('admin', 'barber')
    GROUP BY u.id_user
    ORDER BY total_pengeluaran DESC, total_transaksi DESC
    LIMIT 20
");
$topSpendingList = $topSpendingStmt ? $topSpendingStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6 mb-4">
    <!-- Tren Pelanggan Harian (Horizontal Scroll Max 30 Hari) -->
    <div class="p-6 rounded-2xl border shadow-md flex flex-col justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
        <div>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4 pb-3 border-b border-amber-900/30">
                <h3 class="text-xl font-bold tracking-wide flex items-center gap-2" style="color:#e8d5a3;">
                    <i data-lucide="trending-up" class="w-5 h-5 text-sky-400"></i>
                    Tren Pelanggan (30 Hari Terakhir)
                </h3>
                <span class="text-[11px] text-amber-300 bg-amber-950/60 border border-amber-800/40 px-2.5 py-1 rounded-full font-medium flex items-center gap-1 shrink-0">
                    ↔ Geser Kiri / Kanan (Max 30 Hari)
                </span>
            </div>
            <div id="chartScrollContainer" class="overflow-x-auto custom-scroll pb-2">
                <div style="height: 330px; min-width: 1250px;">
                    <canvas id="adminChart1"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Customer Spending List (Foto Profil & Total Pengeluaran) -->
    <div class="p-6 rounded-2xl border shadow-md flex flex-col justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
        <div>
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-amber-900/30">
                <div>
                    <h3 class="text-xl font-serif font-bold tracking-wide flex items-center gap-2" style="color:#f0d375;">
                        <i data-lucide="crown" class="w-5 h-5 text-amber-400"></i>
                        Top Pengeluaran Pelanggan
                    </h3>
                    <p class="text-xs text-stone-400 mt-0.5">Pelanggan dengan kontribusi transaksi terbesar</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-serif font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 flex items-center gap-1">
                    👑 VIP Gentlemen Club
                </span>
            </div>

            <div class="space-y-3.5 max-h-[350px] overflow-y-auto pr-2 custom-scroll">
                <?php if (empty($topSpendingList)): ?>
                    <div class="text-center text-stone-400 py-8">Belum ada transaksi pelanggan</div>
                <?php else: ?>
                    <?php 
                    foreach ($topSpendingList as $idx => $cust): 
                        $rank = $idx + 1;
                        $formattedPrice = 'Rp ' . number_format($cust['total_pengeluaran'], 0, ',', '.');
                        $tglTerakhir = !empty($cust['transaksi_terakhir']) ? date('d M Y', strtotime($cust['transaksi_terakhir'])) : 'Belum ada';
                        
                        // Check if user has an actual profile photo file in asset/image/
                        $userPhotoPath = "../asset/image/profile_" . $cust['id_user'] . ".jpg";
                        $hasRealPhoto = file_exists(__DIR__ . '/../../' . $userPhotoPath);
                        $initial = strtoupper(substr($cust['nama'], 0, 1));
                    ?>
                    <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-amber-900/20 transition-colors border border-transparent hover:border-amber-900/30">
                        <div class="flex items-center gap-3.5">
                            <!-- Profile Avatar / Initial Badge -->
                            <div class="relative">
                                <?php if ($hasRealPhoto): ?>
                                    <img src="<?= $userPhotoPath ?>" alt="<?= htmlspecialchars($cust['nama']) ?>" class="w-11 h-11 rounded-full object-cover ring-2 ring-amber-500/40 shadow-md">
                                <?php else: ?>
                                    <!-- Default Initial Avatar Circle -->
                                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-amber-800 to-stone-900 border border-amber-600/40 flex items-center justify-center text-amber-200 font-serif font-bold text-base shadow-md">
                                        <?= $initial ?>
                                    </div>
                                <?php endif; ?>
                                
                                <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold <?= $rank === 1 ? 'bg-gradient-to-r from-amber-300 to-yellow-500 text-zinc-950 ring-2 ring-amber-300' : ($rank === 2 ? 'bg-slate-300 text-zinc-950' : ($rank === 3 ? 'bg-amber-700 text-white' : 'bg-stone-700 text-stone-300')) ?>">
                                    <?= $rank ?>
                                </span>
                            </div>
                            <!-- Name & Last Transaction Date -->
                            <div>
                                <h4 class="font-serif font-bold text-sm text-[#f8f4e9] flex items-center gap-1.5">
                                    <?= htmlspecialchars($cust['nama']) ?>
                                    <span class="font-sans text-xs font-normal text-amber-400/80">(@<?= htmlspecialchars($cust['username']) ?>)</span>
                                </h4>
                                <p class="text-xs text-stone-400 mt-0.5 flex items-center gap-2">
                                    <span><?= $cust['total_transaksi'] ?>x Berlayanan</span>
                                    <span class="text-stone-600">•</span>
                                    <span><?= $tglTerakhir ?></span>
                                </p>
                            </div>
                        </div>
                        <!-- Total Money Spent -->
                        <div class="text-right">
                            <div class="text-sm font-extrabold text-amber-400 tracking-wide">
                                <?= $formattedPrice ?>
                            </div>
                            <span class="text-[10px] text-stone-500 uppercase tracking-wider font-medium">Total Pengeluaran</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (document.getElementById('adminChart1')) {
            const labels = <?php echo json_encode($phpLabels); ?>;
            const dataVals = <?php echo json_encode($phpDataVals); ?>;
            const peakIndex = <?php echo $phpPeakIndex; ?>;
            const averageVal = <?php echo $phpAverage; ?>;
            
            const pointColors = dataVals.map((_, i) => i === peakIndex ? '#fde68a' : '#f59e0b');
            const pointRadii = dataVals.map((_, i) => i === peakIndex ? 7 : 4);
            const pointHoverRadii = dataVals.map((_, i) => i === peakIndex ? 9 : 6);
            
            const peakCalloutPlugin = {
                id: 'peakCallout',
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
                    ctx.strokeStyle = '#f59e0b';
                    ctx.lineWidth = 2;
                    ctx.stroke();
                    
                    ctx.fillStyle = '#18120b';
                    ctx.strokeStyle = '#f59e0b';
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    ctx.roundRect(boxX, y - 32, 185, 26, 6);
                    ctx.fill();
                    ctx.stroke();
                    
                    ctx.fillStyle = '#fafafa';
                    ctx.font = 'bold 12px sans-serif';
                    ctx.fillText(`Puncak: ${dataVals[peakIndex]} orang (${labels[peakIndex]})`, boxX + 8, y - 14);
                    ctx.restore();
                }
            };
            
            new Chart(document.getElementById('adminChart1'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Jumlah Pelanggan',
                            data: dataVals,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.18)',
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: pointColors,
                            pointBorderColor: '#18120b',
                            pointBorderWidth: 2,
                            pointRadius: pointRadii,
                            pointHoverRadius: pointHoverRadii,
                            borderWidth: 2
                        },
                        {
                            label: `Rata-rata Harian (${averageVal})`,
                            data: Array(dataVals.length).fill(averageVal),
                            borderColor: '#a1a1aa',
                            borderDash: [5, 5],
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 0,
                            fill: false,
                            tension: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: '#cbd5e1', usePointStyle: true, boxWidth: 8 } },
                        tooltip: {
                            backgroundColor: 'rgba(24, 24, 27, 0.9)',
                            titleColor: '#fafafa',
                            bodyColor: '#a1a1aa',
                            borderColor: 'rgba(56, 189, 248, 0.3)',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Tanggal', color: '#94a3b8', font: { size: 12, weight: 'bold' } },
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#94a3b8', font: { size: 11 } }
                        },
                        y: {
                            title: { display: true, text: 'Jumlah Pelanggan', color: '#94a3b8', font: { size: 12, weight: 'bold' } },
                            beginAtZero: true,
                            min: 0,
                            grid: { color: 'rgba(255, 255, 255, 0.05)', borderDash: [4, 4] },
                            ticks: { color: '#94a3b8', stepSize: 1 }
                        }
                    }
                },
                plugins: [peakCalloutPlugin]
            });

            // Auto scroll chart container to the right (latest date) on page load
            const scrollContainer = document.getElementById('chartScrollContainer');
            if (scrollContainer) {
                scrollContainer.scrollLeft = scrollContainer.scrollWidth;
            }
        }
    });
</script>

<!-- PENDAPATAN BULANAN MODULE (Moved to Dashboard) -->
<?php
    $tahunList = [];
    $yearStmt = $pdo->query("SELECT DISTINCT YEAR(waktu_bayar) as y FROM transaksi WHERE status_pembayaran='lunas' ORDER BY y DESC");
    $tahunList = $yearStmt->fetchAll(PDO::FETCH_COLUMN);
    $selectedTahun = (int)($_GET['tahun'] ?? date('Y'));
    
    $qRevYear = $pdo->prepare("
        SELECT MONTH(waktu_bayar) as bulan, SUM(total_harga) as total
        FROM transaksi
        WHERE YEAR(waktu_bayar) = ? AND status_pembayaran = 'lunas'
        GROUP BY MONTH(waktu_bayar)
        ORDER BY bulan ASC
    ");
    $qRevYear->execute([$selectedTahun]);
    $revRows = $qRevYear->fetchAll(PDO::FETCH_ASSOC);
    $revByMonth = array_fill(0, 12, 0);
    foreach ($revRows as $r) { $revByMonth[(int)$r['bulan']-1] = (float)$r['total']; }
    $totalRevYear = array_sum($revByMonth);
    $maxRevMonth = max($revByMonth) ?: 1;
    $bulanNama2 = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $highlightIdx = array_search(max($revByMonth), $revByMonth);
?>
<!-- Stats row (Pendapatan Bulanan) -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6" style="margin-top: 16px;">
    <div class="rounded-2xl p-5 border" style="background:linear-gradient(135deg,#1e1408,#120e06);border-color:#4a3020;">
        <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#8a6030;">Total Pendapatan <?= $selectedTahun ?></p>
        <p class="text-3xl font-bold" style="color:#e8d5a3;">Rp <?= number_format($totalRevYear, 0, ',', '.') ?></p>
    </div>
    <div class="rounded-2xl p-5 border" style="background:linear-gradient(135deg,#1e1408,#120e06);border-color:#4a3020;">
        <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#8a6030;">Bulan Terbaik</p>
        <p class="text-3xl font-bold" style="color:#c9a03a;"><?= $bulanNama2[$highlightIdx] ?? '-' ?></p>
        <p class="text-sm mt-1" style="color:#8a6030;">Rp <?= number_format($revByMonth[$highlightIdx] ?? 0, 0, ',', '.') ?></p>
    </div>
    <div class="rounded-2xl p-5 border" style="background:linear-gradient(135deg,#1e1408,#120e06);border-color:#4a3020;">
        <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#8a6030;">Rata-rata / Bulan</p>
        <p class="text-3xl font-bold" style="color:#e8d5a3;">Rp <?= number_format($totalRevYear / 12, 0, ',', '.') ?></p>
    </div>
</div>

<!-- Chart Card -->
<div class="rounded-2xl border p-6" style="background:linear-gradient(135deg,#1a1208 0%,#0e0a08 100%);border-color:#3a2510;">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-bold" style="color:#e8d5a3;">Pendapatan Bulanan</h2>
            <p class="text-sm mt-1" style="color:#8a6030;">Grafik pendapatan per bulan tahun <?= $selectedTahun ?></p>
        </div>
        <!-- Year Selector -->
        <form method="GET" class="flex items-center gap-2">
            <input type="hidden" name="page" value="dashboard">
            <select name="tahun" onchange="this.form.submit()" class="rounded-lg px-3 py-1.5 text-sm font-medium border cursor-pointer" style="background:#1a1208;color:#e8d5a3;border-color:#5c3d1a;outline:none;">
                <?php foreach ($tahunList as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $selectedTahun ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
                <?php if (empty($tahunList)): ?>
                    <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                <?php endif; ?>
            </select>
        </form>
    </div>
    <div style="position:relative; height:420px; width:100%;">
        <canvas id="chartPendapatanBulanan"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bulanLabels = <?= json_encode($bulanNama2) ?>;
    const revenueData = <?= json_encode(array_values($revByMonth)) ?>;
    const highlightIdx = <?= (int)$highlightIdx ?>;

    // Format to Juta (millions)
    const dataJuta = revenueData.map(v => parseFloat((v / 1000000).toFixed(2)));
    const maxVal = Math.max(...dataJuta);

    // Build colors: highlighted month gets vivid gold, rest dark amber
    const barColors = dataJuta.map((_, i) =>
        i === highlightIdx
            ? 'rgba(245, 158, 11, 0.95)'
            : 'rgba(217, 119, 6, 0.82)'
    );
    const barColorsHover = dataJuta.map((_, i) =>
        i === highlightIdx
            ? 'rgba(253, 230, 138, 1)'
            : 'rgba(245, 158, 11, 1)'
    );

    // Plugin: draw value labels on top of each bar
    const topLabelPlugin = {
        id: 'topLabel',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
            const meta = chart.getDatasetMeta(0);
            meta.data.forEach((bar, i) => {
                const value = data.datasets[0].data[i];
                if (!value) return;
                ctx.save();
                ctx.fillStyle = '#e8d5a3';
                ctx.font = 'bold 11px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                const label = value >= 1 ? value.toFixed(1) + 'M' : (value * 1000).toFixed(0) + 'K';
                ctx.fillText(label, bar.x, bar.y - 4);
                ctx.restore();
            });
        }
    };

    new Chart(document.getElementById('chartPendapatanBulanan'), {
        type: 'bar',
        data: {
            labels: bulanLabels,
            datasets: [{
                label: 'Pendapatan (Juta Rp)',
                data: dataJuta,
                backgroundColor: barColors,
                hoverBackgroundColor: barColorsHover,
                borderRadius: 6,
                borderSkipped: false,
                borderWidth: 0,
                barPercentage: 0.65,
                categoryPercentage: 0.8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 24, bottom: 4 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(14,10,8,0.95)',
                    titleColor: '#c9a03a',
                    bodyColor: '#e8d5a3',
                    borderColor: 'rgba(201,160,58,0.4)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        title: (items) => items[0].label,
                        label: (item) => {
                            const raw = revenueData[item.dataIndex];
                            return ' Rp ' + raw.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#a08040', font: { size: 13, weight: '500' } },
                    grid: { display: false },
                    border: { color: 'rgba(90,60,20,0.3)' }
                },
                y: {
                    title: { display: true, text: 'Pendapatan (Juta Rp)', color: '#8a6030', font: { size: 12 } },
                    ticks: {
                        color: '#8a6030',
                        callback: (v) => v + 'M',
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(90,60,20,0.15)',
                        borderDash: [4, 4]
                    },
                    border: { display: false },
                    min: 0,
                    suggestedMax: maxVal * 1.2
                }
            },
            animation: {
                duration: 900,
                easing: 'easeOutQuart'
            }
        },
        plugins: [topLabelPlugin]
    });
});
</script>
<?php endif; ?>
