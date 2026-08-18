<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/auth.php';

/**
 * Handle CRUD User Actions (Dipanggil oleh Admin)
 */
if (!function_exists('handle_crud_user')) {
    function handle_crud_user($type) {
        $pdo = get_koneksi();

        if ($type === 'add_user') {
            $fullname = trim($_POST['fullname'] ?? '');
            $username = trim($_POST['username']);
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = trim($_POST['password']);
            $role = trim($_POST['role']);
            
            if (function_exists('validate_account_creation')) {
                $val = validate_account_creation($fullname, $username, $password, $email);
                if (!$val['status']) {
                    set_flash('danger', $val['message']);
                    redirect('admin.php?page=akun');
                    exit;
                }
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_u = $pdo->prepare("INSERT INTO users (fullname, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_u->execute([$fullname, $username, $email, $phone, $hashed_password, $role]);
            set_flash('success', 'User baru berhasil ditambahkan!');
        }
        elseif ($type === 'edit_user') {
            $id = (int)$_POST['id_user'];
            $fullname = trim($_POST['fullname']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $role = $_POST['role'];
            $password = $_POST['password'];

            if (!empty($password)) {
                $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, phone = ?, role = ?, password = ? WHERE id_user = ?");
                $stmt->execute([$fullname, $username, $email, $phone, $role, $password, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, phone = ? WHERE id_user = ?");
                $stmt->execute([$fullname, $username, $email, $phone, $id]);
            }
            set_flash('success', 'User berhasil diupdate!');
        }
        elseif ($type === 'delete_user') {
            $id = (int)($_POST['id_user'] ?? $_POST['id'] ?? 0);
            if ($id > 0) {
                if (isset($_SESSION['user_id']) && $id === (int)$_SESSION['user_id']) {
                    set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri!');
                } else {
                    try {
                        $pdo->beginTransaction();
                        try { $pdo->prepare("DELETE FROM antrian WHERE pelanggan_id = ?")->execute([$id]); } catch (Exception $e) {}
                        try { $pdo->prepare("DELETE FROM ulasan WHERE pelanggan_id = ?")->execute([$id]); } catch (Exception $e) {}
                        try { $pdo->prepare("DELETE FROM barber WHERE user_id = ?")->execute([$id]); } catch (Exception $e) {}
                        
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
                        $stmt->execute([$id]);
                        $pdo->commit();
                        set_flash('success', 'Akun pengguna berhasil dihapus dari database!');
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        set_flash('danger', 'Gagal menghapus user: ' . $e->getMessage());
                    }
                }
            } else {
                set_flash('danger', 'ID User tidak valid!');
            }
        }
    }
}
