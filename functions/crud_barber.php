<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/auth.php';

/**
 * Get All Barbers
 */
if (!function_exists('get_all_barbers')) {
    function get_all_barbers() {
        $pdo = get_koneksi();
        $stmt = $pdo->query("SELECT * FROM barber ORDER BY id ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}

/**
 * Handle CRUD Barber Actions (Dipanggil oleh Admin)
 */
if (!function_exists('handle_crud_barber')) {
    function handle_crud_barber($type) {
        $pdo = get_koneksi();

        if ($type === 'add_barber') {
            $nama = trim($_POST['nama']);
            $kursi = trim($_POST['kursi'] ?? 'Kursi A');
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            
            if (function_exists('validate_account_creation')) {
                $val = validate_account_creation($nama, $username, $password);
                if (!$val['status']) {
                    set_flash('danger', $val['message']);
                    redirect('admin.php?page=akun');
                    exit;
                }
            }
            
            $pdo->beginTransaction();
            $stmt_u = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'barber')");
            $stmt_u->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $user_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO barber (user_id, nama, kursi, status) VALUES (?, ?, ?, 'aktif')");
            $stmt->execute([$user_id, $nama, $kursi]);
            $pdo->commit();
            
            set_flash('success', 'Data Barber berhasil ditambahkan!');
        }
        elseif ($type === 'toggle_barber_status') {
            $barber_id = (int)($_POST['barber_id'] ?? 0);
            $new_status = trim($_POST['new_status'] ?? 'Aktif');
            
            if ($barber_id > 0) {
                $stmt_b_info = $pdo->prepare("SELECT nama, kursi FROM barber WHERE id = ?");
                $stmt_b_info->execute([$barber_id]);
                $b_info = $stmt_b_info->fetch(PDO::FETCH_ASSOC);
                
                $stmt_tog = $pdo->prepare("UPDATE barber SET status = ? WHERE id = ?");
                $stmt_tog->execute([$new_status, $barber_id]);
                
                $b_nama = $b_info['nama'] ?? 'Barber';
                $b_kursi = $b_info['kursi'] ?? 'Kursi';
                
                if (strtolower($new_status) === 'aktif') {
                    set_flash('success', "Status Barber <b>{$b_nama}</b> ({$b_kursi}) berhasil DIKEMBALIKAN AKTIF! Layanan kursi kembali tersedia.");
                } else {
                    set_flash('warning', "Status Barber <b>{$b_nama}</b> ({$b_kursi}) berhasil DINONAKTIFKAN! Layanan pada {$b_kursi} dinonaktifkan sementara.");
                }
            } else {
                set_flash('danger', 'ID Barber tidak valid!');
            }
        }
        elseif ($type === 'delete_barber') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM barber WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('warning', 'Barber berhasil dihapus!');
        }
    }
}
