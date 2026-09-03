<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notifikasi.php';

/**
 * Update Profil & Password Pengguna
 */
if (!function_exists('update_user_profile')) {
    function update_user_profile($user_id, $redirect_url = 'dashboard.php?page=profil') {
        $pdo = get_koneksi();

        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? ($_POST['password'] ?? '');
        $confirm_password = $_POST['confirm_password'] ?? '';

        try {
            $stmt_cur = $pdo->prepare("SELECT * FROM users WHERE id_user = ? LIMIT 1");
            $stmt_cur->execute([$user_id]);
            $user_data = $stmt_cur->fetch(PDO::FETCH_ASSOC);

            if (!$user_data) {
                set_flash('danger', 'Akun tidak ditemukan!');
                redirect($redirect_url);
                exit;
            }

            if (function_exists('contains_sara_words')) {
                if (contains_sara_words($fullname)) {
                    set_flash('danger', 'Nama Lengkap mengandung kata/unsur SARA!');
                    redirect($redirect_url);
                    exit;
                }
                if (contains_sara_words($username)) {
                    set_flash('danger', 'Username mengandung kata/unsur SARA!');
                    redirect($redirect_url);
                    exit;
                }
            }

            $stmt_dup = $pdo->prepare("SELECT id_user FROM users WHERE (LOWER(username) = LOWER(?) OR (email != '' AND LOWER(email) = LOWER(?))) AND id_user != ? LIMIT 1");
            $stmt_dup->execute([$username, $email, $user_id]);
            if ($stmt_dup->fetch()) {
                set_flash('danger', 'Username atau Email sudah terdaftar pada akun lain!');
                redirect($redirect_url);
                exit;
            }

            $update_password_hash = null;

            // Hanya validasi password jika pengguna memang mengisi password baru
            if (!empty($new_password) || !empty($confirm_password)) {
                if (empty($old_password)) {
                    set_flash('danger', 'Silakan masukkan Password Lama Anda untuk mengonfirmasi perubahan password!');
                    redirect($redirect_url);
                    exit;
                }

                $password_correct = false;
                if (password_verify($old_password, $user_data['password'])) {
                    $password_correct = true;
                } elseif ($old_password === $user_data['password']) {
                    $password_correct = true;
                }

                if (!$password_correct) {
                    set_flash('danger', 'Password Lama Anda salah! Verifikasi pemilik akun gagal.');
                    redirect($redirect_url);
                    exit;
                }

                if (empty($new_password) || empty($confirm_password)) {
                    set_flash('danger', 'Password Baru dan Konfirmasi Password wajib diisi!');
                    redirect($redirect_url);
                    exit;
                }

                if ($new_password !== $confirm_password) {
                    set_flash('danger', 'Konfirmasi Password Baru tidak cocok dengan Password Baru!');
                    redirect($redirect_url);
                    exit;
                }

                if (function_exists('validate_account_creation')) {
                    $val_p = validate_account_creation($fullname, $username, $new_password, $email, $user_id);
                    if (!$val_p['status'] && str_contains(strtolower($val_p['message']), 'password')) {
                        set_flash('danger', $val_p['message']);
                        redirect($redirect_url);
                        exit;
                    }
                } else {
                    if (strlen($new_password) < 6) {
                        set_flash('danger', 'Password minimal harus 6-8 karakter!');
                        redirect($redirect_url);
                        exit;
                    }
                    if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[\W_]/', $new_password)) {
                        set_flash('danger', 'Password baru wajib kombinasi Huruf Besar (A-Z), Huruf Kecil (a-z), Angka (0-9), dan Simbol Khusus (@, #, !, dll)!');
                        redirect($redirect_url);
                        exit;
                    }
                }

                $update_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            }

            if (!empty($fullname)) {
                $_SESSION['fullname'] = $fullname;
            }
            $_SESSION['username'] = $username;

            if ($update_password_hash) {
                $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, phone = ?, password = ? WHERE id_user = ?");
                $stmt->execute([$fullname, $username, $email, $phone, $update_password_hash, $user_id]);

                if (function_exists('create_admin_notification') && ($_SESSION['user_role'] ?? '') === 'pelanggan') {
                    $cust_name = !empty($fullname) ? $fullname : $username;
                    create_admin_notification(
                        'security',
                        'Perubahan Password Pelanggan',
                        "Pelanggan <b>{$cust_name}</b> (@{$username}) telah berhasil memperbarui password akunnya.",
                        'admin.php?page=pelanggan'
                    );
                }

                set_flash('success', 'Profil dan Password Anda berhasil diperbarui!');
            } else {
                $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, phone = ? WHERE id_user = ?");
                $stmt->execute([$fullname, $username, $email, $phone, $user_id]);
                set_flash('success', 'Informasi profil berhasil diperbarui!');
            }

            $stmt_b_up = $pdo->prepare("UPDATE barber SET nama = ? WHERE user_id = ?");
            $stmt_b_up->execute([$fullname, $user_id]);

            // Handle Upload Foto Profil
            $file_key = null;
            if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file_key = 'foto_profil';
            } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file_key = 'profile_photo';
            }

            if ($file_key && isset($_FILES[$file_key])) {
                if ($_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    $ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));

                    if ($_FILES[$file_key]['size'] > 5 * 1024 * 1024) {
                        set_flash('danger', 'Ukuran file foto maksimal 5 MB!');
                        redirect($redirect_url);
                        exit;
                    }

                    if (!in_array($ext, $allowed_exts)) {
                        set_flash('danger', 'Format foto tidak didukung! Gunakan format JPG, PNG, atau WEBP.');
                        redirect($redirect_url);
                        exit;
                    }

                    $img_info = @getimagesize($_FILES[$file_key]['tmp_name']);
                    if (!$img_info) {
                        set_flash('danger', 'File yang diunggah bukan format gambar valid!');
                        redirect($redirect_url);
                        exit;
                    }

                    $target_dir = __DIR__ . '/../asset/image';
                    if (!is_dir($target_dir)) {
                        @mkdir($target_dir, 0777, true);
                    }

                    $oldFiles = glob($target_dir . '/profile_' . $user_id . '.*');
                    if (!empty($oldFiles)) {
                        foreach ($oldFiles as $f) {
                            if (is_file($f)) @unlink($f);
                        }
                    }

                    $dest = $target_dir . '/profile_' . $user_id . '.' . $ext;
                    if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $dest)) {
                        set_flash('success', 'Profil dan Foto Profil berhasil diperbarui!');
                    } else {
                        set_flash('warning', 'Profil diperbarui, namun foto gagal disimpan di server.');
                    }
                } elseif ($_FILES[$file_key]['error'] === UPLOAD_ERR_INI_SIZE || $_FILES[$file_key]['error'] === UPLOAD_ERR_FORM_SIZE) {
                    set_flash('danger', 'Ukuran file foto terlalu besar (maksimal 5 MB)!');
                    redirect($redirect_url);
                    exit;
                }
            }

            redirect($redirect_url);
            exit;
        } catch (PDOException $e) {
            set_flash('danger', 'Error: ' . $e->getMessage());
            redirect($redirect_url);
            exit;
        }
    }
}

