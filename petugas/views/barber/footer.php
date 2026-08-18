<!-- Initialize Lucide Icons & Barber Scripts -->
<script>
    lucide.createIcons();

    function openSelectKursiModal() {
        const modal = document.getElementById('selectKursiModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeSelectKursiModal() {
        const modal = document.getElementById('selectKursiModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

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

    function switchBarberTab(tabId, pageName, el) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        const target = document.getElementById(tabId);
        if (target) target.classList.add('active');

        if (window.history && window.history.pushState) {
            window.history.pushState(null, '', 'barber.php?page=' + pageName);
        }

        if (el) {
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.classList.remove('bg-adminlte-primary', 'text-amber-200', 'text-white');
                item.classList.add('text-stone-400');
            });
            el.classList.add('bg-adminlte-primary', 'text-amber-200');
            el.classList.remove('text-stone-400');
        }
    }

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
    }
    
    function closeStruk() {
        document.getElementById('receiptModal').style.display = 'none';
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

    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');

    function applySidebarState(isMinimized) {
        if (isMinimized) {
            sidebar.classList.remove('w-64'); 
            sidebar.classList.add('w-20');
        } else {
            sidebar.classList.remove('w-20'); 
            sidebar.classList.add('w-64');
        }
    }

    if (sidebarToggle && sidebar) {
        const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
        sidebarToggle.addEventListener('click', () => {
            const willMinimize = sidebar.classList.contains('w-64');
            localStorage.setItem('sidebarMinimized', willMinimize);
            applySidebarState(willMinimize);
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Line Chart Performance Barber
        if (document.getElementById('barberChart1')) {
            const labels = <?php echo json_encode($barberLabels ?? []); ?>;
            const dataVals = <?php echo json_encode($barberDataVals ?? []); ?>;
            const peakIndex = <?php echo $barberPeakIndex ?? 0; ?>;
            const averageVal = <?php echo $barberAverage ?? 0; ?>;

            const ctx = document.getElementById('barberChart1').getContext('2d');

            const pointBgColors = dataVals.map((val, idx) => idx === peakIndex ? '#f59e0b' : '#3d2b1a');
            const pointBorderColors = dataVals.map((val, idx) => idx === peakIndex ? '#ffffff' : '#c9a03a');
            const pointRadiusVals = dataVals.map((val, idx) => idx === peakIndex ? 8 : 4);
            const pointHoverRadiusVals = dataVals.map((val, idx) => idx === peakIndex ? 12 : 7);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Jumlah Pelanggan',
                            data: dataVals,
                            borderColor: '#c9a03a',
                            borderWidth: 3.5,
                            tension: 0.35,
                            fill: true,
                            backgroundColor: function(context) {
                                const chart = context.chart;
                                const {ctx, chartArea} = chart;
                                if (!chartArea) return null;
                                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                                gradient.addColorStop(0, 'rgba(201, 160, 58, 0.45)');
                                gradient.addColorStop(0.5, 'rgba(201, 160, 58, 0.15)');
                                gradient.addColorStop(1, 'rgba(201, 160, 58, 0.0)');
                                return gradient;
                            },
                            pointBackgroundColor: pointBgColors,
                            pointBorderColor: pointBorderColors,
                            pointBorderWidth: 2,
                            pointRadius: pointRadiusVals,
                            pointHoverRadius: pointHoverRadiusVals,
                        },
                        {
                            label: 'Rata-rata 30 Hari (' + averageVal + ')',
                            data: Array(labels.length).fill(averageVal),
                            borderColor: 'rgba(244, 63, 94, 0.7)',
                            borderWidth: 2,
                            borderDash: [6, 6],
                            fill: false,
                            pointRadius: 0,
                            pointHoverRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: { color: 'rgba(255, 255, 255, 0.04)' },
                            ticks: { color: '#e8d5a3', font: { family: 'sans-serif', size: 11 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255, 255, 255, 0.08)' },
                            ticks: { color: '#e8d5a3', font: { family: 'sans-serif', size: 11 }, precision: 0 }
                        }
                    },
                    plugins: {
                        legend: { position: 'top', labels: { color: '#e8d5a3', font: { family: 'sans-serif', size: 12 } } },
                        tooltip: { backgroundColor: 'rgba(10, 8, 5, 0.95)', titleColor: '#e8d5a3', bodyColor: '#fff', borderColor: '#8a6030', borderWidth: 1, padding: 12 }
                    }
                }
            });

            setTimeout(() => {
                const scrollContainer = document.getElementById('barberChartScrollContainer');
                if (scrollContainer) scrollContainer.scrollLeft = scrollContainer.scrollWidth;
            }, 300);
        }

        // Pie Chart Services Barber
        if (document.getElementById('barberPieChart')) {
            const pieLabels = <?php echo json_encode($pieLabels ?? []); ?>;
            const pieCounts = <?php echo json_encode($pieCounts ?? []); ?>;

            const pieCtx = document.getElementById('barberPieChart').getContext('2d');
            const pieColors = ['#f59e0b', '#38bdf8', '#10b981', '#a855f7', '#ec4899', '#e11d48'];

            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: pieLabels.length ? pieLabels : ['Kosong'],
                    datasets: [{
                        data: pieCounts.length ? pieCounts : [1],
                        backgroundColor: pieCounts.length ? pieColors.slice(0, pieLabels.length) : ['#30363d'],
                        borderWidth: 2,
                        borderColor: '#1a1208',
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#e8d5a3', padding: 14, font: { family: 'sans-serif', size: 11 } } },
                        tooltip: { backgroundColor: 'rgba(10, 8, 5, 0.95)', titleColor: '#e8d5a3', bodyColor: '#fff', borderColor: '#8a6030', borderWidth: 1, padding: 12 }
                    }
                }
            });
        }
    });
</script>
</body>
</html>
