<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/notifikasi.php';

/**
 * Get All Services
 */
if (!function_exists('get_all_services')) {
    function get_all_services() {
        $pdo = get_koneksi();
        $stmt = $pdo->query("SELECT * FROM layanan ORDER BY id ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}

/**
 * Handle CRUD Layanan Actions (Dipanggil oleh Admin)
 */
if (!function_exists('handle_crud_layanan')) {
    function handle_crud_layanan($type) {
        $pdo = get_koneksi();

        if ($type === 'add_layanan') {
            $nama_layanan = trim($_POST['nama_layanan']);
            $harga = (float)$_POST['harga'];
            $durasi = (int)($_POST['durasi'] ?? 0);
            $deskripsi = trim($_POST['deskripsi'] ?? '');
            $stmt = $pdo->prepare("INSERT INTO layanan (nama_layanan, harga, durasi, deskripsi) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_layanan, $harga, $durasi, $deskripsi]);
            $lastId = $pdo->lastInsertId();
            
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $dest = __DIR__ . '/../asset/image/layanan_' . $lastId . '.' . $ext;
                move_uploaded_file($_FILES['gambar']['tmp_name'], $dest);
            }
            if (function_exists('create_admin_notification')) {
                create_admin_notification(
                    'add_layanan',
                    'Layanan Baru Ditambahkan',
                    "Layanan \"{$nama_layanan}\" (Rp " . number_format($harga, 0, ',', '.') . ") telah ditambahkan!",
                    'admin.php?page=layanan'
                );
            }
            set_flash('success', 'Layanan baru berhasil ditambahkan!');
        }
        elseif ($type === 'edit_layanan') {
            $id = (int)$_POST['id'];
            $nama_layanan = trim($_POST['nama_layanan']);
            $harga = (float)$_POST['harga'];
            $durasi = (int)($_POST['durasi'] ?? 0);
            $deskripsi = trim($_POST['deskripsi'] ?? '');
            
            $stmt = $pdo->prepare("UPDATE layanan SET nama_layanan = ?, harga = ?, durasi = ?, deskripsi = ? WHERE id = ?");
            $stmt->execute([$nama_layanan, $harga, $durasi, $deskripsi, $id]);
            
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $dest = __DIR__ . '/../asset/image/layanan_' . $id . '.' . $ext;
                $oldFiles = glob(__DIR__ . '/../asset/image/layanan_' . $id . '.*');
                foreach ($oldFiles as $f) { if (is_file($f)) unlink($f); }
                move_uploaded_file($_FILES['gambar']['tmp_name'], $dest);
            }
            
            set_flash('success', 'Layanan berhasil diupdate!');
        }
        elseif ($type === 'delete_layanan') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM layanan WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('warning', 'Layanan berhasil dihapus!');
        }
    }
}
