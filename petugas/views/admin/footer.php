<!-- Initialize Lucide Icons & Shared JavaScript Functions -->
<script>
    lucide.createIcons();

    function togglePass(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input) {
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                if (icon) icon.setAttribute('data-lucide', 'eye');
            }
            if (window.lucide) lucide.createIcons();
        }
    }

    function openEditLayananModal(id, name, price, durasi, deskripsi, is_terbaik = 0, gambar_url = '') {
        document.getElementById('editLayananModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.getElementById('edit_layanan_id').value = id;
        document.getElementById('edit_layanan_nama').value = name;
        document.getElementById('edit_layanan_harga').value = price;
        document.getElementById('edit_layanan_durasi').value = durasi;
        document.getElementById('edit_layanan_deskripsi').value = deskripsi;
        const gInput = document.getElementById('edit_layanan_gambar_url');
        if (gInput) gInput.value = gambar_url;
        const cb = document.getElementById('edit_is_terbaik');
        if (cb) cb.checked = (parseInt(is_terbaik) === 1);
    }

    function closeEditLayananModal() {
        document.getElementById('editLayananModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function openAddUserModal() {
        document.getElementById('addUserModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeAddUserModal() {
        document.getElementById('addUserModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function openEditUserModal(id, fullname, username, email, phone, role) {
        document.getElementById('editUserModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_user_fullname').value = fullname;
        document.getElementById('edit_user_username').value = username;
        document.getElementById('edit_user_email').value = email;
        document.getElementById('edit_user_phone').value = phone;
        document.getElementById('edit_user_role').value = role;
    }

    function closeEditUserModal() {
        document.getElementById('editUserModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function openDescModal(title, text, durasi, harga, imgUrl) {
        const modal = document.getElementById('descModal');
        const modalContent = document.getElementById('descModalContent');
        
        document.getElementById('descModalTitle').innerText = title;
        document.getElementById('descModalText').innerText = text;
        document.getElementById('descModalDurasi').innerText = durasi + ' Menit';
        document.getElementById('descModalHarga').innerText = harga;
        
        const imgEl = document.getElementById('descModalImg');
        if (imgEl) imgEl.src = imgUrl;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }, 10);
    }

    function closeDescModal() {
        const modal = document.getElementById('descModal');
        const modalContent = document.getElementById('descModalContent');
        
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }, 300);
    }

    function openAddLayananModal() {
        document.getElementById('addLayananModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeAddLayananModal() {
        document.getElementById('addLayananModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function openCardModal(modalId) {
        const backdrop = document.getElementById('cardModalBackdrop');
        if (!backdrop) return;
        
        document.querySelectorAll('.card-modal-content').forEach(el => el.classList.add('hidden'));
        
        const target = document.getElementById(modalId);
        if (target) {
            target.classList.remove('hidden');
            backdrop.classList.remove('hidden');
            backdrop.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeCardModal() {
        const backdrop = document.getElementById('cardModalBackdrop');
        if (!backdrop) return;
        backdrop.classList.add('hidden');
        backdrop.classList.remove('flex');
        document.body.style.overflow = '';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const backdrop = document.getElementById('cardModalBackdrop');
        if (backdrop) {
            backdrop.addEventListener('click', function(e) {
                if (e.target === backdrop) {
                    closeCardModal();
                }
            });
        }

        const backdropIds = ['editLayananModal', 'addLayananModal', 'addUserModal', 'editUserModal', 'receiptModal', 'descModal'];
        backdropIds.forEach(id => {
            const modal = document.getElementById(id);
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        if (id === 'editLayananModal') closeEditLayananModal();
                        else if (id === 'addLayananModal') closeAddLayananModal();
                        else if (id === 'addUserModal') closeAddUserModal();
                        else if (id === 'editUserModal') closeEditUserModal();
                        else if (id === 'receiptModal') closeStruk();
                        else if (id === 'descModal') closeDescModal();
                    }
                });
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditLayananModal();
                closeAddLayananModal();
                closeAddUserModal();
                closeEditUserModal();
                closeStruk();
                closeDescModal();
                closeCardModal();
            }
        });
    });


    function printStruk(tiket, nama, layanan, total, metode, antrian_id = null, is_new_payment = false) {
        document.getElementById('r_tiket').innerText = tiket;
        document.getElementById('r_nama').innerText = nama;
        document.getElementById('r_layanan').innerText = layanan;
        document.getElementById('r_total').innerText = parseInt(total).toLocaleString('id-ID');
        document.getElementById('r_metode').innerText = metode;
        
        if (is_new_payment && antrian_id) {
            document.getElementById('r_antrian_id').value = antrian_id;
            document.getElementById('r_total_input').value = total;
            document.getElementById('form_confirm_paid').style.display = 'block';
        } else {
            document.getElementById('form_confirm_paid').style.display = 'none';
        }
        document.getElementById('receiptModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeStruk() {
        document.getElementById('receiptModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    let notifiedTickets = [];
    function checkNewPayments() {
        fetch('api_check_payment.php')
            .then(response => response.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    let q = res.data;
                    if (!notifiedTickets.includes(q.id)) {
                        notifiedTickets.push(q.id);
                        let audio = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
                        audio.play().catch(e => {});
                        printStruk(
                            q.no_antrean, 
                            q.pelanggan_nama || 'Guest', 
                            q.nama_layanan || 'Layanan', 
                            q.final_price, 
                            q.metode_bayar || 'Cash/QRIS',
                            q.id,
                            true
                        );
                    }
                }
            }).catch(err => console.error(err));
    }
    setInterval(checkNewPayments, 5000);

    // Initialize Tabulator when ready
    let tables = {};
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            const cleanHtml = function(value) {
                if (typeof value === 'string') {
                    let tmp = document.createElement("DIV");
                    tmp.innerHTML = value;
                    return (tmp.textContent || tmp.innerText || "").trim().replace(/\s+/g, ' ');
                }
                return value;
            };
            
            const commonConfig = {
                layout: "fitColumns",
                renderVertical: "basic",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [10, 25, 50, 100],
                movableColumns: true,
                columnDefaults: {
                    accessorDownload: cleanHtml,
                    accessorPrint: cleanHtml
                },
                printAsHtml: true,
                printStyled: true
            };

            // Initialize Layanan Table
            if (document.getElementById('table-layanan')) {
                tables['table-layanan'] = new Tabulator("#table-layanan", commonConfig);
                tables['table-layanan'].on("tableBuilt", function() {
                    document.getElementById('table-layanan').classList.add('table-loaded');
                });
                tables['table-layanan'].on("renderComplete", function() {
                    lucide.createIcons();
                });
                const searchLayanan = document.getElementById("search-layanan");
                if (searchLayanan) {
                    searchLayanan.addEventListener("keyup", function(){
                        let term = this.value.toLowerCase();
                        tables['table-layanan'].setFilter(function(data){
                            for(let key in data) {
                                if(String(data[key]).toLowerCase().includes(term)) return true;
                            }
                            return false;
                        });
                    });
                }
            }
            
            // Initialize Users Table
            if (document.getElementById('table-users')) {
                tables['table-users'] = new Tabulator("#table-users", commonConfig);
                tables['table-users'].on("tableBuilt", function() {
                    document.getElementById('table-users').classList.add('table-loaded');
                });
                tables['table-users'].on("renderComplete", function() {
                    lucide.createIcons();
                });
                const searchUsers = document.getElementById("search-users");
                if (searchUsers) {
                    searchUsers.addEventListener("keyup", function(){
                        let term = this.value.toLowerCase();
                        tables['table-users'].setFilter(function(data){
                            for(let key in data) {
                                if(String(data[key]).toLowerCase().includes(term)) return true;
                            }
                            return false;
                        });
                    });
                }
            }
            
            // Initialize Transaksi Table
            if (document.getElementById('table-transaksi')) {
                tables['table-transaksi'] = new Tabulator("#table-transaksi", commonConfig);
                tables['table-transaksi'].on("tableBuilt", function() {
                    document.getElementById('table-transaksi').classList.add('table-loaded');
                });
                tables['table-transaksi'].on("renderComplete", function() {
                    lucide.createIcons();
                });
                const searchTransaksi = document.getElementById("search-transaksi");
                if (searchTransaksi) {
                    searchTransaksi.addEventListener("keyup", function(){
                        let term = this.value.toLowerCase();
                        tables['table-transaksi'].setFilter(function(data){
                            for(let key in data) {
                                if(String(data[key]).toLowerCase().includes(term)) return true;
                            }
                            return false;
                        });
                    });
                }
            }
        }, 100);
    });

    function exportData(tableId, format) {
        let headers = [];
        let rows = [];

        if (tables && tables[tableId]) {
            let tab = tables[tableId];
            tab.getColumns().forEach(col => {
                let title = col.getDefinition().title || col.getField();
                if (title && title.toLowerCase() !== 'aksi' && title !== '') {
                    headers.push(title);
                }
            });
            let activeRows = tab.getData("active");
            activeRows.forEach((rowObj, index) => {
                let rowData = [];
                tab.getColumns().forEach(col => {
                    let field = col.getField();
                    let title = col.getDefinition().title || field;
                    if (title && title.toLowerCase() !== 'aksi' && title !== '') {
                        let val = rowObj[field];
                        if (field === 'no' || !val) {
                            if (field === 'no') val = String(index + 1);
                            else val = val || '';
                        }
                        if (typeof val === 'string') {
                            let tmp = document.createElement("DIV");
                            tmp.innerHTML = val;
                            tmp.querySelectorAll('button, form, script, style, input, .hidden, [class*="hidden"], [style*="display:none"], [style*="display: none"]').forEach(el => el.remove());
                            val = (tmp.textContent || tmp.innerText || "").trim().replace(/\s+/g, ' ');
                        }
                        rowData.push(val);
                    }
                });
                rows.push(rowData);
            });
        } else {
            const table = document.getElementById(tableId);
            if (!table) { alert("Tabel tidak ditemukan!"); return; }
            
            let ths = table.querySelectorAll("thead th");
            ths.forEach(th => {
                let txt = th.innerText.trim();
                if (txt && txt.toLowerCase() !== 'aksi' && txt !== '') headers.push(txt);
            });
            
            let trs = table.querySelectorAll("tbody tr");
            trs.forEach((tr, idx) => {
                let rowData = [];
                let tds = tr.querySelectorAll("td");
                tds.forEach((td, colIdx) => {
                    if (colIdx < headers.length) {
                        let clone = td.cloneNode(true);
                        clone.querySelectorAll('button, form, script, style, input, .hidden, [class*="hidden"], [style*="display:none"], [style*="display: none"]').forEach(el => el.remove());
                        let txt = clone.innerText.trim().replace(/\s+/g, ' ');
                        if (colIdx === 0 && (!txt || txt === '')) txt = String(idx + 1);
                        rowData.push(txt);
                    }
                });
                if (rowData.length > 0) rows.push(rowData);
            });
        }

        if (rows.length === 0) {
            alert("Tidak ada data untuk diekspor!");
            return;
        }

        const fileName = tableId.replace('table-', 'laporan_') + '_' + new Date().toISOString().slice(0,10);
        doActualDownload(tableId, format, headers, rows, fileName);
    }

    function doActualDownload(tableId, format, headers, rows, fileName) {
        if (format === 'csv') {
            let csvContent = [headers.map(h => '"' + h.replace(/"/g, '""') + '"').join(",")];
            rows.forEach(r => {
                csvContent.push(r.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(","));
            });
            let blob = new Blob(["\ufeff" + csvContent.join("\n")], { type: "text/csv;charset=utf-8;" });
            let link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = fileName + ".csv";
            link.click();
        }
        else if (format === 'xlsx') {
            if (typeof XLSX !== 'undefined') {
                let aoa = [headers, ...rows];
                let wb = XLSX.utils.book_new();
                let ws = XLSX.utils.aoa_to_sheet(aoa);
                XLSX.utils.book_append_sheet(wb, ws, "Data");
                XLSX.writeFile(wb, fileName + ".xlsx");
            } else {
                alert("Library XLSX (SheetJS) tidak tersedia.");
            }
        }
        else if (format === 'print' || format === 'pdf') {
            // Judul Laporan Resmi
            let rawTitle = tableId.replace('table-', '').replace('-', ' ').toUpperCase();
            let reportTitle = "LAPORAN DATA " + rawTitle;
            if (tableId === 'table-transaksi') reportTitle = "LAPORAN TRANSAKSI PENJUALAN & PEMBAYARAN";
            else if (tableId === 'table-layanan') reportTitle = "LAPORAN DAFTAR LAYANAN & HARGA";
            else if (tableId === 'table-users') reportTitle = "LAPORAN DATA PENGGUNA & HAK AKSES";
            else if (tableId === 'table-antrean') reportTitle = "LAPORAN STATUS ANTREAN PELANGGAN";
            else if (tableId.includes('barber')) reportTitle = "LAPORAN DAFTAR ANTREAN TUGAS BARBER";

            // Alignment & Tipe Kolom
            let colAlignments = [];
            let isMoneyCol = [];
            headers.forEach((h, colIdx) => {
                let hLower = h.toLowerCase();
                let isNo = (colIdx === 0 && (hLower.includes('no') || hLower === '#' || hLower.includes('id')));
                let isMoney = (hLower.includes('harga') || hLower.includes('total') || hLower.includes('bayar') || hLower.includes('nominal') || hLower.includes('biaya') || hLower.includes('tarif') || hLower.includes('rp') || hLower.includes('jumlah'));
                
                if (isNo) {
                    colAlignments.push('center');
                } else if (isMoney) {
                    colAlignments.push('right');
                } else if (hLower.includes('status') || hLower.includes('waktu') || hLower.includes('tanggal') || hLower.includes('est')) {
                    colAlignments.push('center');
                } else {
                    colAlignments.push('left');
                }
                isMoneyCol.push(isMoney);
            });

            // Hitung Ringkasan Data (Tfoot)
            let moneyTotals = headers.map(() => 0);
            let hasMoneyTotal = false;

            rows.forEach(r => {
                r.forEach((val, cIdx) => {
                    if (isMoneyCol[cIdx]) {
                        let cleanNum = String(val).replace(/[^0-9]/g, '');
                        if (cleanNum) {
                            moneyTotals[cIdx] += parseFloat(cleanNum);
                            hasMoneyTotal = true;
                        }
                    }
                });
            });

            let tfootHtml = '';
            if (hasMoneyTotal) {
                let firstMoneyIdx = isMoneyCol.findIndex(m => m === true);
                tfootHtml += '<tr>';
                if (firstMoneyIdx > 0) {
                    tfootHtml += `<td colspan="${firstMoneyIdx}" style="text-align: right; font-weight: bold; background-color: #f0f0f0;">TOTAL KESELURUHAN (${rows.length} Data)</td>`;
                }
                headers.forEach((h, cIdx) => {
                    if (cIdx >= firstMoneyIdx) {
                        if (isMoneyCol[cIdx]) {
                            let formattedSum = 'Rp ' + new Intl.NumberFormat('id-ID').format(moneyTotals[cIdx]);
                            tfootHtml += `<td style="text-align: right; font-weight: bold; background-color: #f0f0f0;">${formattedSum}</td>`;
                        } else {
                            tfootHtml += `<td style="background-color: #f0f0f0;"></td>`;
                        }
                    }
                });
                tfootHtml += '</tr>';
            } else {
                tfootHtml = `<tr><td colspan="${headers.length}" style="text-align: left; font-weight: bold; background-color: #f0f0f0;">TOTAL DATA: ${rows.length} Baris Data</td></tr>`;
            }

            // Tanggal Indonesia Dinamis
            const now = new Date();
            const dateOptions = { day: 'numeric', month: 'long', year: 'numeric' };
            const formattedDateIndo = now.toLocaleDateString('id-ID', dateOptions);
            const formattedTimeIndo = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';

            let html = `
                <!DOCTYPE html>
                <html lang="id">
                <head>
                    <meta charset="UTF-8">
                    <title>${reportTitle} - Elite Barber</title>
                    <style>
                        * { box-sizing: border-box; }
                        body {
                            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
                            margin: 0;
                            padding: 20px;
                            color: #111;
                            background-color: #fff;
                            font-size: 12px;
                        }
                        
                        /* Kop Surat Standard */
                        .kop-container {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding-bottom: 10px;
                        }
                        .kop-brand {
                            display: flex;
                            align-items: center;
                            gap: 12px;
                        }
                        .kop-logo {
                            width: 48px;
                            height: 48px;
                            background-color: #1a1a1a;
                            color: #f59e0b;
                            border-radius: 8px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 26px;
                            font-weight: bold;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                        .kop-title {
                            margin: 0;
                            font-size: 24px;
                            font-weight: 800;
                            letter-spacing: 1.5px;
                            color: #1a1a1a;
                            text-transform: uppercase;
                            line-height: 1.1;
                        }
                        .kop-sub {
                            margin: 3px 0 0 0;
                            font-size: 11px;
                            color: #555;
                            font-style: italic;
                            letter-spacing: 0.5px;
                        }
                        .kop-contact {
                            text-align: right;
                            font-size: 11px;
                            color: #333;
                            line-height: 1.4;
                        }
                        .kop-contact p { margin: 0; }
                        .kop-divider {
                            border-bottom: 3px double #000;
                            margin-top: 5px;
                            margin-bottom: 20px;
                        }

                        /* Judul Laporan */
                        .report-title-section {
                            text-align: center;
                            margin-bottom: 20px;
                        }
                        .report-main-title {
                            margin: 0;
                            font-size: 16px;
                            font-weight: 700;
                            text-transform: uppercase;
                            letter-spacing: 0.8px;
                            color: #111;
                        }
                        .report-sub-meta {
                            margin: 4px 0 0 0;
                            font-size: 11px;
                            color: #555;
                        }

                        /* Standarisasi Tabel */
                        table.report-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 10px;
                            font-size: 11px;
                        }
                        table.report-table th {
                            background-color: #1a1a1a !important;
                            color: #ffffff !important;
                            font-weight: 700;
                            padding: 9px 10px;
                            border: 1px solid #000;
                            text-transform: uppercase;
                            font-size: 10.5px;
                            letter-spacing: 0.5px;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                        table.report-table td {
                            padding: 7px 10px;
                            border: 1px solid #ccc;
                            color: #1a1a1a;
                            line-height: 1.35;
                        }
                        table.report-table tbody tr:nth-child(even) {
                            background-color: #f8f9fa !important;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                        table.report-table tfoot td {
                            padding: 8px 10px;
                            border-top: 2px solid #000;
                            border-bottom: 2px solid #000;
                            border-left: 1px solid #ccc;
                            border-right: 1px solid #ccc;
                            color: #000;
                            font-size: 11px;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }

                        /* Area Tanda Tangan */
                        .signature-wrapper {
                            margin-top: 35px;
                            display: flex;
                            justify-content: flex-end;
                            page-break-inside: avoid;
                        }
                        .signature-box {
                            width: 260px;
                            text-align: center;
                            font-size: 12px;
                            color: #111;
                        }
                        .sig-date { margin: 0 0 4px 0; }
                        .sig-role { margin: 0; font-weight: 700; }
                        .sig-space { height: 65px; }
                        .sig-name { margin: 0; font-weight: 700; }

                        /* CSS Print A4 Optimizations */
                        .report-wrapper {
                            margin-top: 65px;
                        }
                        @page {
                            size: A4 portrait;
                            margin: 12mm 15mm 15mm 15mm;
                        }
                        @media print {
                            body {
                                padding: 0;
                                margin: 0;
                                background: #fff;
                                color: #000;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            .report-wrapper {
                                margin-top: 0 !important;
                                padding-top: 0 !important;
                            }
                            .no-print, button, .tabulator-controls, .print-toolbar {
                                display: none !important;
                            }
                            table.report-table th, table.report-table tbody tr:nth-child(even), table.report-table tfoot td {
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            table.report-table {
                                page-break-inside: auto;
                            }
                            table.report-table tr {
                                page-break-inside: avoid;
                                page-break-after: auto;
                            }
                            .signature-wrapper {
                                page-break-inside: avoid;
                            }
                        }
                    </style>
                </head>
                <body>
                    <!-- Toolbar Kontrol Cetak (Hanya tampil di layar preview, tersembunyi saat dicetak) -->
                    <div class="print-toolbar no-print" style="position: fixed; top: 0; left: 0; right: 0; background: #18120b; color: #f59e0b; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f59e0b; font-family: sans-serif; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 99999;">
                        <div style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13px;">
                            <span>🖨️ Pratinjau Cetak Laporan A4 (${reportTitle})</span>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button onclick="window.print()" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #000; font-weight: bold; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-size: 12px; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                                🖨️ Cetak / Simpan PDF
                            </button>
                            <button onclick="window.close()" style="background: #3f3f46; color: #fff; border: 1px solid #52525b; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                ✕ Tutup
                            </button>
                        </div>
                    </div>

                    <!-- Pembungkus Dokumen Laporan Resmi -->
                    <div class="report-wrapper">
                        <!-- Kop Surat Resmi -->
                        <div class="kop-container">
                            <div class="kop-brand">
                                <div class="kop-logo">✂</div>
                                <div>
                                    <h1 class="kop-title">ELITE BARBER</h1>
                                    <p class="kop-sub">Executive Barbershop & Grooming Studio</p>
                                </div>
                            </div>
                            <div class="kop-contact">
                                <p><strong>Jl. Z.A. Pagar Alam No. 45, Kedaton</strong></p>
                                <p>Bandar Lampung, Lampung 35141</p>
                                <p>Telp/WA: 0812-3456-7890 | Email: info@elitebarber.com</p>
                            </div>
                        </div>
                        <div class="kop-divider"></div>

                        <!-- Judul Dokumen Laporan -->
                        <div class="report-title-section">
                            <h2 class="report-main-title">${reportTitle}</h2>
                            <p class="report-sub-meta">Dicetak pada: ${formattedDateIndo}, ${formattedTimeIndo} | Dokumen Resmi Elite Barber</p>
                        </div>

                        <!-- Data Tabel -->
                        <table class="report-table">
                            <thead>
                                <tr>
                                    ${headers.map((h, i) => `<th style="text-align: ${colAlignments[i]}; ${colAlignments[i] === 'center' && i === 0 ? 'width: 6%;' : ''}">${h}</th>`).join('')}
                                </tr>
                            </thead>
                            <tbody>
                                ${rows.map(r => `
                                    <tr>
                                        ${r.map((v, i) => `<td style="text-align: ${colAlignments[i]};">${v}</td>`).join('')}
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                ${tfootHtml}
                            </tfoot>
                        </table>

                        <!-- Area Tanda Tangan & Validasi -->
                        <div class="signature-wrapper">
                            <div class="signature-box">
                                <p class="sig-date">Bandar Lampung, ${formattedDateIndo}</p>
                                <p class="sig-role">Admin / Pemilik Elite Barber</p>
                                <div class="sig-space"></div>
                                <p class="sig-name">( .................................... )</p>
                            </div>
                        </div>
                    </div>

                    <script>
                        function triggerBrowserPrint() {
                            try {
                                window.focus();
                                window.print();
                            } catch(e) {
                                console.log("Print trigger error:", e);
                            }
                        }
                        if (document.readyState === 'complete') {
                            setTimeout(triggerBrowserPrint, 350);
                        } else {
                            window.addEventListener('load', function() {
                                setTimeout(triggerBrowserPrint, 350);
                            });
                        }
                    </' + '/script>
                </body>
                </html>
            `;
            let blob = new Blob([html], { type: 'text/html;charset=utf-8;' });
            let blobUrl = URL.createObjectURL(blob);
            let printWin = window.open(blobUrl, '_blank');
            if (printWin) {
                printWin.focus();
            }
        }
    }

    // Real-time Clock
    function updateClock() {
        const now = new Date();
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const clockString = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()} | ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
        const clockEl = document.getElementById('realtime-clock');
        if (clockEl) clockEl.textContent = clockString;
    }
    setInterval(updateClock, 1000); 
    updateClock();

    // Sidebar Toggle & Mobile Off-Canvas Drawer Logic
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
    const sidebar = document.getElementById('sidebar');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');

    function isMobileView() {
        return window.innerWidth < 768;
    }

    function openMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        if (sidebarBackdrop) sidebarBackdrop.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function toggleDesktopSidebar() {
        if (!sidebar) return;
        const willMinimize = sidebar.classList.contains('w-64');
        if (willMinimize) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
        } else {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
        }
        localStorage.setItem('sidebarMinimized', willMinimize);
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (isMobileView()) {
                if (sidebar.classList.contains('translate-x-0')) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            } else {
                toggleDesktopSidebar();
            }
        });
    }

    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', closeMobileSidebar);
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeMobileSidebar);
    }

    if (sidebar) {
        sidebar.querySelectorAll('nav a, .sidebar-footer a').forEach(link => {
            link.addEventListener('click', () => {
                if (isMobileView()) closeMobileSidebar();
            });
        });
    }

    window.addEventListener('resize', () => {
        if (!isMobileView()) {
            if (sidebarBackdrop) sidebarBackdrop.classList.add('hidden');
            document.body.style.overflow = '';
            sidebar.classList.remove('-translate-x-full');
            const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
            if (isMinimized) {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
            } else {
                sidebar.classList.remove('w-20');
                sidebar.classList.add('w-64');
            }
        } else {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            if (!sidebar.classList.contains('translate-x-0')) {
                sidebar.classList.add('-translate-x-full');
            }
        }
    });

    // Notifications
    let notifiedUserIds = [];

    function toggleNotifDropdown(e) {
        if (e) e.stopPropagation();
        const dropdown = document.getElementById('notif-dropdown-menu');
        if (dropdown) dropdown.classList.toggle('hidden');
    }

    document.addEventListener('click', function(e) {
        const container = document.getElementById('admin-notif-container');
        const dropdown = document.getElementById('notif-dropdown-menu');
        if (container && dropdown && !container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }

        const profileContainer = document.getElementById('user-profile-dropdown-container');
        if (profileContainer && !profileContainer.contains(e.target)) {
            closeProfileDropdown();
        }
    });

    function toggleProfileDropdown(e) {
        if (e) e.stopPropagation();
        const dropdown = document.getElementById('user-profile-dropdown-menu');
        const chevron = document.getElementById('profile-dropdown-chevron');
        if (dropdown) {
            const isHidden = dropdown.classList.contains('hidden');
            dropdown.classList.toggle('hidden');
            if (chevron) {
                chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        }
    }

    function closeProfileDropdown() {
        const dropdown = document.getElementById('user-profile-dropdown-menu');
        const chevron = document.getElementById('profile-dropdown-chevron');
        if (dropdown && !dropdown.classList.contains('hidden')) {
            dropdown.classList.add('hidden');
            if (chevron) chevron.style.transform = 'rotate(0deg)';
        }
    }

    function getNotifIcon(type) {
        if (type === 'add_layanan') return 'scissors';
        if (type === 'new_transaction') return 'banknote';
        return 'user-plus';
    }

    function fetchNotifications() {
        fetch('admin.php?action=get_unread_notif')
            .then(res => res.json())
            .then(data => {
                if (data && data.status) {
                    const count = data.unread_count || 0;
                    const badge = document.getElementById('notif-badge');
                    const listContainer = document.getElementById('notif-list-container');

                    if (badge) {
                        if (count > 0) {
                            badge.textContent = count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }

                    if (listContainer) {
                        if (!data.notifications || data.notifications.length === 0) {
                            listContainer.innerHTML = '<div class="p-4 text-center text-xs text-zinc-400">Tidak ada notifikasi baru</div>';
                        } else {
                            let html = '';
                            data.notifications.forEach(n => {
                                const iconName = getNotifIcon(n.type);
                                const safeLink = (n.link || '').replace(/'/g, "\\'");
                                html += `
                                    <div onclick="handleNotifClick(${n.id}, '${safeLink}')" class="p-3 hover:bg-amber-900/30 transition-colors cursor-pointer flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 mt-0.5 border border-amber-500/30">
                                            <i data-lucide="${iconName}" class="w-4 h-4"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-xs text-amber-200">${n.title || 'Notifikasi'}</p>
                                            <p class="text-xs text-zinc-300 truncate">${n.message}</p>
                                            <span class="text-[10px] text-zinc-400 mt-1 block">${n.created_at || ''}</span>
                                        </div>
                                    </div>
                                `;
                            });
                            listContainer.innerHTML = html;
                            if (window.lucide) lucide.createIcons();
                        }
                    }

                    const unnotifiedItems = (data.notifications || []).filter(n => !notifiedUserIds.includes(n.id));
                    if (unnotifiedItems.length > 0) {
                        unnotifiedItems.forEach(n => notifiedUserIds.push(n.id));
                        showConsolidatedNotificationModal(unnotifiedItems, data.notifications || []);
                    }
                }
            })
            .catch(err => console.error("Error fetching notifications:", err));
    }

    function handleNotifClick(notifId, targetLink) {
        const dropdown = document.getElementById('notif-dropdown-menu');
        if (dropdown) dropdown.classList.add('hidden');

        fetch(`admin.php?action=mark_notif_read&id=${notifId}`)
            .then(res => res.json())
            .then(() => {
                fetchNotifications();
                if (targetLink) {
                    if (targetLink.includes('#card-pendaftaran-baru')) {
                        scrollToRegistrationCard();
                    } else {
                        window.location.href = targetLink;
                    }
                } else {
                    scrollToRegistrationCard();
                }
            });
    }

    function markAllNotifRead() {
        fetch('admin.php?action=mark_notif_read')
            .then(res => res.json())
            .then(() => {
                fetchNotifications();
            });
    }

    function playNotifChime() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, ctx.currentTime);
            gain1.gain.setValueAtTime(0.3, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.4);

            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880, ctx.currentTime + 0.15);
            gain2.gain.setValueAtTime(0.4, ctx.currentTime + 0.15);
            gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(ctx.currentTime + 0.15);
            osc2.stop(ctx.currentTime + 0.6);
        } catch (e) {}
    }

    function showConsolidatedNotificationModal(newItems, allUnread) {
        playNotifChime();

        if (!window.Swal) return;

        const totalCount = allUnread.length > 0 ? allUnread.length : newItems.length;
        const isMultiple = totalCount > 1;

        let modalTitle = '🔔 NOTIFIKASI BARU!';
        if (isMultiple) {
            modalTitle = `🔔 ${totalCount} NOTIFIKASI BARU SISTEM!`;
        } else {
            const singleType = (newItems[0] || allUnread[0])?.type;
            if (singleType === 'add_layanan') {
                modalTitle = `✂️ LAYANAN BARU DITAMBAHKAN!`;
            } else if (singleType === 'new_transaction') {
                modalTitle = `💰 TRANSAKSI BARU DITERIMA!`;
            } else {
                modalTitle = `🔔 1 PELANGGAN BARU MENDAFTAR!`;
            }
        }

        let listHtml = '';
        if (isMultiple) {
            listHtml += `<div class="space-y-2 max-h-52 overflow-y-auto custom-scroll text-left p-1 my-3">`;
            allUnread.forEach(n => {
                const iconName = getNotifIcon(n.type);
                listHtml += `
                    <div class="p-3 bg-zinc-900/90 rounded-xl border border-amber-500/30 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/40 flex items-center justify-center font-bold text-xs shrink-0">
                                <i data-lucide="${iconName}" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-amber-200 text-xs truncate">${n.title || 'Notifikasi'}</p>
                                <p class="text-xs text-zinc-300 truncate">${n.message}</p>
                                <span class="text-[10px] text-zinc-400 block mt-0.5">${n.created_at || 'Baru saja'}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            listHtml += `</div>`;
        } else {
            const single = newItems[0] || allUnread[0];
            const iconName = getNotifIcon(single.type);
            listHtml = `
                <div class="p-4 bg-zinc-900/90 rounded-2xl border border-amber-500/40 my-3 text-left shadow-inner">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/40 flex items-center justify-center font-bold text-xl shrink-0 shadow-md">
                            <i data-lucide="${iconName}" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-amber-200 text-base mb-0.5">${single.title || 'Notifikasi Baru'}</p>
                            <p class="text-sm text-zinc-300 leading-snug">${single.message}</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-amber-400/80 mt-2.5 pt-2 border-t border-zinc-800 text-right font-mono">Waktu: ${single.created_at || 'Baru saja'}</p>
                </div>
            `;
        }

        const firstLink = (newItems[0] || allUnread[0])?.link || 'admin.php?page=akun#card-pendaftaran-baru';
        const confirmText = isMultiple ? `🚀 Lihat ${totalCount} Notifikasi` : `🚀 Lihat Detail`;

        Swal.fire({
            title: modalTitle,
            html: `
                ${listHtml}
                <p class="text-xs text-zinc-400 mt-2">Klik tombol di bawah untuk langsung menuju ke halaman detail terkait.</p>
            `,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Tutup Alert',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#3f3f46',
            background: '#18120b',
            color: '#fff',
            customClass: {
                popup: 'border-2 border-amber-500 shadow-[0_0_60px_rgba(245,158,11,0.6)] rounded-2xl p-6',
                title: 'text-amber-400 text-xl font-bold font-serif tracking-wide',
                confirmButton: 'px-5 py-3 rounded-xl font-bold shadow-lg shadow-amber-500/40 text-black hover:scale-105 transition-transform'
            },
            didOpen: () => {
                if (window.lucide) lucide.createIcons();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                markAllNotifRead();
                if (firstLink.includes('#card-pendaftaran-baru')) {
                    scrollToRegistrationCard();
                } else {
                    window.location.href = firstLink;
                }
            }
        });
    }

    function scrollToRegistrationCard() {
        const card = document.getElementById('card-pendaftaran-baru');
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.add('ring-4', 'ring-amber-500', 'shadow-[0_0_45px_rgba(245,158,11,0.8)]', 'scale-[1.02]');
            setTimeout(() => {
                card.classList.remove('ring-4', 'ring-amber-500', 'shadow-[0_0_45px_rgba(245,158,11,0.8)]', 'scale-[1.02]');
            }, 4500);
        } else {
            window.location.href = 'admin.php?page=akun#card-pendaftaran-baru';
        }
    }

    setInterval(fetchNotifications, 6000);
    document.addEventListener('DOMContentLoaded', () => {
        fetchNotifications();
        if (window.location.hash === '#card-pendaftaran-baru') {
            setTimeout(scrollToRegistrationCard, 500);
        }
    });
</script>

    </div>

    <!-- Mobile Fixed Admin Bottom Navigation Bar (Identical to Pelanggan & Barber System) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-[#0e0a08]/95 backdrop-blur-md border-t border-amber-500/20 flex justify-around items-center shadow-[0_-4px_25px_rgba(0,0,0,0.8)] transform-gpu"
         style="padding-bottom: env(safe-area-inset-bottom, 8px); padding-top: 8px;">

        <!-- Dashboard -->
        <a href="?page=dashboard" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= ($page === 'dashboard' || empty($page)) ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active Dashboard) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h2.25a3 3 0 013 3v2.25a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm9.75 0a3 3 0 013-3H18a3 3 0 013 3v2.25a3 3 0 01-3 3h-2.25a3 3 0 01-3-3V6zM3 15.75a3 3 0 013-3h2.25a3 3 0 013 3V18a3 3 0 01-3 3H6a3 3 0 01-3-3v-2.25zm9.75 0a3 3 0 013-3H18a3 3 0 013 3V18a3 3 0 01-3 3h-2.25a3 3 0 01-3-3v-2.25z" clip-rule="evenodd" />
            </svg>
            <!-- Outline (Inactive Dashboard) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            <span class="nav-label text-[10px] font-serif font-bold tracking-tight leading-none mt-0.5">Admin</span>
        </a>

        <!-- Antrean -->
        <a href="?page=antrean" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= $page === 'antrean' ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active Antrean) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path fill-rule="evenodd" d="M2.25 5.25a3 3 0 013-3h13.5a3 3 0 013 3v9a3 3 0 01-3 3h-4.99l1.244 2.185A.75.75 0 0115.848 21H8.152a.75.75 0 01-.652-1.122l1.244-2.185H3.75a3 3 0 01-3-3v-9zM4.5 7.5a.75.75 0 000 1.5h15a.75.75 0 000-1.5h-15z" clip-rule="evenodd" />
            </svg>
            <!-- Outline (Inactive Antrean) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-15-4.5h18a2.25 2.25 0 002.25-2.25V5.25A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25v8.25A2.25 2.25 0 005.25 15.75z" />
            </svg>
            <span class="nav-label text-[10px] font-serif font-bold tracking-tight leading-none mt-0.5">Antrean</span>
        </a>

        <!-- Layanan -->
        <a href="?page=layanan" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= $page === 'layanan' ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active Layanan) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M6 2a4 4 0 1 0 2.828 6.828l3.172 3.172-3.172 3.172A4 4 0 1 0 6 22a4 4 0 0 0 2.828-6.828L12 12l5.5-5.5a1 1 0 0 1 1.414 0l1.586 1.586a1 1 0 0 0 1.414-1.414L20.5 5.25a1 1 0 0 0-1.414 0L14 10.343l-2.828-2.828A4 4 0 0 0 6 2zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zm0 14a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/>
            </svg>
            <!-- Outline (Inactive Layanan) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="outline-icon w-6 h-6">
                <circle cx="6" cy="6" r="3"></circle>
                <circle cx="6" cy="18" r="3"></circle>
                <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
                <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
            </svg>
            <span class="nav-label text-[10px] font-serif font-bold tracking-tight leading-none mt-0.5">Layanan</span>
        </a>

        <!-- Transaksi -->
        <a href="?page=transaksi" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= $page === 'transaksi' ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active Transaksi) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75-6.75a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd"/>
            </svg>
            <!-- Outline (Inactive Transaksi) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <span class="nav-label text-[10px] font-serif font-bold tracking-tight leading-none mt-0.5">Transaksi</span>
        </a>

        <!-- Akun -->
        <a href="?page=akun" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= $page === 'akun' ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid (Active Akun) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 015.69 3.117c.428.622.1 1.486-.619 1.698a17.202 17.202 0 01-10.142 0c-.72-.212-1.047-1.076-.618-1.698z" clip-rule="evenodd" />
            </svg>
            <!-- Outline (Inactive Akun) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
            <span class="nav-label text-[10px] font-serif font-bold tracking-tight leading-none mt-0.5">Akun</span>
        </a>
    </nav>
</body>
</html>
