<!-- Daily Chair Selection Banner -->
<?php if (!$has_selected_chair_today): ?>
    <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-amber-950/80 via-amber-900/40 to-amber-950/80 border-2 border-amber-500/50 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 shrink-0 text-xl">
                💈
            </div>
            <div>
                <h4 class="font-bold text-amber-200 text-base">Anda Belum Memilih Kursi Tugas Hari Ini</h4>
                <p class="text-xs text-zinc-300">Silakan pilih Kursi A, B, atau C untuk hari ini agar pelanggan dapat memesan layanan Anda.</p>
            </div>
        </div>
        <button type="button" onclick="openSelectKursiModal()" class="bg-amber-500 hover:bg-amber-400 text-amber-950 font-bold text-xs px-5 py-2.5 rounded-lg transition-all duration-300 shadow-[0_0_15px_rgba(245,158,11,0.4)] shrink-0 flex items-center gap-1.5">
            <i data-lucide="armchair" class="w-4 h-4"></i> Pilih Kursi Sekarang
        </button>
    </div>
<?php else: ?>
    <div class="mb-6 p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between text-xs text-amber-300">
        <div class="flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
            <span>Kursi bertugas Anda hari ini: <strong class="text-amber-200 font-bold"><?= htmlspecialchars($barber['kursi']) ?></strong> (Berlaku s/d Akhir Hari Ini)</span>
        </div>
        <button type="button" onclick="openSelectKursiModal()" class="text-amber-400 hover:text-amber-200 font-semibold underline underline-offset-2">
            Ubah Kursi Tugas
        </button>
    </div>
<?php endif; ?>

<!-- Dashboard Stats -->
<div class="grid grid-cols-3 gap-3 sm:gap-6 mb-6">
    <div class="bg-adminlte-info rounded-xl p-3.5 sm:p-6 relative overflow-hidden text-white shadow-lg border border-blue-500/30">
        <div class="relative z-10">
            <h3 class="text-2xl sm:text-4xl font-black mb-0.5 sm:mb-1"><?= count($queues) ?></h3>
            <p class="text-blue-100 text-xs sm:text-sm font-medium">Total Antrean</p>
        </div>
        <i data-lucide="list" class="absolute -right-3 -bottom-3 sm:-right-4 sm:-bottom-4 w-16 h-16 sm:w-32 sm:h-32 text-white/10 z-0"></i>
    </div>
    <div class="bg-adminlte-warning rounded-xl p-3.5 sm:p-6 relative overflow-hidden text-zinc-950 shadow-lg border border-amber-500/40">
        <div class="relative z-10">
            <h3 class="text-2xl sm:text-4xl font-black mb-0.5 sm:mb-1"><?= $total_waiting ?></h3>
            <p class="text-amber-950 text-xs sm:text-sm font-semibold">Menunggu</p>
        </div>
        <i data-lucide="clock" class="absolute -right-3 -bottom-3 sm:-right-4 sm:-bottom-4 w-16 h-16 sm:w-32 sm:h-32 text-black/10 z-0"></i>
    </div>
    <div class="bg-adminlte-success rounded-xl p-3.5 sm:p-6 relative overflow-hidden text-white shadow-lg border border-emerald-500/30">
        <div class="relative z-10">
            <h3 class="text-2xl sm:text-4xl font-black mb-0.5 sm:mb-1"><?= $total_served ?></h3>
            <p class="text-emerald-100 text-xs sm:text-sm font-medium">Selesai</p>
        </div>
        <i data-lucide="check-circle" class="absolute -right-3 -bottom-3 sm:-right-4 sm:-bottom-4 w-16 h-16 sm:w-32 sm:h-32 text-black/10 z-0"></i>
    </div>
</div>

<!-- Antrean Queue Header -->
<div class="flex items-center justify-between mb-3 px-1">
    <h3 class="font-bold text-white text-base sm:text-lg flex items-center gap-2">
        <i data-lucide="list-ordered" class="w-5 h-5 text-amber-400"></i>
        Daftar Antrean Tugas Anda
    </h3>
    <span class="text-xs text-stone-400 bg-stone-900 px-2.5 py-1 rounded-full border border-stone-800">
        Total: <strong class="text-amber-300"><?= count($queues) ?></strong>
    </span>
</div>

<!-- Mobile Queue Cards View (Visible on Mobile < md) -->
<div class="block md:hidden space-y-3 mb-6">
    <?php if (empty($queues)): ?>
        <div class="p-8 text-center bg-adminlte-card rounded-xl border border-zinc-800 text-stone-500 text-sm">
            <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 text-stone-600"></i>
            Belum ada antrean masuk untuk Anda hari ini.
        </div>
    <?php else: ?>
        <?php foreach ($queues as $q): 
            $base_price = (float)($q['harga'] ?? 0);
            $final_price = $base_price;
            $status = $q['status_antrean'];

            $card_border = 'border-amber-900/40';
            $badge_bg = 'bg-amber-500/20 text-amber-300 border-amber-500/40';
            $status_label = 'MENUNGGU';

            if ($status === 'serving') {
                $card_border = 'border-blue-500/60 shadow-[0_0_15px_rgba(59,130,246,0.25)]';
                $badge_bg = 'bg-blue-500/20 text-blue-300 border-blue-500/40 animate-pulse';
                $status_label = 'SEDANG DILAYANI';
            } elseif ($status === 'payment') {
                $card_border = 'border-amber-500/60 shadow-[0_0_15px_rgba(245,158,11,0.25)]';
                $badge_bg = 'bg-amber-500/20 text-amber-300 border-amber-500/40';
                $status_label = 'MENUNGGU BAYAR';
            } elseif (in_array($status, ['paid', 'review', 'completed'])) {
                $card_border = 'border-emerald-500/40';
                $badge_bg = 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40';
                $status_label = 'SELESAI';
            }
        ?>
        <div class="p-4 rounded-xl border bg-gradient-to-b from-[#1a1208] to-[#120d07] <?= $card_border ?> shadow-lg space-y-3 transition-all duration-300">
            <!-- Header: Tiket & Status Badge -->
            <div class="flex items-center justify-between pb-2 border-b border-amber-900/30">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-stone-400">Tiket:</span>
                    <span class="text-xl font-black text-amber-200 tracking-wider bg-amber-950/80 px-2.5 py-0.5 rounded-lg border border-amber-800/50">
                        <?= htmlspecialchars($q['no_antrean']) ?>
                    </span>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider <?= $badge_bg ?>">
                    <?= $status_label ?>
                </span>
            </div>

            <!-- Details: Pelanggan & Layanan -->
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <span class="text-[10px] text-stone-400 block uppercase tracking-wider">Pelanggan</span>
                    <span class="font-bold text-white truncate block"><?= htmlspecialchars($q['pelanggan_nama'] ?? 'Guest') ?></span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-stone-400 block uppercase tracking-wider">Layanan</span>
                    <span class="font-bold text-amber-100 truncate block"><?= htmlspecialchars($q['nama_layanan'] ?? 'Cukur Standar') ?></span>
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <div class="text-xs text-stone-400">
                    <span>Harga:</span>
                    <span class="text-emerald-400 font-extrabold text-base ml-1">Rp <?= number_format($final_price, 0, ',', '.') ?></span>
                </div>
                <?php if ($status === 'paid' && !empty($q['metode_bayar'])): ?>
                    <span class="text-[10px] text-stone-300 bg-stone-900 px-2 py-0.5 rounded border border-stone-800">
                        Via: <?= htmlspecialchars($q['metode_bayar']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Mobile Action Buttons -->
            <div class="pt-2 border-t border-amber-900/30 flex flex-wrap gap-2">
                <?php if ($status === 'waiting'): ?>
                    <form method="POST" class="flex-1">
                        <input type="hidden" name="action" value="call">
                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs py-2.5 px-3 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-md active:scale-95">
                            <i data-lucide="megaphone" class="w-4 h-4"></i> Panggil
                        </button>
                    </form>
                    <form method="POST" class="shrink-0">
                        <input type="hidden" name="action" value="skip">
                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                        <button type="submit" class="bg-red-950/60 hover:bg-red-900/80 text-red-300 border border-red-800/40 text-xs py-2.5 px-3 rounded-lg flex items-center justify-center gap-1 transition-all active:scale-95" onclick="return confirm('Lewati antrean ini?')">
                            <i data-lucide="skip-forward" class="w-4 h-4"></i> Skip
                        </button>
                    </form>
                <?php elseif ($status === 'serving'): ?>
                    <form method="POST" class="w-full">
                        <input type="hidden" name="action" value="finish_service">
                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-md active:scale-95">
                            <i data-lucide="check" class="w-4 h-4"></i> Selesai Layani
                        </button>
                    </form>
                <?php elseif ($status === 'payment'): ?>
                    <form method="POST" class="w-full">
                        <input type="hidden" name="action" value="confirm_paid">
                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                        <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                        <button type="submit" class="w-full bg-cyan-700 hover:bg-cyan-600 text-white font-bold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-md active:scale-95" onclick="return confirm('Konfirmasi bayar cash langsung?')">
                            <i data-lucide="banknote" class="w-4 h-4"></i> Terima Cash
                        </button>
                    </form>
                <?php elseif ($status === 'paid'): ?>
                    <form method="POST" class="w-full">
                        <input type="hidden" name="action" value="confirm_paid">
                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                        <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-md active:scale-95">
                            <i data-lucide="printer" class="w-4 h-4"></i> Cetak & Selesai
                        </button>
                    </form>
                <?php elseif (in_array($status, ['review', 'completed'])): ?>
                    <button type="button" class="w-full bg-stone-800 hover:bg-stone-700 text-stone-300 font-semibold text-xs py-2.5 px-4 rounded-lg flex items-center justify-center gap-1.5 transition-all active:scale-95" onclick="printStruk('<?= $q['no_antrean'] ?>', '<?= htmlspecialchars($q['pelanggan_nama'] ?? 'Guest') ?>', '<?= htmlspecialchars($q['nama_layanan'] ?? 'Layanan') ?>', '<?= $final_price ?>', '<?= htmlspecialchars($q['metode_bayar'] ?? 'Cash') ?>')">
                        <i data-lucide="printer" class="w-4 h-4"></i> Cetak Ulang Struk
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Desktop Table View (Visible on Desktop >= md) -->
<div class="hidden md:block bg-adminlte-card rounded-xl border border-zinc-700 shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-zinc-800/50 text-zinc-400 text-sm border-b border-zinc-700">
                    <th class="px-6 py-3 font-medium">No. Tiket</th>
                    <th class="px-6 py-3 font-medium">Pelanggan</th>
                    <th class="px-6 py-3 font-medium">Layanan & Harga</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium text-right">Aksi Kerja</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-700/50">
                <?php if (empty($queues)): ?>
                <tr><td colspan="5" class="px-6 py-8 text-center text-zinc-500">Belum ada antrean masuk untuk Anda hari ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($queues as $q): 
                        $base_price = (float)($q['harga'] ?? 0);
                        $final_price = $base_price;
                    ?>
                    <tr class="hover:bg-zinc-800/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-lg text-white"><?= htmlspecialchars($q['no_antrean']) ?></div>
                        </td>
                        <td class="px-6 py-4 font-medium"><?= htmlspecialchars($q['pelanggan_nama'] ?? 'Guest') ?></td>
                        <td class="px-6 py-4">
                            <div class="text-white"><?= htmlspecialchars($q['nama_layanan'] ?? 'Cukur Standar') ?></div>
                            <div class="text-green-400 text-sm">Rp <?= number_format($final_price, 0, ',', '.') ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $badge_class = 'bg-zinc-600 text-white';
                            if ($q['status_antrean'] === 'serving') $badge_class = 'bg-adminlte-primary text-white';
                            if ($q['status_antrean'] === 'payment') $badge_class = 'bg-adminlte-warning text-zinc-900';
                            if (in_array($q['status_antrean'], ['paid', 'review', 'completed'])) $badge_class = 'bg-adminlte-success text-white';
                            ?>
                            <span class="<?= $badge_class ?> px-2 py-1 rounded text-xs font-semibold uppercase">
                                <?= htmlspecialchars($q['status_antrean']) ?>
                            </span>
                            <?php if ($q['status_antrean'] === 'paid' && !empty($q['metode_bayar'])): ?>
                                <div class="text-xs text-zinc-400 mt-1">Via: <?= htmlspecialchars($q['metode_bayar']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <?php if ($q['status_antrean'] === 'waiting'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="call">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <button type="submit" class="bg-adminlte-primary hover:bg-blue-600 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors">
                                            <i data-lucide="megaphone" class="w-3 h-3"></i> Panggil
                                        </button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="skip">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <button type="submit" class="bg-adminlte-danger hover:bg-red-700 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors" onclick="return confirm('Lewati antrean ini?')">
                                            <i data-lucide="skip-forward" class="w-3 h-3"></i> Skip
                                        </button>
                                    </form>
                                <?php elseif ($q['status_antrean'] === 'serving'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="finish_service">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <button type="submit" class="bg-adminlte-success hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors">
                                            <i data-lucide="check" class="w-3 h-3"></i> Selesai Layani
                                        </button>
                                    </form>
                                <?php elseif ($q['status_antrean'] === 'payment'): ?>
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-xs text-zinc-400">Menunggu Bayar...</span>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="confirm_paid">
                                            <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                            <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                                            <button type="submit" class="bg-adminlte-info hover:bg-cyan-600 text-white text-xs px-3 py-1.5 rounded transition-colors" onclick="return confirm('Konfirmasi bayar cash langsung?')">
                                                Terima Cash
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($q['status_antrean'] === 'paid'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="confirm_paid">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                                        <button type="submit" class="bg-adminlte-success hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded transition-colors">
                                            Cetak & Selesai
                                        </button>
                                    </form>
                                <?php elseif (in_array($q['status_antrean'], ['review', 'completed'])): ?>
                                    <button type="button" class="bg-zinc-700 hover:bg-zinc-600 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors" onclick="printStruk('<?= $q['no_antrean'] ?>', '<?= htmlspecialchars($q['pelanggan_nama'] ?? 'Guest') ?>', '<?= htmlspecialchars($q['nama_layanan'] ?? 'Layanan') ?>', '<?= $final_price ?>', '<?= htmlspecialchars($q['metode_bayar'] ?? 'Cash') ?>')">
                                        <i data-lucide="printer" class="w-3 h-3"></i> Cetak Ulang
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
