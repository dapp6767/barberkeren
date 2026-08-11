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

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$valid_token = false;
$user_info = null;

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_info) {
            $valid_token = true;
        }
    } catch (PDOException $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi    = $_POST['konfirmasi_password'] ?? '';

    if (empty($password_baru) || empty($konfirmasi)) {
        if (function_exists('set_flash')) set_flash('danger', 'Password baru dan konfirmasi password wajib diisi!');
    } elseif ($password_baru !== $konfirmasi) {
        if (function_exists('set_flash')) set_flash('danger', 'Konfirmasi password tidak cocok dengan password baru!');
    } elseif (strlen($password_baru) < 6) {
        if (function_exists('set_flash')) set_flash('danger', 'Password baru minimal harus 6 karakter!');
    } else {
        try {
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt_u = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id_user = ?");
            $stmt_u->execute([$hashed_password, $user_info['id_user']]);

            if (function_exists('set_flash')) {
                set_flash('success', 'Password Anda berhasil diperbarui! Silakan login menggunakan password baru.');
            }
            redirect('login.php');
            exit;
        } catch (PDOException $e) {
            if (function_exists('set_flash')) set_flash('danger', 'Gagal memperbarui password: ' . $e->getMessage());
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
                    <i data-lucide="shield-check" class="w-7 h-7 text-gold"></i>
                </span>
                <span class="font-serif text-2xl font-bold text-white tracking-tight">Elite Barber</span>
                <span class="text-xs text-zinc-500 tracking-widest uppercase">Perbarui Password Baru</span>
            </a>
        </div>

        <!-- Form Card -->
        <div class="bg-zinc-900/60 backdrop-blur-md border border-white/10 rounded-2xl p-8 shadow-2xl">

            <?php if (!$valid_token): ?>
                <!-- Invalid Token Card -->
                <div class="text-center space-y-4">
                    <div class="w-12 h-12 rounded-full bg-rose-500/10 border border-rose-500/30 flex items-center justify-center mx-auto text-rose-400">
                        <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                    </div>
                    <h2 class="font-serif text-2xl font-bold text-white">Link Tidak Valid</h2>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        Link reset password ini tidak valid atau sudah kadaluarsa (berlaku 30 menit). Silakan minta link pemulihan baru.
                    </p>
                    <a href="forgot_password.php" class="w-full h-11 rounded-full bg-gold text-zinc-950 font-bold text-xs tracking-wide shadow-lg hover:bg-gold-light transition-all flex items-center justify-center gap-2 mt-4">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        MINTA LINK PEMULIHAN BARU
                    </a>
                </div>
            <?php else: ?>
                <!-- Valid Token Form -->
                <div class="mb-6">
                    <h1 class="font-serif text-3xl font-bold text-white leading-tight">
                        Buat Password<br><span class="text-gold italic font-light">Baru Anda.</span>
                    </h1>
                    <p class="text-zinc-400 text-xs mt-2">
                        Akun: <strong class="text-amber-200"><?= htmlspecialchars($user_info['fullname'] ?? $user_info['username']) ?></strong> (<?= htmlspecialchars($user_info['username']) ?> — <span class="uppercase text-gold"><?= htmlspecialchars($user_info['role']) ?></span>)
                    </p>
                </div>

                <?php if (function_exists('display_flash')) display_flash(); ?>

                <form action="reset_password.php" method="POST" class="space-y-5">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div>
                        <label for="password_baru" class="block text-sm font-medium text-zinc-300 mb-1.5">Password Baru</label>
                        <div class="relative">
                            <input type="password"
                                   id="password_baru"
                                   name="password_baru"
                                   class="auth-input pr-10"
                                   placeholder="Minimal 6 karakter"
                                   required autocomplete="new-password" autofocus>
                            <button type="button" onclick="togglePass('password_baru', 'eye_icon_1')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                <i data-lucide="eye" id="eye_icon_1" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="konfirmasi_password" class="block text-sm font-medium text-zinc-300 mb-1.5">Konfirmasi Password Baru</label>
                        <div class="relative">
                            <input type="password"
                                   id="konfirmasi_password"
                                   name="konfirmasi_password"
                                   class="auth-input pr-10"
                                   placeholder="Ulangi password baru"
                                   required autocomplete="new-password">
                            <button type="button" onclick="togglePass('konfirmasi_password', 'eye_icon_2')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                <i data-lucide="eye" id="eye_icon_2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full h-12 rounded-full bg-gold text-zinc-950 font-bold text-sm tracking-wide shadow-lg hover:bg-[#e8c84a] active:scale-95 transition-all duration-200 flex items-center justify-center gap-2 mt-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        SIMPAN PASSWORD BARU
                    </button>
                </form>
            <?php endif; ?>

            <div class="flex items-center gap-3 my-6">
                <div class="flex-1 h-px bg-white/8"></div>
                <span class="text-xs text-zinc-600">atau</span>
                <div class="flex-1 h-px bg-white/8"></div>
            </div>

            <p class="text-center text-sm text-zinc-500">
                Kembali ke halaman
                <a href="login.php" class="text-gold font-semibold hover:underline ml-1">Masuk Akun</a>
            </p>
        </div>

        <p class="text-center mt-5">
            <a href="../index.php" class="text-xs text-zinc-600 hover:text-zinc-400 transition-colors inline-flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3 h-3"></i>
                Kembali ke Halaman Utama
            </a>
        </p>
    </div>

    <script>
        lucide.createIcons();
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>