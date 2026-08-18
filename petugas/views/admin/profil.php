<?php if ($page === 'profil'): ?>
<!-- PROFIL MODULE -->
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Profil Saya</h2>
            <p class="text-zinc-400 text-sm mt-1">Kelola informasi pribadi dan keamanan akun Anda</p>
        </div>
    </div>

    <form action="admin.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <input type="hidden" name="form_type" value="update_profil">
        <input type="hidden" name="current_page" value="profil">
        <!-- Left Column: Avatar & Summary -->
        <div class="col-span-1">
            <div class="bg-zinc-900/50 backdrop-blur-md border border-zinc-700/50 rounded-xl p-6 shadow-2xl flex flex-col items-center text-center relative overflow-hidden">
                <!-- Background Decoration -->
                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-adminlte-primary/20 to-purple-600/20 z-0"></div>
                
                <div class="relative z-10 w-28 h-28 rounded-full border-4 border-zinc-800 shadow-xl mt-4 mb-4 overflow-hidden bg-zinc-800 group">
                    <?php 
                    $avatar_name = !empty($current_user['fullname']) ? urlencode($current_user['fullname']) : urlencode($current_user['username']);
                    $profile_files = glob(__DIR__ . '/../../asset/image/profile_' . $_SESSION['user_id'] . '.*');
                    $profile_url = !empty($profile_files) ? '../asset/image/' . basename($profile_files[0]) : "https://ui-avatars.com/api/?name={$avatar_name}&background=random&color=fff&size=128&bold=true";
                    ?>
                    <img src="<?= $profile_url ?>" alt="Avatar" class="w-full h-full object-cover">
                    <label for="foto_profil_input" class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white text-xs font-semibold backdrop-blur-sm">
                        <i data-lucide="camera" class="w-6 h-6 mb-1 text-zinc-300"></i>
                        Ubah Foto
                    </label>
                    <input type="file" name="foto_profil" id="foto_profil_input" class="hidden" accept="image/*" onchange="document.getElementById('profile_save_btn').click();">
                </div>
                
                <h3 class="relative z-10 text-xl font-bold text-white mb-1"><?= !empty($current_user['fullname']) ? htmlspecialchars($current_user['fullname']) : htmlspecialchars($current_user['username']) ?></h3>
                <span class="relative z-10 bg-adminlte-primary/20 text-blue-400 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider mb-4 border border-blue-500/20">
                    <?= htmlspecialchars($current_user['role']) ?>
                </span>
                
                <div class="relative z-10 w-full text-left space-y-3 mt-4 border-t border-zinc-700/50 pt-4">
                    <div class="flex items-center gap-3 text-sm text-zinc-400">
                        <i data-lucide="mail" class="w-4 h-4 text-zinc-500"></i>
                        <span class="truncate"><?= !empty($current_user['email']) ? htmlspecialchars($current_user['email']) : '<em class="text-zinc-600">Belum diatur</em>' ?></span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-zinc-400">
                        <i data-lucide="phone" class="w-4 h-4 text-zinc-500"></i>
                        <span><?= !empty($current_user['phone']) ? htmlspecialchars($current_user['phone']) : '<em class="text-zinc-600">Belum diatur</em>' ?></span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-zinc-400">
                        <i data-lucide="user" class="w-4 h-4 text-zinc-500"></i>
                        <span>@<?= htmlspecialchars($current_user['username']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Edit Form -->
        <div class="col-span-1 md:col-span-2">
            <div class="bg-zinc-900/50 backdrop-blur-md border border-zinc-700/50 rounded-xl shadow-2xl overflow-hidden">
                <div class="border-b border-zinc-700/50 px-6 py-4 bg-zinc-800/30 flex items-center gap-3">
                    <i data-lucide="settings-2" class="w-5 h-5 text-adminlte-primary"></i>
                    <h3 class="text-lg font-semibold text-white">Pengaturan Akun</h3>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-2">Nama Lengkap</label>
                            <input type="text" name="fullname" value="<?= htmlspecialchars($current_user['fullname'] ?? '') ?>" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all" placeholder="Nama Lengkap">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-2">Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($current_user['username']) ?>" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-2">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($current_user['email'] ?? '') ?>" class="w-full bg-[#0e0a08] border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all" placeholder="email@contoh.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-2">No. WhatsApp</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all" placeholder="08123456789">
                        </div>
                    </div>

                    <div class="border-t border-zinc-700/50 pt-6 mb-6">
                        <h4 class="text-sm font-medium text-white mb-4 flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-4 h-4 text-adminlte-primary"></i> Keamanan Akun & Ubah Password
                        </h4>
                        
                        <div class="space-y-4 max-w-xl">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Password Lama Saat Ini</label>
                                <div class="relative">
                                    <input type="password" id="admin_old_pass" name="old_password" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all text-sm" placeholder="Masukkan password lama Anda">
                                    <button type="button" onclick="togglePass('admin_old_pass', 'a_eye_old')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                        <i data-lucide="eye" id="a_eye_old" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <p class="text-[11px] text-zinc-500 mt-1">* Wajib diisi untuk memverifikasi bahwa Anda adalah pemilik sah akun ini.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Password Baru</label>
                                    <div class="relative">
                                        <input type="password" id="admin_new_pass" name="new_password" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all text-sm" placeholder="Min. 6-8 karakter">
                                        <button type="button" onclick="togglePass('admin_new_pass', 'a_eye_new')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                            <i data-lucide="eye" id="a_eye_new" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Konfirmasi Password Baru</label>
                                    <div class="relative">
                                        <input type="password" id="admin_conf_pass" name="confirm_password" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all text-sm" placeholder="Ulangi password baru">
                                        <button type="button" onclick="togglePass('admin_conf_pass', 'a_eye_conf')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                            <i data-lucide="eye" id="a_eye_conf" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" id="profile_save_btn" class="bg-adminlte-primary hover:bg-blue-600 text-white font-medium px-6 py-2.5 rounded-lg transition-colors flex items-center gap-2 shadow-lg shadow-blue-500/20">
                            <i data-lucide="save" class="w-4 h-4"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>
