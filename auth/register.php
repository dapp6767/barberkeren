<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/helper.php';

if (file_exists(__DIR__ . '/../functions/auth_functions.php')) {
    require_once __DIR__ . '/../functions/auth_functions.php';
}

// Redirect jika sudah login
if (function_exists('is_logged_in') && is_logged_in()) {
    redirect('../pelanggan/dashboard.php');
    exit;
}

// Endpoint AJAX untuk pengecekan ketersediaan username secara real-time
if (isset($_GET['action']) && $_GET['action'] === 'check_username') {
    header('Content-Type: application/json');
    $uname = trim($_GET['username'] ?? '');
    if (empty($uname)) {
        echo json_encode(['available' => true]);
        exit;
    }
    
    $pdo = get_koneksi();
    $stmt = $pdo->prepare("SELECT id_user FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    $stmt->execute([$uname]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists) {
        echo json_encode([
            'available' => false,
            'message'   => 'Username sudah digunakan, silakan gunakan username lain.'
        ]);
    } else {
        echo json_encode([
            'available' => true,
            'message'   => 'Username tersedia.'
        ]);
    }
    exit;
}

$form_error = '';
$field_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname         = function_exists('sanitize') ? sanitize($_POST['fullname'] ?? '') : trim($_POST['fullname'] ?? '');
    $username         = function_exists('sanitize') ? sanitize($_POST['username'] ?? '') : trim($_POST['username'] ?? '');
    $email            = function_exists('sanitize') ? sanitize($_POST['email'] ?? '') : trim($_POST['email'] ?? '');
    $phone            = function_exists('sanitize') ? sanitize($_POST['phone'] ?? '') : trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Simpan data isian ke session temporary (Old Input).
    // Catatan Keamanan: Kolom password dan konfirmasi password sengaja dikosongkan/tidak disimpan demi alasan keamanan.
    if (function_exists('set_old_input')) {
        set_old_input([
            'fullname' => $fullname,
            'username' => $username,
            'email'    => $email,
            'phone'    => $phone,
        ]);
    } else {
        $_SESSION['old_input'] = [
            'fullname' => $fullname,
            'username' => $username,
            'email'    => $email,
            'phone'    => $phone,
        ];
    }

    if (empty($fullname)) {
        $form_error = 'Nama lengkap wajib diisi!';
        $field_errors['fullname'] = $form_error;
    } elseif (empty($username)) {
        $form_error = 'Username wajib diisi!';
        $field_errors['username'] = $form_error;
    } elseif (empty($email)) {
        $form_error = 'Email wajib diisi!';
        $field_errors['email'] = $form_error;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_error = 'Format email tidak valid! Silakan periksa kembali email Anda.';
        $field_errors['email'] = $form_error;
    } elseif (empty($phone)) {
        $form_error = 'Nomor HP / WhatsApp wajib diisi!';
        $field_errors['phone'] = $form_error;
    } elseif (empty($password)) {
        $form_error = 'Password wajib diisi!';
        $field_errors['password'] = $form_error;
    } elseif ($password !== $confirm_password) {
        $form_error = 'Password dan Konfirmasi Password tidak cocok!';
        $field_errors['confirm_password'] = $form_error;
    } elseif (function_exists('register_user')) {
        $result = register_user($fullname, $username, $email, $phone, $password);
        if ($result['status']) {
            // Bersihkan temporary old input karena registrasi sukses
            if (function_exists('clear_old_input')) {
                clear_old_input();
            } else {
                unset($_SESSION['old_input']);
            }
            if (function_exists('set_flash')) {
                set_flash('success', $result['message']);
            }
            redirect('login.php');
            exit;
        } else {
            $form_error = $result['message'];
            if (!empty($result['field'])) {
                $field_errors[$result['field']] = $result['message'];
            }
        }
    } else {
        $form_error = 'Fungsi pendaftaran akun belum tersedia.';
    }

    if (!empty($form_error) && function_exists('set_flash')) {
        set_flash('danger', $form_error);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — Elite Barber</title>
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
        html, body {
            height: auto;
            min-height: 100%;
        }
        body { font-family: 'Inter', sans-serif; }
        .auth-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fafafa;
            font-size: 0.9rem;
            transition: border-color .2s, box-shadow .2s, background-color .2s;
            outline: none;
        }
        .auth-input:focus {
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.15);
        }
        .auth-input::placeholder { color: #71717a; }

        /* Status Input Error */
        .auth-input.input-error {
            border-color: rgba(239, 68, 68, 0.85) !important;
            background: rgba(239, 68, 68, 0.08) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
        }

        .alert-success { background: rgba(212,175,55,0.1); border: 1px solid rgba(212,175,55,0.3); color: #d4af37; padding: 12px 16px; border-radius: 10px; font-size: 0.875rem; margin-bottom: 16px; }
        .alert-danger  { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.35);  color: #fca5a5; padding: 14px 18px; border-radius: 12px; font-size: 0.875rem; margin-bottom: 20px; }
        .alert-info    { background: rgba(59,130,246,0.1);  border: 1px solid rgba(59,130,246,0.3);  color: #93c5fd; padding: 12px 16px; border-radius: 10px; font-size: 0.875rem; margin-bottom: 16px; }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col justify-start sm:justify-center items-center px-4 py-8 sm:py-12 relative overflow-x-hidden overflow-y-auto bg-black text-zinc-200">

    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-black via-[#0a0a0a] to-[#3e2723] pointer-events-none"></div>
    <div class="fixed top-0 right-0 w-96 h-96 -z-10 pointer-events-none"
         style="background: radial-gradient(circle at top right, rgba(212,175,55,0.1) 0%, transparent 60%);"></div>

    <div class="w-full max-w-lg relative my-auto">

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

            <div class="mb-6">
                <h1 class="font-serif text-3xl font-bold text-white leading-tight">
                    Bergabung<br><span class="text-gold italic font-light">Bersama Kami.</span>
                </h1>
                <p class="text-zinc-400 text-sm mt-2">Buat akun untuk menikmati layanan pangkas premium.</p>
            </div>

            <!-- Banner Notifikasi Peringatan Teratas -->
            <?php if (!empty($form_error)): ?>
                <div class="alert-danger flex items-start gap-3 rounded-xl p-4 mb-5 border border-red-500/40 bg-red-950/40 text-red-200 shadow-xl" role="alert">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 text-red-400 mt-0.5"></i>
                    <div class="flex-1">
                        <span class="font-semibold block text-red-100 text-sm mb-0.5">Pendaftaran Gagal</span>
                        <span class="text-sm leading-relaxed"><?= htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (function_exists('display_flash')) display_flash(); ?>

            <form action="register.php" method="POST" class="space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="fullname_input" class="block text-sm font-medium text-zinc-300 mb-1.5">
                            Nama Lengkap <span class="text-rose-400">*</span>
                        </label>
                        <input type="text"
                               id="fullname_input"
                               name="fullname"
                               class="auth-input <?= !empty($field_errors['fullname']) ? 'input-error' : '' ?>"
                               placeholder="Marco Rossi"
                               value="<?= old('fullname') ?>"
                               required autofocus>
                        <?php if (!empty($field_errors['fullname'])): ?>
                            <p class="field-error-msg text-xs text-red-400 mt-1.5 flex items-center gap-1.5 font-medium">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0 text-red-400"></i>
                                <span><?= htmlspecialchars($field_errors['fullname'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="username_input" class="block text-sm font-medium text-zinc-300 mb-1.5">
                            Username <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <input type="text"
                                   id="username_input"
                                   name="username"
                                   class="auth-input <?= !empty($field_errors['username']) ? 'input-error' : '' ?>"
                                   placeholder="marcorossi"
                                   value="<?= old('username') ?>"
                                   required autocomplete="username">
                        </div>
                        <p id="username_error_feedback" class="field-error-msg text-xs text-red-400 mt-1.5 <?= empty($field_errors['username']) ? 'hidden' : 'flex' ?> items-center gap-1.5 font-medium">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0 text-red-400"></i>
                            <span id="username_error_text"><?= htmlspecialchars($field_errors['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email_input" class="block text-sm font-medium text-zinc-300 mb-1.5">
                            Email <span class="text-rose-400">*</span>
                        </label>
                        <input type="email"
                               id="email_input"
                               name="email"
                               class="auth-input <?= !empty($field_errors['email']) ? 'input-error' : '' ?>"
                               placeholder="nama@email.com"
                               value="<?= old('email') ?>"
                               required autocomplete="email">
                        <?php if (!empty($field_errors['email'])): ?>
                            <p class="field-error-msg text-xs text-red-400 mt-1.5 flex items-center gap-1.5 font-medium">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0 text-red-400"></i>
                                <span><?= htmlspecialchars($field_errors['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="phone_input" class="block text-sm font-medium text-zinc-300 mb-1.5">
                            No. HP / WhatsApp <span class="text-rose-400">*</span>
                        </label>
                        <input type="tel"
                               id="phone_input"
                               name="phone"
                               class="auth-input <?= !empty($field_errors['phone']) ? 'input-error' : '' ?>"
                               placeholder="081234567890"
                               value="<?= old('phone') ?>"
                               required autocomplete="tel">
                        <?php if (!empty($field_errors['phone'])): ?>
                            <p class="field-error-msg text-xs text-red-400 mt-1.5 flex items-center gap-1.5 font-medium">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0 text-red-400"></i>
                                <span><?= htmlspecialchars($field_errors['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password_input" class="block text-sm font-medium text-zinc-300 mb-1.5">
                            Password <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <input type="password"
                                   id="password_input"
                                   name="password"
                                   class="auth-input pr-10 <?= !empty($field_errors['password']) ? 'input-error' : '' ?>"
                                   placeholder="Min. 6-8 karakter"
                                   required autocomplete="new-password">
                            <button type="button" onclick="togglePass('password_input', 'eye_1')" class="absolute right-3 top-3 text-zinc-400 hover:text-white" aria-label="Lihat Password">
                                <i data-lucide="eye" id="eye_1" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <?php if (!empty($field_errors['password'])): ?>
                            <p class="field-error-msg text-xs text-red-400 mt-1.5 flex items-center gap-1.5 font-medium">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0 text-red-400"></i>
                                <span><?= htmlspecialchars($field_errors['password'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="confirm_password_input" class="block text-sm font-medium text-zinc-300 mb-1.5">
                            Konfirmasi Password <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <input type="password"
                                   id="confirm_password_input"
                                   name="confirm_password"
                                   class="auth-input pr-10 <?= !empty($field_errors['confirm_password']) ? 'input-error' : '' ?>"
                                   placeholder="Ulangi password"
                                   required autocomplete="new-password">
                            <button type="button" onclick="togglePass('confirm_password_input', 'eye_2')" class="absolute right-3 top-3 text-zinc-400 hover:text-white" aria-label="Lihat Konfirmasi Password">
                                <i data-lucide="eye" id="eye_2" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <?php if (!empty($field_errors['confirm_password'])): ?>
                            <p class="field-error-msg text-xs text-red-400 mt-1.5 flex items-center gap-1.5 font-medium">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0 text-red-400"></i>
                                <span><?= htmlspecialchars($field_errors['confirm_password'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Password Strength Checklist Box -->
                <div class="bg-black/50 border border-white/10 rounded-xl p-3.5 space-y-2 text-xs">
                    <p class="text-amber-200 font-semibold text-[11px] uppercase tracking-wider mb-1 flex items-center gap-1.5">
                        <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-amber-400"></i> Ketentuan Kombinasi Password:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-zinc-400">
                        <div id="rule_len" class="flex items-center gap-1.5 transition-colors">
                            <i data-lucide="circle-dot" class="w-3 h-3 text-zinc-600"></i> Minimal 6-8 Karakter
                        </div>
                        <div id="rule_case" class="flex items-center gap-1.5 transition-colors">
                            <i data-lucide="circle-dot" class="w-3 h-3 text-zinc-600"></i> Huruf Besar (A-Z) & Kecil (a-z)
                        </div>
                        <div id="rule_num" class="flex items-center gap-1.5 transition-colors">
                            <i data-lucide="circle-dot" class="w-3 h-3 text-zinc-600"></i> Memiliki Angka (0-9)
                        </div>
                        <div id="rule_sym" class="flex items-center gap-1.5 transition-colors">
                            <i data-lucide="circle-dot" class="w-3 h-3 text-zinc-600"></i> Memiliki Simbol (@, #, !, dll)
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full h-12 rounded-full bg-gold text-zinc-950 font-bold text-sm tracking-wide shadow-lg hover:bg-[#e8c84a] active:scale-95 transition-all duration-200 flex items-center justify-center gap-2 mt-2">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    DAFTAR SEKARANG
                </button>
            </form>

            <div class="flex items-center gap-3 my-6">
                <div class="flex-1 h-px bg-white/8"></div>
                <span class="text-xs text-zinc-600">atau</span>
                <div class="flex-1 h-px bg-white/8"></div>
            </div>

            <p class="text-center text-sm text-zinc-500">
                Sudah punya akun?
                <a href="login.php" class="text-gold font-semibold hover:underline ml-1">Login di sini</a>
            </p>
        </div>

        <p class="text-center mt-5">
            <a href="../index.php" class="text-xs text-zinc-600 hover:text-zinc-400 transition-colors inline-flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3 h-3"></i>
                Kembali ke Halaman Utama
            </a>
        </p>
    </div>

    <?php
    // Bersihkan temporary old input dari session setelah halaman selesai dirender
    if (function_exists('clear_old_input')) {
        clear_old_input();
    } else {
        unset($_SESSION['old_input']);
    }
    ?>

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

        // Live reset styling error saat pengguna mulai mengetik di kolom input
        document.querySelectorAll('.auth-input').forEach(function(input) {
            input.addEventListener('input', function() {
                if (this.classList.contains('input-error')) {
                    this.classList.remove('input-error');
                }
                const container = this.closest('div');
                if (container) {
                    const errorMsg = container.querySelector('.field-error-msg');
                    if (errorMsg) {
                        errorMsg.style.display = 'none';
                    }
                }
            });
        });

        // Live check ketersediaan username saat diketik
        const usernameInput = document.getElementById('username_input');
        if (usernameInput) {
            let checkTimeout;
            usernameInput.addEventListener('input', function() {
                clearTimeout(checkTimeout);
                const val = this.value.trim();
                const feedback = document.getElementById('username_error_feedback');
                const feedbackText = document.getElementById('username_error_text');
                if (!feedback || !feedbackText) return;

                if (val.length < 3) {
                    feedback.classList.add('hidden');
                    feedback.classList.remove('flex');
                    feedback.style.display = 'none';
                    usernameInput.classList.remove('input-error');
                    return;
                }

                checkTimeout = setTimeout(() => {
                    fetch('register.php?action=check_username&username=' + encodeURIComponent(val))
                        .then(res => res.json())
                        .then(data => {
                            if (!data.available) {
                                feedbackText.textContent = data.message;
                                feedback.classList.remove('hidden');
                                feedback.classList.add('flex');
                                feedback.style.display = 'flex';
                                usernameInput.classList.add('input-error');
                                lucide.createIcons();
                            } else {
                                feedback.classList.add('hidden');
                                feedback.classList.remove('flex');
                                feedback.style.display = 'none';
                                usernameInput.classList.remove('input-error');
                            }
                        })
                        .catch(() => {});
                }, 400);
            });
        }

        // Password strength checklist
        const passInput = document.getElementById('password_input');
        if (passInput) {
            passInput.addEventListener('input', function() {
                const val = this.value;
                
                // Length check
                const ruleLen = document.getElementById('rule_len');
                if (val.length >= 6) {
                    ruleLen.className = 'flex items-center gap-1.5 text-emerald-400 font-medium';
                } else {
                    ruleLen.className = 'flex items-center gap-1.5 text-zinc-400';
                }

                // Case check (A-Z and a-z)
                const ruleCase = document.getElementById('rule_case');
                if (/[A-Z]/.test(val) && /[a-z]/.test(val)) {
                    ruleCase.className = 'flex items-center gap-1.5 text-emerald-400 font-medium';
                } else {
                    ruleCase.className = 'flex items-center gap-1.5 text-zinc-400';
                }

                // Number check
                const ruleNum = document.getElementById('rule_num');
                if (/[0-9]/.test(val)) {
                    ruleNum.className = 'flex items-center gap-1.5 text-emerald-400 font-medium';
                } else {
                    ruleNum.className = 'flex items-center gap-1.5 text-zinc-400';
                }

                // Symbol check
                const ruleSym = document.getElementById('rule_sym');
                if (/[\W_]/.test(val)) {
                    ruleSym.className = 'flex items-center gap-1.5 text-emerald-400 font-medium';
                } else {
                    ruleSym.className = 'flex items-center gap-1.5 text-zinc-400';
                }
            });
        }
    </script>
</body>
</html>