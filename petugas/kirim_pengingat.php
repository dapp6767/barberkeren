<?php
/**
 * ==============================================================================
 * PEMROSES PENGINGAT WHATSAPP (FONNTE GATEWAY)
 * Elite Barber System
 * ==============================================================================
 * File ini menangani pengiriman pesan WhatsApp kepada pelanggan yang antreannya
 * sebentar lagi akan tiba (~10 menit sebelum giliran) menggunakan API Fonnte.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/koneksi.php';
require_once __DIR__ . '/../functions/helper.php';
require_once __DIR__ . '/../config/database.php';

// Pastikan file notifikasi jika ada dimuat untuk pencatatan riwayat
if (file_exists(__DIR__ . '/../functions/notifikasi.php')) {
    require_once __DIR__ . '/../functions/notifikasi.php';
}

// 1. Proteksi Hak Akses (Hanya Barber dan Admin yang dapat memanggil pelanggan)
if (!function_exists('is_logged_in') || !is_logged_in() || !in_array($_SESSION['user_role'] ?? '', ['admin', 'barber'])) {
    if (function_exists('set_flash')) {
        set_flash('danger', 'Akses ditolak! Silakan login sebagai Barber atau Admin.');
    }
    header('Location: ../auth/login.php');
    exit;
}

// 2. Ambil ID Antrean (Mendukung request POST maupun GET)
$id_antrean = (int)($_POST['id_antrean'] ?? ($_POST['antrian_id'] ?? ($_GET['id_antrean'] ?? ($_GET['antrian_id'] ?? 0))));

if ($id_antrean <= 0) {
    if (function_exists('set_flash')) {
        set_flash('danger', 'ID Antrean tidak valid!');
    }
    header('Location: barber.php');
    exit;
}

// 3. Fungsi cURL Pemanggilan API Fonnte
if (!function_exists('kirim_whatsapp_fonnte')) {
    function kirim_whatsapp_fonnte($target, $pesan, $token = 'MASUKKAN_TOKEN_FONNTE_KAMU') {
        // Bersihkan nomor target dari spasi, tanda hubung, atau karakter selain angka
        $target_clean = preg_replace('/[^0-9]/', '', $target);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'target'      => $target_clean,
                'message'     => $pesan,
                'countryCode' => '62', // Otomatis mengonversi format nomor lokal 08xxx ke +628xxx
            ],
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $token,
            ],
            // Kompatibilitas lingkungan server lokal (Laragon / Windows / SSL Certificate Bundle)
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            return [
                'status'  => false,
                'message' => 'Koneksi cURL gagal: ' . $curl_error
            ];
        }

        $res_json = json_decode($response, true);

        // Evaluasi respon dari Fonnte
        if ($res_json && isset($res_json['status']) && $res_json['status'] == true) {
            return [
                'status'   => true,
                'message'  => 'Pesan WhatsApp berhasil dikirim.',
                'response' => $res_json
            ];
        } else {
            $reason = $res_json['reason'] ?? ($res_json['message'] ?? 'Respon API Fonnte tidak berhasil (HTTP ' . $http_code . ')');
            return [
                'status'   => false,
                'message'  => $reason,
                'response' => $res_json
            ];
        }
    }
}

// 4. Query Data Antrean & Relasi Barber Menggunakan PDO Prepared Statements
$pdo = get_koneksi();

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id, 
            a.no_antrean, 
            a.status_antrean, 
            a.waktu_dibuat,
            u.id_user,
            u.fullname, 
            u.username, 
            u.phone,
            b.id AS barber_id,
            b.nama AS barber_nama, 
            b.kursi,
            l.nama_layanan
        FROM antrian a
        LEFT JOIN users u ON a.pelanggan_id = u.id_user
        LEFT JOIN barber b ON a.barber_id = b.id
        LEFT JOIN layanan l ON a.layanan_id = l.id
        WHERE a.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id_antrean]);
    $antrean = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$antrean) {
        set_flash('danger', 'Data antrean tidak ditemukan di dalam sistem!');
        header('Location: barber.php');
        exit;
    }

    // Ekstraksi field-field yang diperlukan
    $nama_pelanggan = !empty($antrean['fullname']) 
        ? trim($antrean['fullname']) 
        : (!empty($antrean['username']) ? trim($antrean['username']) : 'Pelanggan');

    $no_wa = trim($antrean['phone'] ?? '');

    // Validasi ketersediaan nomor WhatsApp pelanggan
    if (empty($no_wa)) {
        set_flash('danger', "Gagal: Pelanggan <b>" . htmlspecialchars($nama_pelanggan) . "</b> belum melengkapi nomor WhatsApp / HP pada profil akunnya.");
        header('Location: barber.php');
        exit;
    }

    $no_antrean = $antrean['no_antrean'];

    // Nama Barber: jika relasi antrean belum mengikat barber, gunakan nama kapster yang sedang login
    $nama_barber = !empty($antrean['barber_nama']) 
        ? trim($antrean['barber_nama']) 
        : ($_SESSION['fullname'] ?? ($_SESSION['username'] ?? 'Kapster Elite Barber'));

    // Jam booking / jadwal antrean dari waktu dibuat
    $jam_booking = !empty($antrean['waktu_dibuat']) ? date('H:i', strtotime($antrean['waktu_dibuat'])) : date('H:i');

    // 5. Susun Template Pesan Personal Sesuai Format Spesifikasi
    $pesan = "Halo, *{$nama_pelanggan}*! 👋\n"
           . "Antrean kamu di *Elite Barber* sebentar lagi akan tiba.\n\n"
           . "📌 *Nomor Antrean:* {$no_antrean}\n"
           . "✂️ *Barber:* {$nama_barber}\n"
           . "⏰ *Jadwal:* {$jam_booking} WIB\n\n"
           . "Kursi cukur sebentar lagi siap (estimasi ~10 menit lagi). Silakan segera merapat ke barbershop agar giliranmu tidak terlewat. Ditunggu ya!";

    // 6. Token API Fonnte
    // Token dapat diambil dari file .env (FONNTE_TOKEN) atau langsung menggunakan string token di bawah
    $token_fonnte = getenv('FONNTE_TOKEN') ?: ($_ENV['FONNTE_TOKEN'] ?? 'MASUKKAN_TOKEN_FONNTE_KAMU');

    // 7. Eksekusi Pengiriman Pesan
    $hasil = kirim_whatsapp_fonnte($no_wa, $pesan, $token_fonnte);

    if ($hasil['status']) {
        // Catat notifikasi sistem jika fungsi tersedia
        if (function_exists('create_admin_notification')) {
            create_admin_notification(
                'wa_reminder',
                'Pengingat WA Terkirim',
                "Kapster {$nama_barber} telah mengirimkan pengingat WhatsApp ke {$nama_pelanggan} (Antrean: {$no_antrean})",
                "barber.php"
            );
        }

        set_flash('success', "Notifikasi pengingat WhatsApp berhasil dikirim ke <b>" . htmlspecialchars($nama_pelanggan) . "</b> ({$no_wa})!");
    } else {
        // Deteksi jika token masih berupa placeholder bawaan
        if ($token_fonnte === 'MASUKKAN_TOKEN_FONNTE_KAMU' || empty($token_fonnte)) {
            set_flash('warning', "Pesan belum dapat terkirim karena <b>Token Fonnte</b> masih berupa placeholder ('MASUKKAN_TOKEN_FONNTE_KAMU'). Silakan atur token Fonnte aktif Anda pada file <code>.env</code> atau file <code>petugas/kirim_pengingat.php</code>.");
        } else {
            set_flash('danger', "Gagal mengirim WhatsApp ke " . htmlspecialchars($nama_pelanggan) . ": " . htmlspecialchars($hasil['message']));
        }
    }

} catch (PDOException $e) {
    set_flash('danger', 'Terjadi kesalahan basis data: ' . $e->getMessage());
}

// 8. Redirect Kembali ke Dashboard Barber
header('Location: barber.php');
exit;
