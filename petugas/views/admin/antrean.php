<?php if ($page === 'antrean'): ?>
<!-- DASHBOARD ANTREAN MODULE -->
<div class="bg-[#1E1B18] rounded-xl border border-white/10 shadow-xl overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-amber-900/30 bg-[#16120c] flex items-center justify-between flex-wrap gap-2">
        <h3 class="font-bold text-[#e8d5a3] text-base tracking-wide flex items-center gap-2">
            <i data-lucide="list-ordered" class="w-5 h-5 text-amber-400"></i>
            Status Antrean Aktif Hari Ini
        </h3>
    </div>
    <!-- Desktop Table View (hidden on mobile, visible md+) -->
    <div class="hidden md:block overflow-x-auto custom-scroll">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-zinc-900/70 text-zinc-400 text-xs uppercase tracking-wider border-b border-white/10">
                    <th class="px-6 py-4 font-semibold">No. Tiket</th>
                    <th class="px-6 py-4 font-semibold">Pelanggan</th>
                    <th class="px-6 py-4 font-semibold">Layanan</th>
                    <th class="px-6 py-4 font-semibold">Barber</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold">Est. Tunggu</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (empty($active_queues)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-zinc-400 text-sm">Belum ada antrean aktif saat ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($active_queues as $q): ?>
                        <tr class="hover:bg-amber-900/15 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-amber-400 font-mono font-bold text-lg tracking-wide"><?= htmlspecialchars($q['ticket_number']) ?></span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-white"><?= htmlspecialchars($q['customer_name']) ?></td>
                            <td class="px-6 py-4 text-sm">
                                <div class="text-zinc-200 font-medium"><?= htmlspecialchars($q['nama_layanan'] ?? $q['service_name'] ?? 'Standard Cut') ?></div>
                                <?php 
                                    if (!empty($q['barber_id'])) {
                                        $mult = (float)($q['barber_multiplier'] ?? 1.0);
                                        $base = (float)($q['base_price'] ?? 0);
                                        $final = $base * $mult;
                                        echo "<div class='text-emerald-400 mt-0.5 font-semibold text-xs'>Rp " . number_format($final, 0, ',', '.') . "</div>";
                                    } else {
                                        $base = (float)($q['base_price'] ?? 0);
                                        echo "<div class='text-zinc-400 mt-0.5 text-xs'>Mulai Rp " . number_format($base, 0, ',', '.') . "</div>";
                                    }
                                ?>
                            </td>
                            <td class="px-6 py-4 text-zinc-300 text-sm"><?= htmlspecialchars($q['barber_name'] ?? 'Bebas') ?></td>
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
                            <td class="px-6 py-4 text-center">
                                <form method="POST" action="" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus/membatalkan antrean ini?');">
                                    <input type="hidden" name="form_type" value="delete_antrian">
                                    <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/15 hover:bg-rose-500/25 text-rose-300 border border-rose-500/30 text-xs font-medium transition-all flex items-center gap-1 mx-auto" title="Hapus Antrean">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-400"></i> Hapus
                                    </button>
                                </form>
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
            <?php foreach ($active_queues as $q): ?>
            <div class="p-4 rounded-xl border border-white/10 bg-zinc-900/60 transition-all flex flex-col gap-3">
                <!-- Top Row: Ticket Number & Status -->
                <div class="flex justify-between items-center border-b border-white/5 pb-2.5">
                    <span class="text-amber-400 font-mono font-black text-xl tracking-wider"><?= htmlspecialchars($q['ticket_number']) ?></span>
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
                        <span class="font-semibold text-zinc-200"><?= htmlspecialchars($q['nama_layanan'] ?? $q['service_name'] ?? 'Standard Cut') ?></span>
                        <?php 
                            if (!empty($q['barber_id'])) {
                                $mult = (float)($q['barber_multiplier'] ?? 1.0);
                                $base = (float)($q['base_price'] ?? 0);
                                $final = $base * $mult;
                                echo "<span class='text-xs text-emerald-400 font-bold block'>Rp " . number_format($final, 0, ',', '.') . "</span>";
                            } else {
                                $base = (float)($q['base_price'] ?? 0);
                                echo "<span class='text-xs text-zinc-400 block'>Mulai Rp " . number_format($base, 0, ',', '.') . "</span>";
                            }
                        ?>
                    </div>
                </div>

                <!-- Bottom Row: Barber & Estimated Wait Time -->
                <div class="flex justify-between items-center text-xs text-zinc-400 pt-2 border-t border-white/5">
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="scissors" class="w-3.5 h-3.5 text-amber-400"></i>
                        <span>Barber: <?= htmlspecialchars($q['barber_name'] ?? 'Bebas') ?></span>
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
<?php endif; ?>
