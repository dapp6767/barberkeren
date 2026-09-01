<?php if ($page === 'transaksi'): ?>
<!-- TRANSAKSI MODULE -->
<div class="space-y-6 mb-6">
    <!-- ============================================================ -->
    <!-- HOLOGRAPHIC CHART SECTION — LUXURY GOLD & BROWN UI          -->
    <!-- ============================================================ -->
    <style>
    /* === HOLOGRAPHIC CONTAINER (LUXURY BROWN THEME) === */
    .holo-chart-section {
        position: relative;
        background: linear-gradient(135deg, #1a1208 0%, #120e06 100%);
        overflow: hidden;
        padding: 3rem 2rem;
        border: 1px solid #3a2510;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    @media (max-width: 768px) {
        .holo-chart-section {
            padding: 1.25rem 0.75rem;
            border-radius: 12px;
        }
        .holo-chart-section::after,
        .holo-syntax-right {
            display: none !important;
        }
    }

    /* Ambient warm gold backdrop glow */
    .holo-chart-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 60% 40% at 25% 60%, rgba(212, 175, 55, 0.08) 0%, transparent 70%),
            radial-gradient(ellipse 50% 35% at 75% 40%, rgba(245, 158, 11, 0.06) 0%, transparent 70%),
            radial-gradient(ellipse 40% 30% at 50% 80%, rgba(232, 213, 163, 0.05) 0%, transparent 70%);
        pointer-events: none;
    }

    /* Cascading syntax strands left margin */
    .holo-chart-section::after {
        content: 'def calculate_payment_split():\n    chart.render(\n        animated=True,\n        antigravity=True\n    )\n    return data_source\n        .get_metrics()\n\nclass ChartEngine:\n    def __init__(self):\n        self.mode = "luxury_gold"\n\n    def render(self):\n        return self.vibe_code()\n\n# Vibe Coding Mode\n# LUXURY BROWN ACTIVE\nmatrix = [\n    "METODE_PAYMENT",\n    "LAYANAN_STREAM",\n    "QRIS_0x4A2F",\n    "CASH_0x7B1E",\n]\n\nfor node in matrix:\n    emit(node, float=True)';
        position: absolute;
        left: 8px;
        top: 50%;
        transform: translateY(-50%);
        font-family: 'Courier New', monospace;
        font-size: 9px;
        line-height: 1.6;
        color: rgba(232, 213, 163, 0.1);
        white-space: pre;
        pointer-events: none;
        z-index: 0;
    }

    /* Right margin syntax strand */
    .holo-syntax-right {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        font-family: 'Courier New', monospace;
        font-size: 9px;
        line-height: 1.6;
        color: rgba(212, 175, 55, 0.08);
        white-space: pre;
        pointer-events: none;
        z-index: 0;
        text-align: right;
    }

    /* === FLOATING MODULE CARDS === */
    .holo-module {
        position: relative;
        background: rgba(24, 20, 15, 0.85);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(212, 175, 55, 0.25);
        border-radius: 20px;
        padding: 2rem;
        box-shadow:
            0 0 0 1px rgba(212, 175, 55, 0.08) inset,
            0 0 40px rgba(212, 175, 55, 0.08),
            0 25px 60px rgba(0, 0, 0, 0.6);
        overflow: hidden;
        z-index: 1;
        transition: box-shadow 0.4s ease, transform 0.4s ease;
    }
    .holo-module:hover {
        box-shadow:
            0 0 0 1px rgba(212, 175, 55, 0.3) inset,
            0 0 60px rgba(212, 175, 55, 0.15),
            0 30px 80px rgba(0, 0, 0, 0.7);
        transform: translateY(-2px);
    }

    /* Holographic corner accents */
    .holo-module::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 40px; height: 40px;
        border-top: 2px solid rgba(212, 175, 55, 0.6);
        border-left: 2px solid rgba(212, 175, 55, 0.6);
        border-radius: 20px 0 0 0;
        pointer-events: none;
    }
    .holo-module::after {
        content: '';
        position: absolute;
        bottom: 0; right: 0;
        width: 40px; height: 40px;
        border-bottom: 2px solid rgba(232, 213, 163, 0.5);
        border-right: 2px solid rgba(232, 213, 163, 0.5);
        border-radius: 0 0 20px 0;
        pointer-events: none;
    }

    /* === HOLOGRAPHIC HEADER === */
    .holo-title {
        font-family: 'Courier New', 'SF Mono', monospace;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.35em;
        text-transform: uppercase;
        background: linear-gradient(90deg, #e8d5a3, #d4af37, #e8d5a3);
        background-size: 200% 100%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: holo-title-shimmer 4s linear infinite;
        margin-bottom: 1.5rem;
        text-align: center;
        position: relative;
        z-index: 2;
    }
    .holo-title::after {
        content: '';
        display: block;
        height: 1px;
        margin-top: 0.75rem;
        background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.5), rgba(232, 213, 163, 0.4), transparent);
    }
    @keyframes holo-title-shimmer {
        0% { background-position: 0% 50%; }
        100% { background-position: 200% 50%; }
    }

    /* === CANVAS WRAPPER === */
    .holo-canvas-wrap {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @keyframes orbital-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes orbital-spin-rev {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(-360deg); }
    }

    /* === DONUT CENTER HOLOGRAM === */
    .holo-donut-center {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        z-index: 2;
    }
    .holo-total-num {
        font-family: 'Courier New', monospace;
        font-size: 2.8rem;
        font-weight: 900;
        line-height: 1;
        background: linear-gradient(180deg, #ffffff 0%, #e8d5a3 50%, #d4af37 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: none;
        filter: drop-shadow(0 0 12px rgba(212, 175, 55, 0.8));
        animation: holo-pulse-num 3s ease-in-out infinite;
    }
    .holo-total-label {
        font-family: 'Courier New', monospace;
        font-size: 7px;
        letter-spacing: 0.2em;
        color: rgba(232, 213, 163, 0.8);
        margin-top: 4px;
        text-transform: uppercase;
    }
    @keyframes holo-pulse-num {
        0%, 100% { filter: drop-shadow(0 0 8px rgba(212, 175, 55, 0.7)); }
        50% { filter: drop-shadow(0 0 20px rgba(212, 175, 55, 1)) drop-shadow(0 0 40px rgba(232, 213, 163, 0.5)); }
    }

    /* === GLASSMORPHISM METRIC CARDS === */
    .holo-metrics {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        margin-top: 1.75rem;
        position: relative;
        z-index: 2;
    }
    .holo-metric-card {
        background: rgba(212, 175, 55, 0.06);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 10px;
        padding: 10px 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        backdrop-filter: blur(12px);
        transition: all 0.3s ease;
        box-shadow: 0 0 0 0 rgba(212, 175, 55, 0);
        min-width: 80px;
    }
    .holo-metric-card:hover {
        background: rgba(212, 175, 55, 0.12);
        border-color: rgba(212, 175, 55, 0.5);
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.2), 0 0 40px rgba(212, 175, 55, 0.05);
        transform: translateY(-2px);
    }
    .holo-metric-card.cash { border-color: rgba(201, 160, 58, 0.4); background: rgba(201, 160, 58, 0.06); }
    .holo-metric-card.cash:hover { border-color: rgba(201, 160, 58, 0.7); box-shadow: 0 0 20px rgba(201, 160, 58, 0.2); }
    .holo-metric-card.qris { border-color: rgba(16, 185, 129, 0.4); background: rgba(16, 185, 129, 0.06); }
    .holo-metric-card.qris:hover { border-color: rgba(16, 185, 129, 0.7); box-shadow: 0 0 20px rgba(16, 185, 129, 0.2); }
    .holo-metric-card.bank { border-color: rgba(59, 130, 246, 0.4); background: rgba(59, 130, 246, 0.06); }
    .holo-metric-card.bank:hover { border-color: rgba(59, 130, 246, 0.7); box-shadow: 0 0 20px rgba(59, 130, 246, 0.2); }
    .holo-metric-card.lainnya { border-color: rgba(100, 116, 139, 0.35); background: rgba(100, 116, 139, 0.05); }

    .holo-metric-label {
        font-family: 'Courier New', monospace;
        font-size: 8px;
        letter-spacing: 0.15em;
        color: rgba(148, 163, 184, 0.8);
        text-transform: uppercase;
    }
    .holo-metric-value {
        font-family: 'Courier New', monospace;
        font-size: 1.6rem;
        font-weight: 900;
        line-height: 1;
    }
    .holo-metric-value.cash-val { color: #f0c040; text-shadow: 0 0 12px rgba(201, 160, 58, 0.8); }
    .holo-metric-value.qris-val { color: #10b981; text-shadow: 0 0 12px rgba(16, 185, 129, 0.8); }
    .holo-metric-value.bank-val { color: #60a5fa; text-shadow: 0 0 12px rgba(59, 130, 246, 0.8); }
    .holo-metric-value.lainnya-val { color: #94a3b8; text-shadow: 0 0 8px rgba(100, 116, 139, 0.6); }

    /* === BAR CHART SCAN LINES === */
    .holo-bar-wrap {
        position: relative;
        width: 100%;
    }
    .holo-bar-wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        background: repeating-linear-gradient(
            0deg,
            transparent,
            transparent 18px,
            rgba(212, 175, 55, 0.02) 18px,
            rgba(212, 175, 55, 0.02) 19px
        );
        pointer-events: none;
        z-index: 0;
    }

    /* === FLOATING PARTICLES CANVAS === */
    #holo-particles-canvas {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 0;
    }

    /* === LAYOUT GRID === */
    .holo-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        position: relative;
        z-index: 1;
    }
    @media (max-width: 1024px) {
        .holo-grid { grid-template-columns: 1fr; }
        .holo-chart-section::after { display: none; }
    }

    /* Top scanline sweep on module */
    @keyframes holo-scanline {
        0% { top: -2px; opacity: 0; }
        5% { opacity: 1; }
        95% { opacity: 1; }
        100% { top: 100%; opacity: 0; }
    }
    .holo-scanline {
        position: absolute;
        left: 0; right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.6), rgba(232, 213, 163, 0.4), transparent);
        animation: holo-scanline 6s ease-in-out infinite;
        pointer-events: none;
        z-index: 10;
    }

    /* Pulsing aura for top bar */
    @keyframes bar-aura-pulse {
        0%, 100% { opacity: 0.5; }
        50% { opacity: 1; }
    }
    </style>

    <!-- Holographic Chart Section -->
    <div class="holo-chart-section">
        <!-- Floating Particles Canvas (background layer) -->
        <canvas id="holo-particles-canvas" aria-hidden="true"></canvas>

        <!-- Right margin syntax strand -->
        <div class="holo-syntax-right" aria-hidden="true">return data_source&#10;  .get_metrics()&#10;&#10;async def stream():&#10;  yield payload&#10;    .encode("holo")&#10;&#10;ANTIGRAVITY = True&#10;DEPTH = "cyberspace"&#10;MODE = "luxury-gold"&#10;&#10;render(&#10;  chart=True,&#10;  float=True,&#10;  particles=True&#10;)&#10;&#10;# VIBE CODING&#10;# SYSTEM ACTIVE&#10;signal.emit("gold")</div>

        <div class="holo-grid">

            <!-- ======================== -->
            <!-- LEFT: DONUT CHART MODULE -->
            <!-- ======================== -->
            <div class="holo-module">
                <div class="holo-scanline"></div>
                <h4 class="holo-title">METODE PEMBAYARAN</h4>

                <!-- Canvas + Center Hologram -->
                <div class="holo-canvas-wrap holo-canvas-wrap-donut" style="height: 280px;">
                    <canvas id="donutChartTransaksi" style="position:relative;z-index:2;"></canvas>
                    <div class="holo-donut-center" id="holo-donut-center-label">
                        <span class="holo-total-num" id="holo-total-num">0</span>
                        <span class="holo-total-label">TOTAL TRANSAKSI</span>
                    </div>
                </div>

                <!-- Glassmorphism Metric Cards -->
                <div class="holo-metrics" id="holo-metrics-container">
                    <?php
                    $metricColorClass = [
                        'Cash'          => ['card' => 'cash',    'val' => 'cash-val'],
                        'QRIS'          => ['card' => 'qris',    'val' => 'qris-val'],
                        'Transfer Bank' => ['card' => 'bank',    'val' => 'bank-val'],
                    ];
                    $totalDonut = 0;
                    foreach ($chartDataTransaksi as $it) $totalDonut += (int)$it['c'];
                    foreach ($chartDataTransaksi as $item):
                        $mLabel = $item['metode_pembayaran'] ?: 'Belum Lunas';
                        $cls = $metricColorClass[$item['metode_pembayaran']] ?? ['card' => 'lainnya', 'val' => 'lainnya-val'];
                    ?>
                    <div class="holo-metric-card <?= $cls['card'] ?>">
                        <span class="holo-metric-label"><?= htmlspecialchars($mLabel) ?></span>
                        <span class="holo-metric-value <?= $cls['val'] ?>"><?= $item['c'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ======================== -->
            <!-- RIGHT: BAR CHART MODULE  -->
            <!-- ======================== -->
            <div class="holo-module">
                <div class="holo-scanline" style="animation-delay: 3s;"></div>
                <h4 class="holo-title">LAYANAN SERING DIGUNAKAN</h4>

                <div class="holo-bar-wrap">
                    <div style="width: 100%; height: 360px; position: relative; z-index: 1;">
                        <canvas id="barChartLayananTransaksi"></canvas>
                    </div>
                </div>
            </div>

        </div><!-- /.holo-grid -->
    </div><!-- /.holo-chart-section -->

    <!-- Data Table Transaksi -->
    <div class="bg-[#18120b] rounded-lg border border-white/10 shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-white/10 bg-[#22180f]">
            <h3 class="font-serif font-bold text-[#f0d375] tracking-wide text-lg">Laporan Riwayat Transaksi Lunas</h3>
        </div>
        <div class="tabulator-wrapper"><div class="tabulator-controls"><div class="flex gap-2"><button class="tabulator-btn" onclick="exportData('table-transaksi', 'csv')"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> CSV</button><button class="tabulator-btn" onclick="exportData('table-transaksi', 'xlsx')"><i data-lucide="table" class="w-4 h-4"></i> Excel</button><button class="tabulator-btn" onclick="exportData('table-transaksi', 'pdf')"><i data-lucide="file-text" class="w-4 h-4"></i> PDF</button><button class="tabulator-btn" onclick="exportData('table-transaksi', 'print')"><i data-lucide="printer" class="w-4 h-4"></i> Print</button></div><input type="text" class="tabulator-search" id="search-transaksi" placeholder="Filter rows..."></div>
            <!-- Mobile Card View (< 768px) -->
            <div class="md:hidden space-y-3 mb-4 p-4">
                <?php if (empty($transaksi)): ?>
                    <div class="text-center py-6 text-zinc-400 text-xs">Belum ada data transaksi lunas.</div>
                <?php else: ?>
                    <?php foreach ($transaksi as $t): ?>
                    <div class="bg-[#1a1208] border border-amber-900/40 rounded-xl p-4 shadow-md flex flex-col gap-2.5">
                        <div class="flex items-center justify-between border-b border-amber-900/30 pb-2">
                            <span class="font-mono text-xs font-bold text-amber-200/90">#TRX-<?= $t['id'] ?></span>
                            <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                                <?= strtoupper($t['status_pembayaran']) ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <h5 class="text-white font-bold text-sm"><?= htmlspecialchars($t['pelanggan'] ?? 'Guest') ?></h5>
                                <span class="text-xs text-amber-400/90 font-mono">Tiket: <?= htmlspecialchars(!empty($t['no_antrean']) ? $t['no_antrean'] : ('A-' . sprintf('%02d', !empty($t['antrian_id']) ? $t['antrian_id'] : $t['id']))) ?></span>
                            </div>
                            <span class="text-amber-300 font-extrabold text-sm sm:text-base">Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-zinc-400 pt-2 border-t border-amber-900/30">
                            <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3 text-amber-400"></i> <?= $t['waktu_bayar'] ?></span>
                            <button type="button" onclick="printStruk('<?= htmlspecialchars(!empty($t['no_antrean']) ? $t['no_antrean'] : ('A-' . sprintf('%02d', !empty($t['antrian_id']) ? $t['antrian_id'] : $t['id']))) ?>', '<?= htmlspecialchars(addslashes($t['pelanggan'] ?? 'Guest')) ?>', '<?= htmlspecialchars(addslashes($t['layanan_list'] ?? 'Layanan Barber')) ?>', <?= $t['total_harga'] ?>, '<?= htmlspecialchars($t['metode_pembayaran'] ?? 'Cash') ?>', <?= $t['antrian_id'] ?? 'null' ?>)" class="text-amber-400 hover:text-amber-300 font-semibold flex items-center gap-1 px-2.5 py-1 rounded bg-amber-500/10 border border-amber-500/30">
                                <i data-lucide="printer" class="w-3 h-3"></i> Cetak Struk
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Desktop Table View (>= 768px) -->
            <table id="table-transaksi" class="hidden md:table w-full text-left border-collapse"><thead>
                    <tr class="bg-zinc-900/60 text-zinc-300 text-sm border-b border-white/10">
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="id_transaksi" tabulator-formatter="html">ID Transaksi</th>
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="no_tiket" tabulator-formatter="html">No. Tiket</th>
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="pelanggan" tabulator-formatter="html">Pelanggan</th>
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="total_bayar" tabulator-formatter="html">Total Bayar</th>
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="status" tabulator-formatter="html">Status</th>
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="waktu_bayar" tabulator-formatter="html">Waktu Bayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if (empty($transaksi)): ?>
                    <tr><td colspan="6" class="px-6 py-8 text-center text-zinc-400">Belum ada data transaksi lunas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transaksi as $t): ?>
                        <tr class="hover:bg-amber-500/10 transition-colors">
                            <td class="px-6 py-4 font-mono text-amber-200/90 font-medium">#TRX-<?= $t['id'] ?></td>
                            <td class="px-6 py-4 font-bold text-amber-400 font-mono"><?= htmlspecialchars(!empty($t['no_antrean']) ? $t['no_antrean'] : ('A-' . sprintf('%02d', !empty($t['antrian_id']) ? $t['antrian_id'] : $t['id']))) ?></td>
                            <td class="px-6 py-4 text-zinc-200 font-medium"><?= htmlspecialchars($t['pelanggan'] ?? 'Guest') ?></td>
                            <td class="px-6 py-4 text-amber-400 font-bold">Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4">
                                <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2.5 py-1 rounded text-xs font-semibold">
                                    <?= strtoupper($t['status_pembayaran']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-300"><?= $t['waktu_bayar'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ============================================
           FLOATING PARTICLES BACKGROUND (WARM GOLD)
        ============================================ */
        const pCanvas = document.getElementById('holo-particles-canvas');
        if (!pCanvas) return;
        const pSection = pCanvas.parentElement;
        function resizeParticles() {
            if (!pCanvas || !pSection) return;
            pCanvas.width = pSection.offsetWidth;
            pCanvas.height = pSection.offsetHeight;
        }
        resizeParticles();
        window.addEventListener('resize', resizeParticles);
        const pCtx = pCanvas.getContext('2d');

        const PARTICLE_COLORS = [
            'rgba(212,175,55,',   // gold
            'rgba(232,213,163,',  // light gold
            'rgba(245,158,11,',   // amber
            'rgba(201,160,58,',   // bronze
            'rgba(248,250,252,',  // warm white
        ];
        const particles = Array.from({length: 80}, () => ({
            x: Math.random() * 1200,
            y: Math.random() * 500,
            r: Math.random() * 1.8 + 0.3,
            vy: -(Math.random() * 0.5 + 0.1),
            vx: (Math.random() - 0.5) * 0.2,
            alpha: Math.random() * 0.5 + 0.1,
            color: PARTICLE_COLORS[Math.floor(Math.random() * PARTICLE_COLORS.length)],
            life: Math.random(),
            decay: Math.random() * 0.002 + 0.001
        }));

        function animateParticles() {
            if (!pCanvas || !pCtx) return;
            pCanvas.width = pSection.offsetWidth;
            pCanvas.height = pSection.offsetHeight;
            pCtx.clearRect(0, 0, pCanvas.width, pCanvas.height);
            particles.forEach(p => {
                p.y += p.vy;
                p.x += p.vx;
                p.life -= p.decay;
                if (p.life <= 0 || p.y < -10) {
                    p.x = Math.random() * pCanvas.width;
                    p.y = pCanvas.height + 5;
                    p.life = 1;
                    p.alpha = Math.random() * 0.5 + 0.1;
                }
                const a = p.alpha * p.life;
                pCtx.beginPath();
                pCtx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                pCtx.fillStyle = p.color + a + ')';
                pCtx.fill();
            });
            requestAnimationFrame(animateParticles);
        }
        animateParticles();


        /* ============================================
           DONUT CHART — ORBITAL GOLD STREAMS
        ============================================ */
        const elDonut = document.getElementById('donutChartTransaksi');
        if (!elDonut) return;
        const ctxDonut = elDonut.getContext('2d');
        const dataDonut = <?= json_encode($chartDataTransaksi) ?>;

        const labelsDonut = dataDonut.map(item => item.metode_pembayaran || 'Belum Lunas/Lainnya');
        const valuesDonut = dataDonut.map(item => parseInt(item.c));
        const totalDonut = valuesDonut.reduce((a, b) => a + b, 0);

        // Animated total counter
        const numEl = document.getElementById('holo-total-num');
        let counted = 0;
        const countInterval = setInterval(() => {
            counted = Math.min(counted + 1, totalDonut);
            if (numEl) numEl.textContent = counted;
            if (counted >= totalDonut) clearInterval(countInterval);
        }, 80);

        // Holographic segment colors with gold glow
        const holoColorMap = {
            'Cash':                 { bg: 'rgba(201,160,58,0.85)',   border: 'rgba(240,192,64,0.6)',  glow: '#f0c040' },
            'Transfer Bank':        { bg: 'rgba(59,130,246,0.85)',   border: 'rgba(96,165,250,0.6)',  glow: '#60a5fa' },
            'QRIS':                 { bg: 'rgba(16,185,129,0.85)',   border: 'rgba(52,211,153,0.6)',  glow: '#34d399' },
            'Belum Lunas/Lainnya':  { bg: 'rgba(71,85,105,0.75)',    border: 'rgba(100,116,139,0.4)', glow: '#64748b' },
        };
        const bgColors    = labelsDonut.map(l => (holoColorMap[l] || holoColorMap['Belum Lunas/Lainnya']).bg);
        const borderClrs  = labelsDonut.map(l => (holoColorMap[l] || holoColorMap['Belum Lunas/Lainnya']).border);

        // Custom glow plugin
        const donutGlowPlugin = {
            id: 'donutGlow',
            beforeDraw(chart) {
                const ctx = chart.ctx;
                ctx.save();
                ctx.shadowBlur = 24;
                ctx.shadowColor = 'rgba(212,175,55,0.4)';
            },
            afterDraw(chart) {
                chart.ctx.restore();
            }
        };

        const centerAlignPlugin = {
            id: 'centerAlign',
            afterDraw(chart) {
                const centerEl = document.getElementById('holo-donut-center-label');
                if (centerEl && chart.chartArea) {
                    const chartArea = chart.chartArea;
                    const centerX = chartArea.left + (chartArea.right - chartArea.left) / 2;
                    const centerY = chartArea.top + (chartArea.bottom - chartArea.top) / 2;
                    centerEl.style.left = centerX + 'px';
                    centerEl.style.top = centerY + 'px';
                    centerEl.style.transform = 'translate(-50%, -50%)';
                }
            }
        };
        new Chart(ctxDonut, {
            type: 'doughnut',
            plugins: [donutGlowPlugin, centerAlignPlugin],
            data: {
                labels: labelsDonut.length ? labelsDonut : ['Belum ada transaksi'],
                datasets: [{
                    data: valuesDonut.length ? valuesDonut : [1],
                    backgroundColor: valuesDonut.length ? bgColors : ['#1e293b'],
                    borderColor: valuesDonut.length ? borderClrs : ['#334155'],
                    borderWidth: 2,
                    hoverOffset: 14,
                    hoverBorderColor: '#e8d5a3',
                    hoverBorderWidth: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: 'rgba(232,213,163,0.9)',
                            padding: 16,
                            font: { family: "'Courier New', monospace", size: 11, weight: '600' },
                            usePointStyle: true,
                            pointStyleWidth: 10,
                            boxHeight: 8,
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(18,14,6,0.95)',
                        titleColor: '#e8d5a3',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(212,175,55,0.5)',
                        borderWidth: 1,
                        padding: 14,
                        cornerRadius: 10,
                        titleFont: { family: "'Courier New', monospace", size: 12, weight: '700' },
                        bodyFont:  { family: "'Courier New', monospace", size: 11 },
                        callbacks: {
                            label: (ctx) => `  ${ctx.label}: ${ctx.raw} transaksi (${Math.round(ctx.raw/totalDonut*100)}%)`
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });


        /* ============================================
           BAR CHART — GLOWING AMBER/GOLD VOXEL BARS
        ============================================ */
        const elBar = document.getElementById('barChartLayananTransaksi');
        if (!elBar) return;
        const ctxBar = elBar.getContext('2d');
        const dataBar = <?= json_encode($chartDataLayananTransaksi ?? []) ?>;

        const labelsBar = dataBar.map(item => item.nama_layanan);
        const valuesBar = dataBar.map(item => parseInt(item.c));
        const maxVal    = Math.max(...valuesBar, 1);

        // Per-bar gradient: gold/amber intensity
        const barColors = valuesBar.map((v, i) => {
            const intensity = 0.55 + (v / maxVal) * 0.45;
            if (i === 0) {
                // Top bar: vivid gold
                const g = ctxBar.createLinearGradient(0, 0, ctxBar.canvas.offsetWidth || 500, 0);
                g.addColorStop(0, `rgba(201,160,58,${intensity})`);
                g.addColorStop(0.5, `rgba(212,175,55,${intensity})`);
                g.addColorStop(1, `rgba(232,213,163,${intensity + 0.1})`);
                return g;
            }
            const g = ctxBar.createLinearGradient(0, 0, ctxBar.canvas.offsetWidth || 500, 0);
            g.addColorStop(0, `rgba(180,130,40,${intensity * 0.8})`);
            g.addColorStop(1, `rgba(212,175,55,${intensity})`);
            return g;
        });

        // Per-bar border glow
        const barBorders = valuesBar.map((v, i) =>
            i === 0 ? 'rgba(232,213,163,0.7)' : 'rgba(212,175,55,0.3)'
        );

        // Custom bar glow plugin
        const barGlowPlugin = {
            id: 'barGlow',
            afterDatasetsDraw(chart) {
                const ctx = chart.ctx;
                const meta = chart.getDatasetMeta(0);
                meta.data.forEach((bar, i) => {
                    const isTop = i === 0;
                    ctx.save();
                    ctx.shadowBlur = isTop ? 28 : 12;
                    ctx.shadowColor = isTop ? 'rgba(232,213,163,0.7)' : 'rgba(212,175,55,0.4)';
                    ctx.fillStyle = 'transparent';
                    ctx.strokeStyle = isTop ? 'rgba(232,213,163,0.5)' : 'rgba(212,175,55,0.2)';
                    ctx.lineWidth = isTop ? 2 : 1;
                    const { x, y, width, height, base } = bar;
                    ctx.beginPath();
                    ctx.roundRect
                        ? ctx.roundRect(x, y, width - x + base, height, 4)
                        : ctx.rect(x, y, width - x + base, height);
                    ctx.stroke();
                    ctx.restore();

                    // Drifting data particles from top of each bar (antigravity gold)
                    if (valuesBar[i] > 0) {
                        for (let p = 0; p < (isTop ? 5 : 2); p++) {
                            const px = bar.base + Math.random() * (bar.x - bar.base);
                            const py = bar.y + Math.random() * bar.height;
                            const pr = Math.random() * 1.5 + 0.4;
                            const pa = Math.random() * 0.5 + 0.2;
                            ctx.beginPath();
                            ctx.arc(px, py, pr, 0, Math.PI * 2);
                            ctx.fillStyle = isTop
                                ? `rgba(232,213,163,${pa})`
                                : `rgba(212,175,55,${pa * 0.7})`;
                            ctx.fill();
                        }
                    }
                });
            }
        };

        new Chart(ctxBar, {
            type: 'bar',
            plugins: [barGlowPlugin],
            data: {
                labels: labelsBar.length ? labelsBar : ['Belum ada data'],
                datasets: [{
                    label: 'Jumlah Penggunaan',
                    data: valuesBar.length ? valuesBar : [0],
                    backgroundColor: valuesBar.length ? barColors : ['#1e293b'],
                    borderColor: valuesBar.length ? barBorders : ['#334155'],
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(212,175,55,0.08)',
                            drawBorder: false,
                            lineWidth: 1,
                        },
                        border: { display: false },
                        ticks: {
                            color: 'rgba(232,213,163,0.6)',
                            font: { family: "'Courier New', monospace", size: 10 },
                            stepSize: 1,
                        },
                        beginAtZero: true
                    },
                    y: {
                        grid: { display: false, drawBorder: false },
                        border: { display: false },
                        ticks: {
                            color: '#cbd5e1',
                            font: { family: "'Courier New', monospace", size: 11, weight: '600' },
                            padding: 8,
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(18,14,6,0.95)',
                        titleColor: '#e8d5a3',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(212,175,55,0.5)',
                        borderWidth: 1,
                        padding: 14,
                        cornerRadius: 10,
                        titleFont: { family: "'Courier New', monospace", size: 12, weight: '700' },
                        bodyFont:  { family: "'Courier New', monospace", size: 11 },
                        callbacks: {
                            title: (items) => `⬡ ${items[0].label}`,
                            label: (ctx) => `  Digunakan: ${ctx.raw}× oleh pelanggan`
                        }
                    }
                },
                animation: {
                    duration: 1800,
                    easing: 'easeOutQuart',
                    delay: (ctx) => ctx.dataIndex * 100
                }
            }
        });

    }); // end DOMContentLoaded
    </script>
<?php endif; ?>
