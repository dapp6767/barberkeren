<?php if ($page === 'barber'): ?>
<!-- PANEL BARBER MODULE -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-adminlte-info rounded-lg p-6 relative overflow-hidden text-white shadow-lg">
        <div class="relative z-10">
            <h3 class="text-4xl font-bold mb-1"><?= count($barber_queues) ?></h3>
            <p class="text-blue-50 font-medium">Total Antrean Hari Ini</p>
        </div>
        <i data-lucide="list" class="absolute -right-4 -bottom-4 w-32 h-32 text-black/10 z-0"></i>
    </div>
    <div class="bg-adminlte-warning rounded-lg p-6 relative overflow-hidden text-zinc-900 shadow-lg">
        <div class="relative z-10">
            <h3 class="text-4xl font-bold mb-1"><?= $total_b_waiting ?></h3>
            <p class="text-yellow-900 font-medium">Antrean Menunggu</p>
        </div>
        <i data-lucide="clock" class="absolute -right-4 -bottom-4 w-32 h-32 text-black/10 z-0"></i>
    </div>
    <div class="bg-adminlte-success rounded-lg p-6 relative overflow-hidden text-white shadow-lg">
        <div class="relative z-10">
            <h3 class="text-4xl font-bold mb-1"><?= $total_b_served ?></h3>
            <p class="text-green-100 font-medium">Pelanggan Selesai</p>
        </div>
        <i data-lucide="check-circle" class="absolute -right-4 -bottom-4 w-32 h-32 text-black/10 z-0"></i>
    </div>
</div>

<!-- Table Card -->
<div class="bg-adminlte-card rounded-lg border border-zinc-700 shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-700 bg-[#30363d] flex justify-between items-center flex-wrap gap-2">
        <h3 class="font-semibold text-white">Daftar Antrean Tugas Anda</h3>
        <div class="flex gap-2">
            <button class="tabulator-btn" onclick="exportData('table-barber', 'csv')"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> CSV</button>
            <button class="tabulator-btn" onclick="exportData('table-barber', 'xlsx')"><i data-lucide="table" class="w-4 h-4"></i> Excel</button>
            <button class="tabulator-btn" onclick="exportData('table-barber', 'pdf')"><i data-lucide="file-text" class="w-4 h-4"></i> PDF</button>
            <button class="tabulator-btn" onclick="exportData('table-barber', 'print')"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table id="table-barber" class="w-full text-left border-collapse">
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
                <?php if (empty($barber_queues)): ?>
                <tr><td colspan="5" class="px-6 py-8 text-center text-zinc-500">Belum ada antrean masuk untuk Anda hari ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($barber_queues as $q): 
                        $multiplier = (float)($q['multiplier'] ?? 1.0);
                        $base_price = (float)($q['harga'] ?? 0);
                        $final_price = $base_price * $multiplier;
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
                                        <input type="hidden" name="form_type" value="call">
                                        <input type="hidden" name="current_page" value="barber">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <button type="submit" class="bg-adminlte-primary hover:bg-blue-600 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors">
                                            <i data-lucide="megaphone" class="w-3 h-3"></i> Panggil
                                        </button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="form_type" value="skip">
                                        <input type="hidden" name="current_page" value="barber">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <button type="submit" class="bg-adminlte-danger hover:bg-red-700 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors" onclick="return confirm('Lewati antrean ini?')">
                                            <i data-lucide="skip-forward" class="w-3 h-3"></i> Skip
                                        </button>
                                    </form>
                                <?php elseif ($q['status_antrean'] === 'serving'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="form_type" value="finish_service">
                                        <input type="hidden" name="current_page" value="barber">
                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                        <button type="submit" class="bg-adminlte-success hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors">
                                            <i data-lucide="check" class="w-3 h-3"></i> Selesai Layani
                                        </button>
                                    </form>
                                <?php elseif ($q['status_antrean'] === 'payment'): ?>
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-xs text-zinc-400">Menunggu Bayar...</span>
                                        <form method="POST">
                                            <input type="hidden" name="form_type" value="confirm_paid">
                                            <input type="hidden" name="current_page" value="barber">
                                            <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                            <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                                            <button type="submit" class="bg-adminlte-info hover:bg-cyan-600 text-white text-xs px-3 py-1.5 rounded transition-colors" onclick="return confirm('Konfirmasi bayar cash langsung?')">
                                                Terima Cash
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($q['status_antrean'] === 'paid'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="form_type" value="confirm_paid">
                                        <input type="hidden" name="current_page" value="barber">
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
<?php endif; ?>
