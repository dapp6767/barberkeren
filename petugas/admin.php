<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/helper.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/queue_functions.php';

// Proteksi Multi-Role: Hanya Admin
if (!function_exists('is_logged_in') || !is_logged_in() || $_SESSION['user_role'] !== 'admin') {
    set_flash('danger', 'Akses ditolak! Halaman ini khusus Administrator.');
    redirect('../auth/login.php');
    exit;
}

// Auto-Ensure table notifikasi exists & created_at column in users table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifikasi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT '',
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $chkUserCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    if (!$chkUserCol || $chkUserCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
    }
    $chkBarberKursi = $pdo->query("SHOW COLUMNS FROM barber LIKE 'kursi'");
    if (!$chkBarberKursi || $chkBarberKursi->rowCount() === 0) {
        $pdo->exec("ALTER TABLE barber ADD COLUMN kursi VARCHAR(20) DEFAULT 'Kursi A'");
    }
} catch (Exception $e) {}

// Handle AJAX Notification Endpoint
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'get_unread_notif') {
        header('Content-Type: application/json');
        $stmt_n = $pdo->query("SELECT * FROM notifikasi WHERE is_read = 0 ORDER BY id DESC LIMIT 10");
        $unread_list = $stmt_n ? $stmt_n->fetchAll(PDO::FETCH_ASSOC) : [];
        
        $stmt_c = $pdo->query("SELECT COUNT(*) as unread_count FROM notifikasi WHERE is_read = 0");
        $unread_count = $stmt_c ? (int)$stmt_c->fetchColumn() : 0;
        
        echo json_encode([
            'status' => true,
            'unread_count' => $unread_count,
            'notifications' => $unread_list
        ]);
        exit;
    }
    if ($_GET['action'] === 'mark_notif_read') {
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt_m = $pdo->prepare("UPDATE notifikasi SET is_read = 1 WHERE id = ?");
            $stmt_m->execute([$id]);
        } else {
            $pdo->exec("UPDATE notifikasi SET is_read = 1 WHERE is_read = 0");
        }
        echo json_encode(['status' => true]);
        exit;
    }
}

// Handle Form Posts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['form_type'] ?? '';

    try {
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
        elseif ($type === 'delete_antrian') {
            $antrian_id = (int)($_POST['antrian_id'] ?? 0);
            if ($antrian_id > 0) {
                try {
                    $pdo->beginTransaction();
                    try { $pdo->prepare("DELETE FROM ulasan WHERE antrian_id = ?")->execute([$antrian_id]); } catch (Exception $e) {}
                    try { $pdo->prepare("DELETE FROM transaksi WHERE antrian_id = ?")->execute([$antrian_id]); } catch (Exception $e) {}
                    $stmt_del = $pdo->prepare("DELETE FROM antrian WHERE id = ?");
                    $stmt_del->execute([$antrian_id]);
                    $pdo->commit();
                    set_flash('warning', 'Data antrean berhasil dihapus dari sistem!');
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    set_flash('danger', 'Gagal menghapus antrean: ' . $e->getMessage());
                }
            }
        }
        elseif ($type === 'add_user') {
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
            
            // Hash password untuk keamanan dan agar sama dengan sistem registrasi
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt_u = $pdo->prepare("INSERT INTO users (fullname, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_u->execute([$fullname, $username, $email, $phone, $hashed_password, $role]);
            set_flash('success', 'User baru berhasil ditambahkan!');
        }
        elseif ($type === 'add_layanan') {
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
        elseif ($type === 'save_wa_config') {
            $api_key = trim($_POST['wa_api_key']);
            file_put_contents(__DIR__ . '/../config/wa_config.json', json_encode(['api_key' => $api_key]));
            set_flash('success', 'API Key WhatsApp Gateway (Fonnte) berhasil disimpan!');
        }
        elseif ($type === 'delete_barber') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM barber WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('warning', 'Barber berhasil dihapus!');
        }
        elseif ($type === 'delete_layanan') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM layanan WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('warning', 'Layanan berhasil dihapus!');
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
                // Try to delete old extensions
                $oldFiles = glob(__DIR__ . '/../asset/image/layanan_' . $id . '.*');
                foreach ($oldFiles as $f) { if (is_file($f)) unlink($f); }
                move_uploaded_file($_FILES['gambar']['tmp_name'], $dest);
            }
            
            set_flash('success', 'Layanan berhasil diupdate!');
        }
        elseif ($type === 'delete_user') {
            $id = (int)($_POST['id_user'] ?? $_POST['id'] ?? 0);
            if ($id > 0) {
                if (isset($_SESSION['user_id']) && $id === (int)$_SESSION['user_id']) {
                    set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri!');
                } else {
                    try {
                        $pdo->beginTransaction();
                        // Cascade delete related records if foreign keys exist
                        try { $pdo->prepare("DELETE FROM antrian WHERE pelanggan_id = ?")->execute([$id]); } catch (Exception $e) {}
                        try { $pdo->prepare("DELETE FROM ulasan WHERE pelanggan_id = ?")->execute([$id]); } catch (Exception $e) {}
                        try { $pdo->prepare("DELETE FROM barber WHERE user_id = ?")->execute([$id]); } catch (Exception $e) {}
                        try { $pdo->prepare("DELETE FROM kunjungan_website WHERE user_id = ?")->execute([$id]); } catch (Exception $e) {}
                        
                        // Delete user record from database
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
        elseif ($type === 'edit_user') {
            $id = (int)$_POST['id_user'];
            $fullname = trim($_POST['fullname']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $role = $_POST['role'];
            $password = $_POST['password'];

            if (!empty($password)) {
                // If password is provided, update it (without hashing since the old code stored plain text "123")
                // Wait, previous code doesn't hash passwords? The insert uses plain text? Let me check add_user.
                // Let's just insert plain text as the rest of the app seems to use plain text.
                $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, phone = ?, role = ?, password = ? WHERE id_user = ?");
                $stmt->execute([$fullname, $username, $email, $phone, $role, $password, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, phone = ?, role = ? WHERE id_user = ?");
                $stmt->execute([$fullname, $username, $email, $phone, $role, $id]);
            }
            set_flash('success', 'User berhasil diupdate!');
        }
        elseif ($type === 'update_profil') {
            $id = $_SESSION['user_id'];
            $fullname = trim($_POST['fullname'] ?? '');
            $username = trim($_POST['username']);
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            $old_password = $_POST['old_password'] ?? '';
            $new_password = $_POST['new_password'] ?? ($_POST['password'] ?? '');
            $confirm_password = $_POST['confirm_password'] ?? '';

            $stmt_cur = $pdo->prepare("SELECT * FROM users WHERE id_user = ? LIMIT 1");
            $stmt_cur->execute([$id]);
            $user_data = $stmt_cur->fetch(PDO::FETCH_ASSOC);

            if (!$user_data) {
                set_flash('danger', 'Akun tidak ditemukan!');
                redirect('admin.php?page=profil');
                exit;
            }

            if (function_exists('contains_sara_words')) {
                if (contains_sara_words($fullname)) {
                    set_flash('danger', 'Nama Lengkap mengandung kata/unsur SARA!');
                    redirect('admin.php?page=profil');
                    exit;
                }
                if (contains_sara_words($username)) {
                    set_flash('danger', 'Username mengandung kata/unsur SARA!');
                    redirect('admin.php?page=profil');
                    exit;
                }
            }

            $stmt_dup = $pdo->prepare("SELECT id_user FROM users WHERE (LOWER(username) = LOWER(?) OR (email != '' AND LOWER(email) = LOWER(?))) AND id_user != ? LIMIT 1");
            $stmt_dup->execute([$username, $email, $id]);
            if ($stmt_dup->fetch()) {
                set_flash('danger', 'Username atau Email sudah terdaftar pada akun lain!');
                redirect('admin.php?page=profil');
                exit;
            }

            $update_password_hash = null;

            if (!empty($old_password) || !empty($new_password) || !empty($confirm_password)) {
                if (empty($old_password)) {
                    set_flash('danger', 'Silakan masukkan Password Lama Anda untuk mengonfirmasi perubahan password!');
                    redirect('admin.php?page=profil');
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
                    redirect('admin.php?page=profil');
                    exit;
                }

                if (empty($new_password) || empty($confirm_password)) {
                    set_flash('danger', 'Password Baru dan Konfirmasi Password wajib diisi!');
                    redirect('admin.php?page=profil');
                    exit;
                }

                if ($new_password !== $confirm_password) {
                    set_flash('danger', 'Konfirmasi Password Baru tidak cocok dengan Password Baru!');
                    redirect('admin.php?page=profil');
                    exit;
                }

                if (function_exists('validate_account_creation')) {
                    $val_p = validate_account_creation($fullname, $username, $new_password, $email, $id);
                    if (!$val_p['status'] && str_contains(strtolower($val_p['message']), 'password')) {
                        set_flash('danger', $val_p['message']);
                        redirect('admin.php?page=profil');
                        exit;
                    }
                } else {
                    if (strlen($new_password) < 6) {
                        set_flash('danger', 'Password minimal harus 6-8 karakter!');
                        redirect('admin.php?page=profil');
                        exit;
                    }
                    if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[\W_]/', $new_password)) {
                        set_flash('danger', 'Password baru wajib kombinasi Huruf Besar (A-Z), Huruf Kecil (a-z), Angka (0-9), dan Simbol Khusus (@, #, !, dll)!');
                        redirect('admin.php?page=profil');
                        exit;
                    }
                }

                $update_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            }

            if ($update_password_hash) {
                $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, phone = ?, password = ? WHERE id_user = ?");
                $stmt->execute([$fullname, $username, $email, $phone, $update_password_hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, phone = ? WHERE id_user = ?");
                $stmt->execute([$fullname, $username, $email, $phone, $id]);
            }

            if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
                $dest = __DIR__ . '/../asset/image/profile_' . $id . '.' . $ext;
                $oldFiles = glob(__DIR__ . '/../asset/image/profile_' . $id . '.*');
                foreach ($oldFiles as $f) { if (is_file($f)) unlink($f); }
                move_uploaded_file($_FILES['foto_profil']['tmp_name'], $dest);
            }
            $_SESSION['username'] = $username;
            $_SESSION['fullname'] = $fullname;
            set_flash('success', 'Profil berhasil diperbarui!');
            redirect('admin.php?page=profil');
            exit;
        }
        // ================= BARBER ACTIONS =================
        elseif ($type === 'call') {
            $antrian_id = (int)($_POST['antrian_id'] ?? 0);
            $user_id = $_SESSION['user_id'];
            $stmt_b = $pdo->prepare("SELECT * FROM barber WHERE user_id = ? OR id = ? LIMIT 1");
            $stmt_b->execute([$user_id, $user_id]);
            $barber = $stmt_b->fetch(PDO::FETCH_ASSOC);
            $barber_id = $barber['id'] ?? null;
            $stmt = $pdo->prepare("UPDATE antrian SET status_antrean = 'serving', barber_id = ?, served_by_user_id = ? WHERE id = ?");
            $stmt->execute([$barber_id, $user_id, $antrian_id]);
            set_flash('success', 'Pelanggan berhasil dipanggil!');
        } 
        elseif ($type === 'skip') {
            $antrian_id = (int)($_POST['antrian_id'] ?? 0);
            $user_id = $_SESSION['user_id'];
            $stmt_b = $pdo->prepare("SELECT * FROM barber WHERE user_id = ? OR id = ? LIMIT 1");
            $stmt_b->execute([$user_id, $user_id]);
            $barber = $stmt_b->fetch(PDO::FETCH_ASSOC);
            $barber_id = $barber['id'] ?? null;
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE antrian SET status_antrean = 'skipped' WHERE id = ?");
            $stmt->execute([$antrian_id]);
            $stmtNext = $pdo->prepare("SELECT id FROM antrian WHERE status_antrean = 'waiting' AND DATE(waktu_dibuat) = CURDATE() AND (barber_id = ? OR barber_id IS NULL) ORDER BY id ASC LIMIT 1");
            $stmtNext->execute([$barber_id]);
            $nextQueue = $stmtNext->fetch(PDO::FETCH_ASSOC);
            if ($nextQueue) {
                $stmtCall = $pdo->prepare("UPDATE antrian SET status_antrean = 'serving', barber_id = ?, served_by_user_id = ? WHERE id = ?");
                $stmtCall->execute([$barber_id, $user_id, $nextQueue['id']]);
                set_flash('warning', 'Antrean dilewati. Antrean berikutnya otomatis dipanggil.');
            } else {
                set_flash('warning', 'Antrean berhasil dilewati (Skip). Tidak ada antrean berikutnya.');
            }
            $pdo->commit();
        }
        elseif ($type === 'finish_service') {
            $antrian_id = (int)($_POST['antrian_id'] ?? 0);
            $pdo->beginTransaction();
            $stmt1 = $pdo->prepare("UPDATE antrian SET status_antrean = 'payment' WHERE id = ?");
            $stmt1->execute([$antrian_id]);
            $pdo->commit();
            set_flash('success', 'Layanan selesai! Menunggu pelanggan memilih metode pembayaran.');
        }
        elseif ($type === 'confirm_paid') {
            $antrian_id = (int)($_POST['antrian_id'] ?? 0);
            $pdo->beginTransaction();
            $stmt_cek = $pdo->prepare("SELECT id FROM transaksi WHERE antrian_id = ?");
            $stmt_cek->execute([$antrian_id]);
            if (!$stmt_cek->fetch()) {
                $total_harga = (float)($_POST['total_harga'] ?? 0);
                $stmt2 = $pdo->prepare("INSERT INTO transaksi (antrian_id, total_harga, status_pembayaran, metode_pembayaran, waktu_bayar) VALUES (?, ?, 'lunas', 'Cash', NOW())");
                $stmt2->execute([$antrian_id, $total_harga]);

                $stmt_q = $pdo->prepare("SELECT no_antrean FROM antrian WHERE id = ? LIMIT 1");
                $stmt_q->execute([$antrian_id]);
                $q_info = $stmt_q->fetch(PDO::FETCH_ASSOC);
                $no_antrean = $q_info ? $q_info['no_antrean'] : "#$antrian_id";

                if (function_exists('create_admin_notification')) {
                    create_admin_notification(
                        'new_transaction',
                        'Transaksi Baru Dikonfirmasi',
                        "Pembayaran Cash Rp " . number_format($total_harga, 0, ',', '.') . " untuk antrean {$no_antrean} telah dikonfirmasi!",
                        'admin.php?page=transaksi'
                    );
                }
            }
            $stmt1 = $pdo->prepare("UPDATE antrian SET status_antrean = 'review' WHERE id = ?");
            $stmt1->execute([$antrian_id]);
            $pdo->commit();
            set_flash('success', 'Pembayaran Dikonfirmasi! Struk dapat dicetak, dan pelanggan diminta memberi ulasan.');
        }
    } catch (PDOException $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    
    // Redirect back to the same page
    $redirect_page = $_POST['current_page'] ?? 'dashboard';
    redirect("admin.php?page=$redirect_page");
    exit;
}

// Fetch Data Master
$barbers = $pdo->query("SELECT * FROM barber ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$layanan = $pdo->query("SELECT * FROM layanan ORDER BY harga DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$transaksi = $pdo->query("SELECT t.*, a.no_antrean, u.username as pelanggan FROM transaksi t JOIN antrian a ON t.antrian_id = a.id LEFT JOIN users u ON a.pelanggan_id = u.id_user ORDER BY t.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT * FROM users ORDER BY id_user DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch current logged in user
$session_user_id = $_SESSION['user_id'] ?? 0;
$stmt_curr_u = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt_curr_u->execute([$session_user_id]);
$current_user = $stmt_curr_u->fetch(PDO::FETCH_ASSOC);

// Fetch Stats for Dashboard
$total_layanan = count($layanan);
$total_transaksi = count($transaksi);
$total_users = count($users);
$total_barbers = count($barbers);

// Sales Metrics Connected to Database
$sales_total_val = (float)$pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE status_pembayaran = 'lunas'")->fetchColumn();
$sales_today_val = (float)$pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE status_pembayaran = 'lunas' AND DATE(waktu_bayar) = CURDATE()")->fetchColumn();
$sales_today_trx_count = (int)$pdo->query("SELECT COUNT(*) FROM transaksi WHERE status_pembayaran = 'lunas' AND DATE(waktu_bayar) = CURDATE()")->fetchColumn();

$sales_this_week = (float)$pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE status_pembayaran = 'lunas' AND YEARWEEK(waktu_bayar, 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn();
$sales_last_week = (float)$pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE status_pembayaran = 'lunas' AND YEARWEEK(waktu_bayar, 1) = YEARWEEK(CURDATE(), 1) - 1")->fetchColumn();

$week_ratio = 0;
if ($sales_last_week > 0) {
    $week_ratio = round((($sales_this_week - $sales_last_week) / $sales_last_week) * 100);
}

$sales_yesterday = (float)$pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE status_pembayaran = 'lunas' AND DATE(waktu_bayar) = SUBDATE(CURDATE(), 1)")->fetchColumn();
$day_ratio = 0;
if ($sales_yesterday > 0) {
    $day_ratio = round((($sales_today_val - $sales_yesterday) / $sales_yesterday) * 100);
}

// Visits & Daily Revenue Metrics Connected to Database
$total_visits = (int)$pdo->query("SELECT COUNT(*) FROM kunjungan_website")->fetchColumn();
$today_visits = (int)$pdo->query("SELECT COUNT(*) FROM kunjungan_website WHERE DATE(waktu_kunjungan) = CURDATE()")->fetchColumn();
if ($today_visits == 0) {
    $today_visits = (int)$pdo->query("SELECT COUNT(*) FROM kunjungan_website WHERE DATE(waktu_kunjungan) = (SELECT MAX(DATE(waktu_kunjungan)) FROM kunjungan_website)")->fetchColumn();
}
$avg_daily_revenue = (float)$pdo->query("SELECT COALESCE(AVG(daily_total), 0) FROM (SELECT SUM(total_harga) as daily_total FROM transaksi WHERE status_pembayaran = 'lunas' GROUP BY DATE(waktu_bayar)) t")->fetchColumn();

// Transaksi & Conversion Connected to Database
$total_transaksi_lunas = (int)$pdo->query("SELECT COUNT(*) FROM transaksi WHERE status_pembayaran = 'lunas'")->fetchColumn();
$total_antrean_count = (int)$pdo->query("SELECT COUNT(*) FROM antrian")->fetchColumn();
$conversion_rate = $total_antrean_count > 0 ? round(($total_transaksi_lunas / $total_antrean_count) * 100, 1) : 100.0;

// Antrean Hari Ini Metrics Connected to Database
$today_antrian_total = (int)$pdo->query("SELECT COUNT(*) FROM antrian WHERE DATE(waktu_dibuat) = CURDATE()")->fetchColumn();
$today_antrian_waiting = (int)$pdo->query("SELECT COUNT(*) FROM antrian WHERE DATE(waktu_dibuat) = CURDATE() AND status_antrean = 'waiting'")->fetchColumn();
$today_antrian_serving = (int)$pdo->query("SELECT COUNT(*) FROM antrian WHERE DATE(waktu_dibuat) = CURDATE() AND status_antrean = 'serving'")->fetchColumn();
$today_antrian_completed = (int)$pdo->query("SELECT COUNT(*) FROM antrian WHERE DATE(waktu_dibuat) = CURDATE() AND status_antrean IN ('completed', 'review')")->fetchColumn();

// Users & Barbers Connected to Database
$total_users_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_barbers_active = (int)$pdo->query("SELECT COUNT(*) FROM barber WHERE status = 'aktif' OR status = 'Aktif'")->fetchColumn();
$total_layanan_count = (int)$pdo->query("SELECT COUNT(*) FROM layanan")->fetchColumn();

// Fetch Bar chart sparkline data for last 5 days
$trx_bars_data = $pdo->query("
    SELECT DATE_FORMAT(d.dt, '%Y-%m-%d') as tgl, COALESCE(t.cnt, 0) as total
    FROM (
        SELECT CURDATE() - INTERVAL (a.a) DAY as dt
        FROM (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) a
    ) d
    LEFT JOIN (
        SELECT DATE(waktu_bayar) as dt, COUNT(*) as cnt
        FROM transaksi
        WHERE status_pembayaran = 'lunas' AND waktu_bayar >= CURDATE() - INTERVAL 4 DAY
        GROUP BY DATE(waktu_bayar)
    ) t ON d.dt = t.dt
    ORDER BY d.dt ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Modal Detail Queries
$modal_pay_methods = $pdo->query("SELECT COALESCE(NULLIF(metode_pembayaran, ''), 'Cash') as metode, COUNT(*) as count_trx, SUM(total_harga) as total_rev FROM transaksi WHERE status_pembayaran = 'lunas' GROUP BY metode ORDER BY total_rev DESC")->fetchAll(PDO::FETCH_ASSOC);
$modal_top_layanan = $pdo->query("SELECT l.nama_layanan, l.harga, COUNT(t.id) as count_trx, SUM(t.total_harga) as total_rev FROM transaksi t JOIN antrian a ON t.antrian_id = a.id JOIN layanan l ON a.layanan_id = l.id WHERE t.status_pembayaran = 'lunas' GROUP BY l.id ORDER BY total_rev DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$modal_visits_daily = $pdo->query("SELECT DATE(waktu_kunjungan) as tgl, COUNT(*) as jml FROM kunjungan_website GROUP BY DATE(waktu_kunjungan) ORDER BY tgl DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$modal_revenue_daily = $pdo->query("SELECT DATE(waktu_bayar) as tgl, SUM(total_harga) as total FROM transaksi WHERE status_pembayaran = 'lunas' GROUP BY DATE(waktu_bayar) ORDER BY tgl DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$modal_queue_status = $pdo->query("SELECT status_antrean, COUNT(*) as jml FROM antrian GROUP BY status_antrean")->fetchAll(PDO::FETCH_KEY_PAIR);
$modal_barbers_detail = $pdo->query("SELECT b.*, u.username FROM barber b LEFT JOIN users u ON b.user_id = u.id_user ORDER BY b.id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch WA Key
$wa_key = '';
if (file_exists(__DIR__ . '/../config/wa_config.json')) {
    $wa_conf = json_decode(file_get_contents(__DIR__ . '/../config/wa_config.json'), true);
    $wa_key = $wa_conf['api_key'] ?? '';
}

$page = $_GET['page'] ?? 'dashboard';

// Fetch Monthly Revenue Data
$monthlyRevenueQuery = "
    SELECT 
        MONTH(t.waktu_bayar) as bulan,
        SUM(t.total_harga) as total
    FROM transaksi t
    WHERE YEAR(t.waktu_bayar) = YEAR(CURDATE())
      AND t.status_pembayaran = 'lunas'
    GROUP BY MONTH(t.waktu_bayar)
    ORDER BY bulan ASC
";
$monthlyRevenueStmt = $pdo->query($monthlyRevenueQuery);
$monthlyRevenueRaw  = $monthlyRevenueStmt->fetchAll(PDO::FETCH_ASSOC);
$bulanNama = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$monthlyRevenue = array_fill(0, 12, 0);
foreach ($monthlyRevenueRaw as $row) {
    $monthlyRevenue[(int)$row['bulan'] - 1] = (float)$row['total'];
}

// Fetch Data for Charts
$chartDataLayanan = [];
if ($page === 'layanan') {
    $stmt3 = $pdo->query("SELECT l.nama_layanan, COUNT(t.id) as c FROM transaksi t JOIN antrian a ON t.antrian_id = a.id JOIN layanan l ON a.layanan_id = l.id GROUP BY l.id");
    $chartDataLayanan = $stmt3->fetchAll(PDO::FETCH_ASSOC);
}

$chartDataTransaksi = [];
$chartDataLayananTransaksi = [];
if ($page === 'transaksi') {
    $stmtChartT = $pdo->query("SELECT metode_pembayaran, COUNT(*) as c FROM transaksi GROUP BY metode_pembayaran");
    $chartDataTransaksi = $stmtChartT->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtChartLT = $pdo->query("SELECT l.nama_layanan, COUNT(t.id) as c FROM transaksi t JOIN antrian a ON t.antrian_id = a.id JOIN layanan l ON a.layanan_id = l.id GROUP BY l.id ORDER BY c DESC");
    $chartDataLayananTransaksi = $stmtChartLT->fetchAll(PDO::FETCH_ASSOC);
}

$chartDataAkun = [];
if ($page === 'akun') {
    $stmtChartA = $pdo->query("SELECT role, COUNT(*) as c FROM users GROUP BY role");
    $chartDataAkun = $stmtChartA->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Data for Antrean Module
if ($page === 'antrean') {
    $current_serving = get_current_serving_queue();
    $active_queues   = get_active_queues();
}

// Fetch Data for Barber Module
$barber_queues = [];
$total_b_waiting = 0;
$total_b_served = 0;
if ($page === 'barber') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $stmt_b = $pdo->prepare("SELECT * FROM barber WHERE user_id = ? OR id = ? LIMIT 1");
    $stmt_b->execute([$user_id, $user_id]);
    $barber = $stmt_b->fetch(PDO::FETCH_ASSOC);
    $barber_id = $barber['id'] ?? null;

    $today = date('Y-m-d');
    $query = "SELECT a.*, l.nama_layanan, l.harga, u.username as pelanggan_nama, b.multiplier,
              (SELECT metode_pembayaran FROM transaksi t WHERE t.antrian_id = a.id LIMIT 1) as metode_bayar
              FROM antrian a 
              LEFT JOIN layanan l ON a.layanan_id = l.id 
              LEFT JOIN users u ON a.pelanggan_id = u.id_user
              LEFT JOIN barber b ON a.barber_id = b.id
              WHERE DATE(a.waktu_dibuat) = ? AND (a.barber_id = ? OR a.barber_id IS NULL)
              ORDER BY a.id ASC";
    $stmt_q = $pdo->prepare($query);
    $stmt_q->execute([$today, $barber_id]);
    $barber_queues = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

    foreach ($barber_queues as $q) {
        if ($q['status_antrean'] === 'waiting') $total_b_waiting++;
        if (in_array($q['status_antrean'], ['review', 'completed'])) $total_b_served++;
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Elite Barber</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        adminlte: {
                            sidebar: '#0e0a08',      // Deep dark brown-black
                            bg: '#0a0805',           // Almost black with brown tint
                            card: '#1a1208',         // Very dark warm card
                            primary: '#3d2b1a',      // Dark rich brown
                            success: '#1e3a1e',
                            warning: '#e8d5a3',
                            danger: '#4a1e1e',
                            info: '#1e2a3a',
                            accent: '#c9a03a',       // Gold accent
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_processing, .dataTables_wrapper .dataTables_paginate {
            color: #d4c4a0;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #d4c4a0 !important;
        }
        .dataTables_wrapper .dataTables_filter input {
            background-color: #1a1208; border: 1px solid #5c3d1a; color: #e8d5a3;
        }
    </style>

    <!-- Tabulator CSS & JS -->
    <link href="https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
    <!-- SheetJS for XLSX -->
    <script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>
    <!-- jsPDF for PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script>window.jsPDF = window.jspdf.jsPDF;</script>
    <style>
        /* === PREMIUM BROWN-BLACK THEME === */

        /* Custom Tabulator Dark Theme Styles */
        .tabulator-wrapper {
            background: linear-gradient(135deg, #18120b 0%, #120e06 100%);
            padding: 1.5rem; border-radius: 0.75rem; color: #d4d4d8;
            font-size: 14px; border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 30px rgba(0,0,0,0.5), inset 0 1px 0 rgba(245,158,11,0.08);
        }
        .tabulator {
            border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 0.5rem;
            background-color: #120e06; overflow: hidden;
        }
        .tabulator .tabulator-header {
            background: linear-gradient(135deg, #2a1c0a 0%, #1e1408 100%) !important;
            color: #fde68a; border-bottom: 2px solid rgba(245, 158, 11, 0.2); font-weight: 600;
        }
        .tabulator .tabulator-header .tabulator-col {
            background: linear-gradient(135deg, #2a1c0a 0%, #1e1408 100%) !important;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }
        .tabulator-col-title {
            padding: 0.875rem 1.25rem !important; font-size: 0.875rem;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: #f59e0b; background: transparent !important;
        }
        .tabulator-cell { padding: 0.875rem 1.25rem !important; display: flex; align-items: center; }
        .tabulator-row {
            background-color: #120e06; border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #d4d4d8 !important;
            transition: all 0.2s ease;
        }
        .tabulator-row:nth-child(even) { background-color: #18120b; }
        .tabulator-row:hover {
            background: linear-gradient(90deg, #3d2b1a 0%, #2a1c0a 100%) !important;
            border-left: 3px solid #f59e0b;
            box-shadow: inset 0 0 20px rgba(245,158,11,0.06);
        }
        .tabulator-footer {
            background: linear-gradient(135deg, #2a1c0a 0%, #1e1408 100%) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08); color: #d4d4d8; padding: 0.75rem 1rem;
        }
        .tabulator-page {
            background-color: #18120b !important; color: #d4d4d8 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important; padding: 0.25rem 0.5rem;
            border-radius: 0.25rem; margin: 0 0.125rem; transition: all 0.2s;
        }
        .tabulator-page:not(.disabled):hover {
            background: linear-gradient(135deg, #3d2b1a, #2a1c0a) !important;
            color: #fde68a !important; border-color: #f59e0b !important;
        }
        .tabulator-page.active {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            color: #0e0a08 !important; border-color: #f59e0b !important;
            font-weight: 700;
        }
        .tabulator-page.disabled { opacity: 0.5; cursor: not-allowed; }
        .tabulator-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .tabulator-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #3d2b1a, #2a1c0a);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.375rem;
            color: #fde68a; cursor: pointer; display: inline-flex; align-items: center;
            gap: 0.5rem; font-size: 13px; transition: all 0.25s;
        }
        .tabulator-btn:hover {
            background: linear-gradient(135deg, #5c3d1a, #3d2b1a);
            border-color: #f59e0b; color: #f59e0b;
            box-shadow: 0 0 12px rgba(245,158,11,0.2);
        }
        .tabulator-search {
            padding: 0.4rem 0.75rem; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 4px;
            outline: none; width: 250px; background: #120e06; color: #fde68a;
            transition: border-color 0.2s;
        }
        .tabulator-search:focus { border-color: #f59e0b; box-shadow: 0 0 0 2px rgba(245,158,11,0.15); }
        .tabulator-search::placeholder { color: #a1a1aa; }

        /* Fix table cell text colors */
        .tabulator-row .text-white { color: #fde68a !important; }
        .tabulator-row .text-zinc-400 { color: #d4d4d8 !important; }

        /* Custom Luxury Scrollbar for Cards */
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: rgba(18, 14, 6, 0.6); border-radius: 8px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.35); border-radius: 8px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(212, 175, 55, 0.7); }

        /* Smooth Table Loading */
        .tabulator { opacity: 0; transition: opacity 0.5s ease-in-out; }
        .tabulator.table-loaded { opacity: 1; }

        /* ============ SIDEBAR ============ */
        #sidebar {
            background: linear-gradient(180deg, #0e0a08 0%, #120e06 40%, #0a0603 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-x: hidden;
        }
        #brand-logo-container {
            background: linear-gradient(135deg, #1e1408 0%, #2a1c0a 100%);
            border-bottom: 1px solid #4a3020;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #brand-icon { transition: margin 0.3s ease; }
        #brand-text { transition: opacity 0.2s, max-width 0.3s; max-width: 250px; white-space: nowrap; overflow: hidden; }

        /* Sidebar nav links */
        #sidebar nav a {
            position: relative; transition: all 0.25s ease;
            white-space: nowrap; overflow: hidden;
            border: 1px solid transparent;
            border-radius: 0.5rem;
        }
        #sidebar nav a::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 3px; background: linear-gradient(180deg, #c9a03a, #8a6010);
            border-radius: 2px; opacity: 0; transition: opacity 0.25s ease;
        }
        #sidebar nav a:hover {
            background: linear-gradient(90deg, rgba(61,43,26,0.9) 0%, rgba(42,28,10,0.6) 100%) !important;
            border-color: rgba(90,60,26,0.6);
            color: #e8d5a3 !important;
            box-shadow: 0 2px 12px rgba(0,0,0,0.3), inset 0 1px 0 rgba(201,160,58,0.08);
        }
        #sidebar nav a:hover::before { opacity: 1; }
        #sidebar nav a:hover i { transform: scale(1.15); color: #c9a03a; }
        #sidebar nav a i { transition: all 0.25s ease; }

        /* Active sidebar link */
        #sidebar nav a.active-link,
        #sidebar nav a.bg-adminlte-primary {
            background: rgba(245, 158, 11, 0.15) !important;
            border-color: rgba(245, 158, 11, 0.35) !important;
            color: #F59E0B !important;
        }
        #sidebar nav a.active-link i,
        #sidebar nav a.bg-adminlte-primary i {
            color: #F59E0B !important;
        }
        #sidebar nav a.active-link::before,
        #sidebar nav a.bg-adminlte-primary::before { opacity: 1; background: #F59E0B; }

        #sidebar nav span, #sidebar nav p { transition: opacity 0.2s, max-width 0.3s; max-width: 250px; overflow: hidden; white-space: nowrap; }
        #sidebar nav p { color: #6b4c20 !important; }

        /* Minimized State */
        #sidebar.w-20 #brand-logo-container { padding-left: 0; padding-right: 0; justify-content: center; }
        #sidebar.w-20 #brand-icon { margin-right: 0; }
        #sidebar.w-20 #brand-text { opacity: 0; max-width: 0; margin: 0; }
        #sidebar.w-20 nav a { justify-content: center; padding-left: 0; padding-right: 0; gap: 0; }
        #sidebar.w-20 nav span, #sidebar.w-20 nav p { opacity: 0; max-width: 0; padding: 0; margin: 0; border: none; }

        /* ============ PAGE TRANSITION ============ */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-transition { animation: fadeSlideUp 0.4s ease-out forwards; }

        /* ============ CARDS ============ */
        .stat-card {
            background: linear-gradient(135deg, #1e1408 0%, #120e06 100%);
            border: 1px solid #3d2b1a;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-color: #c9a03a;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 0 20px rgba(201,160,58,0.1);
            transform: translateY(-2px);
        }

        /* ============ SCROLLBAR ============ */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #0e0a08; }
        ::-webkit-scrollbar-thumb { background: #3d2b1a; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #c9a03a; }
    </style>
</head>
<body class="text-amber-50 font-sans antialiased overflow-x-hidden flex h-screen">
    <!-- Premium Brown-Black Gradient Background -->
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: linear-gradient(135deg, #0e0a08 0%, #120e06 30%, #1a0e04 60%, #0a0603 100%);"></div>
    <div class="fixed inset-0 z-[-1] pointer-events-none" style="background: radial-gradient(ellipse 80% 60% at 70% 20%, rgba(90,50,15,0.15) 0%, transparent 60%), radial-gradient(ellipse 60% 40% at 20% 80%, rgba(60,30,5,0.1) 0%, transparent 50%);"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-adminlte-sidebar h-full flex flex-col shadow-xl flex-shrink-0 transition-all duration-300">
        <script>
            if(localStorage.getItem('sidebarMinimized') === 'true') {
                document.getElementById('sidebar').classList.replace('w-64', 'w-20');
            }
        </script>
        <!-- Brand Logo -->
        <div id="brand-logo-container" class="h-16 flex items-center px-6 overflow-hidden" style="border-bottom: 1px solid #3a2510;">
            <span id="brand-icon" class="text-2xl mr-3 shrink-0">💈</span>
            <span id="brand-text" class="text-xl font-bold tracking-tight whitespace-nowrap" style="color:#e8d5a3;">Dashboard <span class="font-normal" style="color:#8a6030;">Admin</span></span>
        </div>
        
        <!-- Sidebar Menu -->
        <div class="flex-1 overflow-y-auto py-4">
            <nav class="flex flex-col gap-1 px-3">
                <a href="?page=dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= ($page === 'dashboard' || empty($page)) ? 'bg-adminlte-primary text-amber-200 mt-4' : 'text-stone-400 hover:text-amber-200 mt-4' ?>">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span>Admin</span>
                </a>
                <a href="?page=antrean" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'antrean' ? 'bg-adminlte-primary text-amber-200 mt-1' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white mt-1' ?>">
                    <i data-lucide="monitor" class="w-5 h-5"></i>
                    <span>Antrean</span>
                </a>
                
                <p class="px-3 text-xs font-semibold uppercase tracking-wider mb-2 mt-4" style="color:#5c3d1a;">Kelola Data</p>
                <a href="?page=layanan" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= $page === 'layanan' ? 'bg-adminlte-primary text-amber-200' : 'text-stone-400 hover:text-amber-200' ?>">
                    <i data-lucide="scissors" class="w-5 h-5"></i>
                    <span>Layanan</span>
                </a>
                <a href="?page=transaksi" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'transaksi' ? 'bg-adminlte-primary text-amber-200' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?>">
                    <i data-lucide="receipt-text" class="w-5 h-5"></i>
                    <span>Transaksi</span>
                </a>
                <a href="?page=akun" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'akun' ? 'bg-adminlte-primary text-amber-200' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?>">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    <span>Akun</span>
                </a>


                <p class="px-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2 mt-4">Sistem</p>
                <a href="?page=pengaturan" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'pengaturan' ? 'bg-adminlte-primary text-amber-200' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?>">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                    <span>Pengaturan WA</span>
                </a>

                <p class="px-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2 mt-4">Lainnya</p>
                <a href="?page=profil" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'profil' ? 'bg-adminlte-primary text-amber-200' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?>">
                    <i data-lucide="user" class="w-5 h-5"></i>
                    <span>Profil</span>
                </a>
                <a href="../auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-400 hover:bg-red-400/10 hover:text-red-300 transition-colors mt-1">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Top Navbar -->
        <header class="h-16 flex items-center justify-between px-6 shadow-lg z-10 shrink-0" style="background: linear-gradient(90deg, #1a1008 0%, #110d06 50%, #1a1008 100%); border-bottom: 1px solid rgba(90,55,15,0.4);">
            <div class="flex items-center gap-4">
                <button id="sidebar-toggle" class="transition-colors hover:text-amber-400" style="color:#8a6030;">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="text-xl font-semibold text-white capitalize">
                    <?= $page === 'dashboard' ? 'Dashboard Overview' : str_replace('_', ' ', $page) ?>
                </h1>
                
                <a href="../index.php" class="hidden sm:flex items-center gap-1.5 text-zinc-400 hover:text-blue-400 transition-colors duration-300 text-sm font-medium ml-4 group" title="Ke Home">
                    <i data-lucide="home" class="w-4 h-4 group-hover:scale-110 transition-transform duration-300"></i>
                    <span class="group-hover:underline underline-offset-4">Home</span>
                </a>
            </div>
            <div class="flex items-center gap-4">
                <div id="realtime-clock" class="hidden md:block text-sm text-zinc-300 font-medium tracking-wide"></div>

                <!-- NOTIFICATION BELL & DROPDOWN -->
                <div class="relative" id="admin-notif-container">
                    <button type="button" id="notif-bell-btn" onclick="toggleNotifDropdown(event)" class="relative px-3 py-2 text-amber-300 hover:text-amber-200 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/40 rounded-xl transition-all shadow-lg focus:outline-none flex items-center gap-2 cursor-pointer group" title="Notifikasi Sistem">
                        <i data-lucide="bell" class="w-5 h-5 text-amber-400 group-hover:rotate-12 transition-transform"></i>
                        <span class="text-xs font-bold hidden sm:inline">Notif</span>
                        <span id="notif-badge" class="hidden bg-rose-600 text-white font-extrabold text-xs px-2 py-0.5 rounded-full border border-rose-400 flex items-center justify-center animate-bounce shadow-md">0</span>
                    </button>

                    <!-- Dropdown Content -->
                    <div id="notif-dropdown-menu" class="hidden absolute right-0 mt-3 w-80 sm:w-96 bg-[#18120b] border border-amber-900/50 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-xl transition-all">
                        <div class="p-3.5 border-b border-amber-900/30 bg-[#22180f] flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="bell-ring" class="w-4 h-4 text-amber-400"></i>
                                <span class="font-bold text-sm text-amber-100">Notifikasi Pendaftaran</span>
                            </div>
                            <button type="button" onclick="markAllNotifRead()" class="text-xs text-amber-400 hover:text-amber-300 hover:underline cursor-pointer">Tandai Semua Terbaca</button>
                        </div>
                        <div id="notif-list-container" class="max-h-80 overflow-y-auto divide-y divide-white/5 custom-scroll p-1">
                            <div class="p-4 text-center text-xs text-zinc-400">Memuat notifikasi...</div>
                        </div>
                    </div>
                </div>

                <a href="admin.php?page=profil" class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity" title="Profil Saya">
                    <?php 
                    $nav_avatar_name = !empty($current_user['fullname']) ? urlencode($current_user['fullname']) : urlencode($current_user['username']);
                    $nav_profile_files = glob(__DIR__ . '/../asset/image/profile_' . $_SESSION['user_id'] . '.*');
                    $nav_profile_url = !empty($nav_profile_files) ? '../asset/image/' . basename($nav_profile_files[0]) : "https://ui-avatars.com/api/?name={$nav_avatar_name}&background=random&color=fff&size=64&bold=true";
                    ?>
                    <img src="<?= $nav_profile_url ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover shadow-md border-2 border-zinc-700/50">
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6 page-transition">
            <?php if (function_exists('display_flash')) display_flash(); ?>

            <?php if ($page === 'dashboard' || empty($page)): ?>
            <!-- DASHBOARD METRIC CARDS (LUXURY DARK GOLD THEME CONNECTED TO DB) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Card 1: Antrean Hari Ini -->
                <div class="bg-[#18120b] border border-white/10 hover:border-amber-500/50 rounded-xl p-5 shadow-xl flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/60 group relative overflow-hidden">
                    <div class="relative z-10">
                        <!-- Header -->
                        <div class="flex items-center justify-between text-zinc-300 mb-2">
                            <span class="text-sm font-medium tracking-wide group-hover:text-[#fde68a] transition-colors">Antrean Hari Ini</span>
                            <button type="button" onclick="openCardModal('todayQueueModal')" class="w-6 h-6 rounded-full border border-amber-500/40 flex items-center justify-center text-xs font-serif text-amber-300 hover:text-amber-200 hover:border-amber-400 hover:bg-amber-400/10 cursor-pointer transition-all duration-200" title="Buka Detail Antrean Hari Ini">i</button>
                        </div>
                        <!-- Big Metric Value -->
                        <div class="text-2xl lg:text-3xl font-bold text-white tracking-tight mb-3">
                            <?= number_format($today_antrian_total) ?> <span class="text-sm font-normal text-amber-400/90">Antrean</span>
                        </div>
                        <!-- Status Indicators -->
                        <div class="space-y-1.5 text-xs text-zinc-300 mb-2">
                            <div class="flex items-center justify-between">
                                <span>Menunggu (Waiting)</span>
                                <span class="text-amber-400 font-bold"><?= $today_antrian_waiting ?> Orang</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Sedang Dilayani / Selesai</span>
                                <span class="text-emerald-400 font-bold"><?= $today_antrian_serving + $today_antrian_completed ?> Orang</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Pendapatan Perhari -->
                <div class="bg-[#18120b] border border-white/10 hover:border-amber-500/50 rounded-xl p-5 shadow-xl flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/60 group relative overflow-hidden">
                    <div class="relative z-10">
                        <!-- Header -->
                        <div class="flex items-center justify-between text-zinc-300 mb-1">
                            <span class="text-sm font-medium tracking-wide group-hover:text-[#fde68a] transition-colors">Pendapatan Perhari</span>
                            <button type="button" onclick="openCardModal('dailyRevenueModal')" class="w-6 h-6 rounded-full border border-amber-500/40 flex items-center justify-center text-xs font-serif text-amber-300 hover:text-amber-200 hover:border-amber-400 hover:bg-amber-400/10 cursor-pointer transition-all duration-200" title="Buka Detail Pendapatan Perhari">i</button>
                        </div>
                        <!-- Big Metric Value -->
                        <div class="text-2xl lg:text-3xl font-bold text-white tracking-tight mb-2">
                            Rp <?= number_format($sales_today_val, 0, ',', '.') ?>
                        </div>
                        <!-- Multi-layer Organic Gold/Amber Wave Chart SVG -->
                        <div class="h-14 w-full relative overflow-hidden flex items-end my-1">
                            <svg viewBox="0 0 200 60" class="w-full h-full" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="goldGradCard" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.95"/>
                                        <stop offset="100%" stop-color="#fde68a" stop-opacity="0.25"/>
                                    </linearGradient>
                                    <linearGradient id="amberGradCard" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" stop-color="#d97706" stop-opacity="0.85"/>
                                        <stop offset="100%" stop-color="#b45309" stop-opacity="0.2"/>
                                    </linearGradient>
                                    <linearGradient id="brownGradCard" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" stop-color="#78350f" stop-opacity="0.9"/>
                                        <stop offset="100%" stop-color="#451a03" stop-opacity="0.3"/>
                                    </linearGradient>
                                </defs>
                                <!-- Bottom Warm Brown Layer -->
                                <path d="M 0 55 C 30 50, 50 35, 80 42 C 110 50, 140 45, 170 38 C 185 35, 195 40, 200 42 L 200 60 L 0 60 Z" fill="url(#brownGradCard)"/>
                                <!-- Middle Amber Layer -->
                                <path d="M 0 45 C 25 35, 55 52, 85 45 C 115 38, 145 48, 175 42 C 190 38, 195 45, 200 48 L 200 60 L 0 60 Z" fill="url(#amberGradCard)"/>
                                <!-- Top Gold Wave Layer -->
                                <path d="M 0 40 C 20 22, 45 38, 70 18 C 95 -2, 120 28, 145 12 C 170 -2, 190 18, 200 32 L 200 60 L 0 60 Z" fill="url(#goldGradCard)"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Payments -->
                <div class="bg-[#18120b] border border-white/10 hover:border-amber-500/50 rounded-xl p-5 shadow-xl flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/60 group relative overflow-hidden">
                    <div class="relative z-10">
                        <!-- Header -->
                        <div class="flex items-center justify-between text-zinc-300 mb-1">
                            <span class="text-sm font-medium tracking-wide group-hover:text-[#fde68a] transition-colors">Payments</span>
                            <button type="button" onclick="openCardModal('paymentsModal')" class="w-6 h-6 rounded-full border border-amber-500/40 flex items-center justify-center text-xs font-serif text-amber-300 hover:text-amber-200 hover:border-amber-400 hover:bg-amber-400/10 cursor-pointer transition-all duration-200" title="Buka Detail Payments">i</button>
                        </div>
                        <!-- Big Metric Value -->
                        <div class="text-2xl lg:text-3xl font-bold text-white tracking-tight mb-3">
                            <?= number_format($total_transaksi_lunas) ?>
                        </div>
                        <!-- Vertical Amber Bar Chart Sparkline -->
                        <div class="h-12 w-full flex items-end justify-between gap-2 px-1">
                            <?php 
                            $bar_heights = [45, 95, 80, 50, 75];
                            if (!empty($trx_bars_data)) {
                                $max_val = max(array_column($trx_bars_data, 'total'));
                                if ($max_val > 0) {
                                    $bar_heights = array_map(function($item) use ($max_val) {
                                        return max(30, round(($item['total'] / $max_val) * 95));
                                    }, $trx_bars_data);
                                }
                            }
                            foreach($bar_heights as $idx => $h): 
                            ?>
                            <div class="flex-1 bg-gradient-to-t from-amber-700 via-amber-500 to-amber-400 rounded-sm hover:brightness-125 transition-all duration-200 shadow-sm" style="height: <?= $h ?>%;"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Users & Barber -->
                <div class="bg-[#18120b] border border-white/10 hover:border-amber-500/50 rounded-xl p-5 shadow-xl flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/60 group relative overflow-hidden">
                    <div class="relative z-10">
                        <!-- Header -->
                        <div class="flex items-center justify-between text-zinc-300 mb-1">
                            <span class="text-sm font-medium tracking-wide group-hover:text-[#fde68a] transition-colors">Users & Barber</span>
                            <button type="button" onclick="openCardModal('usersModal')" class="w-6 h-6 rounded-full border border-amber-500/40 flex items-center justify-center text-xs font-serif text-amber-300 hover:text-amber-200 hover:border-amber-400 hover:bg-amber-400/10 cursor-pointer transition-all duration-200" title="Buka Detail Users & Barber">i</button>
                        </div>
                        <!-- Big Metric Value -->
                        <div class="text-2xl lg:text-3xl font-bold text-white tracking-tight mb-2">
                            <?= number_format($total_users_count) ?>
                        </div>
                        <!-- Status Gauge / Activity Sparkline -->
                        <div class="h-12 w-full flex flex-col justify-center gap-1.5 px-1">
                            <div class="flex justify-between items-center text-[11px] text-zinc-300 font-medium">
                                <span>Barber Aktif</span>
                                <span class="text-emerald-400 font-bold"><?= $total_barbers_active ?> Active</span>
                            </div>
                            <div class="w-full bg-zinc-800/90 h-2 rounded-full overflow-hidden p-0.5 border border-zinc-700/50">
                                <div class="bg-gradient-to-r from-amber-500 via-amber-400 to-emerald-400 h-full rounded-full transition-all duration-500 shadow-sm" style="width: 100%;"></div>
                            </div>
                            <div class="flex justify-between items-center text-[10px] text-zinc-300">
                                <span>Kapasitas Layanan</span>
                                <span class="text-amber-300 font-semibold">100% Ready</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- METRIC DETAIL MODALS OVERLAY -->
            <div id="cardModalBackdrop" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden transition-opacity duration-300 items-center justify-center p-4 overflow-y-auto">

                <!-- 1. Today Queue Modal -->
                <div id="todayQueueModal" class="card-modal-content hidden bg-[#18120c] border border-amber-600/40 rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative text-white my-auto">
                    <div class="flex items-center justify-between border-b border-amber-900/40 pb-4 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                                <i data-lucide="list-ordered" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-amber-100">Detail Antrean Hari Ini (<?= date('d M Y') ?>)</h3>
                                <p class="text-xs text-zinc-400">Rincian status antrean pelanggan hari ini</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeCardModal()" class="text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-zinc-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Summary Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                        <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Total Antrean</p>
                            <p class="text-base font-bold text-white"><?= number_format($today_antrian_total) ?></p>
                        </div>
                        <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Menunggu (Waiting)</p>
                            <p class="text-base font-bold text-amber-400"><?= number_format($today_antrian_waiting) ?></p>
                        </div>
                        <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Sedang Dilayani</p>
                            <p class="text-base font-bold text-sky-400"><?= number_format($today_antrian_serving) ?></p>
                        </div>
                        <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Selesai</p>
                            <p class="text-base font-bold text-emerald-400"><?= number_format($today_antrian_completed) ?></p>
                        </div>
                    </div>

                    <div class="text-center pt-2">
                        <a href="admin.php?page=antrean" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 border border-amber-500/40 text-xs font-semibold transition-all">
                            <i data-lucide="external-link" class="w-4 h-4"></i>
                            Buka Halaman Kelola Antrean Selengkapnya
                        </a>
                    </div>
                </div>

                <!-- 2. Daily Revenue Modal -->
                <div id="dailyRevenueModal" class="card-modal-content hidden bg-[#18120c] border border-amber-600/40 rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative text-white my-auto">
                    <div class="flex items-center justify-between border-b border-amber-900/40 pb-4 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                                <i data-lucide="trending-up" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-amber-100">Detail Pendapatan Perhari</h3>
                                <p class="text-xs text-zinc-400">Statistik dan rincian omset harian barbershop</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeCardModal()" class="text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-zinc-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                        <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Hari Ini (<?= date('d M Y') ?>)</p>
                            <p class="text-base font-bold text-emerald-400">Rp <?= number_format($sales_today_val, 0, ',', '.') ?></p>
                            <p class="text-[10px] text-zinc-400 mt-0.5"><?= $sales_today_trx_count ?> Transaksi Lunas</p>
                        </div>
                        <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Kemarin (<?= date('d M Y', strtotime('-1 day')) ?>)</p>
                            <p class="text-base font-bold text-amber-300">Rp <?= number_format($sales_yesterday, 0, ',', '.') ?></p>
                            <p class="text-[10px] text-zinc-400 mt-0.5">Pendapatan Harian Kemarin</p>
                        </div>
                        <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Rata-rata Harian</p>
                            <p class="text-base font-bold text-amber-400">Rp <?= number_format($avg_daily_revenue, 0, ',', '.') ?></p>
                            <p class="text-[10px] text-zinc-400 mt-0.5">Per Hari Aktif</p>
                        </div>
                    </div>

                    <h4 class="text-xs font-semibold text-amber-200 uppercase tracking-wider mb-2">Riwayat Pendapatan Harian (7 Hari Terakhir)</h4>
                    <div class="space-y-2 mb-4">
                        <?php 
                        $max_rev_day = !empty($modal_revenue_daily) ? max(array_column($modal_revenue_daily, 'total')) : 1;
                        foreach($modal_revenue_daily as $rd):
                            $pct_r = round(($rd['total'] / max(1, $max_rev_day)) * 100);
                        ?>
                        <div class="bg-[#100b07] border border-zinc-800/80 rounded-lg p-2.5 flex items-center justify-between text-xs gap-3">
                            <span class="text-zinc-400 w-24 shrink-0"><?= date('d M Y', strtotime($rd['tgl'])) ?></span>
                            <div class="flex-1 bg-zinc-900 h-2 rounded-full overflow-hidden">
                                <div class="bg-gradient-to-r from-amber-500 to-yellow-400 h-full rounded-full" style="width: <?= $pct_r ?>%;"></div>
                            </div>
                            <span class="text-amber-300 font-bold text-right shrink-0">Rp <?= number_format($rd['total'], 0, ',', '.') ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 3. Payments Modal -->
                <div id="paymentsModal" class="card-modal-content hidden bg-[#18120c] border border-blue-600/40 rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative text-white my-auto">
                    <div class="flex items-center justify-between border-b border-blue-900/40 pb-4 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400">
                                <i data-lucide="receipt" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-blue-100">Detail Transaksi & Pembayaran</h3>
                                <p class="text-xs text-zinc-400">Statistik transaksi lunas, status antrean, dan rasio konversi</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeCardModal()" class="text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-zinc-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                        <div class="bg-[#100b07] border border-blue-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Transaksi Lunas</p>
                            <p class="text-base font-bold text-blue-400"><?= number_format($total_transaksi_lunas) ?></p>
                        </div>
                        <div class="bg-[#100b07] border border-blue-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Total Antrean Dibuat</p>
                            <p class="text-base font-bold text-amber-400"><?= number_format($total_antrean_count) ?></p>
                        </div>
                        <div class="bg-[#100b07] border border-blue-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Conversion Rate</p>
                            <p class="text-base font-bold text-emerald-400"><?= $conversion_rate ?>%</p>
                        </div>
                    </div>

                    <h4 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-2">Breakdown Status Antrean</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-5 text-xs">
                        <div class="bg-[#100b07] border border-zinc-800 p-3 rounded-lg text-center">
                            <span class="text-emerald-400 font-bold text-lg block"><?= $modal_queue_status['completed'] ?? 0 ?></span>
                            <span class="text-zinc-400 text-[11px]">Selesai</span>
                        </div>
                        <div class="bg-[#100b07] border border-zinc-800 p-3 rounded-lg text-center">
                            <span class="text-sky-400 font-bold text-lg block"><?= $modal_queue_status['review'] ?? 0 ?></span>
                            <span class="text-zinc-400 text-[11px]">Ulasan</span>
                        </div>
                        <div class="bg-[#100b07] border border-zinc-800 p-3 rounded-lg text-center">
                            <span class="text-amber-400 font-bold text-lg block"><?= $modal_queue_status['payment'] ?? 0 ?></span>
                            <span class="text-zinc-400 text-[11px]">Proses Bayar</span>
                        </div>
                        <div class="bg-[#100b07] border border-zinc-800 p-3 rounded-lg text-center">
                            <span class="text-rose-400 font-bold text-lg block"><?= $modal_queue_status['skipped'] ?? 0 ?></span>
                            <span class="text-zinc-400 text-[11px]">Dilewati</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Users Modal -->
                <div id="usersModal" class="card-modal-content hidden bg-[#18120c] border border-amber-600/40 rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative text-white my-auto">
                    <div class="flex items-center justify-between border-b border-amber-900/40 pb-4 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                                <i data-lucide="users" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-amber-100">Detail Pengguna & Barber</h3>
                                <p class="text-xs text-zinc-400">Rincian user terdaftar berdasarkan role & status tim barber</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeCardModal()" class="text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-zinc-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                        <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Total Users</p>
                            <p class="text-base font-bold text-amber-300"><?= number_format($total_users_count) ?></p>
                        </div>
                        <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Barber Aktif</p>
                            <p class="text-base font-bold text-emerald-400"><?= $total_barbers_active ?> Barber</p>
                        </div>
                        <div class="bg-[#100b07] border border-amber-900/30 rounded-xl p-3.5 text-center">
                            <p class="text-xs text-zinc-400 mb-1">Total Layanan</p>
                            <p class="text-base font-bold text-sky-400"><?= $total_layanan_count ?? $total_layanan ?? 0 ?> Layanan</p>
                        </div>
                    </div>

                    <h4 class="text-xs font-semibold text-amber-200 uppercase tracking-wider mb-2">Tim Barber Saat Ini</h4>
                    <div class="overflow-x-auto mb-3">
                        <table class="w-full text-xs text-left text-zinc-300 border-collapse">
                            <thead>
                                <tr class="border-b border-zinc-800 text-zinc-400 bg-[#100b07]">
                                    <th class="p-2.5 rounded-l-lg">Nama Barber</th>
                                    <th class="p-2.5">Kursi Layanan</th>
                                    <th class="p-2.5 text-center">Status</th>
                                    <th class="p-2.5 text-center rounded-r-lg">Aksi Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/50">
                                <?php foreach($modal_barbers_detail as $mb): 
                                    $is_active = in_array(strtolower($mb['status']), ['aktif']);
                                    $status_badge_class = $is_active 
                                        ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/50' 
                                        : 'bg-rose-950/60 text-rose-400 border border-rose-800/50';
                                ?>
                                <tr class="hover:bg-amber-950/20 transition-colors">
                                    <td class="p-2.5 font-medium text-white"><?= htmlspecialchars($mb['nama']) ?></td>
                                    <td class="p-2.5"><span class="px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-300 border border-amber-500/30 font-semibold text-[11px]"><?= htmlspecialchars($mb['kursi'] ?? 'Kursi A') ?></span></td>
                                    <td class="p-2.5 text-center"><span class="px-2 py-0.5 rounded <?= $status_badge_class ?> text-[11px] capitalize"><?= htmlspecialchars($mb['status']) ?></span></td>
                                    <td class="p-2.5 text-center">
                                        <form method="POST" action="" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status keaktifan barber <?= htmlspecialchars(addslashes($mb['nama'])) ?>?');">
                                            <input type="hidden" name="form_type" value="toggle_barber_status">
                                            <input type="hidden" name="barber_id" value="<?= $mb['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $is_active ? 'Nonaktif' : 'Aktif' ?>">
                                            <?php if ($is_active): ?>
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/15 text-rose-300 hover:bg-rose-500/25 border border-rose-500/30 text-[11px] font-medium transition-all flex items-center gap-1 mx-auto">
                                                    <i data-lucide="power" class="w-3 h-3 text-rose-400"></i> Nonaktifkan
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25 border border-emerald-500/30 text-[11px] font-medium transition-all flex items-center gap-1 mx-auto">
                                                    <i data-lucide="power" class="w-3 h-3 text-emerald-400"></i> Aktifkan
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php
            // Fetch daily customer trend for the last 30 days (All 30 days populated)
            $chartQuery = "
                SELECT DATE(waktu_dibuat) as tanggal, COUNT(*) as jumlah 
                FROM antrian 
                WHERE waktu_dibuat >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
                GROUP BY DATE(waktu_dibuat) 
                ORDER BY DATE(waktu_dibuat) ASC
            ";
            $chartStmt = $pdo->query($chartQuery);
            $chartDataRaw = $chartStmt ? $chartStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $dbDataMap = [];
            foreach ($chartDataRaw as $row) {
                $dbDataMap[$row['tanggal']] = (int)$row['jumlah'];
            }

            $phpLabels = [];
            $phpDataVals = [];
            $phpPeakIndex = 0;
            $phpPeakValue = -1;
            $phpTotal = 0;

            for ($i = 29; $i >= 0; $i--) {
                $tglStr = date('Y-m-d', strtotime("-$i days"));
                $val = $dbDataMap[$tglStr] ?? 0;
                $phpLabels[] = date('d M', strtotime($tglStr));
                $phpDataVals[] = $val;
                $phpTotal += $val;

                if ($val > $phpPeakValue) {
                    $phpPeakValue = $val;
                    $phpPeakIndex = 29 - $i;
                }
            }
            $phpAverage = round($phpTotal / 30, 1);

            // Fetch Top Customers by Total Money Spent (Total Pengeluaran)
            $topSpendingStmt = $pdo->query("
                SELECT 
                    u.id_user, 
                    u.username, 
                    COALESCE(NULLIF(u.fullname, ''), u.username) as nama,
                    COUNT(t.id) as total_transaksi,
                    COALESCE(SUM(t.total_harga), 0) as total_pengeluaran,
                    MAX(t.waktu_bayar) as transaksi_terakhir
                FROM users u
                LEFT JOIN antrian a ON a.pelanggan_id = u.id_user
                LEFT JOIN transaksi t ON t.antrian_id = a.id
                WHERE u.role NOT IN ('admin', 'barber')
                GROUP BY u.id_user
                ORDER BY total_pengeluaran DESC, total_transaksi DESC
                LIMIT 20
            ");
            $topSpendingList = $topSpendingStmt ? $topSpendingStmt->fetchAll(PDO::FETCH_ASSOC) : [];
            ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6 mb-4">
                <!-- Tren Pelanggan Harian (Horizontal Scroll Max 30 Hari) -->
                <div class="p-6 rounded-2xl border shadow-md flex flex-col justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                    <div>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4 pb-3 border-b border-amber-900/30">
                            <h3 class="text-xl font-bold tracking-wide flex items-center gap-2" style="color:#e8d5a3;">
                                <i data-lucide="trending-up" class="w-5 h-5 text-sky-400"></i>
                                Tren Pelanggan (30 Hari Terakhir)
                            </h3>
                            <span class="text-[11px] text-amber-300 bg-amber-950/60 border border-amber-800/40 px-2.5 py-1 rounded-full font-medium flex items-center gap-1 shrink-0">
                                ↔ Geser Kiri / Kanan (Max 30 Hari)
                            </span>
                        </div>
                        <div id="chartScrollContainer" class="overflow-x-auto custom-scroll pb-2">
                            <div style="height: 330px; min-width: 1250px;">
                                <canvas id="adminChart1"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Customer Spending List (Foto Profil & Total Pengeluaran) -->
                <div class="p-6 rounded-2xl border shadow-md flex flex-col justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                    <div>
                        <div class="flex justify-between items-center mb-5 pb-3 border-b border-amber-900/30">
                            <div>
                                <h3 class="text-xl font-bold tracking-wide flex items-center gap-2" style="color:#e8d5a3;">
                                    <i data-lucide="wallet" class="w-5 h-5 text-amber-400"></i>
                                    Top Pengeluaran Pelanggan
                                </h3>
                                <p class="text-xs text-stone-400 mt-0.5">Pelanggan dengan kontribusi transaksi terbesar</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center gap-1">
                                💎 VIP Top Spending
                            </span>
                        </div>

                        <div class="space-y-3.5 max-h-[350px] overflow-y-auto pr-2 custom-scroll">
                            <?php if (empty($topSpendingList)): ?>
                                <div class="text-center text-stone-400 py-8">Belum ada transaksi pelanggan</div>
                            <?php else: ?>
                                <?php 
                                foreach ($topSpendingList as $idx => $cust): 
                                    $rank = $idx + 1;
                                    $formattedPrice = 'Rp ' . number_format($cust['total_pengeluaran'], 0, ',', '.');
                                    $tglTerakhir = !empty($cust['transaksi_terakhir']) ? date('d M Y', strtotime($cust['transaksi_terakhir'])) : 'Belum ada';
                                    
                                    // Check if user has an actual profile photo file in asset/image/
                                    $userPhotoPath = "../asset/image/profile_" . $cust['id_user'] . ".jpg";
                                    $hasRealPhoto = file_exists(__DIR__ . '/' . $userPhotoPath);
                                    $initial = strtoupper(substr($cust['nama'], 0, 1));
                                ?>
                                <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-amber-900/20 transition-colors border border-transparent hover:border-amber-900/30">
                                    <div class="flex items-center gap-3.5">
                                        <!-- Profile Avatar / Initial Badge -->
                                        <div class="relative">
                                            <?php if ($hasRealPhoto): ?>
                                                <img src="<?= $userPhotoPath ?>" alt="<?= htmlspecialchars($cust['nama']) ?>" class="w-11 h-11 rounded-full object-cover ring-2 ring-amber-500/40 shadow-md">
                                            <?php else: ?>
                                                <!-- Default Initial Avatar Circle (Tanpa Paksaan Foto Dummy) -->
                                                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-amber-800 to-stone-900 border border-amber-600/40 flex items-center justify-center text-amber-200 font-bold text-base shadow-md">
                                                    <?= $initial ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold <?= $rank === 1 ? 'bg-amber-400 text-zinc-950 ring-2 ring-amber-300' : ($rank === 2 ? 'bg-slate-300 text-zinc-950' : ($rank === 3 ? 'bg-amber-700 text-white' : 'bg-stone-700 text-stone-300')) ?>">
                                                <?= $rank ?>
                                            </span>
                                        </div>
                                        <!-- Name & Last Transaction Date -->
                                        <div>
                                            <h4 class="font-bold text-sm text-stone-100 flex items-center gap-1.5">
                                                <?= htmlspecialchars($cust['nama']) ?>
                                                <span class="text-xs font-normal text-amber-400/80">(@<?= htmlspecialchars($cust['username']) ?>)</span>
                                            </h4>
                                            <p class="text-xs text-stone-400 mt-0.5 flex items-center gap-2">
                                                <span><?= $cust['total_transaksi'] ?>x Berlayanan</span>
                                                <span class="text-stone-600">•</span>
                                                <span><?= $tglTerakhir ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Total Money Spent -->
                                    <div class="text-right">
                                        <div class="text-sm font-extrabold text-amber-400 tracking-wide">
                                            <?= $formattedPrice ?>
                                        </div>
                                        <span class="text-[10px] text-stone-500 uppercase tracking-wider font-medium">Total Pengeluaran</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    if (document.getElementById('adminChart1')) {
                        const labels = <?php echo json_encode($phpLabels); ?>;
                        const dataVals = <?php echo json_encode($phpDataVals); ?>;
                        const peakIndex = <?php echo $phpPeakIndex; ?>;
                        const averageVal = <?php echo $phpAverage; ?>;
                        
                        const pointColors = dataVals.map((_, i) => i === peakIndex ? '#fde68a' : '#f59e0b');
                        const pointRadii = dataVals.map((_, i) => i === peakIndex ? 7 : 4);
                        const pointHoverRadii = dataVals.map((_, i) => i === peakIndex ? 9 : 6);
                        
                        const peakCalloutPlugin = {
                            id: 'peakCallout',
                            afterDraw: (chart) => {
                                const ctx = chart.ctx;
                                const meta = chart.getDatasetMeta(0);
                                if (!meta || !meta.data || peakIndex < 0 || !meta.data[peakIndex]) return;
                                const point = meta.data[peakIndex];
                                
                                const x = point.x;
                                const y = point.y;
                                
                                ctx.save();

                                let boxX = x + 20;
                                let lineEndX = x + 15;
                                if (boxX + 185 > chart.width) {
                                    boxX = x - 195;
                                    lineEndX = x - 15;
                                }

                                ctx.beginPath();
                                ctx.moveTo(x, y);
                                ctx.lineTo(lineEndX, y - 15);
                                ctx.lineTo(boxX + (boxX < x ? 185 : 0), y - 15);
                                ctx.strokeStyle = '#f59e0b';
                                ctx.lineWidth = 2;
                                ctx.stroke();
                                
                                ctx.fillStyle = '#18120b';
                                ctx.strokeStyle = '#f59e0b';
                                ctx.lineWidth = 2;
                                ctx.beginPath();
                                ctx.roundRect(boxX, y - 32, 185, 26, 6);
                                ctx.fill();
                                ctx.stroke();
                                
                                ctx.fillStyle = '#fafafa';
                                ctx.font = 'bold 12px sans-serif';
                                ctx.fillText(`Puncak: ${dataVals[peakIndex]} orang (${labels[peakIndex]})`, boxX + 8, y - 14);
                                ctx.restore();
                            }
                        };
                        
                        new Chart(document.getElementById('adminChart1'), {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: 'Jumlah Pelanggan',
                                        data: dataVals,
                                        borderColor: '#f59e0b',
                                        backgroundColor: 'rgba(245, 158, 11, 0.18)',
                                        fill: true,
                                        tension: 0.3,
                                        pointBackgroundColor: pointColors,
                                        pointBorderColor: '#18120b',
                                        pointBorderWidth: 2,
                                        pointRadius: pointRadii,
                                        pointHoverRadius: pointHoverRadii,
                                        borderWidth: 2
                                    },
                                    {
                                        label: `Rata-rata Harian (${averageVal})`,
                                        data: Array(dataVals.length).fill(averageVal),
                                        borderColor: '#a1a1aa',
                                        borderDash: [5, 5],
                                        borderWidth: 2,
                                        pointRadius: 0,
                                        pointHoverRadius: 0,
                                        fill: false,
                                        tension: 0
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { labels: { color: '#cbd5e1', usePointStyle: true, boxWidth: 8 } },
                                    tooltip: {
                                        backgroundColor: 'rgba(24, 24, 27, 0.9)',
                                        titleColor: '#fafafa',
                                        bodyColor: '#a1a1aa',
                                        borderColor: 'rgba(56, 189, 248, 0.3)',
                                        borderWidth: 1
                                    }
                                },
                                scales: {
                                    x: {
                                        title: { display: true, text: 'Tanggal', color: '#94a3b8', font: { size: 12, weight: 'bold' } },
                                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                        ticks: { color: '#94a3b8', font: { size: 11 } }
                                    },
                                    y: {
                                        title: { display: true, text: 'Jumlah Pelanggan', color: '#94a3b8', font: { size: 12, weight: 'bold' } },
                                        beginAtZero: true,
                                        min: 0,
                                        grid: { color: 'rgba(255, 255, 255, 0.05)', borderDash: [4, 4] },
                                        ticks: { color: '#94a3b8', stepSize: 1 }
                                    }
                                }
                            },
                            plugins: [peakCalloutPlugin]
                        });

                        // Auto scroll chart container to the right (latest date) on page load
                        const scrollContainer = document.getElementById('chartScrollContainer');
                        if (scrollContainer) {
                            scrollContainer.scrollLeft = scrollContainer.scrollWidth;
                        }
                    }
                });
            </script>

            <!-- PENDAPATAN BULANAN MODULE (Moved to Dashboard) -->
            <?php
                $tahunList = [];
                $yearStmt = $pdo->query("SELECT DISTINCT YEAR(waktu_bayar) as y FROM transaksi WHERE status_pembayaran='lunas' ORDER BY y DESC");
                $tahunList = $yearStmt->fetchAll(PDO::FETCH_COLUMN);
                $selectedTahun = (int)($_GET['tahun'] ?? date('Y'));
                
                $qRevYear = $pdo->prepare("
                    SELECT MONTH(waktu_bayar) as bulan, SUM(total_harga) as total
                    FROM transaksi
                    WHERE YEAR(waktu_bayar) = ? AND status_pembayaran = 'lunas'
                    GROUP BY MONTH(waktu_bayar)
                    ORDER BY bulan ASC
                ");
                $qRevYear->execute([$selectedTahun]);
                $revRows = $qRevYear->fetchAll(PDO::FETCH_ASSOC);
                $revByMonth = array_fill(0, 12, 0);
                foreach ($revRows as $r) { $revByMonth[(int)$r['bulan']-1] = (float)$r['total']; }
                $totalRevYear = array_sum($revByMonth);
                $maxRevMonth = max($revByMonth) ?: 1;
                $bulanNama2 = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                $highlightIdx = array_search(max($revByMonth), $revByMonth);
            ?>
            <!-- Stats row (Pendapatan Bulanan) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6" style="margin-top: 16px;">
                <div class="rounded-2xl p-5 border" style="background:linear-gradient(135deg,#1e1408,#120e06);border-color:#4a3020;">
                    <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#8a6030;">Total Pendapatan <?= $selectedTahun ?></p>
                    <p class="text-3xl font-bold" style="color:#e8d5a3;">Rp <?= number_format($totalRevYear, 0, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl p-5 border" style="background:linear-gradient(135deg,#1e1408,#120e06);border-color:#4a3020;">
                    <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#8a6030;">Bulan Terbaik</p>
                    <p class="text-3xl font-bold" style="color:#c9a03a;"><?= $bulanNama2[$highlightIdx] ?? '-' ?></p>
                    <p class="text-sm mt-1" style="color:#8a6030;">Rp <?= number_format($revByMonth[$highlightIdx] ?? 0, 0, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl p-5 border" style="background:linear-gradient(135deg,#1e1408,#120e06);border-color:#4a3020;">
                    <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#8a6030;">Rata-rata / Bulan</p>
                    <p class="text-3xl font-bold" style="color:#e8d5a3;">Rp <?= number_format($totalRevYear / 12, 0, ',', '.') ?></p>
                </div>
            </div>

            <!-- Chart Card -->
            <div class="rounded-2xl border p-6" style="background:linear-gradient(135deg,#1a1208 0%,#0e0a08 100%);border-color:#3a2510;">
                <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
                    <div>
                        <h2 class="text-2xl font-bold" style="color:#e8d5a3;">Pendapatan Bulanan</h2>
                        <p class="text-sm mt-1" style="color:#8a6030;">Grafik pendapatan per bulan tahun <?= $selectedTahun ?></p>
                    </div>
                    <!-- Year Selector -->
                    <form method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="page" value="dashboard">
                        <select name="tahun" onchange="this.form.submit()" class="rounded-lg px-3 py-1.5 text-sm font-medium border cursor-pointer" style="background:#1a1208;color:#e8d5a3;border-color:#5c3d1a;outline:none;">
                            <?php foreach ($tahunList as $y): ?>
                                <option value="<?= $y ?>" <?= $y == $selectedTahun ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($tahunList)): ?>
                                <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                            <?php endif; ?>
                        </select>
                    </form>
                </div>
                <div style="position:relative; height:420px; width:100%;">
                    <canvas id="chartPendapatanBulanan"></canvas>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const bulanLabels = <?= json_encode($bulanNama2) ?>;
                const revenueData = <?= json_encode(array_values($revByMonth)) ?>;
                const highlightIdx = <?= (int)$highlightIdx ?>;

                // Format to Juta (millions)
                const dataJuta = revenueData.map(v => parseFloat((v / 1000000).toFixed(2)));
                const maxVal = Math.max(...dataJuta);

                // Build colors: highlighted month gets vivid gold, rest dark amber
                const barColors = dataJuta.map((_, i) =>
                    i === highlightIdx
                        ? 'rgba(245, 158, 11, 0.95)'
                        : 'rgba(217, 119, 6, 0.82)'
                );
                const barColorsHover = dataJuta.map((_, i) =>
                    i === highlightIdx
                        ? 'rgba(253, 230, 138, 1)'
                        : 'rgba(245, 158, 11, 1)'
                );

                // Plugin: draw value labels on top of each bar
                const topLabelPlugin = {
                    id: 'topLabel',
                    afterDatasetsDraw(chart) {
                        const { ctx, data } = chart;
                        const meta = chart.getDatasetMeta(0);
                        meta.data.forEach((bar, i) => {
                            const value = data.datasets[0].data[i];
                            if (!value) return;
                            ctx.save();
                            ctx.fillStyle = '#e8d5a3';
                            ctx.font = 'bold 11px Inter, sans-serif';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            const label = value >= 1 ? value.toFixed(1) + 'M' : (value * 1000).toFixed(0) + 'K';
                            ctx.fillText(label, bar.x, bar.y - 4);
                            ctx.restore();
                        });
                    }
                };

                new Chart(document.getElementById('chartPendapatanBulanan'), {
                    type: 'bar',
                    data: {
                        labels: bulanLabels,
                        datasets: [{
                            label: 'Pendapatan (Juta Rp)',
                            data: dataJuta,
                            backgroundColor: barColors,
                            hoverBackgroundColor: barColorsHover,
                            borderRadius: 6,
                            borderSkipped: false,
                            borderWidth: 0,
                            barPercentage: 0.65,
                            categoryPercentage: 0.8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { top: 24, bottom: 4 } },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(14,10,8,0.95)',
                                titleColor: '#c9a03a',
                                bodyColor: '#e8d5a3',
                                borderColor: 'rgba(201,160,58,0.4)',
                                borderWidth: 1,
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    title: (items) => items[0].label,
                                    label: (item) => {
                                        const raw = revenueData[item.dataIndex];
                                        return ' Rp ' + raw.toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: '#a08040', font: { size: 13, weight: '500' } },
                                grid: { display: false },
                                border: { color: 'rgba(90,60,20,0.3)' }
                            },
                            y: {
                                title: { display: true, text: 'Pendapatan (Juta Rp)', color: '#8a6030', font: { size: 12 } },
                                ticks: {
                                    color: '#8a6030',
                                    callback: (v) => v + 'M',
                                    font: { size: 11 }
                                },
                                grid: {
                                    color: 'rgba(90,60,20,0.15)',
                                    borderDash: [4, 4]
                                },
                                border: { display: false },
                                min: 0,
                                suggestedMax: maxVal * 1.2
                            }
                        },
                        animation: {
                            duration: 900,
                            easing: 'easeOutQuart'
                        }
                    },
                    plugins: [topLabelPlugin]
                });
            });
            </script>
            <?php endif; ?>
            <?php if ($page === 'antrean'): ?>
            <!-- DASHBOARD ANTREAN MODULE -->
            <div class="bg-[#1E1B18] rounded-xl border border-white/10 shadow-xl overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-amber-900/30 bg-[#16120c] flex items-center justify-between flex-wrap gap-2">
                    <h3 class="font-bold text-[#e8d5a3] text-base tracking-wide flex items-center gap-2">
                        <i data-lucide="list-ordered" class="w-5 h-5 text-amber-400"></i>
                        Status Antrean Aktif Hari Ini
                    </h3>

                </div>
                <!-- Desktop Table View (hidden on mobile, visible md+) -->
                <div class="hidden md:block overflow-x-auto custom-scroll">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-zinc-900/70 text-zinc-400 text-xs uppercase tracking-wider border-b border-white/10">
                                <th class="px-6 py-4 font-semibold">No. Tiket</th>
                                <th class="px-6 py-4 font-semibold">Pelanggan</th>
                                <th class="px-6 py-4 font-semibold">Layanan</th>
                                <th class="px-6 py-4 font-semibold">Barber</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold">Est. Tunggu</th>
                                <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($active_queues)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-zinc-400 text-sm">Belum ada antrean aktif saat ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($active_queues as $q): ?>
                                    <tr class="hover:bg-amber-900/15 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-amber-400 font-mono font-bold text-lg tracking-wide"><?= htmlspecialchars($q['ticket_number']) ?></span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-white"><?= htmlspecialchars($q['customer_name']) ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <div class="text-zinc-200 font-medium"><?= htmlspecialchars($q['service_name'] ?? 'Standard Cut') ?></div>
                                            <?php 
                                                if (!empty($q['barber_id'])) {
                                                    $mult = (float)($q['barber_multiplier'] ?? 1.0);
                                                    $base = (float)($q['base_price'] ?? 0);
                                                    $final = $base * $mult;
                                                    echo "<div class='text-emerald-400 mt-0.5 font-semibold text-xs'>Rp " . number_format($final, 0, ',', '.') . "</div>";
                                                } else {
                                                    $base = (float)($q['base_price'] ?? 0);
                                                    echo "<div class='text-zinc-400 mt-0.5 text-xs'>Mulai Rp " . number_format($base, 0, ',', '.') . "</div>";
                                                }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-zinc-300 text-sm"><?= htmlspecialchars($q['barber_name'] ?? 'Bebas') ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($q['status'] === 'serving'): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/35 flex items-center gap-1.5 w-fit uppercase">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Melayani
                                                </span>
                                            <?php elseif (in_array($q['status'], ['payment', 'paid'])): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/15 text-blue-400 border border-blue-500/35 flex items-center gap-1.5 w-fit uppercase">
                                                    <span class="w-2 h-2 rounded-full bg-blue-400"></span> Pembayaran
                                                </span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/35 flex items-center gap-1.5 w-fit uppercase">
                                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span> Menunggu
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-zinc-400 text-sm font-mono">
                                            <?= (int)$q['estimated_wait_min'] ?> Menit
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <form method="POST" action="" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus/membatalkan antrean ini?');">
                                                <input type="hidden" name="form_type" value="delete_antrian">
                                                <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/15 hover:bg-rose-500/25 text-rose-300 border border-rose-500/30 text-xs font-medium transition-all flex items-center gap-1 mx-auto" title="Hapus Antrean">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-400"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Vertical Card Stack (No Horizontal Scrolling, Stacks Downwards Vertically) -->
                <div class="block md:hidden p-4 space-y-3">
                    <?php if (empty($active_queues)): ?>
                        <div class="text-center text-zinc-400 py-8 text-sm">Belum ada antrean aktif saat ini.</div>
                    <?php else: ?>
                        <?php foreach ($active_queues as $q): ?>
                        <div class="p-4 rounded-xl border border-white/10 bg-zinc-900/60 transition-all flex flex-col gap-3">
                            <!-- Top Row: Ticket Number & Status -->
                            <div class="flex justify-between items-center border-b border-white/5 pb-2.5">
                                <span class="text-amber-400 font-mono font-black text-xl tracking-wider"><?= htmlspecialchars($q['ticket_number']) ?></span>
                                <?php if ($q['status'] === 'serving'): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/35 flex items-center gap-1.5 uppercase">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Melayani
                                    </span>
                                <?php elseif (in_array($q['status'], ['payment', 'paid'])): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-500/15 text-blue-400 border border-blue-500/35 flex items-center gap-1.5 uppercase">
                                        <span class="w-2 h-2 rounded-full bg-blue-400"></span> Pembayaran
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/35 flex items-center gap-1.5 uppercase">
                                        <span class="w-2 h-2 rounded-full bg-amber-400"></span> Menunggu
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Middle Row: Customer Name & Service -->
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-[11px] text-zinc-400 uppercase font-medium block">Pelanggan</span>
                                    <span class="font-bold text-white"><?= htmlspecialchars($q['customer_name']) ?></span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-zinc-400 uppercase font-medium block">Layanan & Harga</span>
                                    <span class="font-semibold text-zinc-200"><?= htmlspecialchars($q['service_name'] ?? 'Standard Cut') ?></span>
                                    <?php 
                                        if (!empty($q['barber_id'])) {
                                            $mult = (float)($q['barber_multiplier'] ?? 1.0);
                                            $base = (float)($q['base_price'] ?? 0);
                                            $final = $base * $mult;
                                            echo "<span class='text-xs text-emerald-400 font-bold block'>Rp " . number_format($final, 0, ',', '.') . "</span>";
                                        } else {
                                            $base = (float)($q['base_price'] ?? 0);
                                            echo "<span class='text-xs text-zinc-400 block'>Mulai Rp " . number_format($base, 0, ',', '.') . "</span>";
                                        }
                                    ?>
                                </div>
                            </div>

                            <!-- Bottom Row: Barber & Estimated Wait Time -->
                            <div class="flex justify-between items-center text-xs text-zinc-400 pt-2 border-t border-white/5">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="scissors" class="w-3.5 h-3.5 text-amber-400"></i>
                                    <span>Barber: <?= htmlspecialchars($q['barber_name'] ?? 'Bebas') ?></span>
                                </div>
                                <div class="flex items-center gap-1 text-amber-300/90 font-mono">
                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                    <span><?= (int)$q['estimated_wait_min'] ?> Menit Est.</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($page === 'layanan'): ?>
            <!-- LAYANAN MODULE -->
            <div class="mb-6 space-y-6">
                <!-- Donut Chart Layanan -->
                <div class="p-8 bg-adminlte-card rounded-lg border border-zinc-700 shadow-md flex flex-col items-center justify-center">
                    <h4 class="text-white font-semibold mb-6 text-lg tracking-wide uppercase text-zinc-300">Statistik Layanan (Berdasarkan Transaksi)</h4>
                    <div style="width: 100%; max-width: 450px;">
                        <canvas id="donutChartLayanan"></canvas>
                    </div>
                    
                    <div class="mt-6 flex flex-wrap gap-4 justify-center">
                        <?php 
                        $serviceColors = ['#c9a03a', '#e8d5a3', '#8a6030', '#5a3a1a', '#3d2b1a', '#d4af37', '#aa8222', '#6b4c20', '#4a3020', '#2a1c0a'];
                        $idx = 0;
                        // sort largest first
                        $sortedLayanan = $chartDataLayanan;
                        usort($sortedLayanan, function($a, $b) { return $b['c'] - $a['c']; });
                        
                        foreach ($sortedLayanan as $item): 
                            $c = $serviceColors[$idx % count($serviceColors)];
                            $idx++;
                        ?>
                        <div class="bg-zinc-800/50 border border-zinc-700 px-4 py-2 rounded-lg flex items-center gap-2">
                            <span class="text-sm text-zinc-400 capitalize"><?= htmlspecialchars($item['nama_layanan']) ?>:</span>
                            <span class="text-lg font-bold" style="color: <?= $c ?>;"><?= $item['c'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Data Table Layanan -->
                <div class="bg-adminlte-card rounded-lg border border-zinc-700 shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-700 bg-[#30363d] flex justify-between items-center">
                        <h3 class="font-semibold text-white">Daftar Layanan</h3>
                    </div>
                    <div class="tabulator-wrapper">
                        <div class="tabulator-controls flex-wrap gap-3">
                            <div class="flex gap-2 flex-wrap">
                                <button type="button" class="tabulator-btn" style="background-color: #2563eb; color: white; border-color: #3b82f6;" onclick="openAddLayananModal()">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Tambah Layanan
                                </button>
                                <button class="tabulator-btn" onclick="exportData('table-layanan', 'csv')"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> CSV</button>
                                <button class="tabulator-btn" onclick="exportData('table-layanan', 'xlsx')"><i data-lucide="table" class="w-4 h-4"></i> Excel</button>
                                <button class="tabulator-btn" onclick="exportData('table-layanan', 'pdf')"><i data-lucide="file-text" class="w-4 h-4"></i> PDF</button>
                                <button class="tabulator-btn" onclick="exportData('table-layanan', 'print')"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                            </div>
                            <input type="text" class="tabulator-search" id="search-layanan" placeholder="Filter rows...">
                        </div>
                        <table id="table-layanan" class="w-full text-left border-collapse display">
                            <thead>
                                <tr class="bg-zinc-800/50 text-zinc-400 text-sm border-b border-zinc-700">
                                    <th class="px-4 py-3 font-medium text-center" tabulator-field="no" width="70" tabulator-formatter="rownum">No.</th>
                                    <th class="px-6 py-3 font-medium" tabulator-field="layanan" tabulator-formatter="html">Layanan</th>
                                    <th class="px-6 py-3 font-medium" tabulator-field="durasi">Durasi</th>
                                    <th class="px-6 py-3 font-medium" tabulator-field="harga" tabulator-formatter="html">Harga</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-700/50">
                                <?php foreach ($layanan as $l): ?>
                                <tr class="hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-4 py-3 text-zinc-400 text-center font-medium"></td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="text-white font-medium"><?= htmlspecialchars($l['nama_layanan']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-zinc-400"><?= htmlspecialchars($l['durasi'] ?? 0) ?> Menit</td>
                                    <td class="px-6 py-3">
                                        <span class="hidden"><?= sprintf('%010d', $l['harga']) ?></span>
                                        <div class="flex justify-between items-center w-full min-w-[200px]">
                                            <span class="font-medium">Rp <?= number_format($l['harga'], 0, ',', '.') ?></span>
                                            <div class="flex items-center gap-2">
                                            <?php 
                                                $files = glob(__DIR__ . '/../asset/image/layanan_' . $l['id'] . '.*');
                                                $nama_lower = strtolower($l['nama_layanan']);
                                                $default_images = [
                                                    'pridecut' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                                                    'maxcut' => '../asset/image/maxcut.png',
                                                    'hair coloring' => 'https://images.unsplash.com/photo-1620331311520-246422fd82f9?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                                                    'hairlight' => '../asset/image/hairlight.png',
                                                    'full hairlight' => '../asset/image/full_hairlight.png',
                                                    'hair tattoo' => 'https://images.unsplash.com/photo-1593702295094-aea22597af65?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                                                    'shave' => 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                                                    'korean wave' => 'https://images.unsplash.com/photo-1605497788044-5a32c7078486?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'
                                                ];
                                                $img_url = !empty($files) ? '../asset/image/' . basename($files[0]) : ($default_images[$nama_lower] ?? 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80');
                                                $desc_text = !empty(trim($l['deskripsi'] ?? '')) ? htmlspecialchars(str_replace(["\r", "\n"], ["\\r", "\\n"], addslashes($l['deskripsi'])), ENT_QUOTES) : 'Belum ada informasi tambahan untuk layanan ini.';
                                            ?>
                                                <button type="button" onclick="openDescModal('<?= htmlspecialchars(addslashes($l['nama_layanan']), ENT_QUOTES) ?>', '<?= $desc_text ?>', '<?= htmlspecialchars($l['durasi'] ?? 0) ?>', 'Rp <?= number_format($l['harga'], 0, ',', '.') ?>', '<?= $img_url ?>')" class="text-blue-400 hover:text-blue-300 p-1.5 rounded hover:bg-blue-400/10 transition-colors" title="Lihat Lebih Lengkap">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>
                                                <button type="button" onclick="openEditLayananModal(<?= $l['id'] ?>, '<?= htmlspecialchars($l['nama_layanan'], ENT_QUOTES) ?>', <?= $l['harga'] ?>, <?= $l['durasi'] ?? 0 ?>, '<?= htmlspecialchars(str_replace(["\r", "\n"], ["\\r", "\\n"], $l['deskripsi'] ?? ''), ENT_QUOTES) ?>')" class="text-blue-400 hover:text-blue-300 p-1.5 rounded hover:bg-blue-400/10 transition-colors" title="Edit">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="form_type" value="delete_layanan">
                                                    <input type="hidden" name="current_page" value="layanan">
                                                    <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                                    <button type="submit" class="text-red-400 hover:text-red-300 p-1.5 rounded hover:bg-red-400/10 transition-colors" onclick="return confirm('Hapus layanan ini?')" title="Hapus">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const elDonutLayanan = document.getElementById('donutChartLayanan');
                if (!elDonutLayanan) return;
                const ctx = elDonutLayanan.getContext('2d');
                const rawData = <?= json_encode($chartDataLayanan ?? []) ?>;
                
                rawData.sort((a, b) => parseInt(b.c || 0) - parseInt(a.c || 0));

                let finalLabels = [];
                let finalValues = [];

                if (rawData.length > 4) {
                    const topServices = rawData.slice(0, 4);
                    const otherServices = rawData.slice(4);
                    const otherTotal = otherServices.reduce((sum, item) => sum + parseInt(item.c || 0), 0);

                    finalLabels = topServices.map(item => item.nama_layanan);
                    finalValues = topServices.map(item => parseInt(item.c || 0));

                    if (otherTotal > 0) {
                        finalLabels.push('Lainnya');
                        finalValues.push(otherTotal);
                    }
                } else {
                    finalLabels = rawData.map(item => item.nama_layanan);
                    finalValues = rawData.map(item => parseInt(item.c || 0));
                }

                // Dark Gold / Amber / Warm Brown Palette (#F59E0B, #D97706, #B45309, #FDE68A, #78350F)
                const amberPalette = ['#F59E0B', '#D97706', '#B45309', '#FDE68A', '#78350F'];
                const colors = finalLabels.map((_, i) => amberPalette[i % amberPalette.length]);
                const totalCount = finalValues.reduce((a, b) => a + b, 0);

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: finalLabels.length ? finalLabels : ['Belum ada transaksi'],
                        datasets: [{
                            data: finalValues.length ? finalValues : [1],
                            backgroundColor: finalValues.length ? colors : ['#3d2b1a'],
                            borderColor: '#18120b',
                            borderWidth: 2,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: '#d4d4d8',
                                    padding: 14,
                                    font: { family: 'Inter, sans-serif', size: 12, weight: '500' },
                                    usePointStyle: true,
                                    boxWidth: 8
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(24, 18, 11, 0.95)',
                                titleColor: '#fde68a',
                                bodyColor: '#d4d4d8',
                                borderColor: 'rgba(245, 158, 11, 0.4)',
                                borderWidth: 1,
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        const val = context.raw || 0;
                                        const pct = totalCount > 0 ? Math.round((val / totalCount) * 100) : 0;
                                        return ` ${context.label}: ${val} layanan (${pct}%)`;
                                    }
                                }
                            }
                        },
                        animation: { animateScale: true, animateRotate: true, duration: 1500 }
                    }
                });
            });
                </script>
            </div>
            <?php endif; ?>
            <?php if ($page === 'transaksi'): ?>
            <!-- TRANSAKSI MODULE -->
            <div class="space-y-6 mb-6">
                <!-- ============================================================ -->
                <!-- HOLOGRAPHIC CHART SECTION — LUXURY GOLD & BROWN UI          -->
                <!-- ============================================================ -->
                <style>
                /* === HOLOGRAPHIC CONTAINER (LUXURY BROWN THEME) === */
                .holo-chart-section {
                    position: relative;
                    background: linear-gradient(135deg, #1a1208 0%, #120e06 100%);
                    overflow: hidden;
                    padding: 3rem 2rem;
                    border: 1px solid #3a2510;
                    border-radius: 16px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
                }

                /* Ambient warm gold backdrop glow */
                .holo-chart-section::before {
                    content: '';
                    position: absolute;
                    inset: 0;
                    background:
                        radial-gradient(ellipse 60% 40% at 25% 60%, rgba(212, 175, 55, 0.08) 0%, transparent 70%),
                        radial-gradient(ellipse 50% 35% at 75% 40%, rgba(245, 158, 11, 0.06) 0%, transparent 70%),
                        radial-gradient(ellipse 40% 30% at 50% 80%, rgba(232, 213, 163, 0.05) 0%, transparent 70%);
                    pointer-events: none;
                }

                /* Cascading syntax strands left margin */
                .holo-chart-section::after {
                    content: 'def calculate_payment_split():\n    chart.render(\n        animated=True,\n        antigravity=True\n    )\n    return data_source\n        .get_metrics()\n\nclass ChartEngine:\n    def __init__(self):\n        self.mode = "luxury_gold"\n\n    def render(self):\n        return self.vibe_code()\n\n# Vibe Coding Mode\n# LUXURY BROWN ACTIVE\nmatrix = [\n    "METODE_PAYMENT",\n    "LAYANAN_STREAM",\n    "QRIS_0x4A2F",\n    "CASH_0x7B1E",\n]\n\nfor node in matrix:\n    emit(node, float=True)';
                    position: absolute;
                    left: 8px;
                    top: 50%;
                    transform: translateY(-50%);
                    font-family: 'Courier New', monospace;
                    font-size: 9px;
                    line-height: 1.6;
                    color: rgba(232, 213, 163, 0.1);
                    white-space: pre;
                    pointer-events: none;
                    z-index: 0;
                }

                /* Right margin syntax strand */
                .holo-syntax-right {
                    position: absolute;
                    right: 8px;
                    top: 50%;
                    transform: translateY(-50%);
                    font-family: 'Courier New', monospace;
                    font-size: 9px;
                    line-height: 1.6;
                    color: rgba(212, 175, 55, 0.08);
                    white-space: pre;
                    pointer-events: none;
                    z-index: 0;
                    text-align: right;
                }

                /* === FLOATING MODULE CARDS === */
                .holo-module {
                    position: relative;
                    background: rgba(24, 20, 15, 0.85);
                    backdrop-filter: blur(24px);
                    -webkit-backdrop-filter: blur(24px);
                    border: 1px solid rgba(212, 175, 55, 0.25);
                    border-radius: 20px;
                    padding: 2rem;
                    box-shadow:
                        0 0 0 1px rgba(212, 175, 55, 0.08) inset,
                        0 0 40px rgba(212, 175, 55, 0.08),
                        0 25px 60px rgba(0, 0, 0, 0.6);
                    overflow: hidden;
                    z-index: 1;
                    transition: box-shadow 0.4s ease, transform 0.4s ease;
                }
                .holo-module:hover {
                    box-shadow:
                        0 0 0 1px rgba(212, 175, 55, 0.3) inset,
                        0 0 60px rgba(212, 175, 55, 0.15),
                        0 30px 80px rgba(0, 0, 0, 0.7);
                    transform: translateY(-2px);
                }

                /* Holographic corner accents */
                .holo-module::before {
                    content: '';
                    position: absolute;
                    top: 0; left: 0;
                    width: 40px; height: 40px;
                    border-top: 2px solid rgba(212, 175, 55, 0.6);
                    border-left: 2px solid rgba(212, 175, 55, 0.6);
                    border-radius: 20px 0 0 0;
                    pointer-events: none;
                }
                .holo-module::after {
                    content: '';
                    position: absolute;
                    bottom: 0; right: 0;
                    width: 40px; height: 40px;
                    border-bottom: 2px solid rgba(232, 213, 163, 0.5);
                    border-right: 2px solid rgba(232, 213, 163, 0.5);
                    border-radius: 0 0 20px 0;
                    pointer-events: none;
                }

                /* === HOLOGRAPHIC HEADER === */
                .holo-title {
                    font-family: 'Courier New', 'SF Mono', monospace;
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 0.35em;
                    text-transform: uppercase;
                    background: linear-gradient(90deg, #e8d5a3, #d4af37, #e8d5a3);
                    background-size: 200% 100%;
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    animation: holo-title-shimmer 4s linear infinite;
                    margin-bottom: 1.5rem;
                    text-align: center;
                    position: relative;
                    z-index: 2;
                }
                .holo-title::after {
                    content: '';
                    display: block;
                    height: 1px;
                    margin-top: 0.75rem;
                    background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.5), rgba(232, 213, 163, 0.4), transparent);
                }
                @keyframes holo-title-shimmer {
                    0% { background-position: 0% 50%; }
                    100% { background-position: 200% 50%; }
                }

                /* === CANVAS WRAPPER === */
                .holo-canvas-wrap {
                    position: relative;
                    width: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                @keyframes orbital-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                @keyframes orbital-spin-rev {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(-360deg); }
                }

                /* === DONUT CENTER HOLOGRAM === */
                .holo-donut-center {
                    position: absolute;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    pointer-events: none;
                    z-index: 2;
                }
                .holo-total-num {
                    font-family: 'Courier New', monospace;
                    font-size: 2.8rem;
                    font-weight: 900;
                    line-height: 1;
                    background: linear-gradient(180deg, #ffffff 0%, #e8d5a3 50%, #d4af37 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    text-shadow: none;
                    filter: drop-shadow(0 0 12px rgba(212, 175, 55, 0.8));
                    animation: holo-pulse-num 3s ease-in-out infinite;
                }
                .holo-total-label {
                    font-family: 'Courier New', monospace;
                    font-size: 7px;
                    letter-spacing: 0.2em;
                    color: rgba(232, 213, 163, 0.8);
                    margin-top: 4px;
                    text-transform: uppercase;
                }
                @keyframes holo-pulse-num {
                    0%, 100% { filter: drop-shadow(0 0 8px rgba(212, 175, 55, 0.7)); }
                    50% { filter: drop-shadow(0 0 20px rgba(212, 175, 55, 1)) drop-shadow(0 0 40px rgba(232, 213, 163, 0.5)); }
                }

                /* === GLASSMORPHISM METRIC CARDS === */
                .holo-metrics {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    justify-content: center;
                    margin-top: 1.75rem;
                    position: relative;
                    z-index: 2;
                }
                .holo-metric-card {
                    background: rgba(212, 175, 55, 0.06);
                    border: 1px solid rgba(212, 175, 55, 0.2);
                    border-radius: 10px;
                    padding: 10px 16px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 3px;
                    backdrop-filter: blur(12px);
                    transition: all 0.3s ease;
                    box-shadow: 0 0 0 0 rgba(212, 175, 55, 0);
                    min-width: 80px;
                }
                .holo-metric-card:hover {
                    background: rgba(212, 175, 55, 0.12);
                    border-color: rgba(212, 175, 55, 0.5);
                    box-shadow: 0 0 20px rgba(212, 175, 55, 0.2), 0 0 40px rgba(212, 175, 55, 0.05);
                    transform: translateY(-2px);
                }
                .holo-metric-card.cash { border-color: rgba(201, 160, 58, 0.4); background: rgba(201, 160, 58, 0.06); }
                .holo-metric-card.cash:hover { border-color: rgba(201, 160, 58, 0.7); box-shadow: 0 0 20px rgba(201, 160, 58, 0.2); }
                .holo-metric-card.qris { border-color: rgba(16, 185, 129, 0.4); background: rgba(16, 185, 129, 0.06); }
                .holo-metric-card.qris:hover { border-color: rgba(16, 185, 129, 0.7); box-shadow: 0 0 20px rgba(16, 185, 129, 0.2); }
                .holo-metric-card.bank { border-color: rgba(59, 130, 246, 0.4); background: rgba(59, 130, 246, 0.06); }
                .holo-metric-card.bank:hover { border-color: rgba(59, 130, 246, 0.7); box-shadow: 0 0 20px rgba(59, 130, 246, 0.2); }
                .holo-metric-card.lainnya { border-color: rgba(100, 116, 139, 0.35); background: rgba(100, 116, 139, 0.05); }

                .holo-metric-label {
                    font-family: 'Courier New', monospace;
                    font-size: 8px;
                    letter-spacing: 0.15em;
                    color: rgba(148, 163, 184, 0.8);
                    text-transform: uppercase;
                }
                .holo-metric-value {
                    font-family: 'Courier New', monospace;
                    font-size: 1.6rem;
                    font-weight: 900;
                    line-height: 1;
                }
                .holo-metric-value.cash-val { color: #f0c040; text-shadow: 0 0 12px rgba(201, 160, 58, 0.8); }
                .holo-metric-value.qris-val { color: #10b981; text-shadow: 0 0 12px rgba(16, 185, 129, 0.8); }
                .holo-metric-value.bank-val { color: #60a5fa; text-shadow: 0 0 12px rgba(59, 130, 246, 0.8); }
                .holo-metric-value.lainnya-val { color: #94a3b8; text-shadow: 0 0 8px rgba(100, 116, 139, 0.6); }

                /* === BAR CHART SCAN LINES === */
                .holo-bar-wrap {
                    position: relative;
                    width: 100%;
                }
                .holo-bar-wrap::before {
                    content: '';
                    position: absolute;
                    inset: 0;
                    background: repeating-linear-gradient(
                        0deg,
                        transparent,
                        transparent 18px,
                        rgba(212, 175, 55, 0.02) 18px,
                        rgba(212, 175, 55, 0.02) 19px
                    );
                    pointer-events: none;
                    z-index: 0;
                }

                /* === FLOATING PARTICLES CANVAS === */
                #holo-particles-canvas {
                    position: absolute;
                    inset: 0;
                    pointer-events: none;
                    z-index: 0;
                }

                /* === LAYOUT GRID === */
                .holo-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 2rem;
                    position: relative;
                    z-index: 1;
                }
                @media (max-width: 1024px) {
                    .holo-grid { grid-template-columns: 1fr; }
                    .holo-chart-section::after { display: none; }
                }

                /* Top scanline sweep on module */
                @keyframes holo-scanline {
                    0% { top: -2px; opacity: 0; }
                    5% { opacity: 1; }
                    95% { opacity: 1; }
                    100% { top: 100%; opacity: 0; }
                }
                .holo-scanline {
                    position: absolute;
                    left: 0; right: 0;
                    height: 2px;
                    background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.6), rgba(232, 213, 163, 0.4), transparent);
                    animation: holo-scanline 6s ease-in-out infinite;
                    pointer-events: none;
                    z-index: 10;
                }

                /* Pulsing aura for top bar */
                @keyframes bar-aura-pulse {
                    0%, 100% { opacity: 0.5; }
                    50% { opacity: 1; }
                }
                </style>

                <!-- Holographic Chart Section -->
                <div class="holo-chart-section">
                    <!-- Floating Particles Canvas (background layer) -->
                    <canvas id="holo-particles-canvas" aria-hidden="true"></canvas>

                    <!-- Right margin syntax strand -->
                    <div class="holo-syntax-right" aria-hidden="true">return data_source&#10;  .get_metrics()&#10;&#10;async def stream():&#10;  yield payload&#10;    .encode("holo")&#10;&#10;ANTIGRAVITY = True&#10;DEPTH = "cyberspace"&#10;MODE = "luxury-gold"&#10;&#10;render(&#10;  chart=True,&#10;  float=True,&#10;  particles=True&#10;)&#10;&#10;# VIBE CODING&#10;# SYSTEM ACTIVE&#10;signal.emit("gold")</div>

                    <div class="holo-grid">

                        <!-- ======================== -->
                        <!-- LEFT: DONUT CHART MODULE -->
                        <!-- ======================== -->
                        <div class="holo-module">
                            <div class="holo-scanline"></div>
                            <h4 class="holo-title">METODE PEMBAYARAN</h4>

                            <!-- Canvas + Center Hologram -->
                            <div class="holo-canvas-wrap holo-canvas-wrap-donut" style="height: 280px;">
                                <canvas id="donutChartTransaksi" style="position:relative;z-index:2;"></canvas>
                                <div class="holo-donut-center" id="holo-donut-center-label">
                                    <span class="holo-total-num" id="holo-total-num">0</span>
                                    <span class="holo-total-label">TOTAL TRANSAKSI</span>
                                </div>
                            </div>

                            <!-- Glassmorphism Metric Cards -->
                            <div class="holo-metrics" id="holo-metrics-container">
                                <?php
                                $metricColorClass = [
                                    'Cash'          => ['card' => 'cash',    'val' => 'cash-val'],
                                    'QRIS'          => ['card' => 'qris',    'val' => 'qris-val'],
                                    'Transfer Bank' => ['card' => 'bank',    'val' => 'bank-val'],
                                ];
                                $totalDonut = 0;
                                foreach ($chartDataTransaksi as $it) $totalDonut += (int)$it['c'];
                                foreach ($chartDataTransaksi as $item):
                                    $mLabel = $item['metode_pembayaran'] ?: 'Belum Lunas';
                                    $cls = $metricColorClass[$item['metode_pembayaran']] ?? ['card' => 'lainnya', 'val' => 'lainnya-val'];
                                ?>
                                <div class="holo-metric-card <?= $cls['card'] ?>">
                                    <span class="holo-metric-label"><?= htmlspecialchars($mLabel) ?></span>
                                    <span class="holo-metric-value <?= $cls['val'] ?>"><?= $item['c'] ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- ======================== -->
                        <!-- RIGHT: BAR CHART MODULE  -->
                        <!-- ======================== -->
                        <div class="holo-module">
                            <div class="holo-scanline" style="animation-delay: 3s;"></div>
                            <h4 class="holo-title">LAYANAN SERING DIGUNAKAN</h4>

                            <div class="holo-bar-wrap">
                                <div style="width: 100%; height: 360px; position: relative; z-index: 1;">
                                    <canvas id="barChartLayananTransaksi"></canvas>
                                </div>
                            </div>
                        </div>

                    </div><!-- /.holo-grid -->
                </div><!-- /.holo-chart-section -->

                <!-- Data Table Transaksi -->
                <div class="bg-[#18120b] rounded-lg border border-white/10 shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-white/10 bg-[#22180f]">
                        <h3 class="font-semibold text-amber-100">Laporan Riwayat Transaksi Lunas</h3>
                    </div>
                    <div class="tabulator-wrapper"><div class="tabulator-controls"><div class="flex gap-2"><button class="tabulator-btn" onclick="exportData('table-transaksi', 'csv')"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> CSV</button><button class="tabulator-btn" onclick="exportData('table-transaksi', 'xlsx')"><i data-lucide="table" class="w-4 h-4"></i> Excel</button><button class="tabulator-btn" onclick="exportData('table-transaksi', 'pdf')"><i data-lucide="file-text" class="w-4 h-4"></i> PDF</button><button class="tabulator-btn" onclick="exportData('table-transaksi', 'print')"><i data-lucide="printer" class="w-4 h-4"></i> Print</button></div><input type="text" class="tabulator-search" id="search-transaksi" placeholder="Filter rows..."></div><table id="table-transaksi" class="w-full text-left border-collapse"><thead>
                                <tr class="bg-zinc-900/60 text-zinc-300 text-sm border-b border-white/10">
                                    <th class="px-6 py-3.5 font-semibold">ID Transaksi</th>
                                    <th class="px-6 py-3.5 font-semibold">No. Tiket</th>
                                    <th class="px-6 py-3.5 font-semibold">Pelanggan</th>
                                    <th class="px-6 py-3.5 font-semibold">Total Bayar</th>
                                    <th class="px-6 py-3.5 font-semibold" tabulator-formatter="html">Status</th>
                                    <th class="px-6 py-3.5 font-semibold">Waktu Bayar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($transaksi)): ?>
                                <tr><td colspan="6" class="px-6 py-8 text-center text-zinc-400">Belum ada data transaksi lunas.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($transaksi as $t): ?>
                                    <tr class="hover:bg-amber-500/10 transition-colors">
                                        <td class="px-6 py-4 font-mono text-amber-200/90 font-medium">#TRX-<?= $t['id'] ?></td>
                                        <td class="px-6 py-4 font-bold text-white"><?= htmlspecialchars($t['no_antrean']) ?></td>
                                        <td class="px-6 py-4 text-zinc-200 font-medium"><?= htmlspecialchars($t['pelanggan'] ?? 'Guest') ?></td>
                                        <td class="px-6 py-4 text-amber-400 font-bold">Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></td>
                                        <td class="px-6 py-4">
                                            <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2.5 py-1 rounded text-xs font-semibold">
                                                <?= strtoupper($t['status_pembayaran']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-zinc-300"><?= $t['waktu_bayar'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {

                    /* ============================================
                       FLOATING PARTICLES BACKGROUND (WARM GOLD)
                    ============================================ */
                    const pCanvas = document.getElementById('holo-particles-canvas');
                    if (!pCanvas) return;
                    const pSection = pCanvas.parentElement;
                    function resizeParticles() {
                        if (!pCanvas || !pSection) return;
                        pCanvas.width = pSection.offsetWidth;
                        pCanvas.height = pSection.offsetHeight;
                    }
                    resizeParticles();
                    window.addEventListener('resize', resizeParticles);
                    const pCtx = pCanvas.getContext('2d');

                    const PARTICLE_COLORS = [
                        'rgba(212,175,55,',   // gold
                        'rgba(232,213,163,',  // light gold
                        'rgba(245,158,11,',   // amber
                        'rgba(201,160,58,',   // bronze
                        'rgba(248,250,252,',  // warm white
                    ];
                    const particles = Array.from({length: 80}, () => ({
                        x: Math.random() * 1200,
                        y: Math.random() * 500,
                        r: Math.random() * 1.8 + 0.3,
                        vy: -(Math.random() * 0.5 + 0.1),
                        vx: (Math.random() - 0.5) * 0.2,
                        alpha: Math.random() * 0.5 + 0.1,
                        color: PARTICLE_COLORS[Math.floor(Math.random() * PARTICLE_COLORS.length)],
                        life: Math.random(),
                        decay: Math.random() * 0.002 + 0.001
                    }));

                    function animateParticles() {
                        if (!pCanvas || !pCtx) return;
                        pCanvas.width = pSection.offsetWidth;
                        pCanvas.height = pSection.offsetHeight;
                        pCtx.clearRect(0, 0, pCanvas.width, pCanvas.height);
                        particles.forEach(p => {
                            p.y += p.vy;
                            p.x += p.vx;
                            p.life -= p.decay;
                            if (p.life <= 0 || p.y < -10) {
                                p.x = Math.random() * pCanvas.width;
                                p.y = pCanvas.height + 5;
                                p.life = 1;
                                p.alpha = Math.random() * 0.5 + 0.1;
                            }
                            const a = p.alpha * p.life;
                            pCtx.beginPath();
                            pCtx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                            pCtx.fillStyle = p.color + a + ')';
                            pCtx.fill();
                        });
                        requestAnimationFrame(animateParticles);
                    }
                    animateParticles();


                    /* ============================================
                       DONUT CHART — ORBITAL GOLD STREAMS
                    ============================================ */
                    const elDonut = document.getElementById('donutChartTransaksi');
                    if (!elDonut) return;
                    const ctxDonut = elDonut.getContext('2d');
                    const dataDonut = <?= json_encode($chartDataTransaksi) ?>;

                    const labelsDonut = dataDonut.map(item => item.metode_pembayaran || 'Belum Lunas/Lainnya');
                    const valuesDonut = dataDonut.map(item => parseInt(item.c));
                    const totalDonut = valuesDonut.reduce((a, b) => a + b, 0);

                    // Animated total counter
                    const numEl = document.getElementById('holo-total-num');
                    let counted = 0;
                    const countInterval = setInterval(() => {
                        counted = Math.min(counted + 1, totalDonut);
                        if (numEl) numEl.textContent = counted;
                        if (counted >= totalDonut) clearInterval(countInterval);
                    }, 80);

                    // Holographic segment colors with gold glow
                    const holoColorMap = {
                        'Cash':                 { bg: 'rgba(201,160,58,0.85)',   border: 'rgba(240,192,64,0.6)',  glow: '#f0c040' },
                        'Transfer Bank':        { bg: 'rgba(59,130,246,0.85)',   border: 'rgba(96,165,250,0.6)',  glow: '#60a5fa' },
                        'QRIS':                 { bg: 'rgba(16,185,129,0.85)',   border: 'rgba(52,211,153,0.6)',  glow: '#34d399' },
                        'Belum Lunas/Lainnya':  { bg: 'rgba(71,85,105,0.75)',    border: 'rgba(100,116,139,0.4)', glow: '#64748b' },
                    };
                    const bgColors    = labelsDonut.map(l => (holoColorMap[l] || holoColorMap['Belum Lunas/Lainnya']).bg);
                    const borderClrs  = labelsDonut.map(l => (holoColorMap[l] || holoColorMap['Belum Lunas/Lainnya']).border);

                    // Custom glow plugin
                    const donutGlowPlugin = {
                        id: 'donutGlow',
                        beforeDraw(chart) {
                            const ctx = chart.ctx;
                            ctx.save();
                            ctx.shadowBlur = 24;
                            ctx.shadowColor = 'rgba(212,175,55,0.4)';
                        },
                        afterDraw(chart) {
                            chart.ctx.restore();
                        }
                    };

                    const centerAlignPlugin = {
                        id: 'centerAlign',
                        afterDraw(chart) {
                            const centerEl = document.getElementById('holo-donut-center-label');
                            if (centerEl && chart.chartArea) {
                                const chartArea = chart.chartArea;
                                const centerX = chartArea.left + (chartArea.right - chartArea.left) / 2;
                                const centerY = chartArea.top + (chartArea.bottom - chartArea.top) / 2;
                                centerEl.style.left = centerX + 'px';
                                centerEl.style.top = centerY + 'px';
                                centerEl.style.transform = 'translate(-50%, -50%)';
                            }
                        }
                    };
                    new Chart(ctxDonut, {
                        type: 'doughnut',
                        plugins: [donutGlowPlugin, centerAlignPlugin],
                        data: {
                            labels: labelsDonut.length ? labelsDonut : ['Belum ada transaksi'],
                            datasets: [{
                                data: valuesDonut.length ? valuesDonut : [1],
                                backgroundColor: valuesDonut.length ? bgColors : ['#1e293b'],
                                borderColor: valuesDonut.length ? borderClrs : ['#334155'],
                                borderWidth: 2,
                                hoverOffset: 14,
                                hoverBorderColor: '#e8d5a3',
                                hoverBorderWidth: 3,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '68%',
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        color: 'rgba(232,213,163,0.9)',
                                        padding: 16,
                                        font: { family: "'Courier New', monospace", size: 11, weight: '600' },
                                        usePointStyle: true,
                                        pointStyleWidth: 10,
                                        boxHeight: 8,
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(18,14,6,0.95)',
                                    titleColor: '#e8d5a3',
                                    bodyColor: '#e2e8f0',
                                    borderColor: 'rgba(212,175,55,0.5)',
                                    borderWidth: 1,
                                    padding: 14,
                                    cornerRadius: 10,
                                    titleFont: { family: "'Courier New', monospace", size: 12, weight: '700' },
                                    bodyFont:  { family: "'Courier New', monospace", size: 11 },
                                    callbacks: {
                                        label: (ctx) => `  ${ctx.label}: ${ctx.raw} transaksi (${Math.round(ctx.raw/totalDonut*100)}%)`
                                    }
                                }
                            },
                            animation: {
                                animateScale: true,
                                animateRotate: true,
                                duration: 2000,
                                easing: 'easeOutQuart'
                            }
                        }
                    });


                    /* ============================================
                       BAR CHART — GLOWING AMBER/GOLD VOXEL BARS
                    ============================================ */
                    const elBar = document.getElementById('barChartLayananTransaksi');
                    if (!elBar) return;
                    const ctxBar = elBar.getContext('2d');
                    const dataBar = <?= json_encode($chartDataLayananTransaksi ?? []) ?>;

                    const labelsBar = dataBar.map(item => item.nama_layanan);
                    const valuesBar = dataBar.map(item => parseInt(item.c));
                    const maxVal    = Math.max(...valuesBar, 1);

                    // Per-bar gradient: gold/amber intensity
                    const barColors = valuesBar.map((v, i) => {
                        const intensity = 0.55 + (v / maxVal) * 0.45;
                        if (i === 0) {
                            // Top bar: vivid gold
                            const g = ctxBar.createLinearGradient(0, 0, ctxBar.canvas.offsetWidth || 500, 0);
                            g.addColorStop(0, `rgba(201,160,58,${intensity})`);
                            g.addColorStop(0.5, `rgba(212,175,55,${intensity})`);
                            g.addColorStop(1, `rgba(232,213,163,${intensity + 0.1})`);
                            return g;
                        }
                        const g = ctxBar.createLinearGradient(0, 0, ctxBar.canvas.offsetWidth || 500, 0);
                        g.addColorStop(0, `rgba(180,130,40,${intensity * 0.8})`);
                        g.addColorStop(1, `rgba(212,175,55,${intensity})`);
                        return g;
                    });

                    // Per-bar border glow
                    const barBorders = valuesBar.map((v, i) =>
                        i === 0 ? 'rgba(232,213,163,0.7)' : 'rgba(212,175,55,0.3)'
                    );

                    // Custom bar glow plugin
                    const barGlowPlugin = {
                        id: 'barGlow',
                        afterDatasetsDraw(chart) {
                            const ctx = chart.ctx;
                            const meta = chart.getDatasetMeta(0);
                            meta.data.forEach((bar, i) => {
                                const isTop = i === 0;
                                ctx.save();
                                ctx.shadowBlur = isTop ? 28 : 12;
                                ctx.shadowColor = isTop ? 'rgba(232,213,163,0.7)' : 'rgba(212,175,55,0.4)';
                                ctx.fillStyle = 'transparent';
                                ctx.strokeStyle = isTop ? 'rgba(232,213,163,0.5)' : 'rgba(212,175,55,0.2)';
                                ctx.lineWidth = isTop ? 2 : 1;
                                const { x, y, width, height, base } = bar;
                                ctx.beginPath();
                                ctx.roundRect
                                    ? ctx.roundRect(x, y, width - x + base, height, 4)
                                    : ctx.rect(x, y, width - x + base, height);
                                ctx.stroke();
                                ctx.restore();

                                // Drifting data particles from top of each bar (antigravity gold)
                                if (valuesBar[i] > 0) {
                                    for (let p = 0; p < (isTop ? 5 : 2); p++) {
                                        const px = bar.base + Math.random() * (bar.x - bar.base);
                                        const py = bar.y + Math.random() * bar.height;
                                        const pr = Math.random() * 1.5 + 0.4;
                                        const pa = Math.random() * 0.5 + 0.2;
                                        ctx.beginPath();
                                        ctx.arc(px, py, pr, 0, Math.PI * 2);
                                        ctx.fillStyle = isTop
                                            ? `rgba(232,213,163,${pa})`
                                            : `rgba(212,175,55,${pa * 0.7})`;
                                        ctx.fill();
                                    }
                                }
                            });
                        }
                    };

                    new Chart(ctxBar, {
                        type: 'bar',
                        plugins: [barGlowPlugin],
                        data: {
                            labels: labelsBar.length ? labelsBar : ['Belum ada data'],
                            datasets: [{
                                label: 'Jumlah Penggunaan',
                                data: valuesBar.length ? valuesBar : [0],
                                backgroundColor: valuesBar.length ? barColors : ['#1e293b'],
                                borderColor: valuesBar.length ? barBorders : ['#334155'],
                                borderWidth: 1,
                                borderRadius: 4,
                                borderSkipped: false,
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    grid: {
                                        color: 'rgba(212,175,55,0.08)',
                                        drawBorder: false,
                                        lineWidth: 1,
                                    },
                                    border: { display: false },
                                    ticks: {
                                        color: 'rgba(232,213,163,0.6)',
                                        font: { family: "'Courier New', monospace", size: 10 },
                                        stepSize: 1,
                                    },
                                    beginAtZero: true
                                },
                                y: {
                                    grid: { display: false, drawBorder: false },
                                    border: { display: false },
                                    ticks: {
                                        color: '#cbd5e1',
                                        font: { family: "'Courier New', monospace", size: 11, weight: '600' },
                                        padding: 8,
                                    }
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: 'rgba(18,14,6,0.95)',
                                    titleColor: '#e8d5a3',
                                    bodyColor: '#e2e8f0',
                                    borderColor: 'rgba(212,175,55,0.5)',
                                    borderWidth: 1,
                                    padding: 14,
                                    cornerRadius: 10,
                                    titleFont: { family: "'Courier New', monospace", size: 12, weight: '700' },
                                    bodyFont:  { family: "'Courier New', monospace", size: 11 },
                                    callbacks: {
                                        title: (items) => `⬡ ${items[0].label}`,
                                        label: (ctx) => `  Digunakan: ${ctx.raw}× oleh pelanggan`
                                    }
                                }
                            },
                            animation: {
                                duration: 1800,
                                easing: 'easeOutQuart',
                                delay: (ctx) => ctx.dataIndex * 100
                            }
                        }
                    });

                }); // end DOMContentLoaded
                </script>
            </div>
            <?php endif; ?>
            <?php if ($page === 'akun'): ?>
            <!-- AKUN PENGGUNA MODULE -->
            <?php
            // Fetch Users with Real-Time Online Status & Last Active Timestamp for Akun Module Card
            $userActivityStmt = $pdo->query("
                SELECT 
                    u.id_user, 
                    u.username, 
                    u.fullname, 
                    u.role,
                    COALESCE(u.is_online, 0) as is_online,
                    COALESCE(NULLIF(u.fullname, ''), u.username) as nama,
                    COALESCE(
                        u.last_active, 
                        (SELECT MAX(waktu_kunjungan) FROM kunjungan_website WHERE user_id = u.id_user),
                        (SELECT MAX(waktu_dibuat) FROM antrian WHERE pelanggan_id = u.id_user)
                    ) as last_seen
                FROM users u
                ORDER BY (u.is_online = 1 AND u.last_active >= NOW() - INTERVAL 15 MINUTE) DESC, last_seen DESC
                LIMIT 50
            ");
            $userActivityList = $userActivityStmt ? $userActivityStmt->fetchAll(PDO::FETCH_ASSOC) : [];
            ?>

            <div class="mb-6 space-y-6">
                <!-- Grid Donut Chart & Card Status Keaktifan Akun -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: Card Pendaftaran Akun Baru (Notifikasi Registrasi) -->
                    <?php
                    // Fetch Recent Customer Registrations safely
                    $recentCustomers = [];
                    $newTodayCount = 0;
                    $newWeekCount = 0;
                    try {
                        $recentCustomersStmt = $pdo->query("
                            SELECT id_user, username, fullname, email, phone, created_at 
                            FROM users 
                            WHERE role = 'pelanggan' 
                            ORDER BY id_user DESC 
                            LIMIT 10
                        ");
                        $recentCustomers = $recentCustomersStmt ? $recentCustomersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

                        $countTodayStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'pelanggan' AND DATE(created_at) = CURDATE()");
                        $newTodayCount = $countTodayStmt ? (int)$countTodayStmt->fetchColumn() : 0;

                        $countWeekStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'pelanggan' AND created_at >= NOW() - INTERVAL 7 DAY");
                        $newWeekCount = $countWeekStmt ? (int)$countWeekStmt->fetchColumn() : 0;
                    } catch (Exception $e) {
                        // Fallback if created_at column is missing or newly added
                        try {
                            $pdo->exec("ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                            $recentCustomersStmt = $pdo->query("SELECT id_user, username, fullname, email, phone, created_at FROM users WHERE role = 'pelanggan' ORDER BY id_user DESC LIMIT 10");
                            $recentCustomers = $recentCustomersStmt ? $recentCustomersStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                            $newWeekCount = count($recentCustomers);
                        } catch (Exception $ex) {
                            $recentCustomers = [];
                        }
                    }
                    ?>
                    <div id="card-pendaftaran-baru" class="p-6 rounded-2xl border shadow-md flex flex-col justify-between transition-all duration-500" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <div class="flex justify-between items-center mb-4 pb-3 border-b border-amber-900/30">
                                <div>
                                    <h3 class="text-lg font-bold tracking-wide flex items-center gap-2" style="color:#e8d5a3;">
                                        <i data-lucide="user-plus" class="w-5 h-5 text-amber-400"></i>
                                        Pendaftaran Akun Baru
                                    </h3>
                                    <p class="text-xs text-stone-400 mt-0.5">Daftar pelanggan yang baru mendaftar di web</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/30 flex items-center gap-1.5 shadow-sm">
                                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span> Terbaru
                                </span>
                            </div>

                            <!-- Summary Badges -->
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="bg-zinc-900/80 border border-amber-900/40 p-3 rounded-xl flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                                        <i data-lucide="user-check" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <span class="text-[11px] text-zinc-400 block uppercase font-medium">Hari Ini</span>
                                        <span class="text-lg font-bold text-white"><?= $newTodayCount ?> <span class="text-xs font-normal text-amber-300/80">Baru</span></span>
                                    </div>
                                </div>
                                <div class="bg-zinc-900/80 border border-amber-900/40 p-3 rounded-xl flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400 shrink-0">
                                        <i data-lucide="users" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <span class="text-[11px] text-zinc-400 block uppercase font-medium">7 Hari Terakhir</span>
                                        <span class="text-lg font-bold text-white"><?= $newWeekCount ?> <span class="text-xs font-normal text-blue-300/80">Akun</span></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Feed List -->
                            <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1 custom-scroll">
                                <?php if (empty($recentCustomers)): ?>
                                    <div class="text-center text-stone-400 py-8 text-sm">Belum ada pendaftaran akun pelanggan baru</div>
                                <?php else: ?>
                                    <?php foreach ($recentCustomers as $rc): 
                                        $rcName = !empty($rc['fullname']) ? htmlspecialchars($rc['fullname']) : htmlspecialchars($rc['username']);
                                        $rcInitial = strtoupper(substr($rcName, 0, 1));
                                        $rcTime = !empty($rc['created_at']) ? date('d M Y H:i', strtotime($rc['created_at'])) : 'Baru saja';

                                        // Check for uploaded profile photo
                                        $userPhotoFiles = glob(__DIR__ . '/../asset/image/profile_' . $rc['id_user'] . '.*');
                                        $hasPhoto = !empty($userPhotoFiles);
                                        $photoUrl = $hasPhoto ? '../asset/image/' . basename($userPhotoFiles[0]) . '?v=' . filemtime($userPhotoFiles[0]) : null;
                                    ?>
                                    <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-900/50 hover:bg-amber-900/20 border border-white/5 hover:border-amber-900/40 transition-all">
                                        <div class="flex items-center gap-3">
                                            <?php if ($hasPhoto): ?>
                                                <img src="<?= $photoUrl ?>" alt="<?= $rcName ?>" class="w-10 h-10 rounded-full object-cover ring-2 ring-amber-500/50 shadow-md shrink-0">
                                            <?php else: ?>
                                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-700 to-amber-900 border border-amber-500/40 flex items-center justify-center text-amber-100 font-bold text-sm shadow-md shrink-0">
                                                    <?= $rcInitial ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h4 class="font-bold text-sm text-stone-100 flex items-center gap-1.5">
                                                    <?= $rcName ?>
                                                    <span class="text-xs font-normal text-amber-400/80">(@<?= htmlspecialchars($rc['username']) ?>)</span>
                                                </h4>
                                                <div class="flex items-center gap-2 mt-0.5 text-xs text-stone-400">
                                                    <span class="truncate max-w-[140px]" title="<?= htmlspecialchars($rc['email'] ?? '') ?>"><?= !empty($rc['email']) ? htmlspecialchars($rc['email']) : '-' ?></span>
                                                    <span>•</span>
                                                    <span class="text-amber-200/80"><?= $rcTime ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">
                                            Pelanggan
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Card Status Keaktifan Akun (Gambar Style) -->
                    <div class="p-6 rounded-2xl border shadow-md flex flex-col justify-between" style="background: linear-gradient(135deg, #1a1208 0%, #120e06 100%); border-color: #3a2510;">
                        <div>
                            <div class="flex justify-between items-center mb-5 pb-3 border-b border-amber-900/30">
                                <div>
                                    <h3 class="text-xl font-bold tracking-wide flex items-center gap-2" style="color:#e8d5a3;">
                                        <i data-lucide="users" class="w-5 h-5 text-emerald-400"></i>
                                        Status Keaktifan Akun
                                    </h3>
                                    <p class="text-xs text-stone-400 mt-0.5">Status online real-time & waktu terakhir aktif</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Status Aktif
                                </span>
                            </div>

                            <div class="space-y-3.5 max-h-[350px] overflow-y-auto pr-2 custom-scroll">
                                <?php if (empty($userActivityList)): ?>
                                    <div class="text-center text-stone-400 py-8">Belum ada aktivitas akun</div>
                                <?php else: ?>
                                    <?php 
                                    foreach ($userActivityList as $uAct): 
                                        $last_time = $uAct['last_seen'] ? strtotime($uAct['last_seen']) : 0;
                                        // Account is ONLINE only if is_online == 1 AND active within last 15 mins
                                        $is_currently_online = ((int)$uAct['is_online'] === 1) && ($last_time > 0) && (time() - $last_time <= 900);
                                        $formatted_date = $last_time > 0 ? date('d M Y H:i', $last_time) : 'Belum ada';
                                        
                                        $userPhotoPath = "../asset/image/profile_" . $uAct['id_user'] . ".jpg";
                                        $hasRealPhoto = file_exists(__DIR__ . '/' . $userPhotoPath);
                                        $initial = strtoupper(substr($uAct['nama'], 0, 1));
                                    ?>
                                    <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-amber-900/20 transition-colors border border-transparent hover:border-amber-900/30">
                                        <div class="flex items-center gap-3.5">
                                            <!-- Profile Avatar Circle with GREEN DOT at corner for ONLINE ONLY -->
                                            <div class="relative shrink-0">
                                                <?php if ($hasRealPhoto): ?>
                                                    <img src="<?= $userPhotoPath ?>" alt="<?= htmlspecialchars($uAct['nama']) ?>" class="w-11 h-11 rounded-full object-cover ring-2 ring-amber-500/40 shadow-md">
                                                <?php else: ?>
                                                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-amber-800 to-stone-900 border border-amber-600/40 flex items-center justify-center text-amber-200 font-bold text-base shadow-md">
                                                        <?= $initial ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($is_currently_online): ?>
                                                    <!-- GREEN DOT AT CORNER OF PROFILE FOR REALTIME ONLINE -->
                                                    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-[#120e06] ring-2 ring-emerald-400/50 shadow-md" title="Online Sekarang"></span>
                                                <?php else: ?>
                                                    <!-- GRAY DOT FOR OFFLINE / LOGGED OUT -->
                                                    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-zinc-600 rounded-full border-2 border-[#120e06]" title="Offline"></span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- User Info & Status Text -->
                                            <div>
                                                <h4 class="font-bold text-sm text-stone-100 flex items-center gap-1.5">
                                                    <?= htmlspecialchars($uAct['nama']) ?>
                                                    <span class="text-xs font-normal text-amber-400/80">(@<?= htmlspecialchars($uAct['username']) ?>)</span>
                                                </h4>
                                                <div class="flex items-center gap-2 mt-0.5 text-xs text-stone-400">
                                                    <span class="capitalize px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-300 text-[10px]"><?= htmlspecialchars($uAct['role']) ?></span>
                                                    <span>•</span>
                                                    <?php if ($is_currently_online): ?>
                                                        <span class="text-emerald-400 font-medium flex items-center gap-1">
                                                            Online Sekarang
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-zinc-400">
                                                            Terakhir online: <?= $formatted_date ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Side Status Badge -->
                                        <div>
                                            <?php if ($is_currently_online): ?>
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1 shadow-sm">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Online
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-1 rounded-full text-xs text-zinc-400 bg-zinc-800/80 border border-zinc-700">
                                                    Offline
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Users -->
                <div class="bg-[#18120b] rounded-lg border border-white/10 shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-white/10 bg-[#22180f]">
                        <h3 class="font-semibold text-amber-100">Daftar Akun Pengguna</h3>
                    </div>
                    <div class="tabulator-wrapper">
                        <div class="tabulator-controls">
                            <div class="flex gap-2">
                                <button type="button" class="tabulator-btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #0e0a08; border-color: #f59e0b; font-weight: 700;" onclick="openAddUserModal()">
                                    <i data-lucide="user-plus" class="w-4 h-4"></i> Tambah Akun
                                </button>
                                <button class="tabulator-btn" onclick="exportData('table-users', 'csv')"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> CSV</button><button class="tabulator-btn" onclick="exportData('table-users', 'xlsx')"><i data-lucide="table" class="w-4 h-4"></i> Excel</button><button class="tabulator-btn" onclick="exportData('table-users', 'pdf')"><i data-lucide="file-text" class="w-4 h-4"></i> PDF</button><button class="tabulator-btn" onclick="exportData('table-users', 'print')"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                            </div>
                            <input type="text" class="tabulator-search" id="search-users" placeholder="Filter rows...">
                        </div>
                        <table id="table-users" class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-zinc-900/60 text-zinc-300 text-sm border-b border-white/10">
                                    <th class="px-4 py-3.5 font-semibold text-center" tabulator-field="no" width="70" tabulator-formatter="rownum">No.</th>
                                    <th class="px-6 py-3.5 font-semibold" tabulator-field="fullname">Nama Lengkap</th>
                                    <th class="px-6 py-3.5 font-semibold" tabulator-field="username">Username</th>
                                    <th class="px-6 py-3.5 font-semibold" tabulator-field="email">Email</th>
                                    <th class="px-6 py-3.5 font-semibold" tabulator-field="phone">No. WA</th>
                                    <th class="px-6 py-3.5 font-semibold" tabulator-field="role" tabulator-formatter="html">Role</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-amber-500/10 transition-colors">
                                    <td class="px-4 py-4 text-zinc-400 text-center font-medium"></td>
                                    <td class="px-6 py-4 text-white font-medium"><?= !empty($u['fullname']) ? htmlspecialchars($u['fullname']) : '-' ?></td>
                                    <td class="px-6 py-4 text-amber-200/90 font-mono font-medium"><?= htmlspecialchars($u['username']) ?></td>
                                    <td class="px-6 py-4 text-zinc-300"><?= !empty($u['email']) ? htmlspecialchars($u['email']) : '-' ?></td>
                                    <td class="px-6 py-4 text-zinc-300"><?= !empty($u['phone']) ? htmlspecialchars($u['phone']) : '-' ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-between items-center w-full min-w-[150px]">
                                            <span class="text-zinc-300 capitalize font-medium"><?= htmlspecialchars($u['role']) ?></span>
                                            <div class="flex items-center gap-2">
                                                <button type="button" onclick="openEditUserModal(<?= $u['id_user'] ?>, '<?= htmlspecialchars($u['fullname'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($u['phone'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>')" class="text-amber-400 hover:text-amber-300 p-1.5 rounded hover:bg-amber-400/10 transition-colors" title="Edit">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="form_type" value="delete_user">
                                                    <input type="hidden" name="current_page" value="akun">
                                                    <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                                    <input type="hidden" name="id" value="<?= $u['id_user'] ?>">
                                                    <button type="submit" class="text-rose-400 hover:text-rose-300 p-1.5 rounded hover:bg-rose-400/10 transition-colors" onclick="return confirm('Hapus user ini dari database?')" title="Hapus">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('donutChartAkun').getContext('2d');
                    const data = <?= json_encode($chartDataAkun) ?>;
                    
                    const labels = data.map(item => item.role.charAt(0).toUpperCase() + item.role.slice(1));
                    const values = data.map(item => item.c);
                    
                    const colorMap = { 'Admin': '#ef4444', 'Barber': '#f59e0b', 'Pelanggan': '#d97706' };
                    const colors = labels.map(label => colorMap[label] || '#fde68a');
                    
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels.length ? labels : ['Kosong'],
                            datasets: [{
                                data: values.length ? values : [1],
                                backgroundColor: values.length ? colors : ['#30363d'],
                                borderWidth: 0,
                                hoverOffset: 12
                            }]
                        },
                        options: {
                            responsive: true,
                            cutout: '65%',
                            plugins: {
                                legend: { position: 'right', labels: { color: '#e8d5a3', padding: 20, font: { family: 'sans-serif' } } },
                                tooltip: { backgroundColor: 'rgba(10, 8, 5, 0.9)', titleColor: '#e8d5a3', bodyColor: '#fff', borderColor: '#8a6030', borderWidth: 1, padding: 12 }
                            },
                            animation: { animateScale: true, animateRotate: true, duration: 1500 }
                        }
                    });
                });
                </script>
            </div>
            <?php endif; ?>
            <?php if ($page === 'profil'): ?>
            <!-- PROFIL MODULE -->
            <div class="max-w-4xl mx-auto">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Profil Saya</h2>
                        <p class="text-zinc-400 text-sm mt-1">Kelola informasi pribadi dan keamanan akun Anda</p>
                    </div>
                </div>

                <form action="admin.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <input type="hidden" name="form_type" value="update_profil">
                    <input type="hidden" name="current_page" value="profil">
                    <!-- Left Column: Avatar & Summary -->
                    <div class="col-span-1">
                        <div class="bg-zinc-900/50 backdrop-blur-md border border-zinc-700/50 rounded-xl p-6 shadow-2xl flex flex-col items-center text-center relative overflow-hidden">
                            <!-- Background Decoration -->
                            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-adminlte-primary/20 to-purple-600/20 z-0"></div>
                            
                            <div class="relative z-10 w-28 h-28 rounded-full border-4 border-zinc-800 shadow-xl mt-4 mb-4 overflow-hidden bg-zinc-800 group">
                                <?php 
                                $avatar_name = !empty($current_user['fullname']) ? urlencode($current_user['fullname']) : urlencode($current_user['username']);
                                $profile_files = glob(__DIR__ . '/../asset/image/profile_' . $_SESSION['user_id'] . '.*');
                                $profile_url = !empty($profile_files) ? '../asset/image/' . basename($profile_files[0]) : "https://ui-avatars.com/api/?name={$avatar_name}&background=random&color=fff&size=128&bold=true";
                                ?>
                                <img src="<?= $profile_url ?>" alt="Avatar" class="w-full h-full object-cover">
                                <label for="foto_profil_input" class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white text-xs font-semibold backdrop-blur-sm">
                                    <i data-lucide="camera" class="w-6 h-6 mb-1 text-zinc-300"></i>
                                    Ubah Foto
                                </label>
                                <input type="file" name="foto_profil" id="foto_profil_input" class="hidden" accept="image/*" onchange="document.getElementById('profile_save_btn').click();">
                            </div>
                            
                            <h3 class="relative z-10 text-xl font-bold text-white mb-1"><?= !empty($current_user['fullname']) ? htmlspecialchars($current_user['fullname']) : htmlspecialchars($current_user['username']) ?></h3>
                            <span class="relative z-10 bg-adminlte-primary/20 text-blue-400 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider mb-4 border border-blue-500/20">
                                <?= htmlspecialchars($current_user['role']) ?>
                            </span>
                            
                            <div class="relative z-10 w-full text-left space-y-3 mt-4 border-t border-zinc-700/50 pt-4">
                                <div class="flex items-center gap-3 text-sm text-zinc-400">
                                    <i data-lucide="mail" class="w-4 h-4 text-zinc-500"></i>
                                    <span class="truncate"><?= !empty($current_user['email']) ? htmlspecialchars($current_user['email']) : '<em class="text-zinc-600">Belum diatur</em>' ?></span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-zinc-400">
                                    <i data-lucide="phone" class="w-4 h-4 text-zinc-500"></i>
                                    <span><?= !empty($current_user['phone']) ? htmlspecialchars($current_user['phone']) : '<em class="text-zinc-600">Belum diatur</em>' ?></span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-zinc-400">
                                    <i data-lucide="user" class="w-4 h-4 text-zinc-500"></i>
                                    <span>@<?= htmlspecialchars($current_user['username']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Edit Form -->
                    <div class="col-span-1 md:col-span-2">
                        <div class="bg-zinc-900/50 backdrop-blur-md border border-zinc-700/50 rounded-xl shadow-2xl overflow-hidden">
                            <div class="border-b border-zinc-700/50 px-6 py-4 bg-zinc-800/30 flex items-center gap-3">
                                <i data-lucide="settings-2" class="w-5 h-5 text-adminlte-primary"></i>
                                <h3 class="text-lg font-semibold text-white">Pengaturan Akun</h3>
                            </div>
                            
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-400 mb-2">Nama Lengkap</label>
                                        <input type="text" name="fullname" value="<?= htmlspecialchars($current_user['fullname'] ?? '') ?>" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all" placeholder="Nama Lengkap">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-400 mb-2">Username</label>
                                        <input type="text" name="username" value="<?= htmlspecialchars($current_user['username']) ?>" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-400 mb-2">Email</label>
                                        <input type="email" name="email" value="<?= htmlspecialchars($current_user['email'] ?? '') ?>" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all" placeholder="email@contoh.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-400 mb-2">No. WhatsApp</label>
                                        <input type="text" name="phone" value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all" placeholder="08123456789">
                                    </div>
                                </div>

                                <div class="border-t border-zinc-700/50 pt-6 mb-6">
                                    <h4 class="text-sm font-medium text-white mb-4 flex items-center gap-2">
                                        <i data-lucide="shield-check" class="w-4 h-4 text-adminlte-primary"></i> Keamanan Akun & Ubah Password
                                    </h4>
                                    
                                    <div class="space-y-4 max-w-xl">
                                        <div>
                                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Password Lama Saat Ini</label>
                                            <div class="relative">
                                                <input type="password" id="admin_old_pass" name="old_password" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all text-sm" placeholder="Masukkan password lama Anda">
                                                <button type="button" onclick="togglePass('admin_old_pass', 'a_eye_old')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                                    <i data-lucide="eye" id="a_eye_old" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                            <p class="text-[11px] text-zinc-500 mt-1">* Wajib diisi untuk memverifikasi bahwa Anda adalah pemilik sah akun ini.</p>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Password Baru</label>
                                                <div class="relative">
                                                    <input type="password" id="admin_new_pass" name="new_password" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all text-sm" placeholder="Min. 6-8 karakter">
                                                    <button type="button" onclick="togglePass('admin_new_pass', 'a_eye_new')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                                        <i data-lucide="eye" id="a_eye_new" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Konfirmasi Password Baru</label>
                                                <div class="relative">
                                                    <input type="password" id="admin_conf_pass" name="confirm_password" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white pr-10 focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all text-sm" placeholder="Ulangi password baru">
                                                    <button type="button" onclick="togglePass('admin_conf_pass', 'a_eye_conf')" class="absolute right-3 top-3 text-zinc-400 hover:text-white">
                                                        <i data-lucide="eye" id="a_eye_conf" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" id="profile_save_btn" class="bg-adminlte-primary hover:bg-blue-600 text-white font-medium px-6 py-2.5 rounded-lg transition-colors flex items-center gap-2 shadow-lg shadow-blue-500/20">
                                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            <?php if ($page === 'pengaturan'): ?>
            <!-- PENGATURAN WA MODULE -->
            <div class="max-w-3xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-white tracking-tight">Pengaturan WhatsApp Gateway</h2>
                    <p class="text-zinc-400 text-sm mt-1">Konfigurasi API Key dari Fonnte untuk notifikasi otomatis</p>
                </div>
                
                <div class="bg-zinc-900/50 backdrop-blur-md border border-zinc-700/50 rounded-xl p-6 shadow-2xl">
                    <form action="admin.php" method="POST">
                        <input type="hidden" name="form_type" value="save_wa_config">
                        <input type="hidden" name="current_page" value="pengaturan">
                        
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-zinc-400 mb-2">API Key Fonnte</label>
                            <input type="text" name="wa_api_key" value="<?= htmlspecialchars($wa_key) ?>" class="w-full bg-zinc-950/50 border border-zinc-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-adminlte-primary focus:ring-1 focus:ring-adminlte-primary transition-all" placeholder="Masukkan token Fonnte Anda di sini..." required>
                            <p class="text-xs text-zinc-500 mt-2">Dapatkan API Key ini dengan mendaftar di <a href="https://fonnte.com" target="_blank" class="text-blue-400 hover:underline">fonnte.com</a></p>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-adminlte-primary hover:bg-blue-600 text-white font-medium px-6 py-2.5 rounded-lg transition-colors flex items-center gap-2 shadow-lg shadow-blue-500/20">
                                <i data-lucide="save" class="w-4 h-4"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($page === 'barber'): ?>
            <!-- PANEL BARBER MODULE -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-adminlte-info rounded-lg p-6 relative overflow-hidden text-white shadow-lg">
                    <div class="relative z-10">
                        <h3 class="text-4xl font-bold mb-1"><?= count($barber_queues) ?></h3>
                        <p class="text-blue-50 font-medium">Total Antrean Hari Ini</p>
                    </div>
                    <i data-lucide="list" class="absolute -right-4 -bottom-4 w-32 h-32 text-black/10 z-0"></i>
                </div>
                <div class="bg-adminlte-warning rounded-lg p-6 relative overflow-hidden text-zinc-900 shadow-lg">
                    <div class="relative z-10">
                        <h3 class="text-4xl font-bold mb-1"><?= $total_b_waiting ?></h3>
                        <p class="text-yellow-900 font-medium">Antrean Menunggu</p>
                    </div>
                    <i data-lucide="clock" class="absolute -right-4 -bottom-4 w-32 h-32 text-black/10 z-0"></i>
                </div>
                <div class="bg-adminlte-success rounded-lg p-6 relative overflow-hidden text-white shadow-lg">
                    <div class="relative z-10">
                        <h3 class="text-4xl font-bold mb-1"><?= $total_b_served ?></h3>
                        <p class="text-green-100 font-medium">Pelanggan Selesai</p>
                    </div>
                    <i data-lucide="check-circle" class="absolute -right-4 -bottom-4 w-32 h-32 text-black/10 z-0"></i>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-adminlte-card rounded-lg border border-zinc-700 shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-700 bg-[#30363d] flex justify-between items-center">
                    <h3 class="font-semibold text-white">Daftar Antrean Tugas Anda</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-zinc-800/50 text-zinc-400 text-sm border-b border-zinc-700">
                                <th class="px-6 py-3 font-medium">No. Tiket</th>
                                <th class="px-6 py-3 font-medium">Pelanggan</th>
                                <th class="px-6 py-3 font-medium">Layanan & Harga</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 font-medium text-right">Aksi Kerja</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-700/50">
                            <?php if (empty($barber_queues)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-zinc-500">Belum ada antrean masuk untuk Anda hari ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($barber_queues as $q): 
                                    $multiplier = (float)($q['multiplier'] ?? 1.0);
                                    $base_price = (float)($q['harga'] ?? 0);
                                    $final_price = $base_price * $multiplier;
                                ?>
                                <tr class="hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-lg text-white"><?= htmlspecialchars($q['no_antrean']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 font-medium"><?= htmlspecialchars($q['pelanggan_nama'] ?? 'Guest') ?></td>
                                    <td class="px-6 py-4">
                                        <div class="text-white"><?= htmlspecialchars($q['nama_layanan'] ?? 'Cukur Standar') ?></div>
                                        <div class="text-green-400 text-sm">Rp <?= number_format($final_price, 0, ',', '.') ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $badge_class = 'bg-zinc-600 text-white';
                                        if ($q['status_antrean'] === 'serving') $badge_class = 'bg-adminlte-primary text-white';
                                        if ($q['status_antrean'] === 'payment') $badge_class = 'bg-adminlte-warning text-zinc-900';
                                        if (in_array($q['status_antrean'], ['paid', 'review', 'completed'])) $badge_class = 'bg-adminlte-success text-white';
                                        ?>
                                        <span class="<?= $badge_class ?> px-2 py-1 rounded text-xs font-semibold uppercase">
                                            <?= htmlspecialchars($q['status_antrean']) ?>
                                        </span>
                                        <?php if ($q['status_antrean'] === 'paid' && !empty($q['metode_bayar'])): ?>
                                            <div class="text-xs text-zinc-400 mt-1">Via: <?= htmlspecialchars($q['metode_bayar']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <?php if ($q['status_antrean'] === 'waiting'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="form_type" value="call">
                                                    <input type="hidden" name="current_page" value="barber">
                                                    <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                    <button type="submit" class="bg-adminlte-primary hover:bg-blue-600 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors">
                                                        <i data-lucide="megaphone" class="w-3 h-3"></i> Panggil
                                                    </button>
                                                </form>
                                                <form method="POST">
                                                    <input type="hidden" name="form_type" value="skip">
                                                    <input type="hidden" name="current_page" value="barber">
                                                    <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                    <button type="submit" class="bg-adminlte-danger hover:bg-red-700 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors" onclick="return confirm('Lewati antrean ini?')">
                                                        <i data-lucide="skip-forward" class="w-3 h-3"></i> Skip
                                                    </button>
                                                </form>
                                            <?php elseif ($q['status_antrean'] === 'serving'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="form_type" value="finish_service">
                                                    <input type="hidden" name="current_page" value="barber">
                                                    <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                    <button type="submit" class="bg-adminlte-success hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors">
                                                        <i data-lucide="check" class="w-3 h-3"></i> Selesai Layani
                                                    </button>
                                                </form>
                                            <?php elseif ($q['status_antrean'] === 'payment'): ?>
                                                <div class="flex flex-col items-end gap-1">
                                                    <span class="text-xs text-zinc-400">Menunggu Bayar...</span>
                                                    <form method="POST">
                                                        <input type="hidden" name="form_type" value="confirm_paid">
                                                        <input type="hidden" name="current_page" value="barber">
                                                        <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                        <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                                                        <button type="submit" class="bg-adminlte-info hover:bg-cyan-600 text-white text-xs px-3 py-1.5 rounded transition-colors" onclick="return confirm('Konfirmasi bayar cash langsung?')">
                                                            Terima Cash
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php elseif ($q['status_antrean'] === 'paid'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="form_type" value="confirm_paid">
                                                    <input type="hidden" name="current_page" value="barber">
                                                    <input type="hidden" name="antrian_id" value="<?= $q['id'] ?>">
                                                    <input type="hidden" name="total_harga" value="<?= $final_price ?>">
                                                    <button type="submit" class="bg-adminlte-success hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded transition-colors">
                                                        Cetak & Selesai
                                                    </button>
                                                </form>
                                            <?php elseif (in_array($q['status_antrean'], ['review', 'completed'])): ?>
                                                <button type="button" class="bg-zinc-700 hover:bg-zinc-600 text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 transition-colors" onclick="printStruk('<?= $q['no_antrean'] ?>', '<?= htmlspecialchars($q['pelanggan_nama'] ?? 'Guest') ?>', '<?= htmlspecialchars($q['nama_layanan'] ?? 'Layanan') ?>', '<?= $final_price ?>', '<?= htmlspecialchars($q['metode_bayar'] ?? 'Cash') ?>')">
                                                    <i data-lucide="printer" class="w-3 h-3"></i> Cetak Ulang
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Initialize Lucide Icons -->

<script>
        lucide.createIcons();

        function openEditLayananModal(id, name, price, durasi, deskripsi) {
            document.getElementById('editLayananModal').style.display = 'flex';
            document.getElementById('edit_layanan_id').value = id;
            document.getElementById('edit_layanan_nama').value = name;
            document.getElementById('edit_layanan_harga').value = price;
            document.getElementById('edit_layanan_durasi').value = durasi;
            document.getElementById('edit_layanan_deskripsi').value = deskripsi;
        }

        function closeEditLayananModal() {
            document.getElementById('editLayananModal').style.display = 'none';
        }

        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'flex';
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }

        function openEditUserModal(id, fullname, username, email, phone, role) {
            document.getElementById('editUserModal').style.display = 'flex';
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_user_fullname').value = fullname;
            document.getElementById('edit_user_username').value = username;
            document.getElementById('edit_user_email').value = email;
            document.getElementById('edit_user_phone').value = phone;
            document.getElementById('edit_user_role').value = role;
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        function openDescModal(title, text, durasi, harga, imgUrl) {
            const modal = document.getElementById('descModal');
            const modalContent = document.getElementById('descModalContent');
            
            document.getElementById('descModalTitle').innerText = title;
            document.getElementById('descModalText').innerText = text;
            document.getElementById('descModalDurasi').innerText = durasi + ' Menit';
            document.getElementById('descModalHarga').innerText = harga;
            
            const imgEl = document.getElementById('descModalImg');
            if (imgEl) imgEl.src = imgUrl;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Trigger animation
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.classList.add('opacity-100');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
        }

        function closeDescModal() {
            const modal = document.getElementById('descModal');
            const modalContent = document.getElementById('descModalContent');
            
            modal.classList.remove('opacity-100');
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        function openAddLayananModal() {
            document.getElementById('addLayananModal').style.display = 'flex';
        }
        
        function closeAddLayananModal() {
            document.getElementById('addLayananModal').style.display = 'none';
        }
    </script>
    
    <!-- Modal Deskripsi Layanan -->
    <div id="descModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden justify-center items-center z-[9999] opacity-0 transition-opacity duration-300">
        <div class="bg-zinc-900/90 border border-zinc-700/50 text-zinc-200 w-[90vw] max-w-[600px] rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.5)] transform scale-95 transition-all duration-300 relative overflow-hidden" id="descModalContent">
            <!-- Glow effect -->
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <!-- Image Cover -->
            <div class="relative w-full h-72 bg-zinc-800">
                <img id="descModalImg" src="" alt="Layanan" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-zinc-900/95 via-zinc-900/40 to-transparent"></div>
                <button onclick="closeDescModal()" class="absolute top-4 right-4 text-zinc-300 hover:text-white bg-black/50 hover:bg-black/70 p-2.5 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-600 backdrop-blur-sm z-20">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <div class="relative z-10 px-8 pb-8 pt-0 -mt-12">
                <div class="flex flex-col gap-3 mb-6">
                    <h3 class="text-3xl font-bold text-white leading-tight drop-shadow-lg" id="descModalTitle">Nama Layanan</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="flex items-center gap-2 text-blue-400 bg-blue-500/10 border border-blue-500/20 w-fit px-4 py-1.5 rounded-full text-sm font-semibold uppercase tracking-wider backdrop-blur-sm">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            <span id="descModalDurasi">0 Menit</span>
                        </div>
                        <div class="flex items-center gap-2 text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 w-fit px-4 py-1.5 rounded-full text-sm font-semibold uppercase tracking-wider backdrop-blur-sm">
                            <i data-lucide="banknote" class="w-4 h-4"></i>
                            <span id="descModalHarga">Rp 0</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-black/40 border border-zinc-800/80 rounded-xl p-6 mb-2 shadow-inner">
                    <p id="descModalText" class="text-zinc-300 text-base leading-relaxed whitespace-pre-wrap"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Layanan -->
    <div id="addLayananModal" class="fixed inset-0 bg-black/70 hidden justify-center items-center z-[9999]">
        <div class="bg-adminlte-card border border-zinc-700 text-zinc-200 p-6 w-[400px] rounded-lg shadow-2xl">
            <div class="flex justify-between items-center mb-4 border-b border-zinc-700 pb-3">
                <h3 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="plus-circle" class="w-5 h-5 text-adminlte-primary"></i> Tambah Layanan</h3>
                <button onclick="closeAddLayananModal()" class="text-zinc-400 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form action="admin.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="form_type" value="add_layanan">
                <input type="hidden" name="current_page" value="layanan">
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Nama Layanan</label>
                    <input type="text" name="nama_layanan" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="Paket VIP" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Harga (Rp)</label>
                    <input type="number" name="harga" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="50000" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Durasi (Menit)</label>
                    <input type="number" name="durasi" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="30" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Deskripsi (Opsional)</label>
                    <textarea name="deskripsi" rows="3" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="Fitur layanan (pisahkan dengan Enter)"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Gambar Layanan</label>
                    <input type="file" name="gambar" accept="image/*" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-adminlte-primary file:text-white hover:file:bg-blue-600">
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full bg-adminlte-primary hover:bg-blue-700 text-white font-medium py-2.5 rounded-md transition-colors flex justify-center items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Layanan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit Layanan -->
    <div id="editLayananModal" class="fixed inset-0 bg-black/70 hidden justify-center items-center z-[9999]">
        <div class="bg-adminlte-card border border-zinc-700 text-zinc-200 p-6 w-[400px] rounded-lg shadow-2xl">
            <div class="flex justify-between items-center mb-4 border-b border-zinc-700 pb-3">
                <h3 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="edit" class="w-5 h-5 text-blue-400"></i> Edit Layanan</h3>
                <button onclick="closeEditLayananModal()" class="text-zinc-400 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form action="admin.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="form_type" value="edit_layanan">
                <input type="hidden" name="current_page" value="layanan">
                <input type="hidden" name="id" id="edit_layanan_id">
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Nama Layanan</label>
                    <input type="text" name="nama_layanan" id="edit_layanan_nama" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Harga (Rp)</label>
                    <input type="number" name="harga" id="edit_layanan_harga" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Durasi (Menit)</label>
                    <input type="number" name="durasi" id="edit_layanan_durasi" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Deskripsi (Opsional)</label>
                    <textarea name="deskripsi" id="edit_layanan_deskripsi" rows="3" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="Fitur layanan (pisahkan dengan Enter)"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Update Gambar Layanan (Biarkan kosong jika tidak diubah)</label>
                    <input type="file" name="gambar" accept="image/*" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-500 file:text-white hover:file:bg-blue-600">
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-md transition-colors flex justify-center items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Update Layanan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Akun -->
    <div id="addUserModal" class="fixed inset-0 bg-black/70 hidden justify-center items-center z-[9999]">
        <div class="bg-adminlte-card border border-zinc-700 text-zinc-200 p-6 w-[400px] rounded-lg shadow-2xl">
            <div class="flex justify-between items-center mb-4 border-b border-zinc-700 pb-3">
                <h3 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="user-plus" class="w-5 h-5 text-adminlte-primary"></i> Tambah User Baru</h3>
                <button onclick="closeAddUserModal()" class="text-zinc-400 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form action="admin.php" method="POST" class="space-y-4">
                <input type="hidden" name="form_type" value="add_user">
                <input type="hidden" name="current_page" value="akun">
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Nama Lengkap</label>
                    <input type="text" name="fullname" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="Contoh: Marco Rossi" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Username</label>
                    <input type="text" name="username" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="marcorossi" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Email</label>
                    <input type="email" name="email" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="nama@email.com" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">No. HP / WhatsApp</label>
                    <input type="text" name="phone" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="081234567890" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Password</label>
                    <input type="password" name="password" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="••••••••" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Role</label>
                    <select name="role" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                        <option value="admin">Admin</option>
                        <option value="barber">Barber</option>
                        <option value="pelanggan">Pelanggan</option>
                    </select>
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full bg-adminlte-primary hover:bg-blue-700 text-white font-medium py-2.5 rounded-md transition-colors flex justify-center items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Akun -->
    <div id="editUserModal" class="fixed inset-0 bg-black/70 hidden justify-center items-center z-[9999]">
        <div class="bg-adminlte-card border border-zinc-700 text-zinc-200 p-6 w-[400px] rounded-lg shadow-2xl">
            <div class="flex justify-between items-center mb-4 border-b border-zinc-700 pb-3">
                <h3 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="edit" class="w-5 h-5 text-blue-400"></i> Edit User</h3>
                <button onclick="closeEditUserModal()" class="text-zinc-400 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form action="admin.php" method="POST" class="space-y-4">
                <input type="hidden" name="form_type" value="edit_user">
                <input type="hidden" name="current_page" value="akun">
                <input type="hidden" name="id_user" id="edit_user_id">
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Nama Lengkap</label>
                    <input type="text" name="fullname" id="edit_user_fullname" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Username</label>
                    <input type="text" name="username" id="edit_user_username" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Email</label>
                    <input type="email" name="email" id="edit_user_email" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">No. HP / WhatsApp</label>
                    <input type="text" name="phone" id="edit_user_phone" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Role</label>
                    <select name="role" id="edit_user_role" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" required>
                        <option value="admin">Admin</option>
                        <option value="barber">Barber</option>
                        <option value="pelanggan">Pelanggan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-400 mb-1">Password Baru (Biarkan kosong jika tidak diganti)</label>
                    <input type="password" name="password" class="w-full bg-zinc-900 border border-zinc-700 rounded-md px-4 py-2 text-white focus:outline-none focus:border-adminlte-primary" placeholder="••••••••">
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-md transition-colors flex justify-center items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Cetak Struk (Untuk Panel Barber) -->
    <div id="receiptModal" class="fixed inset-0 bg-black/70 hidden justify-center items-center z-[9999]">
        <div class="bg-white text-black p-5 w-[300px] font-mono rounded-lg text-[13px]" id="printable-receipt">
            <span class="block text-center font-bold text-base mb-1">ELITE BARBERSHOP</span>
            <span class="block text-center text-\\[11px\\] mt-0">Jl. Nawawi Gelar Dalom, Sumberjo, Rajabasa Jaya, Bandarlampung</span>
            <span class="block text-center text-\\[11px\\] mt-0">Telp: 0857-8894-2309</span>
            <hr class="border-t border-dashed border-black my-2.5">
            <p class="my-1 flex justify-between"><span>No. Tiket</span> <span id="r_tiket"></span></p>
            <p class="my-1 flex justify-between"><span>Pelanggan</span> <span id="r_nama"></span></p>
            <p class="my-1 flex justify-between"><span>Layanan</span> <span id="r_layanan"></span></p>
            <hr class="border-t border-dashed border-black my-2.5">
            <p class="my-1 flex justify-between"><span>TOTAL</span> <span>Rp <span id="r_total"></span></span></p>
            <p class="my-1 flex justify-between"><span>PEMBAYARAN</span> <span id="r_metode"></span></p>
            <p class="my-1 flex justify-between"><span>STATUS</span> <span>LUNAS</span></p>
            <hr class="border-t border-dashed border-black my-2.5">
            <span class="block text-center text-\\[11px\\] mt-2.5">Terima kasih atas kunjungan Anda!</span>
            <span class="block text-center text-\\[11px\\] mt-0">IG: @</span>
            
            <button onclick="window.print()" class="no-print bg-adminlte-primary text-white w-full py-2 mt-4 rounded-md text-sm hover:bg-blue-600 transition-colors">🖨️ Cetak</button>
            <button onclick="closeStruk()" class="no-print bg-zinc-600 text-white w-full py-2 mt-2 rounded-md text-sm hover:bg-zinc-700 transition-colors">Tutup</button>
            <form id="form_confirm_paid" method="POST" style="display:none;" class="no-print mt-2">
                <input type="hidden" name="form_type" value="confirm_paid">
                <input type="hidden" name="current_page" value="barber">
                <input type="hidden" name="antrian_id" id="r_antrian_id" value="">
                <input type="hidden" name="total_harga" id="r_total_input" value="">
                <button type="submit" id="btn_confirm_paid" class="bg-adminlte-success text-white w-full py-2 rounded-md text-sm hover:bg-green-700 transition-colors">Konfirmasi Selesai</button>
            </form>
        </div>
    </div>
    
    <style>
        @media print {
            @page { margin: 0; } /* Menghilangkan header & footer bawaan browser saat print */
            body * { visibility: hidden; }
            #printable-receipt, #printable-receipt * { visibility: visible; }
            #printable-receipt { 
                position: absolute; left: 0; top: 0; 
                width: 100% !important; height: 100% !important; 
                margin: 0; padding: 40px !important; 
                font-size: 24px !important; box-sizing: border-box; 
                transform: none !important;
            }
            #printable-receipt span.text-base { font-size: 48px !important; margin-bottom: 20px !important; display: block; }
            #printable-receipt span.text-\\[11px\\] { font-size: 24px !important; margin-bottom: 10px !important; display: block; }
            #printable-receipt hr { margin: 30px 0 !important; border-top: 2px dashed #000 !important; }
            #printable-receipt p { margin: 20px 0 !important; font-size: 24px !important; }
            #printable-receipt .no-print { display: none !important; }
        }
    </style>

    <script>
        function openCardModal(modalId) {
            const backdrop = document.getElementById('cardModalBackdrop');
            if (!backdrop) return;
            
            // Hide all card modal content items first
            document.querySelectorAll('.card-modal-content').forEach(el => el.classList.add('hidden'));
            
            // Show selected modal content
            const target = document.getElementById(modalId);
            if (target) {
                target.classList.remove('hidden');
                backdrop.classList.remove('hidden');
                backdrop.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeCardModal() {
            const backdrop = document.getElementById('cardModalBackdrop');
            if (!backdrop) return;
            backdrop.classList.add('hidden');
            backdrop.classList.remove('flex');
            document.body.style.overflow = '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const backdrop = document.getElementById('cardModalBackdrop');
            if (backdrop) {
                backdrop.addEventListener('click', function(e) {
                    if (e.target === backdrop) {
                        closeCardModal();
                    }
                });
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeCardModal();
                }
            });
        });

        function printStruk(tiket, nama, layanan, total, metode, antrian_id = null, is_new_payment = false) {
            document.getElementById('r_tiket').innerText = tiket;
            document.getElementById('r_nama').innerText = nama;
            document.getElementById('r_layanan').innerText = layanan;
            document.getElementById('r_total').innerText = parseInt(total).toLocaleString('id-ID');
            document.getElementById('r_metode').innerText = metode;
            
            if (is_new_payment && antrian_id) {
                document.getElementById('r_antrian_id').value = antrian_id;
                document.getElementById('r_total_input').value = total;
                document.getElementById('form_confirm_paid').style.display = 'block';
            } else {
                document.getElementById('form_confirm_paid').style.display = 'none';
            }
            document.getElementById('receiptModal').style.display = 'flex';
        }
        
        function closeStruk() {
            document.getElementById('receiptModal').style.display = 'none';
        }


        let notifiedTickets = [];
        function checkNewPayments() {
            fetch('api_check_payment.php')
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success' && res.data) {
                        let q = res.data;
                        if (!notifiedTickets.includes(q.id)) {
                            notifiedTickets.push(q.id);
                            let audio = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
                            audio.play().catch(e => {});
                            printStruk(
                                q.no_antrean, 
                                q.pelanggan_nama || 'Guest', 
                                q.nama_layanan || 'Layanan', 
                                q.final_price, 
                                q.metode_bayar || 'Cash/QRIS',
                                q.id,
                                true
                            );
                        }
                    }
                }).catch(err => console.error(err));
        }
        setInterval(checkNewPayments, 5000);
        

        // Initialize Tabulator when ready
        let tables = {};
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                const cleanHtml = function(value) {
                    if (typeof value === 'string') {
                        let tmp = document.createElement("DIV");
                        tmp.innerHTML = value;
                        return (tmp.textContent || tmp.innerText || "").trim().replace(/\s+/g, ' ');
                    }
                    return value;
                };
                
                const commonConfig = {
                    layout: "fitColumns",
                    renderVertical: "basic",
                    pagination: "local",
                    paginationSize: 10,
                    paginationSizeSelector: [10, 25, 50, 100],
                    movableColumns: true,
                    columnDefaults: {
                        accessorDownload: cleanHtml,
                        accessorPrint: cleanHtml
                    },
                    printAsHtml: true,
                    printStyled: true
                };

                // Initialize Layanan Table
                if (document.getElementById('table-layanan')) {
                    tables['table-layanan'] = new Tabulator("#table-layanan", commonConfig);
                    tables['table-layanan'].on("tableBuilt", function() {
                        document.getElementById('table-layanan').classList.add('table-loaded');
                    });
                    tables['table-layanan'].on("renderComplete", function() {
                        lucide.createIcons();
                    });
                    const searchLayanan = document.getElementById("search-layanan");
                    if (searchLayanan) {
                        searchLayanan.addEventListener("keyup", function(){
                            let term = this.value.toLowerCase();
                            tables['table-layanan'].setFilter(function(data){
                                for(let key in data) {
                                    if(String(data[key]).toLowerCase().includes(term)) return true;
                                }
                                return false;
                            });
                        });
                    }
                }
                
                // Initialize Users Table
                if (document.getElementById('table-users')) {
                    tables['table-users'] = new Tabulator("#table-users", commonConfig);
                    tables['table-users'].on("tableBuilt", function() {
                        document.getElementById('table-users').classList.add('table-loaded');
                    });
                    tables['table-users'].on("renderComplete", function() {
                        lucide.createIcons();
                    });
                    const searchUsers = document.getElementById("search-users");
                    if (searchUsers) {
                        searchUsers.addEventListener("keyup", function(){
                            let term = this.value.toLowerCase();
                            tables['table-users'].setFilter(function(data){
                                for(let key in data) {
                                    if(String(data[key]).toLowerCase().includes(term)) return true;
                                }
                                return false;
                            });
                        });
                    }
                }
                
                // Initialize Transaksi Table
                if (document.getElementById('table-transaksi')) {
                    tables['table-transaksi'] = new Tabulator("#table-transaksi", commonConfig);
                    tables['table-transaksi'].on("tableBuilt", function() {
                        document.getElementById('table-transaksi').classList.add('table-loaded');
                    });
                    tables['table-transaksi'].on("renderComplete", function() {
                        lucide.createIcons();
                    });
                    const searchTransaksi = document.getElementById("search-transaksi");
                    if (searchTransaksi) {
                        searchTransaksi.addEventListener("keyup", function(){
                            let term = this.value.toLowerCase();
                            tables['table-transaksi'].setFilter(function(data){
                                for(let key in data) {
                                    if(String(data[key]).toLowerCase().includes(term)) return true;
                                }
                                return false;
                            });
                        });
                    }
                }
            }, 100);
        });

        function exportData(tableId, format) {
            let headers = [];
            let rows = [];

            // Attempt to fetch data from Tabulator instance if initialized
            if (tables && tables[tableId]) {
                let tab = tables[tableId];
                tab.getColumns().forEach(col => {
                    let title = col.getDefinition().title || col.getField();
                    if (title && title.toLowerCase() !== 'aksi' && title !== '') {
                        headers.push(title);
                    }
                });
                let activeRows = tab.getData("active");
                activeRows.forEach((rowObj, index) => {
                    let rowData = [];
                    tab.getColumns().forEach(col => {
                        let field = col.getField();
                        let title = col.getDefinition().title || field;
                        if (title && title.toLowerCase() !== 'aksi' && title !== '') {
                            let val = rowObj[field];
                            if (field === 'no' || !val) {
                                if (field === 'no') val = String(index + 1);
                                else val = val || '';
                            }
                            if (typeof val === 'string') {
                                let tmp = document.createElement("DIV");
                                tmp.innerHTML = val;
                                tmp.querySelectorAll('button, form, script, style, input, .hidden, [class*="hidden"], [style*="display:none"], [style*="display: none"]').forEach(el => el.remove());
                                val = (tmp.textContent || tmp.innerText || "").trim().replace(/\s+/g, ' ');
                            }
                            rowData.push(val);
                        }
                    });
                    rows.push(rowData);
                });
            } else {
                const table = document.getElementById(tableId);
                if (!table) { alert("Tabel tidak ditemukan!"); return; }
                
                let ths = table.querySelectorAll("thead th");
                ths.forEach(th => {
                    let txt = th.innerText.trim();
                    if (txt && txt.toLowerCase() !== 'aksi' && txt !== '') headers.push(txt);
                });
                
                let trs = table.querySelectorAll("tbody tr");
                trs.forEach((tr, idx) => {
                    let rowData = [];
                    let tds = tr.querySelectorAll("td");
                    tds.forEach((td, colIdx) => {
                        if (colIdx < headers.length) {
                            let clone = td.cloneNode(true);
                            clone.querySelectorAll('button, form, script, style, input, .hidden, [class*="hidden"], [style*="display:none"], [style*="display: none"]').forEach(el => el.remove());
                            let txt = clone.innerText.trim().replace(/\s+/g, ' ');
                            if (colIdx === 0 && (!txt || txt === '')) txt = String(idx + 1);
                            rowData.push(txt);
                        }
                    });
                    if (rowData.length > 0) rows.push(rowData);
                });
            }

            if (rows.length === 0) {
                alert("Tidak ada data untuk diekspor!");
                return;
            }

            const fileName = tableId.replace('table-', 'laporan_') + '_' + new Date().toISOString().slice(0,10);
            doActualDownload(tableId, format, headers, rows, fileName);
        }

        function doActualDownload(tableId, format, headers, rows, fileName) {
            // 1. CSV EXPORT
            if (format === 'csv') {
                let csvContent = [headers.map(h => '"' + h.replace(/"/g, '""') + '"').join(",")];
                rows.forEach(r => {
                    csvContent.push(r.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(","));
                });
                let blob = new Blob(["\ufeff" + csvContent.join("\n")], { type: "text/csv;charset=utf-8;" });
                let link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = fileName + ".csv";
                link.click();
            }
            // 2. EXCEL (XLSX) EXPORT
            else if (format === 'xlsx') {
                if (typeof XLSX !== 'undefined') {
                    let aoa = [headers, ...rows];
                    let wb = XLSX.utils.book_new();
                    let ws = XLSX.utils.aoa_to_sheet(aoa);
                    XLSX.utils.book_append_sheet(wb, ws, "Data");
                    XLSX.writeFile(wb, fileName + ".xlsx");
                } else {
                    alert("Library XLSX (SheetJS) tidak tersedia.");
                }
            }
            // 3. PDF EXPORT (Inline preview in new tab using browser native PDF viewer)
            else if (format === 'pdf') {
                const jsPDFObj = window.jspdf ? window.jspdf.jsPDF : (window.jsPDF || null);
                if (jsPDFObj) {
                    const doc = new jsPDFObj({ orientation: 'landscape' });
                    doc.setFontSize(14);
                    doc.text("Laporan Data " + tableId.replace('table-', '').toUpperCase(), 14, 15);
                    doc.setFontSize(10);
                    doc.text("Tanggal: " + new Date().toLocaleDateString('id-ID'), 14, 22);
                    
                    doc.autoTable({
                        head: [headers],
                        body: rows,
                        startY: 28,
                        styles: { fontSize: 9, cellPadding: 3 },
                        headStyles: { fillColor: [40, 30, 20], textColor: [232, 213, 163], fontStyle: 'bold' },
                        alternateRowStyles: { fillColor: [245, 245, 245] }
                    });

                    const blob = doc.output('blob');
                    const pdfUrl = URL.createObjectURL(blob);
                    window.open(pdfUrl, '_blank');
                } else {
                    alert("Library jsPDF tidak tersedia.");
                }
            }
            // 4. PRINT EXPORT
            else if (format === 'print') {
                let printWin = window.open('', '_blank', 'width=950,height=700');
                let html = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Cetak Data ${tableId}</title>
                        <style>
                            body { font-family: Arial, sans-serif; padding: 20px; color: #111; }
                            h2 { margin-bottom: 5px; color: #333; }
                            p { font-size: 12px; color: #666; margin-top: 0; margin-bottom: 20px; }
                            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                            th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; font-size: 12px; }
                            th { background-color: #2a1e12; color: #e8d5a3; font-weight: bold; }
                            tr:nth-child(even) { background-color: #f9f9f9; }
                            @media print {
                                body { padding: 0; }
                                @page { size: landscape; margin: 1cm; }
                            }
                        </style>
                    </head>
                    <body>
                        <h2>Laporan Data ${tableId.replace('table-', '').toUpperCase()}</h2>
                        <p>Dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
                        <table>
                            <thead>
                                <tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>
                            </thead>
                            <tbody>
                                ${rows.map(r => `<tr>${r.map(v => `<td>${v}</td>`).join('')}</tr>`).join('')}
                            </tbody>
                        </table>
                        <script>
                            window.onload = function() {
                                window.print();
                                setTimeout(function(){ window.close(); }, 500);
                            };
                        <' + '/script>
                    </body>
                    </html>
                `;
                printWin.document.open();
                printWin.document.write(html);
                printWin.document.close();
            }
        }

        // Real-time Clock
        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const clockString = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()} | ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
            const clockEl = document.getElementById('realtime-clock');
            if (clockEl) clockEl.textContent = clockString;
        }
        setInterval(updateClock, 1000); 
        updateClock();

        // Sidebar Toggle with Smooth State Persistence
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');

        function applySidebarState(isMinimized) {
            if (isMinimized) {
                sidebar.classList.remove('w-64'); 
                sidebar.classList.add('w-20');
            } else {
                sidebar.classList.remove('w-20'); 
                sidebar.classList.add('w-64');
            }
        }

        if (sidebarToggle && sidebar) {
            // Load state without transition glitch
            const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
            
            // Toggle on click
            sidebarToggle.addEventListener('click', () => {
                const willMinimize = sidebar.classList.contains('w-64');
                localStorage.setItem('sidebarMinimized', willMinimize);
                applySidebarState(willMinimize);
            });
        }

        // ================= ADMIN REGISTRATION NOTIFICATION SYSTEM =================
        let notifiedUserIds = [];

        function toggleNotifDropdown(e) {
            if (e) e.stopPropagation();
            const dropdown = document.getElementById('notif-dropdown-menu');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', function(e) {
            const container = document.getElementById('admin-notif-container');
            const dropdown = document.getElementById('notif-dropdown-menu');
            if (container && dropdown && !container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        function getNotifIcon(type) {
            if (type === 'add_layanan') return 'scissors';
            if (type === 'new_transaction') return 'banknote';
            return 'user-plus';
        }

        function fetchNotifications() {
            fetch('admin.php?action=get_unread_notif')
                .then(res => res.json())
                .then(data => {
                    if (data && data.status) {
                        const count = data.unread_count || 0;
                        const badge = document.getElementById('notif-badge');
                        const listContainer = document.getElementById('notif-list-container');

                        if (badge) {
                            if (count > 0) {
                                badge.textContent = count;
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        }

                        if (listContainer) {
                            if (!data.notifications || data.notifications.length === 0) {
                                listContainer.innerHTML = '<div class="p-4 text-center text-xs text-zinc-400">Tidak ada notifikasi baru</div>';
                            } else {
                                let html = '';
                                data.notifications.forEach(n => {
                                    const iconName = getNotifIcon(n.type);
                                    const safeLink = (n.link || '').replace(/'/g, "\\'");
                                    html += `
                                        <div onclick="handleNotifClick(${n.id}, '${safeLink}')" class="p-3 hover:bg-amber-900/30 transition-colors cursor-pointer flex items-start gap-3">
                                            <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 mt-0.5 border border-amber-500/30">
                                                <i data-lucide="${iconName}" class="w-4 h-4"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold text-xs text-amber-200">${n.title || 'Notifikasi'}</p>
                                                <p class="text-xs text-zinc-300 truncate">${n.message}</p>
                                                <span class="text-[10px] text-zinc-400 mt-1 block">${n.created_at || ''}</span>
                                            </div>
                                        </div>
                                    `;
                                });
                                listContainer.innerHTML = html;
                                if (window.lucide) lucide.createIcons();
                            }
                        }

                        // Consolidated Notification Alert (Groups 2 or more new notifications into 1 single modal)
                        const unnotifiedItems = (data.notifications || []).filter(n => !notifiedUserIds.includes(n.id));
                        if (unnotifiedItems.length > 0) {
                            unnotifiedItems.forEach(n => notifiedUserIds.push(n.id));
                            showConsolidatedNotificationModal(unnotifiedItems, data.notifications || []);
                        }
                    }
                })
                .catch(err => console.error("Error fetching notifications:", err));
        }

        function handleNotifClick(notifId, targetLink) {
            const dropdown = document.getElementById('notif-dropdown-menu');
            if (dropdown) dropdown.classList.add('hidden');

            fetch(`admin.php?action=mark_notif_read&id=${notifId}`)
                .then(res => res.json())
                .then(() => {
                    fetchNotifications();
                    if (targetLink) {
                        if (targetLink.includes('#card-pendaftaran-baru')) {
                            scrollToRegistrationCard();
                        } else {
                            window.location.href = targetLink;
                        }
                    } else {
                        scrollToRegistrationCard();
                    }
                });
        }

        function markAllNotifRead() {
            fetch('admin.php?action=mark_notif_read')
                .then(res => res.json())
                .then(() => {
                    fetchNotifications();
                });
        }

        // Web Audio Synthesizer for Notification Chime (2-Tone Pleasant Bell)
        function playNotifChime() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                
                // Tone 1
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, ctx.currentTime); // D5 note
                gain1.gain.setValueAtTime(0.3, ctx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(ctx.currentTime);
                osc1.stop(ctx.currentTime + 0.4);

                // Tone 2 (Higher note)
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880, ctx.currentTime + 0.15); // A5 note
                gain2.gain.setValueAtTime(0.4, ctx.currentTime + 0.15);
                gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(ctx.currentTime + 0.15);
                osc2.stop(ctx.currentTime + 0.6);
            } catch (e) {}
        }

        function showConsolidatedNotificationModal(newItems, allUnread) {
            playNotifChime();

            if (!window.Swal) return;

            const totalCount = allUnread.length > 0 ? allUnread.length : newItems.length;
            const isMultiple = totalCount > 1;

            let modalTitle = '🔔 NOTIFIKASI BARU!';
            if (isMultiple) {
                modalTitle = `🔔 ${totalCount} NOTIFIKASI BARU SISTEM!`;
            } else {
                const singleType = (newItems[0] || allUnread[0])?.type;
                if (singleType === 'add_layanan') {
                    modalTitle = `✂️ LAYANAN BARU DITAMBAHKAN!`;
                } else if (singleType === 'new_transaction') {
                    modalTitle = `💰 TRANSAKSI BARU DITERIMA!`;
                } else {
                    modalTitle = `🔔 1 PELANGGAN BARU MENDAFTAR!`;
                }
            }

            let listHtml = '';
            if (isMultiple) {
                listHtml += `<div class="space-y-2 max-h-52 overflow-y-auto custom-scroll text-left p-1 my-3">`;
                allUnread.forEach(n => {
                    const iconName = getNotifIcon(n.type);
                    listHtml += `
                        <div class="p-3 bg-zinc-900/90 rounded-xl border border-amber-500/30 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/40 flex items-center justify-center font-bold text-xs shrink-0">
                                    <i data-lucide="${iconName}" class="w-4 h-4"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-amber-200 text-xs truncate">${n.title || 'Notifikasi'}</p>
                                    <p class="text-xs text-zinc-300 truncate">${n.message}</p>
                                    <span class="text-[10px] text-zinc-400 block mt-0.5">${n.created_at || 'Baru saja'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                listHtml += `</div>`;
            } else {
                const single = newItems[0] || allUnread[0];
                const iconName = getNotifIcon(single.type);
                listHtml = `
                    <div class="p-4 bg-zinc-900/90 rounded-2xl border border-amber-500/40 my-3 text-left shadow-inner">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/40 flex items-center justify-center font-bold text-xl shrink-0 shadow-md">
                                <i data-lucide="${iconName}" class="w-6 h-6"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-amber-200 text-base mb-0.5">${single.title || 'Notifikasi Baru'}</p>
                                <p class="text-sm text-zinc-300 leading-snug">${single.message}</p>
                            </div>
                        </div>
                        <p class="text-[11px] text-amber-400/80 mt-2.5 pt-2 border-t border-zinc-800 text-right font-mono">Waktu: ${single.created_at || 'Baru saja'}</p>
                    </div>
                `;
            }

            const firstLink = (newItems[0] || allUnread[0])?.link || 'admin.php?page=akun#card-pendaftaran-baru';
            const confirmText = isMultiple ? `🚀 Lihat ${totalCount} Notifikasi` : `🚀 Lihat Detail`;

            Swal.fire({
                title: modalTitle,
                html: `
                    ${listHtml}
                    <p class="text-xs text-zinc-400 mt-2">Klik tombol di bawah untuk langsung menuju ke halaman detail terkait.</p>
                `,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'Tutup Alert',
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#3f3f46',
                background: '#18120b',
                color: '#fff',
                customClass: {
                    popup: 'border-2 border-amber-500 shadow-[0_0_60px_rgba(245,158,11,0.6)] rounded-2xl p-6',
                    title: 'text-amber-400 text-xl font-bold font-serif tracking-wide',
                    confirmButton: 'px-5 py-3 rounded-xl font-bold shadow-lg shadow-amber-500/40 text-black hover:scale-105 transition-transform'
                },
                didOpen: () => {
                    if (window.lucide) lucide.createIcons();
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    markAllNotifRead();
                    if (firstLink.includes('#card-pendaftaran-baru')) {
                        scrollToRegistrationCard();
                    } else {
                        window.location.href = firstLink;
                    }
                }
            });
        }

        function scrollToRegistrationCard() {
            const card = document.getElementById('card-pendaftaran-baru');
            if (card) {
                // Smooth scroll to card
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Apply intense glowing amber ring highlight effect
                card.classList.add('ring-4', 'ring-amber-500', 'shadow-[0_0_45px_rgba(245,158,11,0.8)]', 'scale-[1.02]');
                setTimeout(() => {
                    card.classList.remove('ring-4', 'ring-amber-500', 'shadow-[0_0_45px_rgba(245,158,11,0.8)]', 'scale-[1.02]');
                }, 4500);
            } else {
                // If on a different sub-page, redirect to page=akun
                window.location.href = 'admin.php?page=akun#card-pendaftaran-baru';
            }
        }

        // Auto Poll Notifications every 6 seconds
        setInterval(fetchNotifications, 6000);
        document.addEventListener('DOMContentLoaded', () => {
            fetchNotifications();
            // Check if hash points to card-pendaftaran-baru
            if (window.location.hash === '#card-pendaftaran-baru') {
                setTimeout(scrollToRegistrationCard, 500);
            }
        });</script>
</body>
</html>





