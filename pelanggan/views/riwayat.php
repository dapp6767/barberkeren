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

    <!-- Export Action Bar -->
    <div class="riwayat-export-bar border-b border-white/5 bg-[#181410]">
        <div class="flex items-center gap-2">
            <i data-lucide="download" class="w-3.5 h-3.5 text-amber-500/60"></i>
            <span class="riwayat-export-label">Ekspor Data</span>
        </div>
        <div id="riwayat-btn-container" class="flex flex-wrap gap-1.5"></div>
    </div>

    <!-- Desktop Table View (md+) -->
    <div id="printable-riwayat" class="hidden md:block overflow-x-auto custom-scroll p-2">
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

        <!-- Mobile Export Buttons -->
        <div class="bg-[#16120c] border border-white/8 rounded-xl p-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500 mb-2 flex items-center gap-1.5">
                <i data-lucide="download" class="w-3 h-3"></i> Ekspor Data
            </p>
            <div class="flex flex-wrap gap-1.5" id="riwayat-mobile-export">
                <button onclick="exportRiwayatCSV()" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold bg-blue-900/25 text-blue-300 border border-blue-500/30 hover:bg-blue-900/45 transition-all">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i> CSV
                </button>
                <button onclick="printRiwayat()" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold bg-amber-900/25 text-amber-300 border border-amber-500/30 hover:bg-amber-900/45 transition-all">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i> Print
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// =========================================================
//  Riwayat Table — DataTables + Buttons Initialization
// =========================================================
$(document).ready(function () {
    if ($.fn.DataTable && $('#riwayatTable').length) {
        var riwayatDT = $('#riwayatTable').DataTable({
            dom: '<"dataTables_header"f>t<"dataTables_footer"<"dataTables_footer_right"li>p>',
            pageLength: 10,
            language: {
                search:         "Cari Riwayat:",
                lengthMenu:     "Tampilkan _MENU_ data",
                info:           "Menampilkan _START_ sampai _END_ dari _TOTAL_ riwayat",
                infoEmpty:      "Tidak ada data",
                paginate: {
                    previous:   "‹",
                    next:       "›"
                }
            },
            columnDefs: [
                { orderable: true, targets: '_all' }
            ],
            order: [[3, 'desc']],  // sort by date desc
            buttons: [
                {
                    extend:    'excelHtml5',
                    text:      '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:4px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg> Excel',
                    className:  'buttons-excel',
                    title:      'Riwayat Cukur - Elite Barber',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend:    'csvHtml5',
                    text:      '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:4px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> CSV',
                    className:  'buttons-csv',
                    title:      'Riwayat Cukur - Elite Barber',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend:    'pdfHtml5',
                    text:      '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:4px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg> PDF',
                    className:  'buttons-pdf',
                    title:      'Riwayat Cukur - Elite Barber',
                    orientation: 'landscape',
                    pageSize:   'A4',
                    exportOptions: { columns: ':visible' },
                    customize: function (doc) {
                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fillColor = '#1a0e04';
                        doc.styles.tableHeader.color     = '#f59e0b';
                        doc.styles.tableHeader.bold      = true;
                    }
                },
                {
                    extend:    'print',
                    text:      '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:4px"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg> Print',
                    className:  'buttons-print',
                    title:      'Riwayat Cukur - Elite Barber',
                    exportOptions: { columns: ':visible' },
                    customize: function (win) {
                        $(win.document.body).css({
                            'font-family': 'sans-serif',
                            'background':  '#fff',
                            'color':       '#111'
                        });
                        $(win.document.body).find('h1').css({
                            'color':       '#b45309',
                            'font-size':   '18px',
                            'font-weight': 'bold',
                            'margin-bottom': '12px'
                        });
                        $(win.document.body).find('table').css({
                            'border-collapse': 'collapse',
                            'width':           '100%'
                        });
                        $(win.document.body).find('table th').css({
                            'background':  '#1a0e04',
                            'color':       '#f59e0b',
                            'padding':     '8px 12px',
                            'font-size':   '11px',
                            'text-transform': 'uppercase'
                        });
                        $(win.document.body).find('table td').css({
                            'padding':     '7px 12px',
                            'border-bottom': '1px solid #eee',
                            'font-size':   '12px'
                        });
                    }
                }
            ]
        });

        // Move the generated buttons into our custom container
        riwayatDT.buttons().container().appendTo('#riwayat-btn-container');
    }
});

// ===== Mobile Export Helpers =====
function exportRiwayatCSV() {
    var rows = [['No. Tiket','Layanan','Kursi','Tanggal & Waktu','Total Harga']];
    $('#riwayatTable tbody tr').each(function () {
        var cols = [];
        $(this).find('td').each(function (i) {
            var txt = $(this).text().trim().replace(/\s+/g, ' ');
            cols.push('"' + txt.replace(/"/g, '""') + '"');
        });
        if (cols.length) rows.push(cols);
    });
    var csv   = rows.map(function(r){ return r.join(','); }).join('\n');
    var blob  = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    var url   = URL.createObjectURL(blob);
    var a     = document.createElement('a');
    a.href    = url;
    a.download = 'riwayat-cukur-elite-barber.csv';
    a.click();
    URL.revokeObjectURL(url);
}

function printRiwayat() {
    var printContent = document.getElementById('printable-riwayat');
    if (!printContent) { window.print(); return; }
    var winPrint = window.open('', '', 'width=900,height=650');
    winPrint.document.write(
        '<html><head><title>Riwayat Cukur - Elite Barber</title>' +
        '<style>' +
        'body{font-family:sans-serif;color:#111;padding:20px;}' +
        'h2{color:#b45309;margin-bottom:12px;}' +
        'table{border-collapse:collapse;width:100%;}' +
        'th{background:#1a0e04;color:#f59e0b;padding:9px 12px;font-size:11px;text-transform:uppercase;text-align:left;}' +
        'td{padding:8px 12px;border-bottom:1px solid #eee;font-size:12px;}' +
        'tfoot td{background:#fffbeb;font-weight:bold;color:#b45309;}' +
        '</style></head><body>' +
        '<h2>Riwayat Cukur &mdash; Elite Barber</h2>' +
        printContent.innerHTML +
        '</body></html>'
    );
    winPrint.document.close();
    winPrint.focus();
    winPrint.print();
    winPrint.close();
}
</script>
</section>
