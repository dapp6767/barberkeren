        </main>
    </div>

    <!-- Mobile Fixed Bottom Navigation Bar -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-[#0e0a08]/95 backdrop-blur-md border-t border-amber-500/20 flex justify-around items-center shadow-[0_-4px_25px_rgba(0,0,0,0.8)] transform-gpu"
         style="padding-bottom: env(safe-area-inset-bottom, 8px); padding-top: 8px;">

        <!-- Beranda -->
        <a href="javascript:void(0)" onclick="switchTab('tab-dashboard', this)" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= $is_dashboard ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z"/>
                <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Beranda</span>
        </a>

        <!-- Layanan -->
        <a href="javascript:void(0)" onclick="switchTab('tab-layanan', this)" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= $is_layanan ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <!-- Solid Scissors -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M6 2a4 4 0 1 0 2.828 6.828l3.172 3.172-3.172 3.172A4 4 0 1 0 6 22a4 4 0 0 0 2.828-6.828L12 12l5.5-5.5a1 1 0 0 1 1.414 0l1.586 1.586a1 1 0 0 0 1.414-1.414L20.5 5.25a1 1 0 0 0-1.414 0L14 10.343l-2.828-2.828A4 4 0 0 0 6 2zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zm0 14a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/>
            </svg>
            <!-- Outline Scissors -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="outline-icon w-6 h-6">
                <circle cx="6" cy="6" r="3"></circle>
                <circle cx="6" cy="18" r="3"></circle>
                <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
                <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
            </svg>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Layanan</span>
        </a>

        <!-- Scan QRIS -->
        <a href="javascript:void(0)" onclick="switchTab('tab-qris', this)" class="nav-item <?= $is_qris ? 'active' : '' ?> relative -top-5 flex flex-col items-center group">
            <div class="flex items-center justify-center w-14 h-14 bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 rounded-full shadow-[0_4px_20px_rgba(245,158,11,0.5)] border-4 border-[#140f09] transition-transform duration-200 active:scale-95 group-hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="w-7 h-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                </svg>
            </div>
            <span class="nav-label absolute -bottom-5 text-[10px] font-bold text-amber-400 whitespace-nowrap">Scan QRIS</span>
        </a>

        <!-- Riwayat -->
        <a href="javascript:void(0)" onclick="switchTab('tab-riwayat', this)" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= $is_riwayat ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                <path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75-6.75a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd"/>
                <path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="outline-icon w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Riwayat</span>
        </a>

        <!-- Profil -->
        <a href="javascript:void(0)" onclick="switchTab('tab-profil', this)" class="nav-item flex flex-col items-center gap-0.5 py-1 px-3 min-w-[64px] rounded-xl transition-all duration-200 relative group <?= $is_profil ? 'active' : '' ?>">
            <div class="nav-indicator"></div>
            <?php
                $bn_has_pic = false;
                $bn_profile_files = glob(__DIR__ . '/../../asset/image/profile_' . ($my_user_id ?? 0) . '.*');
                if (!empty($bn_profile_files)) {
                    $bn_has_pic = true;
                    $bn_profile_url = '../asset/image/' . basename($bn_profile_files[0]) . '?v=' . filemtime($bn_profile_files[0]);
                }
            ?>
            <?php if ($bn_has_pic): ?>
                <img src="<?= $bn_profile_url ?>" alt="Foto Profil" class="profile-img w-6 h-6 rounded-full object-cover border-2 transition-all">
            <?php else: ?>
                <!-- Solid Profile (Active) -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="solid-icon w-6 h-6 text-amber-400">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm-7 18a7 7 0 0 1 14 0 1 1 0 0 1-1 1H6a1 1 0 0 1-1-1z" />
                </svg>
                <!-- Outline Profile (Inactive) -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="outline-icon w-6 h-6">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            <?php endif; ?>
            <span class="nav-label text-[10px] font-semibold tracking-tight leading-none mt-0.5">Profil</span>
        </a>
    </nav>

    <script>
        lucide.createIcons();

        // Real-time Clock
        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            const dayName = days[now.getDay()];
            const day = String(now.getDate()).padStart(2, '0');
            const month = months[now.getMonth()];
            const year = now.getFullYear();
            
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            const clockString = `${dayName}, ${day} ${month} ${year} | ${hours}:${minutes}:${seconds}`;
            const clockEl = document.getElementById('realtime-clock');
            if (clockEl) {
                clockEl.textContent = clockString;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Sidebar Toggle with Persistence
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');

        function applySidebarState(isMinimized) {
            const fouc = document.getElementById('fouc-style');
            if (fouc) fouc.remove();
            
            if (isMinimized) {
                sidebar.classList.remove('w-64'); 
                sidebar.classList.add('w-20');
            } else {
                sidebar.classList.remove('w-20'); 
                sidebar.classList.add('w-64');
            }
        }

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                if (window.innerWidth < 768) {
                    sidebar.classList.toggle('open-mobile');
                } else {
                    const willMinimize = sidebar.classList.contains('w-64');
                    localStorage.setItem('sidebarMinimized', willMinimize);
                    applySidebarState(willMinimize);
                }
            });

            document.addEventListener('click', (e) => {
                if (window.innerWidth < 768 && sidebar.classList.contains('open-mobile') && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('open-mobile');
                }
                const profileContainer = document.getElementById('user-profile-dropdown-container');
                if (profileContainer && !profileContainer.contains(e.target)) {
                    closeProfileDropdown();
                }
            });
        }

        // Profile Dropdown Toggle Helper
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

        // SPA Navigation Helper
        function switchTab(targetTabId, navElement) {
            const currentTab = document.querySelector('.tab-content.active');
            const targetTab = document.getElementById(targetTabId);

            if (currentTab && currentTab.id === targetTabId) return;

            updateNavState(navElement);
            executeDOMSwitch(currentTab, targetTab);
        }

        function executeDOMSwitch(currentTab, targetTab) {
            if (currentTab) currentTab.classList.remove('active');
            if (targetTab) {
                targetTab.classList.add('active');
                const mainArea = document.querySelector('main');
                if(mainArea) mainArea.scrollTop = 0;
            }

            if (targetTab && targetTab.id === 'tab-qris') {
                if (typeof startQrisCamera === 'function') startQrisCamera();
            } else {
                if (typeof stopQrisCamera === 'function') stopQrisCamera();
            }
        }

        function updateNavState(activeNav) {
            if (!activeNav) return;
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            activeNav.classList.add('active');

            const onclickAttr = activeNav.getAttribute('onclick');
            const match = onclickAttr ? onclickAttr.match(/switchTab\('([^']+)'/) || onclickAttr.match(/navigateToTab\('([^']+)'/) : null;
            if (match && match[1]) {
                const targetTabId = match[1];
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    item.classList.remove('bg-adminlte-primary', 'text-amber-200');
                    item.classList.add('text-stone-400');
                });
                const sidebarLink = document.querySelector(`.sidebar-item[onclick*="${targetTabId}"]`);
                if (sidebarLink) {
                    sidebarLink.classList.remove('text-stone-400');
                    sidebarLink.classList.add('bg-adminlte-primary', 'text-amber-200');
                }
            }
        }
        
        window.navigateToTab = function(tabId) {
            const navLink = document.querySelector(`.nav-item[onclick*="${tabId}"]`);
            if (navLink) switchTab(tabId, navLink);
        };

        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.setAttribute('data-lucide', 'eye-off');
                } else {
                    input.type = 'password';
                    icon.setAttribute('data-lucide', 'eye');
                }
                if (window.lucide) lucide.createIcons();
            }
        }

        const profPassInput = document.getElementById('new_pass_input');
        if (profPassInput) {
            profPassInput.addEventListener('input', function() {
                const val = this.value;
                const rLen = document.getElementById('prof_rule_len');
                const rCase = document.getElementById('prof_rule_case');
                const rNum = document.getElementById('prof_rule_num');
                const rSym = document.getElementById('prof_rule_sym');

                if (rLen) rLen.className = val.length >= 6 ? 'flex items-center gap-1.5 text-emerald-400 font-medium' : 'flex items-center gap-1.5 text-zinc-400';
                if (rCase) rCase.className = (/[A-Z]/.test(val) && /[a-z]/.test(val)) ? 'flex items-center gap-1.5 text-emerald-400 font-medium' : 'flex items-center gap-1.5 text-zinc-400';
                if (rNum) rNum.className = /[0-9]/.test(val) ? 'flex items-center gap-1.5 text-emerald-400 font-medium' : 'flex items-center gap-1.5 text-zinc-400';
                if (rSym) rSym.className = /[\W_]/.test(val) ? 'flex items-center gap-1.5 text-emerald-400 font-medium' : 'flex items-center gap-1.5 text-zinc-400';
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) {
                lucide.createIcons();
            }
            const activeTab = document.querySelector('.tab-content.active');
            if (activeTab && activeTab.id === 'tab-qris') {
                if (typeof startQrisCamera === 'function') startQrisCamera();
            }
        });

        // DataTables Init
        $(document).ready(function() {
            if (window.lucide) lucide.createIcons();

            const commonDataTableLang = {
                search: "Cari Data:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Belum ada data",
                infoFiltered: "(disaring dari _MAX_ total data)",
                zeroRecords: "Tidak ada data yang sesuai",
                paginate: {
                    first: "Awal",
                    last: "Akhir",
                    next: "❯",
                    previous: "❮"
                }
            };

            const commonDom = '<"dataTables_header"f>rt<"dataTables_footer"i<"dataTables_footer_right"lp>>';

            if ($('#riwayatTable').length) {
                $('#riwayatTable').DataTable({
                    dom: commonDom,
                    language: Object.assign({}, commonDataTableLang, {
                        search: "Cari Riwayat:",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ riwayat",
                        infoEmpty: "Belum ada data riwayat",
                        infoFiltered: "(disaring dari _MAX_ total riwayat)",
                        zeroRecords: "Tidak ada riwayat yang sesuai"
                    }),
                    pageLength: 10,
                    order: [[3, 'desc']],
                    responsive: true
                });
            }

            if ($('#activeQueueTable').length && $('#activeQueueTable tbody tr').length > 0 && !$('#activeQueueTable tbody tr td[colspan]').length) {
                $('#activeQueueTable').DataTable({
                    dom: commonDom,
                    language: Object.assign({}, commonDataTableLang, {
                        search: "Cari Antrean:",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ antrean",
                        infoEmpty: "Belum ada antrean aktif",
                        infoFiltered: "(disaring dari _MAX_ total antrean)",
                        zeroRecords: "Tidak ada antrean yang sesuai"
                    }),
                    pageLength: 10,
                    order: [],
                    responsive: true
                });
            }
        });
    </script>
</body>
</html>
