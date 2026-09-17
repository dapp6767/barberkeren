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

    <!-- Export Action Bar (identik dengan admin) -->
    <div class="flex items-center justify-between flex-wrap gap-2 px-5 py-3 border-b border-white/5 bg-[#181410]">
        <span class="text-[11px] font-bold uppercase tracking-widest text-zinc-500 flex items-center gap-1.5">
            <i data-lucide="download" class="w-3.5 h-3.5 text-amber-500/60"></i>
            Ekspor Data
        </span>
        <div class="flex flex-wrap gap-1.5">
            <button onclick="exportRiwayat('csv')"
                class="px-3 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 border border-white/10 transition-all">
                <i data-lucide="file-text" class="w-4 h-4 text-blue-400"></i> CSV
            </button>
            <button onclick="exportRiwayat('xlsx')"
                class="px-3 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 border border-white/10 transition-all">
                <i data-lucide="table-2" class="w-4 h-4 text-emerald-400"></i> Excel
            </button>
            <button onclick="exportRiwayat('pdf')"
                class="px-3 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 border border-white/10 transition-all">
                <i data-lucide="file-down" class="w-4 h-4 text-red-400"></i> PDF
            </button>
            <button onclick="exportRiwayat('print')"
                class="px-3 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 border border-white/10 transition-all">
                <i data-lucide="printer" class="w-4 h-4 text-amber-400"></i> Print
            </button>
        </div>
    </div>

    <!-- Desktop Table View (md+) -->
    <div class="hidden md:block overflow-x-auto custom-scroll p-2">
        <table id="table-riwayat-pelanggan" class="w-full text-left border-collapse display">
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

<script>
// =========================================================
//  DataTables Init — Riwayat Pelanggan
// =========================================================
$(document).ready(function () {
    if ($.fn.DataTable && $('#table-riwayat-pelanggan').length &&
        $('#table-riwayat-pelanggan tbody tr').length > 0 &&
        !$('#table-riwayat-pelanggan tbody tr td[colspan]').length) {

        $('#table-riwayat-pelanggan').DataTable({
            dom: '<"dataTables_header"f>t<"dataTables_footer"<"dataTables_footer_right"li>p>',
            pageLength: 10,
            language: {
                search:      "Cari Riwayat:",
                lengthMenu:  "Tampilkan _MENU_ data",
                info:        "Menampilkan _START_ sampai _END_ dari _TOTAL_ riwayat",
                infoEmpty:   "Tidak ada data riwayat",
                infoFiltered:"(disaring dari _MAX_ total riwayat)",
                zeroRecords: "Tidak ada riwayat yang sesuai",
                paginate: { previous: "❮", next: "❯" }
            },
            order: [[3, 'desc']],
            responsive: true
        });
    }
});

// =========================================================
//  exportRiwayat() — identik dengan exportData() di admin
//  tableId = 'table-riwayat-pelanggan'
// =========================================================
function exportRiwayat(format) {
    var tableId  = 'table-riwayat-pelanggan';
    var headers  = [];
    var rows     = [];

    var tableEl = document.getElementById(tableId);
    if (!tableEl) { alert('Tabel tidak ditemukan!'); return; }

    // Ambil header (kecuali kolom 'Aksi')
    tableEl.querySelectorAll('thead th').forEach(function(th) {
        var txt = th.innerText.trim();
        if (txt && txt.toLowerCase() !== 'aksi' && txt !== '') headers.push(txt);
    });

    // Ambil baris (via DataTables jika aktif, atau DOM langsung)
    if (window.jQuery && $.fn.dataTable && $.fn.dataTable.isDataTable('#' + tableId)) {
        $('#' + tableId).DataTable().rows({ search: 'applied' }).every(function(rowIdx, tableLoop, rowLoop) {
            var rowNode = this.node();
            if (rowNode) {
                var rowData = [];
                rowNode.querySelectorAll('td').forEach(function(td, colIdx) {
                    if (colIdx < headers.length) {
                        var clone = td.cloneNode(true);
                        clone.querySelectorAll('button, form, script, style, input, .hidden, [class*="hidden"], [style*="display:none"], [style*="display: none"]').forEach(function(el) { el.remove(); });
                        var txt = clone.innerText.trim().replace(/\s+/g, ' ');
                        if (colIdx === 0 && (!txt || txt === '')) txt = String(rowLoop + 1);
                        rowData.push(txt);
                    }
                });
                if (rowData.length > 0) rows.push(rowData);
            }
        });
    } else {
        tableEl.querySelectorAll('tbody tr').forEach(function(tr, idx) {
            var rowData = [];
            tr.querySelectorAll('td').forEach(function(td, colIdx) {
                if (colIdx < headers.length) {
                    var clone = td.cloneNode(true);
                    clone.querySelectorAll('button, form, script, style, input, .hidden, [class*="hidden"], [style*="display:none"], [style*="display: none"]').forEach(function(el) { el.remove(); });
                    var txt = clone.innerText.trim().replace(/\s+/g, ' ');
                    if (colIdx === 0 && (!txt || txt === '')) txt = String(idx + 1);
                    rowData.push(txt);
                }
            });
            if (rowData.length > 0) rows.push(rowData);
        });
    }

    if (rows.length === 0) { alert('Tidak ada data untuk diekspor!'); return; }

    var fileName = 'riwayat_cukur_' + new Date().toISOString().slice(0, 10);
    doRiwayatDownload(format, headers, rows, fileName);
}

function doRiwayatDownload(format, headers, rows, fileName) {

    // ── Deteksi tipe kolom & alignment (identik admin) ──────────────────────
    var colAlignments = [];
    var isMoneyCol    = [];
    headers.forEach(function(h, colIdx) {
        var hLower = h.toLowerCase().trim();
        var isNo       = (colIdx === 0 && (hLower.includes('no') || hLower === '#' || hLower.includes('id')));
        var isDateTime = (hLower.includes('waktu') || hLower.includes('tanggal') || hLower.includes('tgl') || hLower.includes('jam') || hLower.includes('date') || hLower.includes('time'));
        var isMoney    = !isDateTime && (hLower.includes('harga') || hLower.includes('total bayar') || hLower.includes('total harga') || hLower.includes('nominal') || hLower.includes('biaya') || hLower.includes('tarif') || (hLower.includes('total') && !hLower.includes('antrean') && !hLower.includes('tiket')) || hLower === 'bayar');
        colAlignments.push(isNo ? 'center' : (isMoney ? 'right' : (isDateTime ? 'center' : 'left')));
        isMoneyCol.push(isMoney);
    });

    // ── Hitung total kolom uang (identik admin) ──────────────────────────────
    var moneyTotals  = headers.map(function() { return 0; });
    var hasMoneyTotal = false;
    rows.forEach(function(r) {
        r.forEach(function(val, cIdx) {
            if (isMoneyCol[cIdx]) {
                var strVal   = String(val).trim();
                var cleanNum = strVal.replace(/[^0-9]/g, '');
                if (cleanNum && !strVal.includes('-') && !strVal.includes(':')) {
                    moneyTotals[cIdx] += parseFloat(cleanNum);
                    hasMoneyTotal = true;
                }
            }
        });
    });

    // ── Tanggal Indonesia ────────────────────────────────────────────────────
    var now               = new Date();
    var dateOptions       = { day: 'numeric', month: 'long', year: 'numeric' };
    var formattedDateIndo = now.toLocaleDateString('id-ID', dateOptions);
    var formattedTimeIndo = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
    var reportTitle       = 'LAPORAN RIWAYAT CUKUR PELANGGAN';

    // ════════════════════════════════════════════════════════════════════════
    //  CSV
    // ════════════════════════════════════════════════════════════════════════
    if (format === 'csv') {
        var csvContent = [headers.map(function(h) { return '"' + h.replace(/"/g, '""') + '"'; }).join(',')];
        rows.forEach(function(r) {
            csvContent.push(r.map(function(v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(','));
        });
        var blob = new Blob(['\ufeff' + csvContent.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = fileName + '.csv';
        link.click();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  EXCEL (SheetJS — identik admin)
    // ════════════════════════════════════════════════════════════════════════
    else if (format === 'xlsx') {
        if (typeof XLSX !== 'undefined') {
            var aoa = [headers].concat(rows);
            var wb  = XLSX.utils.book_new();
            var ws  = XLSX.utils.aoa_to_sheet(aoa);
            XLSX.utils.book_append_sheet(wb, ws, 'Riwayat Cukur');
            XLSX.writeFile(wb, fileName + '.xlsx');
        } else {
            alert('Library XLSX (SheetJS) tidak tersedia.');
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRINT — Kop Surat Resmi + iframe (identik admin)
    // ════════════════════════════════════════════════════════════════════════
    else if (format === 'print') {
        // Tfoot HTML
        var tfootHtml = '';
        if (hasMoneyTotal) {
            var firstMoneyIdx = isMoneyCol.findIndex(function(m) { return m === true; });
            tfootHtml += '<tr>';
            if (firstMoneyIdx > 0) {
                tfootHtml += '<td colspan="' + firstMoneyIdx + '" style="text-align:right;font-weight:bold;background-color:#f0f0f0;">TOTAL KESELURUHAN (' + rows.length + ' Data)</td>';
            }
            headers.forEach(function(h, cIdx) {
                if (cIdx >= firstMoneyIdx) {
                    if (isMoneyCol[cIdx]) {
                        var formattedSum = 'Rp ' + new Intl.NumberFormat('id-ID').format(moneyTotals[cIdx]);
                        tfootHtml += '<td style="text-align:right;font-weight:bold;background-color:#f0f0f0;">' + formattedSum + '</td>';
                    } else {
                        tfootHtml += '<td style="background-color:#f0f0f0;"></td>';
                    }
                }
            });
            tfootHtml += '</tr>';
        } else {
            tfootHtml = '<tr><td colspan="' + headers.length + '" style="text-align:left;font-weight:bold;background-color:#f0f0f0;">TOTAL DATA: ' + rows.length + ' Baris Data</td></tr>';
        }

        var html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>' + reportTitle + ' - Elite Barber</title><style>' +
            '* { box-sizing:border-box; }' +
            'body { font-family:"Segoe UI",Arial,Helvetica,sans-serif; margin:0; padding:20px; color:#111; background:#fff; font-size:12px; }' +
            '.kop-container { display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; }' +
            '.kop-brand { display:flex; align-items:center; gap:12px; }' +
            '.kop-logo { width:46px;height:46px;background-color:#1a1a1a;color:#f59e0b;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:bold;box-shadow:0 2px 4px rgba(0,0,0,.1);-webkit-print-color-adjust:exact;print-color-adjust:exact; }' +
            '.kop-title { margin:0;font-size:22px;font-weight:800;letter-spacing:1.5px;color:#1a1a1a;text-transform:uppercase;line-height:1.1; }' +
            '.kop-sub { margin:3px 0 0;font-size:11px;color:#555;font-style:italic; }' +
            '.kop-contact { text-align:right;font-size:11px;color:#333;line-height:1.4; } .kop-contact p { margin:0; }' +
            '.kop-divider { border-bottom:3px double #000;margin-top:5px;margin-bottom:18px; }' +
            '.report-title-section { text-align:center;margin-bottom:18px; }' +
            '.report-main-title { margin:0;font-size:16px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#111; }' +
            '.report-sub-meta { margin:4px 0 0;font-size:11px;color:#555; }' +
            'table.report-table { width:100%;border-collapse:collapse;margin-top:10px;font-size:11px; }' +
            'table.report-table th { background-color:#1a1a1a!important;color:#fff!important;font-weight:700;padding:8px 10px;border:1px solid #000;text-transform:uppercase;font-size:10.5px;letter-spacing:.5px;-webkit-print-color-adjust:exact;print-color-adjust:exact; }' +
            'table.report-table td { padding:7px 10px;border:1px solid #ccc;color:#1a1a1a;line-height:1.35; }' +
            'table.report-table tbody tr:nth-child(even) { background-color:#f8f9fa!important;-webkit-print-color-adjust:exact;print-color-adjust:exact; }' +
            'table.report-table tfoot td { padding:8px 10px;border-top:2px solid #000;border-bottom:2px solid #000;border-left:1px solid #ccc;border-right:1px solid #ccc;color:#000;font-size:11px;-webkit-print-color-adjust:exact;print-color-adjust:exact; }' +
            '.signature-wrapper { margin-top:35px;display:flex;justify-content:flex-end;page-break-inside:avoid; }' +
            '.signature-box { width:250px;text-align:center;font-size:12px;color:#111; }' +
            '.sig-date { margin:0 0 4px; } .sig-role { margin:0;font-weight:700; } .sig-space { height:60px; } .sig-name { margin:0;font-weight:700; }' +
            '@page { size:A4 portrait; margin:12mm 15mm 15mm 15mm; }' +
            '@media print { body { padding:0;margin:0;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact; } table.report-table th, table.report-table tbody tr:nth-child(even), table.report-table tfoot td { -webkit-print-color-adjust:exact;print-color-adjust:exact; } table.report-table { page-break-inside:auto; } table.report-table tr { page-break-inside:avoid;page-break-after:auto; } .signature-wrapper { page-break-inside:avoid; } }' +
            '</style></head><body>' +
            '<div class="kop-container"><div class="kop-brand"><div class="kop-logo">✂</div><div><h1 class="kop-title">ELITE BARBER</h1><p class="kop-sub">Executive Barbershop &amp; Grooming Studio</p></div></div>' +
            '<div class="kop-contact"><p><strong>Jl. Z.A. Pagar Alam No. 45, Kedaton</strong></p><p>Bandar Lampung, Lampung 35141</p><p>Telp/WA: 0812-3456-7890 | Email: info@elitebarber.com</p></div></div>' +
            '<div class="kop-divider"></div>' +
            '<div class="report-title-section"><h2 class="report-main-title">' + reportTitle + '</h2><p class="report-sub-meta">Dicetak pada: ' + formattedDateIndo + ', ' + formattedTimeIndo + ' | Dokumen Resmi Elite Barber</p></div>' +
            '<table class="report-table"><thead><tr>' +
            headers.map(function(h, i) { return '<th style="text-align:' + colAlignments[i] + ';">' + h + '</th>'; }).join('') +
            '</tr></thead><tbody>' +
            rows.map(function(r) { return '<tr>' + r.map(function(v, i) { return '<td style="text-align:' + colAlignments[i] + ';">' + v + '</td>'; }).join('') + '</tr>'; }).join('') +
            '</tbody><tfoot>' + tfootHtml + '</tfoot></table>' +
            '<div class="signature-wrapper"><div class="signature-box"><p class="sig-date">Bandar Lampung, ' + formattedDateIndo + '</p><p class="sig-role">Admin / Pemilik Elite Barber</p><div class="sig-space"></div><p class="sig-name">( .................................... )</p></div></div>' +
            '</body></html>';

        // Cetak via invisible iframe (identik admin — tidak buka popup baru)
        var printIframe = document.createElement('iframe');
        printIframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        document.body.appendChild(printIframe);
        var doc = printIframe.contentWindow.document;
        doc.open(); doc.write(html); doc.close();
        setTimeout(function() {
            try { printIframe.contentWindow.focus(); printIframe.contentWindow.print(); } catch(e) { console.error('Print error:', e); }
            setTimeout(function() { if (printIframe.parentNode) printIframe.parentNode.removeChild(printIframe); }, 2000);
        }, 350);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PDF — jsPDF + AutoTable + Preview Tab (identik admin)
    // ════════════════════════════════════════════════════════════════════════
    else if (format === 'pdf') {
        // Buka tab baru dengan loading state dahulu (anti popup-blocker)
        var previewWindow = window.open('', '_blank');
        if (previewWindow) {
            previewWindow.document.write('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Memuat Preview Dokumen...</title><style>' +
                'body{margin:0;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;font-family:"Segoe UI",-apple-system,BlinkMacSystemFont,Roboto,sans-serif;background:#0f172a;color:#f8fafc;}' +
                '.loader-box{text-align:center;background:#1e293b;padding:32px 40px;border-radius:16px;box-shadow:0 10px 25px -5px rgba(0,0,0,.5);border:1px solid #334155;}' +
                '.spinner{width:42px;height:42px;border:4px solid rgba(245,158,11,.2);border-top-color:#f59e0b;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 18px;}' +
                '@keyframes spin{to{transform:rotate(360deg);}}' +
                'h3{margin:0 0 6px;font-size:17px;font-weight:700;color:#f8fafc;letter-spacing:.5px;}p{margin:0;color:#94a3b8;font-size:13px;}' +
                '</style></head><body><div class="loader-box"><div class="spinner"></div><h3>MEMBUAT PREVIEW PDF</h3><p>Menyiapkan dokumen resmi Elite Barber...</p></div></body></html>');
        }

        var jsPDFClass = window.jspdf ? window.jspdf.jsPDF : (window.jsPDF || null);
        if (jsPDFClass) {
            try {
                var isLandscape = headers.length > 5;
                var pageWidth   = isLandscape ? 297 : 210;
                var pageHeight  = isLandscape ? 210 : 297;
                var rightMargin = pageWidth - 14;
                var centerX     = pageWidth / 2;

                var doc = new jsPDFClass({ orientation: isLandscape ? 'landscape' : 'portrait', unit: 'mm', format: 'a4' });

                // 1. Kop Surat — Badge EB
                doc.setFillColor(26, 26, 26);
                doc.roundedRect(14, 10, 13, 13, 2, 2, 'F');
                doc.setTextColor(245, 158, 11);
                doc.setFontSize(11); doc.setFont('helvetica', 'bold');
                doc.text('EB', 20.5, 18.5, { align: 'center' });

                // Brand name
                doc.setTextColor(26, 26, 26); doc.setFontSize(15); doc.setFont('helvetica', 'bold');
                doc.text('ELITE BARBER', 30, 16);
                doc.setFontSize(8); doc.setFont('helvetica', 'italic'); doc.setTextColor(110, 110, 110);
                doc.text('Executive Barbershop & Grooming Studio', 30, 20.5);

                // Alamat (kanan)
                doc.setFont('helvetica', 'normal'); doc.setFontSize(7.5); doc.setTextColor(50, 50, 50);
                doc.text('Jl. Z.A. Pagar Alam No. 45, Kedaton', rightMargin, 13, { align: 'right' });
                doc.text('Bandar Lampung, Lampung 35141', rightMargin, 17, { align: 'right' });
                doc.text('Telp/WA: 0812-3456-7890 | info@elitebarber.com', rightMargin, 21, { align: 'right' });

                // Garis dobel pembatas kop
                doc.setDrawColor(0, 0, 0);
                doc.setLineWidth(0.6); doc.line(14, 25.5, rightMargin, 25.5);
                doc.setLineWidth(0.2); doc.line(14, 26.5, rightMargin, 26.5);

                // 2. Judul Laporan
                doc.setFont('helvetica', 'bold'); doc.setFontSize(11); doc.setTextColor(17, 17, 17);
                doc.text(reportTitle, centerX, 33, { align: 'center' });
                doc.setFont('helvetica', 'normal'); doc.setFontSize(7.5); doc.setTextColor(100, 100, 100);
                doc.text('Dicetak pada: ' + formattedDateIndo + ', ' + formattedTimeIndo + ' | Dokumen Resmi Elite Barber', centerX, 37.5, { align: 'center' });

                // 3. Konfigurasi kolom
                var columnStyles = {};
                colAlignments.forEach(function(align, idx) {
                    columnStyles[idx] = { halign: align };
                    var hLower = headers[idx].toLowerCase();
                    if (idx === 0 && (hLower.includes('no') || hLower === '#' || hLower.includes('id'))) {
                        columnStyles[idx].cellWidth = 10;
                        columnStyles[idx].halign = 'center';
                    }
                });

                // Footer rows PDF
                var footRows = [];
                if (hasMoneyTotal) {
                    var firstMoneyIdx = isMoneyCol.findIndex(function(m) { return m === true; });
                    var footRow = [];
                    headers.forEach(function(h, cIdx) {
                        if (cIdx === 0 && firstMoneyIdx > 0) {
                            footRow.push({ content: 'TOTAL KESELURUHAN (' + rows.length + ' Data)', colSpan: firstMoneyIdx, styles: { halign: 'right', fontStyle: 'bold' } });
                        } else if (cIdx < firstMoneyIdx) {
                            // covered by colSpan
                        } else if (isMoneyCol[cIdx]) {
                            footRow.push({ content: 'Rp ' + new Intl.NumberFormat('id-ID').format(moneyTotals[cIdx]), styles: { halign: 'right', fontStyle: 'bold' } });
                        } else {
                            footRow.push({ content: '', styles: { halign: 'center' } });
                        }
                    });
                    footRows.push(footRow);
                } else {
                    footRows.push([{ content: 'TOTAL DATA: ' + rows.length + ' Baris Data', colSpan: headers.length, styles: { fontStyle: 'bold' } }]);
                }

                // 4. AutoTable
                doc.autoTable({
                    head: [headers],
                    body: rows,
                    foot: footRows,
                    startY: 42,
                    margin: { left: 14, right: 14, top: 14, bottom: 18 },
                    theme: 'grid',
                    headStyles: { fillColor: [26, 26, 26], textColor: [255, 255, 255], fontStyle: 'bold', fontSize: 8, cellPadding: 2.2, halign: 'center', valign: 'middle' },
                    bodyStyles: { fontSize: 7.5, textColor: [30, 30, 30], cellPadding: 2, valign: 'middle' },
                    alternateRowStyles: { fillColor: [248, 249, 250] },
                    footStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontSize: 8, cellPadding: 2.2, fontStyle: 'bold', lineColor: [50, 50, 50], lineWidth: 0.2 },
                    columnStyles: columnStyles,
                    styles: { lineColor: [210, 210, 210], lineWidth: 0.1, overflow: 'linebreak' },
                    didDrawPage: function(data) {
                        var totalPages = doc.internal.getNumberOfPages();
                        doc.setFontSize(7); doc.setFont('helvetica', 'normal'); doc.setTextColor(140, 140, 140);
                        doc.text('Halaman ' + data.pageNumber + ' dari ' + totalPages, rightMargin, pageHeight - 8, { align: 'right' });
                        doc.text('Elite Barber System - Dokumen Otentik Terverifikasi', 14, pageHeight - 8);
                    }
                });

                // 5. Tanda Tangan
                var finalY = doc.lastAutoTable ? doc.lastAutoTable.finalY + 10 : 160;
                if (finalY > pageHeight - 40) { doc.addPage(); finalY = 20; }
                var sigX = rightMargin - 30;
                doc.setFontSize(8); doc.setFont('helvetica', 'normal'); doc.setTextColor(20, 20, 20);
                doc.text('Bandar Lampung, ' + formattedDateIndo, sigX, finalY, { align: 'center' });
                doc.setFont('helvetica', 'bold');
                doc.text('Admin / Pemilik Elite Barber', sigX, finalY + 4.5, { align: 'center' });
                doc.text('( .................................... )', sigX, finalY + 22, { align: 'center' });

                // 6. Tampilkan di tab preview
                var pdfBlob    = doc.output('blob');
                var pdfBlobUrl = URL.createObjectURL(pdfBlob);
                if (previewWindow && !previewWindow.closed) {
                    previewWindow.location.href = pdfBlobUrl;
                } else {
                    window.open(pdfBlobUrl, '_blank');
                }
            } catch(e) {
                console.error('PDF Generate Error:', e);
                if (previewWindow && !previewWindow.closed) {
                    previewWindow.document.body.innerHTML = '<div style="color:#ef4444;text-align:center;padding:40px;font-family:sans-serif;"><h3>Gagal membuat preview PDF</h3><p>' + e.message + '</p></div>';
                }
            }
        } else {
            if (previewWindow && !previewWindow.closed) previewWindow.close();
            alert('Library jsPDF tidak ditemukan di browser.');
        }
    }
}
</script>
</section>
