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
    <div class="hidden md:block overflow-x-auto custom-scroll p-2">
        <table id="riwayatTable" class="w-full text-left border-collapse display">
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
            <div class="flex items-center gap-1.5 text-xs text-zinc-500 pt-2 border-t border-white/5">
                <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-amber-400/60"></i>
                <span><?= date('d M Y', strtotime($h['waktu_bayar'])) ?></span>
                <span class="text-zinc-700">·</span>
                <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-400/60"></i>
                <span><?= date('H:i', strtotime($h['waktu_bayar'])) ?> WIB</span>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="bg-amber-500/10 border border-amber-500/25 rounded-xl px-4 py-3 flex justify-between items-center">
            <span class="text-xs font-semibold uppercase tracking-wider text-amber-300/70">Total Keseluruhan</span>
            <span class="text-amber-400 font-black text-lg">Rp <?= number_format(array_sum(array_column($history, 'total_harga')), 0, ',', '.') ?></span>
        </div>
    </div>
    <?php endif; ?>
</div>
</section>
