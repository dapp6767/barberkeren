<!-- Modal Deskripsi Layanan -->
<div id="descModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden justify-center items-center z-[9999] opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900/90 border border-zinc-700/50 text-zinc-200 w-[90vw] max-w-[600px] rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.5)] transform scale-95 transition-all duration-300 relative overflow-hidden" id="descModalContent">
        <!-- Glow effect -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>
        
        <!-- Image Cover -->
        <div class="relative w-full h-72 bg-zinc-800">
            <img id="descModalImg" src="" alt="Layanan" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-900/95 via-zinc-900/40 to-transparent"></div>
            <button onclick="closeDescModal()" class="absolute top-4 right-4 text-zinc-300 hover:text-white bg-black/50 hover:bg-black/70 p-2.5 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-600 backdrop-blur-sm z-20">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <div class="relative z-10 px-8 pb-8 pt-0 -mt-12">
            <div class="flex flex-col gap-3 mb-6">
                <h3 class="text-3xl font-bold text-white leading-tight drop-shadow-lg" id="descModalTitle">Nama Layanan</h3>
                <div class="flex items-center gap-2 mt-1">
                    <div class="flex items-center gap-2 text-blue-400 bg-blue-500/10 border border-blue-500/20 w-fit px-4 py-1.5 rounded-full text-sm font-semibold uppercase tracking-wider backdrop-blur-sm">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        <span id="descModalDurasi">0 Menit</span>
                    </div>
                    <div class="flex items-center gap-2 text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 w-fit px-4 py-1.5 rounded-full text-sm font-semibold uppercase tracking-wider backdrop-blur-sm">
                        <i data-lucide="banknote" class="w-4 h-4"></i>
                        <span id="descModalHarga">Rp 0</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-black/40 border border-zinc-800/80 rounded-xl p-6 mb-2 shadow-inner">
                <p id="descModalText" class="text-zinc-300 text-base leading-relaxed whitespace-pre-wrap"></p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Layanan -->
<div id="addLayananModal" class="fixed inset-0 bg-black/70 hidden justify-center items-center z-[9999] p-4">
    <div class="bg-adminlte-card border border-zinc-700 text-zinc-200 p-4 sm:p-6 w-[92vw] max-w-[420px] max-h-[90vh] overflow-y-auto custom-scroll rounded-lg shadow-2xl">
        <div class="flex justify-between items-center mb-4 border-b border-zinc-700 pb-3">
            <h3 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="plus-circle" class="w-5 h-5 text-adminlte-primary"></i> Tambah Layanan</h3>
            <button onclick="closeAddLayananModal()" class="text-zinc-400 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form action="admin.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="form_type" value="add_layanan">
            <input type="hidden" name="current_page" value="layanan">
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Nama Layanan</label>
                <input type="text" name="nama_layanan" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="Paket VIP" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Harga (Rp)</label>
                <input type="number" name="harga" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="50000" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Durasi (Menit)</label>
                <input type="number" name="durasi" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="30" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Deskripsi (Opsional)</label>
                <textarea name="deskripsi" rows="3" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="Fitur layanan (pisahkan dengan Enter)"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Gambar Layanan</label>
                <input type="file" name="gambar" accept="image/*" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-adminlte-primary file:text-white hover:file:bg-blue-600">
            </div>
            <div class="flex items-center gap-2 pt-1 bg-amber-500/10 border border-amber-500/20 p-2.5 rounded-md">
                <input type="checkbox" name="is_terbaik" id="add_is_terbaik" value="1" class="w-4 h-4 rounded border-zinc-700 bg-zinc-900 text-amber-500 focus:ring-amber-500 focus:ring-offset-zinc-900">
                <label for="add_is_terbaik" class="text-xs font-semibold text-amber-400 cursor-pointer select-none">Tandai sebagai Layanan TERBAIK (Badge "TERBAIK")</label>
            </div>
            <div class="pt-2">
                <button type="submit" class="w-full bg-adminlte-primary hover:bg-blue-700 text-white font-medium py-2.5 rounded-md transition-colors flex justify-center items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Simpan Layanan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Layanan -->
<div id="editLayananModal" class="fixed inset-0 bg-black/70 hidden justify-center items-center z-[9999] p-4">
    <div class="bg-adminlte-card border border-zinc-700 text-zinc-200 p-4 sm:p-6 w-[92vw] max-w-[420px] max-h-[90vh] overflow-y-auto custom-scroll rounded-lg shadow-2xl">
        <div class="flex justify-between items-center mb-4 border-b border-zinc-700 pb-3">
            <h3 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="edit" class="w-5 h-5 text-blue-400"></i> Edit Layanan</h3>
            <button onclick="closeEditLayananModal()" class="text-zinc-400 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form action="admin.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="form_type" value="edit_layanan">
            <input type="hidden" name="current_page" value="layanan">
            <input type="hidden" name="id" id="edit_layanan_id">
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Nama Layanan</label>
                <input type="text" name="nama_layanan" id="edit_layanan_nama" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Harga (Rp)</label>
                <input type="number" name="harga" id="edit_layanan_harga" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Durasi (Menit)</label>
                <input type="number" name="durasi" id="edit_layanan_durasi" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Deskripsi (Opsional)</label>
                <textarea name="deskripsi" id="edit_layanan_deskripsi" rows="3" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="Fitur layanan (pisahkan dengan Enter)"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Update Gambar Layanan (Biarkan kosong jika tidak diubah)</label>
                <input type="file" name="gambar" accept="image/*" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-500 file:text-white hover:file:bg-blue-600">
            </div>
            <div class="flex items-center gap-2 pt-1 bg-amber-500/10 border border-amber-500/20 p-2.5 rounded-md">
                <input type="checkbox" name="is_terbaik" id="edit_is_terbaik" value="1" class="w-4 h-4 rounded border-zinc-700 bg-zinc-900 text-amber-500 focus:ring-amber-500 focus:ring-offset-zinc-900">
                <label for="edit_is_terbaik" class="text-xs font-semibold text-amber-400 cursor-pointer select-none">Tandai sebagai Layanan TERBAIK (Badge "TERBAIK")</label>
            </div>
            <div class="pt-2">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-md transition-colors flex justify-center items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Update Layanan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Akun -->
<div id="addUserModal" class="fixed inset-0 bg-black/70 hidden justify-center items-center z-[9999] p-4">
    <div class="bg-adminlte-card border border-zinc-700 text-zinc-200 p-4 sm:p-6 w-[92vw] max-w-[420px] max-h-[90vh] overflow-y-auto custom-scroll rounded-lg shadow-2xl">
        <div class="flex justify-between items-center mb-4 border-b border-zinc-700 pb-3">
            <h3 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="user-plus" class="w-5 h-5 text-adminlte-primary"></i> Tambah User Baru</h3>
            <button onclick="closeAddUserModal()" class="text-zinc-400 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form action="admin.php" method="POST" class="space-y-4">
            <input type="hidden" name="form_type" value="add_user">
            <input type="hidden" name="current_page" value="akun">
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Nama Lengkap</label>
                <input type="text" name="fullname" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="Contoh: Marco Rossi" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Username</label>
                <input type="text" name="username" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="marcorossi" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Email</label>
                <input type="email" name="email" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="nama@email.com" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">No. HP / WhatsApp</label>
                <input type="text" name="phone" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="081234567890" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Password</label>
                <input type="password" name="password" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="••••••••" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Role</label>
                <select name="role" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                    <option value="admin">Admin</option>
                    <option value="barber">Barber</option>
                    <option value="pelanggan">Pelanggan</option>
                </select>
            </div>
            <div class="pt-2">
                <button type="submit" class="w-full bg-adminlte-primary hover:bg-blue-700 text-white font-medium py-2.5 rounded-md transition-colors flex justify-center items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Simpan User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Akun -->
<div id="editUserModal" class="fixed inset-0 bg-black/70 hidden justify-center items-center z-[9999] p-4">
    <div class="bg-adminlte-card border border-zinc-700 text-zinc-200 p-4 sm:p-6 w-[92vw] max-w-[420px] max-h-[90vh] overflow-y-auto custom-scroll rounded-lg shadow-2xl">
        <div class="flex justify-between items-center mb-4 border-b border-zinc-700 pb-3">
            <h3 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="edit" class="w-5 h-5 text-blue-400"></i> Edit User</h3>
            <button onclick="closeEditUserModal()" class="text-zinc-400 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form action="admin.php" method="POST" class="space-y-4">
            <input type="hidden" name="form_type" value="edit_user">
            <input type="hidden" name="current_page" value="akun">
            <input type="hidden" name="id_user" id="edit_user_id">
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Nama Lengkap</label>
                <input type="text" name="fullname" id="edit_user_fullname" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Username</label>
                <input type="text" name="username" id="edit_user_username" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Email</label>
                <input type="email" name="email" id="edit_user_email" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">No. HP / WhatsApp</label>
                <input type="text" name="phone" id="edit_user_phone" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Role</label>
                <select name="role" id="edit_user_role" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                    <option value="admin">Admin</option>
                    <option value="barber">Barber</option>
                    <option value="pelanggan">Pelanggan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-400 mb-1">Password Baru (Biarkan kosong jika tidak diganti)</label>
                <input type="password" name="password" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="••••••••">
            </div>
            <div class="pt-2">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-md transition-colors flex justify-center items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Update User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cetak Struk (Untuk Panel Barber) -->
<div id="receiptModal" class="fixed inset-0 bg-black/70 hidden justify-center items-center z-[9999]">
    <div class="bg-white text-black p-5 w-[300px] font-mono rounded-lg text-[13px]" id="printable-receipt">
        <span class="block text-center font-bold text-base mb-1">ELITE BARBERSHOP</span>
        <span class="block text-center text-\\[11px\\] mt-0">Jl. Nawawi Gelar Dalom, Sumberjo, Rajabasa Jaya, Bandarlampung</span>
        <span class="block text-center text-\\[11px\\] mt-0">Telp: 0857-8894-2309</span>
        <hr class="border-t border-dashed border-black my-2.5">
        <p class="my-1 flex justify-between"><span>No. Tiket</span> <span id="r_tiket"></span></p>
        <p class="my-1 flex justify-between"><span>Pelanggan</span> <span id="r_nama"></span></p>
        <p class="my-1 flex justify-between"><span>Layanan</span> <span id="r_layanan"></span></p>
        <hr class="border-t border-dashed border-black my-2.5">
        <p class="my-1 flex justify-between"><span>TOTAL</span> <span>Rp <span id="r_total"></span></span></p>
        <p class="my-1 flex justify-between"><span>PEMBAYARAN</span> <span id="r_metode"></span></p>
        <p class="my-1 flex justify-between"><span>STATUS</span> <span>LUNAS</span></p>
        <hr class="border-t border-dashed border-black my-2.5">
        <span class="block text-center text-\\[11px\\] mt-2.5">Terima kasih atas kunjungan Anda!</span>
        <span class="block text-center text-\\[11px\\] mt-0">IG: @</span>
        
        <button onclick="window.print()" class="no-print bg-adminlte-primary text-white w-full py-2 mt-4 rounded-md text-sm hover:bg-blue-600 transition-colors">🖨️ Cetak</button>
        <button onclick="closeStruk()" class="no-print bg-zinc-600 text-white w-full py-2 mt-2 rounded-md text-sm hover:bg-zinc-700 transition-colors">Tutup</button>
        <form id="form_confirm_paid" method="POST" style="display:none;" class="no-print mt-2">
            <input type="hidden" name="form_type" value="confirm_paid">
            <input type="hidden" name="current_page" value="barber">
            <input type="hidden" name="antrian_id" id="r_antrian_id" value="">
            <input type="hidden" name="total_harga" id="r_total_input" value="">
            <button type="submit" id="btn_confirm_paid" class="bg-adminlte-success text-white w-full py-2 rounded-md text-sm hover:bg-green-700 transition-colors">Konfirmasi Selesai</button>
        </form>
    </div>
</div>

<style>
    @media print {
        @page { margin: 0; } /* Menghilangkan header & footer bawaan browser saat print */
        body * { visibility: hidden; }
        #printable-receipt, #printable-receipt * { visibility: visible; }
        #printable-receipt { 
            position: absolute; left: 0; top: 0; 
            width: 100% !important; height: 100% !important; 
            margin: 0; padding: 40px !important; 
            font-size: 24px !important; box-sizing: border-box; 
            transform: none !important;
        }
        #printable-receipt span.text-base { font-size: 48px !important; margin-bottom: 20px !important; display: block; }
        #printable-receipt span.text-\\[11px\\] { font-size: 24px !important; margin-bottom: 10px !important; display: block; }
        #printable-receipt hr { margin: 30px 0 !important; border-top: 2px dashed #000 !important; }
        #printable-receipt p { margin: 20px 0 !important; font-size: 24px !important; }
        #printable-receipt .no-print { display: none !important; }
    }
</style>
