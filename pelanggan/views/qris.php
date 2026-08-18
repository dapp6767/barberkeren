<section id="tab-qris" class="tab-content <?= $is_qris ? 'active' : '' ?>">
<!-- SCAN QRIS MODULE -->
<div class="max-w-md mx-auto mt-4 pb-24">
    <div class="bg-[#1E1B18] rounded-2xl border border-amber-900/40 p-6 shadow-2xl relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-500/20 blur-3xl rounded-full"></div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-amber-500/20 blur-3xl rounded-full"></div>
        
        <div class="text-center relative z-10 mb-6">
            <h2 class="text-2xl font-bold text-white tracking-tight flex justify-center items-center gap-2">
                <i data-lucide="qr-code" class="w-6 h-6 text-amber-400"></i> Scan QRIS
            </h2>
            <p class="text-zinc-400 text-sm mt-1">Pembayaran lebih mudah dan cepat</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-inner relative z-10 mx-auto w-64 h-64 flex items-center justify-center mb-6">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=EliteBarberQRIS&color=3d2b1a" alt="QRIS Code" class="w-full h-full object-contain">
            <div class="absolute inset-0 border-4 border-amber-400/50 rounded-xl pointer-events-none"></div>
            <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-amber-500 rounded-tl-xl pointer-events-none"></div>
            <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-amber-500 rounded-tr-xl pointer-events-none"></div>
            <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-amber-500 rounded-bl-xl pointer-events-none"></div>
            <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-amber-500 rounded-br-xl pointer-events-none"></div>
        </div>

        <div class="text-center relative z-10 bg-zinc-900/50 rounded-xl p-4 border border-white/5">
            <p class="text-xs text-zinc-400 uppercase tracking-widest font-semibold mb-1">Merchant</p>
            <p class="text-lg text-amber-400 font-bold mb-3">ELITE BARBER</p>
            <p class="text-sm text-zinc-300">NMID: ID10203040506070809</p>
        </div>
    </div>
</div>
</section>
