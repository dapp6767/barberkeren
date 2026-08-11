<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/helper.php';
require_once __DIR__ . '/../config/database.php';

function redirect_by_role($role) {
    switch ($role) {
        case 'admin':   redirect('../petugas/admin.php'); break;
        case 'barber':  redirect('../petugas/barber.php'); break;
        default:        redirect('../pelanggan/dashboard.php'); break;
    }
    exit;
}

if (function_exists('is_logged_in') && is_logged_in()) {
    redirect_by_role($_SESSION['user_role'] ?? 'pelanggan');
}

$search_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_input = trim($_POST['search_input'] ?? '');
    $search_val   = $search_input;

    if (empty($search_input)) {
        if (function_exists('set_flash')) set_flash('danger', 'Silakan masukkan Username, Email, atau Nomor HP!');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_user, username, fullname, role FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1");
            $stmt->execute([$search_input, $search_input, $search_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Simpan ID user di session untuk reset langsung
                $_SESSION['reset_user_id'] = $user['id_user'];
                
                if (function_exists('set_flash')) {
                    set_flash('success', 'Akun ditemukan! Silakan masukkan password baru Anda.');
                }
                redirect('reset_password.php');
                exit;
            } else {
                if (function_exists('set_flash')) {
                    set_flash('danger', 'Akun dengan Username / Email / Nomor HP tersebut tidak ditemukan!');
                }
            }
        } catch (PDOException $e) {
            if (function_exists('set_flash')) {
                set_flash('danger', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — Elite Barber</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                        gold: { DEFAULT: '#d4af37', light: '#e8c84a', dark: '#a9882b' }
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
    <div class="fixed top-0 right-0 w-96 h-96 -z-10 pointer-events-none" style="background: radial-gradient(circle at top right, rgba(212,175,55,0.1) 0%, transparent 60%);"></div>

    <div class="w-full max-w-md relative py-8">

        <!-- Logo / Brand -->
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-flex flex-col items-center gap-2">
                <span class="w-14 h-14 rounded-full bg-gold/10 border border-gold/30 flex items-center justify-center">
                    <i data-lucide="key-round" class="w-7 h-7 text-gold"></i>
                </span>
                <span class="font-serif text-2xl font-bold text-white tracking-tight">Elite Barber</span>
                <span class="text-xs text-zinc-500 tracking-widest uppercase">Cari Akun Pemulihan</span>
            </a>
        </div>

        <!-- Form Card -->
        <div class="bg-zinc-900/60 backdrop-blur-md border border-white/10 rounded-2xl p-8 shadow-2xl">

            <div class="mb-6">
                <h1 class="font-serif text-3xl font-bold text-white leading-tight">
                    Lupa Password<br><span class="text-gold italic font-light">Akun Anda?</span>
                </h1>
                <p class="text-zinc-400 text-sm mt-2">Masukkan Username, Email, atau Nomor HP terdaftar Anda.</p>
            </div>

            <?php if (function_exists('display_flash')) display_flash(); ?>

            <form action="forgot_password.php" method="POST" class="space-y-5">
                <div>
                    <label for="search_input" class="block text-sm font-medium text-zinc-300 mb-1.5">Username / Email / No. HP</label>
                    <input type="text"
                           id="search_input"
                           name="search_input"
                           class="auth-input"
                           placeholder="Masukkan Username, Email, atau No. HP"
                           value="<?= htmlspecialchars($search_val, ENT_QUOTES, 'UTF-8') ?>"
                           required autocomplete="username" autofocus>
                </div>

                <button type="submit"
                        class="w-full h-12 rounded-full bg-gold text-zinc-950 font-bold text-sm tracking-wide shadow-lg hover:bg-[#e8c84a] active:scale-95 transition-all duration-200 flex items-center justify-center gap-2">
                    <span>LANJUT KE PASSWORD BARU</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            <div class="flex items-center gap-3 my-6">
                <div class="flex-1 h-px bg-white/8"></div>
                <span class="text-xs text-zinc-600">atau</span>
                <div class="flex-1 h-px bg-white/8"></div>
            </div>

            <p class="text-center text-sm text-zinc-500">
                Ingat password Anda?
                <a href="login.php" class="text-gold font-semibold hover:underline ml-1">Masuk Kembali</a>
            </p>
        </div>

        <p class="text-center mt-5">
            <a href="../index.php" class="text-xs text-zinc-600 hover:text-zinc-400 transition-colors inline-flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3 h-3"></i>
                Kembali ke Halaman Utama
            </a>
        </p>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
