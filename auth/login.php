<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/helper.php';

if (file_exists(__DIR__ . '/../functions/auth_functions.php')) {
    require_once __DIR__ . '/../functions/auth_functions.php';
}

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

$username_email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_email   = function_exists('sanitize') ? sanitize($_POST['username_email'] ?? '') : trim($_POST['username_email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $username_email_val = $username_email;

    if (empty($username_email) || empty($password)) {
        if (function_exists('set_flash')) set_flash('danger', 'Username/Email dan Password wajib diisi!');
    } else {
        if (function_exists('login_user')) {
            $result = login_user($username_email, $password);
            if ($result['status']) {
                if (function_exists('set_flash')) set_flash('success', 'Selamat datang kembali!');
                $role = $result['role'] ?? $_SESSION['user_role'] ?? 'pelanggan';
                redirect_by_role($role);
            } else {
                if (function_exists('set_flash')) set_flash('danger', $result['message'] ?? 'Username/Email atau Password salah!');
            }
        } else {
            if (file_exists(__DIR__ . '/../config/database.php')) {
                require_once __DIR__ . '/../config/database.php';
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
                    $stmt->execute([$username_email, $username_email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                        $_SESSION['user_id']   = $user['id_user'] ?? $user['id'];
                        $_SESSION['username']  = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        if (function_exists('set_flash')) set_flash('success', 'Selamat datang kembali!');
                        redirect_by_role($user['role']);
                    } else {
                        if (function_exists('set_flash')) set_flash('danger', 'Username/Email atau Password salah!');
                    }
                } catch (PDOException $e) {
                    if (function_exists('set_flash')) set_flash('danger', 'Error Database: ' . $e->getMessage());
                }
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
    <title>Login — Elite Barber</title>
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

        /* Flash messages */
        .alert-success { background: rgba(212,175,55,0.1); border: 1px solid rgba(212,175,55,0.3); color: #d4af37; padding: 12px 16px; border-radius: 10px; font-size: 0.875rem; margin-bottom: 16px; }
        .alert-danger  { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.3);  color: #f87171; padding: 12px 16px; border-radius: 10px; font-size: 0.875rem; margin-bottom: 16px; }
        .alert-info    { background: rgba(59,130,246,0.1);  border: 1px solid rgba(59,130,246,0.3);  color: #93c5fd; padding: 12px 16px; border-radius: 10px; font-size: 0.875rem; margin-bottom: 16px; }
    </style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center px-4 relative overflow-hidden bg-black text-zinc-200">

    <!-- Background gradient matching landing page -->
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-black via-[#0a0a0a] to-[#3e2723] pointer-events-none"></div>

    <!-- Decorative gold glow top-right -->
    <div class="fixed top-0 right-0 w-96 h-96 -z-10 pointer-events-none"
         style="background: radial-gradient(circle at top right, rgba(212,175,55,0.1) 0%, transparent 60%);"></div>

    <!-- Card -->
    <div class="w-full max-w-md relative">

        <!-- Logo / Brand -->
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-flex flex-col items-center gap-2">
                <span class="w-14 h-14 rounded-full bg-gold/10 border border-gold/30 flex items-center justify-center">
                    <i data-lucide="scissors" class="w-7 h-7 text-gold"></i>
                </span>
                <span class="font-serif text-2xl font-bold text-white tracking-tight">Elite Barber</span>
                <span class="text-xs text-zinc-500 tracking-widest uppercase">Sistem Antrean Digital</span>
            </a>
        </div>

        <!-- Form card -->
        <div class="bg-zinc-900/60 backdrop-blur-md border border-white/8 rounded-2xl p-8 shadow-2xl">

            <!-- Heading -->
            <div class="mb-6">
                <h1 class="font-serif text-3xl font-bold text-white leading-tight">
                    Selamat Datang<br><span class="text-gold italic font-light">Kembali.</span>
                </h1>
                <p class="text-zinc-400 text-sm mt-2">Masuk untuk melanjutkan sesi Anda.</p>
            </div>

            <!-- Flash messages -->
            <?php if (function_exists('display_flash')) display_flash(); ?>

            <!-- Form -->
            <form action="login.php" method="POST" class="space-y-5">

                <div>
                    <label for="username_email" class="block text-sm font-medium text-zinc-300 mb-1.5">Username / Email</label>
                    <input type="text"
                           id="username_email"
                           name="username_email"
                           class="auth-input"
                           placeholder="Masukkan username atau email"
                           value="<?= htmlspecialchars($username_email_val, ENT_QUOTES, 'UTF-8') ?>"
                           required autocomplete="username" autofocus>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="text-sm font-medium text-zinc-300">Password</label>
                        <a href="forgot_password.php" class="text-xs text-gold hover:text-gold-light transition-colors">Lupa password?</a>
                    </div>
                    <input type="password"
                           id="password"
                           name="password"
                           class="auth-input"
                           placeholder="Masukkan password"
                           required autocomplete="current-password">
                </div>

                
                <button type="submit"
                        class="w-full h-12 rounded-full bg-gold text-zinc-950 font-bold text-sm tracking-wide shadow-lg hover:bg-[#e8c84a] active:scale-95 transition-all duration-200 flex items-center justify-center gap-2 mt-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    MASUK
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center gap-3 my-6">
                <div class="flex-1 h-px bg-white/8"></div>
                <span class="text-xs text-zinc-600">atau</span>
                <div class="flex-1 h-px bg-white/8"></div>
            </div>

            <!-- Register link -->
            <p class="text-center text-sm text-zinc-500">
                Belum punya akun?
                <a href="register.php" class="text-gold font-semibold hover:underline ml-1">Daftar Sekarang</a>
            </p>
        </div>

        <!-- Back link -->
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