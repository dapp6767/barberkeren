<?php if ($page === 'pengaturan'): ?>
<!-- PENGATURAN WA MODULE -->
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-white tracking-tight">Pengaturan WhatsApp Gateway</h2>
        <p class="text-zinc-400 text-sm mt-1">Konfigurasi API Key dari Fonnte untuk notifikasi otomatis</p>
    </div>
    
    <div class="bg-zinc-900/50 backdrop-blur-md border border-zinc-700/50 rounded-xl p-6 shadow-2xl">
        <form action="admin.php" method="POST">
            <input type="hidden" name="form_type" value="save_wa_config">
            <input type="hidden" name="current_page" value="pengaturan">
            
            <div class="mb-5">
                <label class="block text-sm font-medium text-zinc-400 mb-2">API Key Fonnte</label>
                <input type="text" name="wa_api_key" value="<?= htmlspecialchars($wa_key) ?>" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all" placeholder="Masukkan token Fonnte Anda di sini..." required>
                <p class="text-xs text-zinc-500 mt-2">Dapatkan API Key ini dengan mendaftar di <a href="https://fonnte.com" target="_blank" class="text-blue-400 hover:underline">fonnte.com</a></p>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-adminlte-primary hover:bg-blue-600 text-white font-medium px-6 py-2.5 rounded-lg transition-colors flex items-center gap-2 shadow-lg shadow-blue-500/20">
                    <i data-lucide="save" class="w-4 h-4"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
