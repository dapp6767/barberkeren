<?php if ($page === 'layanan'): ?>
<!-- LAYANAN MODULE -->
<div class="mb-6 space-y-6">
    <!-- Donut Chart Layanan -->
    <div class="p-4 sm:p-6 md:p-8 bg-adminlte-card rounded-lg border border-zinc-700 shadow-md flex flex-col items-center justify-center">
        <h4 class="text-white font-semibold mb-6 text-lg tracking-wide uppercase text-zinc-300">Statistik Layanan (Berdasarkan Transaksi)</h4>
        <div style="width: 100%; max-width: 450px;">
            <canvas id="donutChartLayanan"></canvas>
        </div>
        
        <div class="mt-6 flex flex-wrap gap-4 justify-center">
            <?php 
            $serviceColors = ['#c9a03a', '#e8d5a3', '#8a6030', '#5a3a1a', '#3d2b1a', '#d4af37', '#aa8222', '#6b4c20', '#4a3020', '#2a1c0a'];
            $idx = 0;
            // sort largest first
            $sortedLayanan = $chartDataLayanan;
            usort($sortedLayanan, function($a, $b) { return $b['c'] - $a['c']; });
            
            foreach ($sortedLayanan as $item): 
                $c = $serviceColors[$idx % count($serviceColors)];
                $idx++;
            ?>
            <div class="bg-zinc-800/50 border border-zinc-700 px-4 py-2 rounded-lg flex items-center gap-2">
                <span class="text-sm text-zinc-400 capitalize"><?= htmlspecialchars($item['nama_layanan']) ?>:</span>
                <span class="text-lg font-bold" style="color: <?= $c ?>;"><?= $item['c'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Data Table Layanan -->
    <div class="bg-adminlte-card rounded-lg border border-zinc-700 shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-700 bg-[#30363d] flex justify-between items-center">
            <h3 class="font-semibold text-white">Daftar Layanan</h3>
        </div>
        <div class="tabulator-wrapper">
            <div class="tabulator-controls flex-wrap gap-3">
                <div class="flex gap-2 flex-wrap">
                    <button type="button" class="tabulator-btn" style="background-color: #2563eb; color: white; border-color: #3b82f6;" onclick="openAddLayananModal()">
                        <i data-lucide="plus" class="w-4 h-4"></i> Tambah Layanan
                    </button>
                    <button class="tabulator-btn" onclick="exportData('table-layanan', 'csv')"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> CSV</button>
                    <button class="tabulator-btn" onclick="exportData('table-layanan', 'xlsx')"><i data-lucide="table" class="w-4 h-4"></i> Excel</button>
                    <button class="tabulator-btn" onclick="exportData('table-layanan', 'pdf')"><i data-lucide="file-text" class="w-4 h-4"></i> PDF</button>
                    <button class="tabulator-btn" onclick="exportData('table-layanan', 'print')"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                </div>
                <input type="text" class="tabulator-search" id="search-layanan" placeholder="Filter rows...">
            </div>
            <!-- Mobile Card View (< 768px) -->
            <div class="md:hidden space-y-3 mb-4">
                <?php foreach ($layanan as $l): 
                    $files = glob(__DIR__ . '/../../asset/image/layanan_' . $l['id'] . '.*');
                    $nama_lower = trim(strtolower($l['nama_layanan']));
                    $default_images = [
                        'pangkas rambut biasa' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                        'pangkas rambut luar biasa' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                        'pridecut' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                        'maxcut' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                        'paket cukur sultan' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                        'paket cukur segar' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                        'hair coloring' => 'https://images.unsplash.com/photo-1620331311520-246422fd82f9?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                        'hairlight' => '../asset/image/hairlight.png',
                        'full hairlight' => '../asset/image/full_hairlight.png',
                        'hair tattoo' => 'https://images.unsplash.com/photo-1593702295094-aea22597af65?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                        'shave' => 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                        'korean wave' => 'https://images.unsplash.com/photo-1605497788044-5a32c7078486?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'
                    ];
                    $img_url = !empty($files) ? '../asset/image/' . basename($files[0]) : ($default_images[$nama_lower] ?? 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80');
                    $desc_text = !empty(trim($l['deskripsi'] ?? '')) ? htmlspecialchars(str_replace(["\r", "\n"], ["\\r", "\\n"], addslashes($l['deskripsi'])), ENT_QUOTES) : 'Belum ada informasi tambahan untuk layanan ini.';
                ?>
                <div class="bg-[#1a1208] border border-amber-900/40 rounded-xl p-4 shadow-md flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-white font-bold text-sm sm:text-base"><?= htmlspecialchars($l['nama_layanan']) ?></span>
                                <?php if (!empty($l['is_terbaik'])): ?>
                                    <span class="bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Terbaik</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs text-amber-400/90 font-medium block mt-1"><i data-lucide="clock" class="w-3 h-3 inline mr-1"></i><?= htmlspecialchars($l['durasi'] ?? 0) ?> Menit</span>
                        </div>
                        <span class="text-amber-300 font-extrabold text-sm sm:text-base whitespace-nowrap bg-amber-500/10 border border-amber-500/30 px-2.5 py-1 rounded-lg">Rp <?= number_format($l['harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-amber-900/30">
                        <button type="button" onclick="openDescModal('<?= htmlspecialchars(addslashes($l['nama_layanan']), ENT_QUOTES) ?>', '<?= $desc_text ?>', '<?= htmlspecialchars($l['durasi'] ?? 0) ?>', 'Rp <?= number_format($l['harga'], 0, ',', '.') ?>', '<?= $img_url ?>')" class="px-3 py-1.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/30 text-xs font-semibold flex items-center gap-1.5 hover:bg-blue-500/20 transition-colors">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i> Detail
                        </button>
                        <button type="button" onclick="openEditLayananModal(<?= $l['id'] ?>, '<?= htmlspecialchars($l['nama_layanan'], ENT_QUOTES) ?>', <?= $l['harga'] ?>, <?= $l['durasi'] ?? 0 ?>, '<?= htmlspecialchars(str_replace(["\r", "\n"], ["\\r", "\\n"], $l['deskripsi'] ?? ''), ENT_QUOTES) ?>', <?= (int)($l['is_terbaik'] ?? 0) ?>)" class="px-3 py-1.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/30 text-xs font-semibold flex items-center gap-1.5 hover:bg-amber-500/20 transition-colors">
                            <i data-lucide="edit" class="w-3.5 h-3.5"></i> Edit
                        </button>
                        <form method="POST" class="inline">
                            <input type="hidden" name="form_type" value="delete_layanan">
                            <input type="hidden" name="current_page" value="layanan">
                            <input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/30 text-xs font-semibold flex items-center gap-1.5 hover:bg-rose-500/20 transition-colors" onclick="return confirm('Hapus layanan ini?')">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop Table View (>= 768px) -->
            <table id="table-layanan" class="hidden md:table w-full text-left border-collapse display">
                <thead>
                    <tr class="bg-zinc-800/50 text-zinc-400 text-sm border-b border-zinc-700">
                        <th class="px-4 py-3 font-medium text-center" tabulator-field="no" width="70" tabulator-formatter="rownum">No.</th>
                        <th class="px-6 py-3 font-medium" tabulator-field="layanan" tabulator-formatter="html">Layanan</th>
                        <th class="px-6 py-3 font-medium" tabulator-field="durasi">Durasi</th>
                        <th class="px-6 py-3 font-medium" tabulator-field="harga" tabulator-formatter="html">Harga</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-700/50">
                    <?php foreach ($layanan as $l): ?>
                    <tr class="hover:bg-zinc-800/30 transition-colors">
                        <td class="px-4 py-3 text-zinc-400 text-center font-medium"></td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <span class="text-white font-medium"><?= htmlspecialchars($l['nama_layanan']) ?></span>
                                <?php if (!empty($l['is_terbaik'])): ?>
                                    <span class="bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Terbaik</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-zinc-400"><?= htmlspecialchars($l['durasi'] ?? 0) ?> Menit</td>
                        <td class="px-6 py-3">
                            <span class="hidden"><?= sprintf('%010d', $l['harga']) ?></span>
                            <div class="flex justify-between items-center w-full min-w-[200px]">
                                <span class="font-medium">Rp <?= number_format($l['harga'], 0, ',', '.') ?></span>
                                <div class="flex items-center gap-2">
                                <?php 
                                    $files = glob(__DIR__ . '/../../asset/image/layanan_' . $l['id'] . '.*');
                                    $nama_lower = trim(strtolower($l['nama_layanan']));
                                    $default_images = [
                                        'pangkas rambut biasa' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                                        'pangkas rambut luar biasa' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                                        'pridecut' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                                        'maxcut' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                                        'paket cukur sultan' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                                        'paket cukur segar' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                                        'hair coloring' => 'https://images.unsplash.com/photo-1620331311520-246422fd82f9?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                                        'hairlight' => '../asset/image/hairlight.png',
                                        'full hairlight' => '../asset/image/full_hairlight.png',
                                        'hair tattoo' => 'https://images.unsplash.com/photo-1593702295094-aea22597af65?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                                        'shave' => 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                                        'korean wave' => 'https://images.unsplash.com/photo-1605497788044-5a32c7078486?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'
                                    ];
                                    $img_url = !empty($files) ? '../asset/image/' . basename($files[0]) : ($default_images[$nama_lower] ?? 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80');
                                    $desc_text = !empty(trim($l['deskripsi'] ?? '')) ? htmlspecialchars(str_replace(["\r", "\n"], ["\\r", "\\n"], addslashes($l['deskripsi'])), ENT_QUOTES) : 'Belum ada informasi tambahan untuk layanan ini.';
                                ?>
                                    <button type="button" onclick="openDescModal('<?= htmlspecialchars(addslashes($l['nama_layanan']), ENT_QUOTES) ?>', '<?= $desc_text ?>', '<?= htmlspecialchars($l['durasi'] ?? 0) ?>', 'Rp <?= number_format($l['harga'], 0, ',', '.') ?>', '<?= $img_url ?>')" class="text-blue-400 hover:text-blue-300 p-1.5 rounded hover:bg-blue-400/10 transition-colors" title="Lihat Lebih Lengkap">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button type="button" onclick="openEditLayananModal(<?= $l['id'] ?>, '<?= htmlspecialchars($l['nama_layanan'], ENT_QUOTES) ?>', <?= $l['harga'] ?>, <?= $l['durasi'] ?? 0 ?>, '<?= htmlspecialchars(str_replace(["\r", "\n"], ["\\r", "\\n"], $l['deskripsi'] ?? ''), ENT_QUOTES) ?>', <?= (int)($l['is_terbaik'] ?? 0) ?>)" class="text-blue-400 hover:text-blue-300 p-1.5 rounded hover:bg-blue-400/10 transition-colors" title="Edit">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="form_type" value="delete_layanan">
                                        <input type="hidden" name="current_page" value="layanan">
                                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                        <button type="submit" class="text-red-400 hover:text-red-300 p-1.5 rounded hover:bg-red-400/10 transition-colors" onclick="return confirm('Hapus layanan ini?')" title="Hapus">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const elDonutLayanan = document.getElementById('donutChartLayanan');
    if (!elDonutLayanan) return;
    const ctx = elDonutLayanan.getContext('2d');
    const rawData = <?= json_encode($chartDataLayanan ?? []) ?>;
    
    rawData.sort((a, b) => parseInt(b.c || 0) - parseInt(a.c || 0));

    let finalLabels = [];
    let finalValues = [];

    if (rawData.length > 4) {
        const topServices = rawData.slice(0, 4);
        const otherServices = rawData.slice(4);
        const otherTotal = otherServices.reduce((sum, item) => sum + parseInt(item.c || 0), 0);

        finalLabels = topServices.map(item => item.nama_layanan);
        finalValues = topServices.map(item => parseInt(item.c || 0));

        if (otherTotal > 0) {
            finalLabels.push('Lainnya');
            finalValues.push(otherTotal);
        }
    } else {
        finalLabels = rawData.map(item => item.nama_layanan);
        finalValues = rawData.map(item => parseInt(item.c || 0));
    }

    // Dark Gold / Amber / Warm Brown Palette (#F59E0B, #D97706, #B45309, #FDE68A, #78350F)
    const amberPalette = ['#F59E0B', '#D97706', '#B45309', '#FDE68A', '#78350F'];
    const colors = finalLabels.map((_, i) => amberPalette[i % amberPalette.length]);
    const totalCount = finalValues.reduce((a, b) => a + b, 0);

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: finalLabels.length ? finalLabels : ['Belum ada transaksi'],
            datasets: [{
                data: finalValues.length ? finalValues : [1],
                backgroundColor: finalValues.length ? colors : ['#3d2b1a'],
                borderColor: '#18120b',
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#d4d4d8',
                        padding: 14,
                        font: { family: 'Inter, sans-serif', size: 12, weight: '500' },
                        usePointStyle: true,
                        boxWidth: 8
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(24, 18, 11, 0.95)',
                    titleColor: '#fde68a',
                    bodyColor: '#d4d4d8',
                    borderColor: 'rgba(245, 158, 11, 0.4)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const val = context.raw || 0;
                            const pct = totalCount > 0 ? Math.round((val / totalCount) * 100) : 0;
                            return ` ${context.label}: ${val} layanan (${pct}%)`;
                        }
                    }
                }
            },
            animation: { animateScale: true, animateRotate: true, duration: 1500 }
        }
    });
});
</script>
<?php endif; ?>
