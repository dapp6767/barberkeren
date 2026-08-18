<!-- Modal Pilih Kursi Tugas Hari Ini -->
<div id="selectKursiModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden justify-center items-center z-[9999]">
    <div class="bg-gradient-to-b from-[#1a1208] to-[#120e06] border-2 border-amber-500/60 text-zinc-100 p-6 sm:p-8 w-[92vw] max-w-[480px] rounded-2xl shadow-[0_0_50px_rgba(245,158,11,0.3)] relative">
        <button type="button" onclick="closeSelectKursiModal()" class="absolute top-4 right-4 text-stone-400 hover:text-white p-1 rounded-lg">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-300 mx-auto mb-3 text-2xl shadow-inner">
                💈
            </div>
            <h3 class="text-xl font-extrabold text-amber-200">Pilih Kursi Tugas Hari Ini</h3>
            <p class="text-xs text-stone-400 mt-1">Pilih kursi kerja Anda untuk bertugas melayani pelanggan pada hari ini (<?= date('d M Y') ?>).</p>
        </div>

        <form method="POST" action="barber.php?page=dashboard" class="space-y-4">
            <input type="hidden" name="action" value="select_kursi">
            
            <div class="grid grid-cols-1 gap-3">
                <?php 
                $chairs = [
                    'Kursi A' => ['desc' => 'Kursi Utama 1 (Depan)', 'icon' => 'armchair'],
                    'Kursi B' => ['desc' => 'Kursi Utama 2 (Tengah)', 'icon' => 'armchair'],
                    'Kursi C' => ['desc' => 'Kursi VIP / 3 (Samping)', 'icon' => 'armchair'],
                ];
                $current_chair = $barber['kursi'] ?? 'Kursi A';
                foreach ($chairs as $kKey => $kInfo):
                    $is_occupied = isset($occupied_chairs[$kKey]);
                    $occupied_by = $is_occupied ? $occupied_chairs[$kKey] : '';
                    $is_selected_by_me = ($has_selected_chair_today && $current_chair === $kKey);
                ?>
                <label class="relative flex items-center justify-between p-4 rounded-xl border-2 transition-all cursor-pointer <?= $is_selected_by_me ? 'border-amber-400 bg-amber-500/15 shadow-[0_0_15px_rgba(245,158,11,0.2)]' : ($is_occupied ? 'border-zinc-800 bg-zinc-900/40 opacity-60 cursor-not-allowed' : 'border-amber-900/40 bg-zinc-900/60 hover:border-amber-500/50 hover:bg-amber-950/20') ?>">
                    <div class="flex items-center gap-3.5">
                        <input type="radio" name="kursi" value="<?= $kKey ?>" <?= $is_selected_by_me ? 'checked' : '' ?> <?= ($is_occupied && !$is_selected_by_me) ? 'disabled' : '' ?> class="w-4 h-4 text-amber-500 focus:ring-amber-500 bg-stone-900 border-stone-700">
                        <div>
                            <span class="font-bold text-white text-base block"><?= $kKey ?></span>
                            <span class="text-xs text-stone-400 block"><?= $kInfo['desc'] ?></span>
                        </div>
                    </div>
                    <div>
                        <?php if ($is_selected_by_me): ?>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">Kursi Anda</span>
                        <?php elseif ($is_occupied): ?>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40" title="Diisi oleh <?= htmlspecialchars($occupied_by) ?>">Terisi (<?= htmlspecialchars($occupied_by) ?>)</span>
                        <?php else: ?>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">Tersedia</span>
                        <?php endif; ?>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-amber-950 font-black text-sm py-3 px-4 rounded-xl transition-all shadow-[0_0_20px_rgba(245,158,11,0.4)] flex items-center justify-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i> Simpan & Mulai Bertugas
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cetak Struk -->
<div id="receiptModal" class="receipt-modal">
    <div class="receipt-card" id="printable-receipt">
        <span class="r-title">ELITE BARBERSHOP</span>
        <span class="r-subtitle">Jl. Nawawi Gelar Dalom, Sumberjo, Rajabasa Jaya, Bandarlampung </span>
        <span class="r-subtitle">Telp: 0857-8894-2309</span>
        <hr>
        <p><span>No. Tiket</span> <span id="r_tiket"></span></p>
        <p><span>Pelanggan</span> <span id="r_nama"></span></p>
        <p><span>Layanan</span> <span id="r_layanan"></span></p>
        <hr>
        <p><span>TOTAL</span> <span>Rp <span id="r_total"></span></span></p>
        <p><span>PEMBAYARAN</span> <span id="r_metode"></span></p>
        <p><span>STATUS</span> <span>LUNAS</span></p>
        <hr>
        <span class="r-subtitle" style="margin-top:10px;">Terima kasih atas kunjungan Anda!</span>
        <span class="r-subtitle">IG: @</span>
        
        <button onclick="window.print()" class="no-print bg-adminlte-primary text-white w-full py-2 mt-4 rounded-md text-sm hover:bg-blue-600 transition-colors">🖨️ Cetak</button>
        <button onclick="closeStruk()" class="no-print bg-zinc-600 text-white w-full py-2 mt-2 rounded-md text-sm hover:bg-zinc-700 transition-colors">Tutup</button>
        <form id="form_confirm_paid" method="POST" style="display:none;" class="no-print mt-2">
            <input type="hidden" name="action" value="confirm_paid">
            <input type="hidden" name="antrian_id" id="r_antrian_id" value="">
            <input type="hidden" name="total_harga" id="r_total_input" value="">
            <button type="submit" id="btn_confirm_paid" class="bg-adminlte-success text-white w-full py-2 rounded-md text-sm hover:bg-green-700 transition-colors">Konfirmasi Selesai</button>
        </form>
    </div>
</div>
