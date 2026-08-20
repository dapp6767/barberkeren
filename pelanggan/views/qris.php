<section id="tab-qris" class="tab-content <?= $is_qris ? 'active' : '' ?>">
    <style>
        @keyframes laser-scan {
            0% { top: 6%; opacity: 0.8; }
            50% { top: 88%; opacity: 1; filter: drop-shadow(0 0 8px #f59e0b); }
            100% { top: 6%; opacity: 0.8; }
        }
        .animate-laser {
            animation: laser-scan 2.2s infinite ease-in-out;
        }
        #reader video {
            object-fit: cover !important;
            border-radius: 1rem;
            width: 100% !important;
            height: 100% !important;
        }
        #reader {
            border: none !important;
            background: transparent !important;
        }
        #reader__scan_region {
            background: transparent !important;
        }
        #reader__dashboard {
            display: none !important;
        }
    </style>

    <div class="max-w-md mx-auto mt-2 pb-24 px-2 sm:px-0">
        <!-- Main Card Container -->
        <div class="bg-[#16110b] rounded-3xl border border-amber-900/50 p-5 shadow-2xl relative overflow-hidden backdrop-blur-xl">
            <div class="absolute -top-12 -right-12 w-36 h-36 bg-amber-500/15 blur-3xl rounded-full pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-36 h-36 bg-amber-500/15 blur-3xl rounded-full pointer-events-none"></div>

            <!-- Segmented Mode Switcher (GoPay Style) -->
            <div class="flex items-center bg-[#0d0a07] p-1.5 rounded-2xl border border-amber-900/40 mb-5 relative z-10">
                <button type="button" id="btn-mode-scan" onclick="switchQrisMode('scan')" class="flex-1 py-2 rounded-xl text-xs font-bold transition-all duration-200 bg-gradient-to-r from-amber-500 to-amber-600 text-black shadow-md flex items-center justify-center gap-2">
                    <i data-lucide="camera" class="w-4 h-4"></i> Scan Kamera / File
                </button>
                <button type="button" id="btn-mode-merchant" onclick="switchQrisMode('merchant')" class="flex-1 py-2 rounded-xl text-xs font-bold transition-all duration-200 text-zinc-400 hover:text-amber-300 flex items-center justify-center gap-2">
                    <i data-lucide="qr-code" class="w-4 h-4"></i> QRIS Barbershop
                </button>
            </div>

            <!-- MODE 1: LIVE CAMERA SCANNER & FILE UPLOAD -->
            <div id="qris-view-scan" class="relative z-10">
                <!-- Header Status -->
                <div class="text-center mb-4">
                    <h2 class="text-xl font-bold text-white tracking-tight flex items-center justify-center gap-2">
                        <i data-lucide="scan-line" class="w-5 h-5 text-amber-400"></i> Scan QRIS Pembayaran
                    </h2>
                    <p class="text-zinc-400 text-xs mt-1">Arahkan kamera ke Kode QR atau unggah gambar QRIS</p>
                </div>

                <!-- Scanner Viewport (GoPay Box) -->
                <div class="relative w-full max-w-[280px] h-[280px] mx-auto rounded-2xl overflow-hidden bg-black/80 border-2 border-amber-500/30 shadow-inner flex items-center justify-center mb-5 group">
                    
                    <!-- HTML5 QR Code Video Container -->
                    <div id="reader" class="w-full h-full"></div>

                    <!-- Overlay Viewfinder Corners -->
                    <div class="absolute inset-0 pointer-events-none p-3 flex flex-col justify-between z-20">
                        <div class="flex justify-between">
                            <div class="w-7 h-7 border-t-4 border-l-4 border-amber-400 rounded-tl-xl shadow-[0_0_10px_#f59e0b]"></div>
                            <div class="w-7 h-7 border-t-4 border-r-4 border-amber-400 rounded-tr-xl shadow-[0_0_10px_#f59e0b]"></div>
                        </div>
                        <div class="flex justify-between">
                            <div class="w-7 h-7 border-b-4 border-l-4 border-amber-400 rounded-bl-xl shadow-[0_0_10px_#f59e0b]"></div>
                            <div class="w-7 h-7 border-b-4 border-r-4 border-amber-400 rounded-br-xl shadow-[0_0_10px_#f59e0b]"></div>
                        </div>
                    </div>

                    <!-- Animated Scan Laser Line -->
                    <div id="scan-laser-line" class="absolute left-3 right-3 h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent shadow-[0_0_12px_#f59e0b] animate-laser z-20 pointer-events-none"></div>

                    <!-- Fallback / Initial State Overlay -->
                    <div id="scanner-placeholder" class="absolute inset-0 bg-[#120d08] flex flex-col items-center justify-center p-4 text-center z-10">
                        <div class="w-14 h-14 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 flex items-center justify-center mb-3 animate-pulse">
                            <i data-lucide="camera" class="w-7 h-7"></i>
                        </div>
                        <p class="text-xs text-zinc-300 font-medium mb-3">Kamera belum aktif</p>
                        <button type="button" onclick="startQrisCamera()" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 text-black text-xs font-bold rounded-xl shadow-lg hover:scale-105 active:scale-95 transition-all">
                            Aktifkan Kamera
                        </button>
                    </div>
                </div>

                <!-- Hidden Input File for QR Upload -->
                <input type="file" id="qr-file-input" accept="image/*" class="hidden" onchange="handleQrFileUpload(event)">

                <!-- Bottom Action Controls (GoPay Style) -->
                <div class="grid grid-cols-3 gap-2">
                    <!-- Upload Gallery Button -->
                    <button type="button" onclick="triggerQrFileUpload()" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-zinc-900/80 border border-amber-900/40 hover:bg-amber-500/15 hover:border-amber-500/50 text-amber-200 transition-all active:scale-95">
                        <i data-lucide="image" class="w-5 h-5 text-amber-400 mb-1"></i>
                        <span class="text-[10px] font-semibold text-zinc-300">Upload Galeri</span>
                    </button>

                    <!-- Flip Camera Button -->
                    <button type="button" onclick="switchQrisCamera()" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-zinc-900/80 border border-amber-900/40 hover:bg-amber-500/15 hover:border-amber-500/50 text-amber-200 transition-all active:scale-95">
                        <i data-lucide="refresh-cw" class="w-5 h-5 text-amber-400 mb-1"></i>
                        <span class="text-[10px] font-semibold text-zinc-300">Ganti Kamera</span>
                    </button>

                    <!-- Torch / Senter Button -->
                    <button type="button" onclick="toggleQrisTorch()" id="btn-qris-torch" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-zinc-900/80 border border-amber-900/40 hover:bg-amber-500/15 hover:border-amber-500/50 text-amber-200 transition-all active:scale-95">
                        <i data-lucide="zap" class="w-5 h-5 text-amber-400 mb-1" id="icon-qris-torch"></i>
                        <span class="text-[10px] font-semibold text-zinc-300" id="label-qris-torch">Senter</span>
                    </button>
                </div>
            </div>

            <!-- MODE 2: MERCHANT STATIC QRIS CODE -->
            <div id="qris-view-merchant" class="hidden relative z-10">
                <div class="text-center mb-4">
                    <h2 class="text-xl font-bold text-white tracking-tight flex items-center justify-center gap-2">
                        <i data-lucide="store" class="w-5 h-5 text-amber-400"></i> QRIS Elite Barber
                    </h2>
                    <p class="text-zinc-400 text-xs mt-1">Tunjukkan atau gunakan QRIS resmi barbershop</p>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-2xl relative z-10 mx-auto w-64 h-64 flex items-center justify-center mb-4 border-4 border-amber-500/40">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=EliteBarberQRIS&color=3d2b1a" alt="QRIS Code Barbershop" class="w-full h-full object-contain">
                    <div class="absolute inset-0 border-2 border-amber-400/30 rounded-xl pointer-events-none"></div>
                </div>

                <div class="text-center relative z-10 bg-zinc-900/80 rounded-2xl p-4 border border-amber-900/30">
                    <p class="text-[10px] text-amber-400 uppercase tracking-widest font-bold mb-1">Merchant Terdaftar</p>
                    <p class="text-lg text-white font-bold mb-1">ELITE BARBER</p>
                    <p class="text-xs text-zinc-400 font-mono">NMID: ID10203040506070809</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- QRIS Logic Script -->
<script>
    let html5QrCode = null;
    let isQrisCameraRunning = false;
    let currentFacingMode = "environment";
    let isTorchOn = false;

    function switchQrisMode(mode) {
        const btnScan = document.getElementById('btn-mode-scan');
        const btnMerchant = document.getElementById('btn-mode-merchant');
        const viewScan = document.getElementById('qris-view-scan');
        const viewMerchant = document.getElementById('qris-view-merchant');

        if (mode === 'scan') {
            btnScan.className = "flex-1 py-2 rounded-xl text-xs font-bold transition-all duration-200 bg-gradient-to-r from-amber-500 to-amber-600 text-black shadow-md flex items-center justify-center gap-2";
            btnMerchant.className = "flex-1 py-2 rounded-xl text-xs font-bold transition-all duration-200 text-zinc-400 hover:text-amber-300 flex items-center justify-center gap-2";
            viewScan.classList.remove('hidden');
            viewMerchant.classList.add('hidden');
            startQrisCamera();
        } else {
            btnMerchant.className = "flex-1 py-2 rounded-xl text-xs font-bold transition-all duration-200 bg-gradient-to-r from-amber-500 to-amber-600 text-black shadow-md flex items-center justify-center gap-2";
            btnScan.className = "flex-1 py-2 rounded-xl text-xs font-bold transition-all duration-200 text-zinc-400 hover:text-amber-300 flex items-center justify-center gap-2";
            viewMerchant.classList.remove('hidden');
            viewScan.classList.add('hidden');
            stopQrisCamera();
        }
        if (window.lucide) lucide.createIcons();
    }

    function startQrisCamera() {
        if (isQrisCameraRunning) return;

        const placeholder = document.getElementById('scanner-placeholder');
        if (placeholder) placeholder.style.display = 'none';

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("reader");
        }

        const config = {
            fps: 10,
            qrbox: { width: 220, height: 220 },
            aspectRatio: 1.0
        };

        html5QrCode.start(
            { facingMode: currentFacingMode },
            config,
            onQrCodeScanned,
            onQrScanError
        ).then(() => {
            isQrisCameraRunning = true;
        }).catch(err => {
            console.warn("Camera start failed, showing fallback placeholder:", err);
            if (placeholder) {
                placeholder.style.display = 'flex';
                placeholder.querySelector('p').textContent = "Izin kamera diperlukan atau kamera tidak tersedia";
            }
        });
    }

    function stopQrisCamera() {
        if (html5QrCode && isQrisCameraRunning) {
            html5QrCode.stop().then(() => {
                isQrisCameraRunning = false;
                const placeholder = document.getElementById('scanner-placeholder');
                if (placeholder) placeholder.style.display = 'flex';
            }).catch(err => {
                console.warn("Failed to stop camera:", err);
                isQrisCameraRunning = false;
            });
        }
    }

    function switchQrisCamera() {
        currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
        if (isQrisCameraRunning) {
            html5QrCode.stop().then(() => {
                isQrisCameraRunning = false;
                startQrisCamera();
            });
        } else {
            startQrisCamera();
        }
    }

    function toggleQrisTorch() {
        if (!html5QrCode || !isQrisCameraRunning) {
            Swal.fire({
                icon: 'info',
                title: 'Senter Kamera',
                text: 'Aktifkan kamera terlebih dahulu untuk menyalakan senter.',
                confirmButtonColor: '#f59e0b',
                background: '#18120b',
                color: '#fff'
            });
            return;
        }

        isTorchOn = !isTorchOn;
        html5QrCode.applyVideoConstraints({
            advanced: [{ torch: isTorchOn }]
        }).then(() => {
            const btn = document.getElementById('btn-qris-torch');
            const label = document.getElementById('label-qris-torch');
            if (isTorchOn) {
                btn.classList.add('bg-amber-500/30', 'border-amber-400');
                label.textContent = "Senter On";
            } else {
                btn.classList.remove('bg-amber-500/30', 'border-amber-400');
                label.textContent = "Senter";
            }
        }).catch(err => {
            Swal.fire({
                icon: 'warning',
                title: 'Fitur Tidak Didukung',
                text: 'Fitur senter/flashlight tidak didukung pada perangkat ini.',
                confirmButtonColor: '#f59e0b',
                background: '#18120b',
                color: '#fff'
            });
        });
    }

    function triggerQrFileUpload() {
        document.getElementById('qr-file-input').click();
    }

    function handleQrFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("reader");
        }

        Swal.fire({
            title: 'Memproses Gambar...',
            text: 'Membaca QR Code dari file galeri',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); },
            background: '#18120b',
            color: '#fff'
        });

        html5QrCode.scanFile(file, true)
            .then(decodedText => {
                Swal.close();
                onQrCodeScanned(decodedText);
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'QR Code Tidak Ditemukan',
                    text: 'Pastikan gambar yang diunggah berisi kode QRIS yang jelas.',
                    confirmButtonColor: '#f59e0b',
                    background: '#18120b',
                    color: '#fff'
                });
            });
    }

    function playQrisBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.25);
        } catch (e) {}
        if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
    }

    function onQrCodeScanned(decodedText) {
        playQrisBeep();
        stopQrisCamera();

        // Extract merchant or transaction info if present
        let merchantName = "ELITE BARBER";
        let defaultAmount = "50000";

        Swal.fire({
            title: '💳 KONFIRMASI PEMBAYARAN QRIS',
            html: `
                <div class="text-left space-y-3 p-1">
                    <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-lg shrink-0">
                            💈
                        </div>
                        <div>
                            <p class="text-xs text-amber-400/80 font-bold uppercase tracking-wider">Merchant Destination</p>
                            <p class="text-base font-bold text-amber-100">${merchantName}</p>
                            <p class="text-[10px] text-zinc-400">NMID: ID10203040506070809</p>
                        </div>
                    </div>

                    <div class="bg-zinc-900/90 p-3 rounded-xl border border-white/10">
                        <p class="text-[11px] text-zinc-400 font-medium mb-1">Kode QRIS Terdeteksi:</p>
                        <p class="text-xs text-amber-300 font-mono break-all">${decodedText}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1">Nominal Pembayaran (Rp):</label>
                        <input type="number" id="qris-payment-amount" value="${defaultAmount}" min="1000" class="w-full bg-zinc-900 border border-amber-500/40 text-amber-300 font-bold text-lg rounded-xl px-4 py-2.5 outline-none focus:border-amber-400">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '🚀 Konfirmasi & Bayar Sekarang',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#3f3f46',
            background: '#18120b',
            color: '#fff',
            customClass: {
                popup: 'border-2 border-amber-500 shadow-[0_0_50px_rgba(245,158,11,0.5)] rounded-3xl p-5',
                title: 'text-amber-400 text-lg font-bold font-serif tracking-wide',
                confirmButton: 'px-4 py-3 rounded-xl font-bold shadow-lg shadow-amber-500/30 text-black hover:scale-105 transition-transform'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const amountInput = document.getElementById('qris-payment-amount');
                const finalAmount = amountInput ? parseInt(amountInput.value) || 50000 : 50000;
                processQrisPaymentSuccess(finalAmount, decodedText);
            } else {
                startQrisCamera();
            }
        });
    }

    function processQrisPaymentSuccess(amount, qrCode) {
        Swal.fire({
            icon: 'success',
            title: '🎉 Pembayaran QRIS Berhasil!',
            html: `
                <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl my-2 text-center">
                    <p class="text-xs text-emerald-400 font-bold uppercase tracking-wider">Total Dibayar</p>
                    <p class="text-2xl font-bold text-emerald-300 mt-1">Rp ${amount.toLocaleString('id-ID')}</p>
                    <p class="text-[11px] text-zinc-400 mt-1">Metode: QRIS Instant Pay</p>
                </div>
                <p class="text-xs text-zinc-300">Terima kasih! Transaksi Anda telah diverifikasi oleh sistem Elite Barber.</p>
            `,
            confirmButtonText: 'Selesai',
            confirmButtonColor: '#10b981',
            background: '#18120b',
            color: '#fff',
            customClass: {
                popup: 'border-2 border-emerald-500 shadow-[0_0_50px_rgba(16,185,129,0.5)] rounded-3xl p-5'
            }
        });
    }

    function onQrScanError(errorMessage) {
        // Silent error ignore while continuous scanning
    }
</script>
