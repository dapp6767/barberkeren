<?php
require_once __DIR__ . '/../functions/helper.php';
require_once __DIR__ . '/../functions/auth_functions.php';

$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (empty($token)) {
    set_flash('danger', 'Token reset tidak ditemukan!');
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        set_flash('danger', 'Konfirmasi password tidak cocok!');
    } else {
        $result = reset_password_with_token($token, $new_password);
        if ($result['status']) {
            set_flash('success', $result['message']);
            redirect('login.php');
        } else {
            set_flash('danger', $result['message']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Elite Barber</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans:  ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        gold: { DEFAULT: '#d4af37', light: '#e8c84a' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .auth-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fafafa;
            font-size: 0.9rem;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .auth-input:focus {
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.15);
        }
        .auth-input::placeholder { color: #71717a; }
        .alert-success { background: rgba(212,175,55,0.1); border: 1px solid rgba(212,175,55,0.3); color: #d4af37; padding: 12px 16px; border-radius: 10px; font-size: 0.875rem; margin-bottom: 16px; }
        .alert-danger  { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.3);  color: #f87171; padding: 12px 16px; border-radius: 10px; font-size: 0.875rem; margin-bottom: 16px; }
    </style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center px-4 relative overflow-hidden bg-black text-zinc-200">

    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-black via-[#0a0a0a] to-[#3e2723] pointer-events-none"></div>
    <div class="fixed top-0 right-0 w-96 h-96 -z-10 pointer-events-none"
         style="background: radial-gradient(circle at top right, rgba(212,175,55,0.1) 0%, transparent 60%);"></div>

    <div class="w-full max-w-md relative">

        <div class="text-center mb-8">
            <a href="../index.php" class="inline-flex flex-col items-center gap-2">
                <span class="w-14 h-14 rounded-full bg-gold/10 border border-gold/30 flex items-center justify-center">
                    <i data-lucide="lock" class="w-7 h-7 text-gold"></i>
                </span>
                <span class="font-serif text-2xl font-bold text-white tracking-tight">Elite Barber</span>
                <span class="text-xs text-zinc-500 tracking-widest uppercase">Reset Password</span>
            </a>
        </div>

        <div class="bg-zinc-900/60 backdrop-blur-md border border-white/8 rounded-2xl p-8 shadow-2xl">

            <div class="mb-6">
                <h1 class="font-serif text-3xl font-bold text-white leading-tight">
                    Kata Sandi<br><span class="text-gold italic font-light">Baru.</span>
                </h1>
                <p class="text-zinc-400 text-sm mt-2">Buat kata sandi baru yang kuat untuk akun Anda.</p>
            </div>

            <?php display_flash(); ?>

            <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1.5">Password Baru</label>
                    <input type="password" name="new_password" class="auth-input" placeholder="Min. 6 karakter" required autofocus>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1.5">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" class="auth-input" placeholder="Ulangi password baru" required>
                </div>

                <button type="submit"
                        class="w-full h-12 rounded-full bg-gold text-zinc-950 font-bold text-sm tracking-wide shadow-lg hover:bg-[#e8c84a] active:scale-95 transition-all duration-200 flex items-center justify-center gap-2 mt-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    SIMPAN PASSWORD BARU
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="login.php" class="text-xs text-zinc-600 hover:text-zinc-400 transition-colors inline-flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-3 h-3"></i>
                    Kembali ke Login
                </a>
            </div>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>