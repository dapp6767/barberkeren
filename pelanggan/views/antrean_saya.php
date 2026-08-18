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
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=EliteBarberQRIS&color=3d2b1a" alt="QRIS" class="w-32 h-32 mx-auto bg-white p-1.5 rounded-lg shadow-md">
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

            <?php else: 
                $selected_service_id = $_GET['service_id'] ?? '';
                $selected_service_name = '';
                if (!empty($selected_service_id) && !empty($services)) {
                    foreach ($services as $s) {
                        $s_id = $s['id'] ?? $s['id_service'] ?? 0;
                        if ($s_id == $selected_service_id) {
                            $s_name = $s['nama_layanan'] ?? $s['service_name'] ?? '';
                            $s_price = (float)($s['harga'] ?? $s['price'] ?? 0);
                            $selected_service_name = $s_name . ' - Rp ' . number_format($s_price, 0, ',', '.');
                            break;
                        }
                    }
                }
            ?>
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
                            <input type="hidden" name="service_id" value="<?= htmlspecialchars($selected_service_id) ?>" required>
                            
                            <button type="button" onclick="navigateToTab('tab-layanan')" class="w-full bg-zinc-900/90 border border-amber-500/40 hover:border-amber-400 hover:bg-zinc-800/80 rounded-xl px-4 py-3.5 text-left text-white font-semibold transition-all duration-200 cursor-pointer group flex items-center justify-between shadow-md active:scale-[0.99]">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center shrink-0 group-hover:bg-amber-500/20 transition-colors">
                                        <i data-lucide="scissors" class="w-4 h-4 text-amber-400"></i>
                                    </div>
                                    <span class="truncate text-sm md:text-base <?= empty($selected_service_name) ? 'text-zinc-400 italic font-normal' : 'text-amber-300 font-bold' ?>">
                                        <?= !empty($selected_service_name) ? htmlspecialchars($selected_service_name) : '-- Pilih dari Menu Layanan --' ?>
                                    </span>
                                </div>
                                <span class="text-xs bg-amber-500/20 text-amber-300 group-hover:bg-amber-400 group-hover:text-zinc-950 font-bold px-3 py-1.5 rounded-lg border border-amber-500/30 transition-all shrink-0 ml-2 flex items-center gap-1">
                                    <?= !empty($selected_service_name) ? 'Ubah' : 'Pilih' ?> <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </span>
                            </button>
                        </div>
                        <button type="submit" id="btn_submit_antrean" <?= empty($selected_service_id) ? 'disabled' : '' ?> class="w-full bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-white font-bold py-3.5 px-6 rounded-xl transition-all shadow-lg border border-amber-400/30 flex items-center justify-center gap-2 min-h-[48px] text-base active:scale-98 mt-4 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-amber-600 disabled:hover:to-amber-500">
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
    <!-- Desktop Table View -->
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
                                <div class="text-zinc-200 font-medium"><?= htmlspecialchars($q['nama_layanan'] ?? $q['service_name'] ?? 'Standard Cut') ?></div>
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

    <!-- Mobile Vertical Card Stack -->
    <div class="block md:hidden p-4 space-y-3">
        <?php if (empty($active_queues)): ?>
            <div class="text-center text-zinc-400 py-8 text-sm">Belum ada antrean aktif saat ini.</div>
        <?php else: ?>
            <?php foreach ($active_queues as $q): 
                $is_my_row = (!empty($my_user_id) && isset($q['pelanggan_id']) && (int)$q['pelanggan_id'] === (int)$my_user_id);
                $card_border = $is_my_row ? 'border-2 border-amber-500 bg-amber-500/10 shadow-[0_0_20px_rgba(245,158,11,0.25)]' : 'border border-white/10 bg-zinc-900/60 hover:border-amber-500/30';
            ?>
            <div class="p-4 rounded-xl <?= $card_border ?> transition-all flex flex-col gap-3">
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

                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-[11px] text-zinc-400 uppercase font-medium block">Pelanggan</span>
                        <span class="font-bold text-white"><?= htmlspecialchars($q['customer_name']) ?></span>
                    </div>
                    <div>
                        <span class="text-[11px] text-zinc-400 uppercase font-medium block">Layanan & Harga</span>
                        <span class="font-semibold text-zinc-200"><?= htmlspecialchars($q['nama_layanan'] ?? $q['service_name'] ?? 'Standard Cut') ?></span>
                        <span class="text-xs text-emerald-400 font-bold block">Rp <?= number_format((float)($q['base_price'] ?? 0), 0, ',', '.') ?></span>
                    </div>
                </div>

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
</section>
