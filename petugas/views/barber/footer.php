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

    // Profile Dropdown Toggle
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

    document.addEventListener('click', function(e) {
        const profileContainer = document.getElementById('user-profile-dropdown-container');
        if (profileContainer && !profileContainer.contains(e.target)) {
            closeProfileDropdown();
        }
    });

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

<!-- Mobile Fixed Bottom Navigation Bar — Identical UI/UX to Customer -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-[#0e0a08]/95 border-t border-amber-900/40 flex justify-around items-center shadow-2xl transform-gpu"
     style="padding-bottom: env(safe-area-inset-bottom, 8px); padding-top: 8px;">

    <!-- Panel -->
    <a href="barber.php?page=dashboard" class="nav-item flex flex-col items-center gap-0.5 py-1 px-2 min-w-[60px] rounded-xl transition-colors duration-200 relative <?= ($current_page === 'dashboard' || empty($current_page)) ? 'active' : '' ?>">
        <div class="nav-indicator"></div>
        <!-- Solid (Active) -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400 transform -rotate-45">
            <path d="M9.64 7.64c.23-.5.36-1.05.36-1.64 0-2.21-1.79-4-4-4S2 3.79 2 6s1.79 4 4 4c.59 0 1.14-.13 1.64-.36L10 12l-2.36 2.36C7.14 14.13 6.59 14 6 14c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4c0-.59-.13-1.14-.36-1.64L12 14l7 7h3v-1L9.64 7.64zm-3.64 12c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm0-10c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM19 3l-6 6 2 2 7-7V3h-3z"/>
        </svg>
        <!-- Outline (Inactive) -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="outline-icon w-6 h-6 transform -rotate-45">
            <circle cx="6" cy="6" r="3"></circle>
            <circle cx="6" cy="18" r="3"></circle>
            <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
            <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
            <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
        </svg>
        <?php if (isset($total_waiting) && $total_waiting > 0): ?>
            <span class="absolute -top-1 -right-1 bg-amber-500 text-amber-950 text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center shadow-md animate-pulse">
                <?= $total_waiting ?>
            </span>
        <?php endif; ?>
        <span class="active-pulse w-1.5 h-1.5 rounded-full bg-[#F59E0B] shadow-[0_0_6px_#F59E0B] absolute top-1 right-3"></span>
        <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Panel</span>
    </a>

    <!-- Statistik -->
    <a href="barber.php?page=charts" class="nav-item flex flex-col items-center gap-0.5 py-1 px-2 min-w-[60px] rounded-xl transition-colors duration-200 relative <?= ($current_page === 'charts') ? 'active' : '' ?>">
        <div class="nav-indicator"></div>
        <!-- Solid (Active) -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
            <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c-1.036 0-1.875.84-1.875 1.875v9.375c0 1.036.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V10.5c0-1.036-.84-1.875-1.875-1.875h-.75zM3 15c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v3c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 18v-3z"/>
        </svg>
        <!-- Outline (Inactive) -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
        </svg>
        <span class="active-pulse w-1.5 h-1.5 rounded-full bg-[#F59E0B] shadow-[0_0_6px_#F59E0B] absolute top-1 right-3"></span>
        <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Statistik</span>
    </a>

    <!-- Kursi -->
    <a href="javascript:void(0)" id="btn-nav-kursi" onclick="openSelectKursiModal()" class="nav-item flex flex-col items-center gap-0.5 py-1 px-2 min-w-[60px] rounded-xl transition-colors duration-200 relative <?= (isset($has_selected_chair_today) && !$has_selected_chair_today && ($current_page === 'dashboard' || empty($current_page))) ? 'active' : '' ?>">
        <div class="nav-indicator"></div>
        <!-- Solid (Active) -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
            <path d="M7 3a1 1 0 00-1 1v7a1 1 0 001 1h10a1 1 0 001-1V4a1 1 0 00-1-1H7z" />
            <path d="M4 12a2 2 0 00-2 2v2a2 2 0 002 2h16a2 2 0 002-2v-2a2 2 0 00-2-2H4z" />
            <path d="M6 18v3a1 1 0 102 0v-3H6zm10 0v3a1 1 0 102 0v-3h-2z" />
        </svg>
        <!-- Outline (Inactive) -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 4v7h10V4H7zM4 12h16v4H4v-4zM6 16v4M18 16v4" />
        </svg>
        <span class="active-pulse w-1.5 h-1.5 rounded-full bg-[#F59E0B] shadow-[0_0_6px_#F59E0B] absolute top-1 right-3"></span>
        <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">
            <span>Kursi</span>
            <?php if (isset($has_selected_chair_today) && $has_selected_chair_today && isset($barber['kursi'])): ?>
                <span class="font-extrabold text-amber-400 text-[9px] uppercase">(<?= str_replace('Kursi ', '', $barber['kursi']) ?>)</span>
            <?php endif; ?>
        </span>
    </a>

    <!-- Profil -->
    <?php
        $b_avatar_name = urlencode($user_data['fullname'] ?? $user_data['username'] ?? 'Barber');
        $b_profile_files = glob(__DIR__ . '/../../asset/image/profile_' . ($user_id ?? 0) . '.*');
        $b_profile_url = !empty($b_profile_files)
            ? '../asset/image/' . basename($b_profile_files[0]) . '?v=' . filemtime($b_profile_files[0])
            : "https://ui-avatars.com/api/?name={$b_avatar_name}&background=3d2b1a&color=F59E0B&size=64&bold=true";
    ?>
    <a href="barber.php?page=profil" class="nav-item flex flex-col items-center gap-0.5 py-1 px-2 min-w-[60px] rounded-xl transition-colors duration-200 relative <?= in_array($current_page ?? '', ['profil', 'profile']) ? 'active' : '' ?>">
        <div class="nav-indicator"></div>
        <img src="<?= $b_profile_url ?>" alt="Foto Profil" class="profile-img w-7 h-7 rounded-full object-cover border-2 transition-all">
        <span class="active-pulse w-1.5 h-1.5 rounded-full bg-[#F59E0B] shadow-[0_0_6px_#F59E0B] absolute top-0 right-3"></span>
        <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Profil</span>
    </a>
</nav>
</body>
</html>

