<?php if ($page === 'akun'): ?>
<!-- AKUN PENGGUNA MODULE -->
<?php
// Fetch Users with Real-Time Online Status & Last Active Timestamp for Akun Module Card
$userActivityStmt = $pdo->query("
    SELECT 
        u.id_user, 
        u.username, 
        u.fullname, 
        u.role,
        COALESCE(u.is_online, 0) as is_online,
        COALESCE(NULLIF(u.fullname, ''), u.username) as nama,
        COALESCE(
            u.last_active, 
            (SELECT MAX(waktu_dibuat) FROM antrian WHERE pelanggan_id = u.id_user)
        ) as last_seen
    FROM users u
    ORDER BY (u.is_online = 1 AND u.last_active >= NOW() - INTERVAL 15 MINUTE) DESC, last_seen DESC
    LIMIT 50
");
$userActivityList = $userActivityStmt ? $userActivityStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<div class="mb-6 space-y-6">
    <!-- Grid Donut Chart & Card Status Keaktifan Akun -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left: Card Pendaftaran Akun Baru (Notifikasi Registrasi) -->
        <?php
        // Fetch Recent Customer Registrations safely
        $recentCustomers = [];
        $newTodayCount = 0;
        $newWeekCount = 0;
        try {
            $recentCustomersStmt = $pdo->query("
                SELECT id_user, username, fullname, email, phone, created_at 
                FROM users 
                WHERE role = 'pelanggan' 
                ORDER BY id_user DESC 
                LIMIT 10
            ");
            $recentCustomers = $recentCustomersStmt ? $recentCustomersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $countTodayStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'pelanggan' AND DATE(created_at) = CURDATE()");
            $newTodayCount = $countTodayStmt ? (int)$countTodayStmt->fetchColumn() : 0;

            $countWeekStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'pelanggan' AND created_at >= NOW() - INTERVAL 7 DAY");
            $newWeekCount = $countWeekStmt ? (int)$countWeekStmt->fetchColumn() : 0;
        } catch (Exception $e) {
            // Fallback if created_at column is missing or newly added
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                $recentCustomersStmt = $pdo->query("SELECT id_user, username, fullname, email, phone, created_at FROM users WHERE role = 'pelanggan' ORDER BY id_user DESC LIMIT 10");
                $recentCustomers = $recentCustomersStmt ? $recentCustomersStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                $newWeekCount = count($recentCustomers);
            } catch (Exception $ex) {
                $recentCustomers = [];
            }
        }
        ?>
        <div id="card-pendaftaran-baru" class="p-6 rounded-2xl border shadow-md flex flex-col justify-between transition-all duration-500" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
            <div>
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-amber-900/30">
                    <div>
                        <h3 class="text-lg font-bold tracking-wide flex items-center gap-2" style="color:#e8d5a3;">
                            <i data-lucide="user-plus" class="w-5 h-5 text-amber-400"></i>
                            Pendaftaran Akun Baru
                        </h3>
                        <p class="text-xs text-stone-400 mt-0.5">Daftar pelanggan yang baru mendaftar di web</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/30 flex items-center gap-1.5 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span> Terbaru
                    </span>
                </div>

                <!-- Summary Badges -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-zinc-900/80 border border-amber-900/40 p-3 rounded-xl flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                            <i data-lucide="user-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <span class="text-[11px] text-zinc-400 block uppercase font-medium">Hari Ini</span>
                            <span class="text-lg font-bold text-white"><?= $newTodayCount ?> <span class="text-xs font-normal text-amber-300/80">Baru</span></span>
                        </div>
                    </div>
                    <div class="bg-zinc-900/80 border border-amber-900/40 p-3 rounded-xl flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400 shrink-0">
                            <i data-lucide="users" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <span class="text-[11px] text-zinc-400 block uppercase font-medium">7 Hari Terakhir</span>
                            <span class="text-lg font-bold text-white"><?= $newWeekCount ?> <span class="text-xs font-normal text-blue-300/80">Akun</span></span>
                        </div>
                    </div>
                </div>

                <!-- Feed List -->
                <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1 custom-scroll">
                    <?php if (empty($recentCustomers)): ?>
                        <div class="text-center text-stone-400 py-8 text-sm">Belum ada pendaftaran akun pelanggan baru</div>
                    <?php else: ?>
                        <?php foreach ($recentCustomers as $rc): 
                            $rcName = !empty($rc['fullname']) ? htmlspecialchars($rc['fullname']) : htmlspecialchars($rc['username']);
                            $rcInitial = strtoupper(substr($rcName, 0, 1));
                            $rcTime = !empty($rc['created_at']) ? date('d M Y H:i', strtotime($rc['created_at'])) : 'Baru saja';

                            // Check for uploaded profile photo
                            $userPhotoFiles = glob(__DIR__ . '/../../asset/image/profile_' . $rc['id_user'] . '.*');
                            $hasPhoto = !empty($userPhotoFiles);
                            $photoUrl = $hasPhoto ? '../asset/image/' . basename($userPhotoFiles[0]) . '?v=' . filemtime($userPhotoFiles[0]) : null;
                        ?>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-900/50 hover:bg-amber-900/20 border border-white/5 hover:border-amber-900/40 transition-all">
                            <div class="flex items-center gap-3">
                                <?php if ($hasPhoto): ?>
                                    <img src="<?= $photoUrl ?>" alt="<?= $rcName ?>" class="w-10 h-10 rounded-full object-cover ring-2 ring-amber-500/50 shadow-md shrink-0">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-700 to-amber-900 border border-amber-500/40 flex items-center justify-center text-amber-100 font-bold text-sm shadow-md shrink-0">
                                        <?= $rcInitial ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-bold text-sm text-stone-100 flex items-center gap-1.5">
                                        <?= $rcName ?>
                                        <span class="text-xs font-normal text-amber-400/80">(@<?= htmlspecialchars($rc['username']) ?>)</span>
                                    </h4>
                                    <div class="flex items-center gap-2 mt-0.5 text-xs text-stone-400">
                                        <span class="truncate max-w-[140px]" title="<?= htmlspecialchars($rc['email'] ?? '') ?>"><?= !empty($rc['email']) ? htmlspecialchars($rc['email']) : '-' ?></span>
                                        <span>•</span>
                                        <span class="text-amber-200/80"><?= $rcTime ?></span>
                                    </div>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">
                                Pelanggan
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Card Status Keaktifan Akun (Gambar Style) -->
        <div class="p-6 rounded-2xl border shadow-md flex flex-col justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
            <div>
                <div class="flex justify-between items-center mb-5 pb-3 border-b border-amber-900/30">
                    <div>
                        <h3 class="text-xl font-bold tracking-wide flex items-center gap-2" style="color:#e8d5a3;">
                            <i data-lucide="users" class="w-5 h-5 text-emerald-400"></i>
                            Status Keaktifan Akun
                        </h3>
                        <p class="text-xs text-stone-400 mt-0.5">Status online real-time & waktu terakhir aktif</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Status Aktif
                    </span>
                </div>

                <div class="space-y-3.5 max-h-[350px] overflow-y-auto pr-2 custom-scroll">
                    <?php if (empty($userActivityList)): ?>
                        <div class="text-center text-stone-400 py-8">Belum ada aktivitas akun</div>
                    <?php else: ?>
                        <?php 
                        foreach ($userActivityList as $uAct): 
                            $last_time = $uAct['last_seen'] ? strtotime($uAct['last_seen']) : 0;
                            // Account is ONLINE only if is_online == 1 AND active within last 15 mins
                            $is_currently_online = ((int)$uAct['is_online'] === 1) && ($last_time > 0) && (time() - $last_time <= 900);
                            $formatted_date = $last_time > 0 ? date('d M Y H:i', $last_time) : 'Belum ada';
                            
                            $userPhotoPath = "../asset/image/profile_" . $uAct['id_user'] . ".jpg";
                            $hasRealPhoto = file_exists(__DIR__ . '/../../' . $userPhotoPath);
                            $initial = strtoupper(substr($uAct['nama'], 0, 1));
                        ?>
                        <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-amber-900/20 transition-colors border border-transparent hover:border-amber-900/30">
                            <div class="flex items-center gap-3.5">
                                <!-- Profile Avatar Circle with GREEN DOT at corner for ONLINE ONLY -->
                                <div class="relative shrink-0">
                                    <?php if ($hasRealPhoto): ?>
                                        <img src="<?= $userPhotoPath ?>" alt="<?= htmlspecialchars($uAct['nama']) ?>" class="w-11 h-11 rounded-full object-cover ring-2 ring-amber-500/40 shadow-md">
                                    <?php else: ?>
                                        <div class="w-11 h-11 rounded-full bg-gradient-to-br from-amber-800 to-stone-900 border border-amber-600/40 flex items-center justify-center text-amber-200 font-bold text-base shadow-md">
                                            <?= $initial ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_currently_online): ?>
                                        <!-- GREEN DOT AT CORNER OF PROFILE FOR REALTIME ONLINE -->
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-[#120e06] ring-2 ring-emerald-400/50 shadow-md" title="Online Sekarang"></span>
                                    <?php else: ?>
                                        <!-- GRAY DOT FOR OFFLINE / LOGGED OUT -->
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-zinc-600 rounded-full border-2 border-[#120e06]" title="Offline"></span>
                                    <?php endif; ?>
                                </div>

                                <!-- User Info & Status Text -->
                                <div>
                                    <h4 class="font-bold text-sm text-stone-100 flex items-center gap-1.5">
                                        <?= htmlspecialchars($uAct['nama']) ?>
                                        <span class="text-xs font-normal text-amber-400/80">(@<?= htmlspecialchars($uAct['username']) ?>)</span>
                                    </h4>
                                    <div class="flex items-center gap-2 mt-0.5 text-xs text-stone-400">
                                        <span class="capitalize px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-300 text-[10px]"><?= htmlspecialchars($uAct['role']) ?></span>
                                        <span>•</span>
                                        <?php if ($is_currently_online): ?>
                                            <span class="text-emerald-400 font-medium flex items-center gap-1">
                                                Online Sekarang
                                            </span>
                                        <?php else: ?>
                                            <span class="text-zinc-400">
                                                Terakhir online: <?= $formatted_date ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Side Status Badge -->
                            <div>
                                <?php if ($is_currently_online): ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Online
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs text-zinc-400 bg-zinc-800/80 border border-zinc-700">
                                        Offline
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Users -->
    <div class="bg-[#18120b] rounded-lg border border-white/10 shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-white/10 bg-[#22180f]">
            <h3 class="font-semibold text-amber-100">Daftar Akun Pengguna</h3>
        </div>
        <div class="tabulator-wrapper">
            <div class="tabulator-controls">
                <div class="flex gap-2">
                    <button type="button" class="tabulator-btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #0e0a08; border-color: #f59e0b; font-weight: 700;" onclick="openAddUserModal()">
                        <i data-lucide="user-plus" class="w-4 h-4"></i> Tambah Akun
                    </button>
                    <button class="tabulator-btn" onclick="exportData('table-users', 'csv')"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> CSV</button><button class="tabulator-btn" onclick="exportData('table-users', 'xlsx')"><i data-lucide="table" class="w-4 h-4"></i> Excel</button><button class="tabulator-btn" onclick="exportData('table-users', 'pdf')"><i data-lucide="file-text" class="w-4 h-4"></i> PDF</button><button class="tabulator-btn" onclick="exportData('table-users', 'print')"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                </div>
                <input type="text" class="tabulator-search" id="search-users" placeholder="Filter rows...">
            </div>
            <table id="table-users" class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-zinc-900/60 text-zinc-300 text-sm border-b border-white/10">
                        <th class="px-4 py-3.5 font-semibold text-center" tabulator-field="no" width="70" tabulator-formatter="rownum">No.</th>
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="fullname">Nama Lengkap</th>
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="username">Username</th>
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="email">Email</th>
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="phone">No. WA</th>
                        <th class="px-6 py-3.5 font-semibold" tabulator-field="role" tabulator-formatter="html">Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-amber-500/10 transition-colors">
                        <td class="px-4 py-4 text-zinc-400 text-center font-medium"></td>
                        <td class="px-6 py-4 text-white font-medium"><?= !empty($u['fullname']) ? htmlspecialchars($u['fullname']) : '-' ?></td>
                        <td class="px-6 py-4 text-amber-200/90 font-mono font-medium"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="px-6 py-4 text-zinc-300"><?= !empty($u['email']) ? htmlspecialchars($u['email']) : '-' ?></td>
                        <td class="px-6 py-4 text-zinc-300"><?= !empty($u['phone']) ? htmlspecialchars($u['phone']) : '-' ?></td>
                        <td class="px-6 py-4">
                            <div class="flex justify-between items-center w-full min-w-[150px]">
                                <span class="text-zinc-300 capitalize font-medium"><?= htmlspecialchars($u['role']) ?></span>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="openEditUserModal(<?= $u['id_user'] ?>, '<?= htmlspecialchars($u['fullname'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($u['phone'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>')" class="text-amber-400 hover:text-amber-300 p-1.5 rounded hover:bg-amber-400/10 transition-colors" title="Edit">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="form_type" value="delete_user">
                                        <input type="hidden" name="current_page" value="akun">
                                        <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                        <input type="hidden" name="id" value="<?= $u['id_user'] ?>">
                                        <button type="submit" class="text-rose-400 hover:text-rose-300 p-1.5 rounded hover:bg-rose-400/10 transition-colors" onclick="return confirm('Hapus user ini dari database?')" title="Hapus">
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
<?php endif; ?>
