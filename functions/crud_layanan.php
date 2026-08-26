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

        // Ensure gambar column exists
        try {
            $chkGmb = $pdo->query("SHOW COLUMNS FROM layanan LIKE 'gambar'");
            if (!$chkGmb || $chkGmb->rowCount() === 0) {
                $pdo->exec("ALTER TABLE layanan ADD COLUMN gambar VARCHAR(255) DEFAULT NULL");
            }
        } catch (Exception $e) {}

        if ($type === 'add_layanan') {
            $nama_layanan = trim($_POST['nama_layanan']);
            $harga = (float)$_POST['harga'];
            $durasi = (int)($_POST['durasi'] ?? 0);
            $deskripsi = trim($_POST['deskripsi'] ?? '');
            $is_terbaik = isset($_POST['is_terbaik']) ? 1 : 0;
            $gambar_url = trim($_POST['gambar_url'] ?? '');

            $stmt = $pdo->prepare("INSERT INTO layanan (nama_layanan, harga, durasi, deskripsi, is_terbaik, gambar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama_layanan, $harga, $durasi, $deskripsi, $is_terbaik, $gambar_url]);
            $lastId = $pdo->lastInsertId();
            
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $filename = 'layanan_' . $lastId . '.' . strtolower($ext);
                $dest = __DIR__ . '/../asset/image/' . $filename;
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $dest)) {
                    $stmtImg = $pdo->prepare("UPDATE layanan SET gambar = ? WHERE id = ?");
                    $stmtImg->execute([$filename, $lastId]);
                }
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
            $is_terbaik = isset($_POST['is_terbaik']) ? 1 : 0;
            $gambar_url = trim($_POST['gambar_url'] ?? '');
            
            $stmt = $pdo->prepare("UPDATE layanan SET nama_layanan = ?, harga = ?, durasi = ?, deskripsi = ?, is_terbaik = ? WHERE id = ?");
            $stmt->execute([$nama_layanan, $harga, $durasi, $deskripsi, $is_terbaik, $id]);
            
            if (!empty($gambar_url)) {
                $stmtImgUrl = $pdo->prepare("UPDATE layanan SET gambar = ? WHERE id = ?");
                $stmtImgUrl->execute([$gambar_url, $id]);
            }

            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $filename = 'layanan_' . $id . '.' . strtolower($ext);
                $dest = __DIR__ . '/../asset/image/' . $filename;
                $oldFiles = glob(__DIR__ . '/../asset/image/layanan_' . $id . '.*');
                foreach ($oldFiles as $f) { if (is_file($f)) unlink($f); }
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $dest)) {
                    $stmtImg = $pdo->prepare("UPDATE layanan SET gambar = ? WHERE id = ?");
                    $stmtImg->execute([$filename, $id]);
                }
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
