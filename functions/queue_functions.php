<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helper.php';

/**
 * Helper internal untuk mendapatkan koneksi PDO secara aman
 */
function get_db_connection() {
    global $pdo;

    if (!isset($pdo) || !$pdo instanceof PDO) {
        die("<b>Error Database:</b> Variabel \$pdo tidak ditemukan atau bernilai null. Pastikan file <code>config/database.php</code> membuat koneksi PDO dan menyimpannya di variabel <code>\$pdo</code>.");
    }

    return $pdo;
}

/**
 * Generate Ticket Number (contoh: A-01, A-02) berdasarkan antrean hari ini
 */
function determine_queue_letter($barber_id) {
    $pdo = get_db_connection();
    
    // Map barber ID to Queue Letter (Assuming IDs 1st active is A, 2nd is B, 3rd is C)
    // To make it dynamic, let's get active barbers and assign A, B, C based on their order.
    $stmt = $pdo->query("SELECT id FROM barber WHERE status = 'aktif' OR status = 'Aktif' ORDER BY id ASC");
    $barbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $map = [];
    $letters = ['A', 'B', 'C', 'D', 'E'];
    foreach ($barbers as $index => $id) {
        $map[$id] = $letters[$index] ?? 'Z';
    }

    if (!empty($barber_id) && isset($map[$barber_id])) {
        return ['letter' => $map[$barber_id], 'barber_id' => $barber_id];
    }

    // If barber_id is not specified (Bebas), find the one with the shortest queue.
    // Prioritize A, then B, then C if tie.
    $counts = [];
    foreach ($map as $id => $letter) {
        $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM antrian WHERE barber_id = ? AND status_antrean IN ('waiting', 'serving') AND DATE(waktu_dibuat) = CURDATE()");
        $stmtCnt->execute([$id]);
        $counts[$id] = $stmtCnt->fetchColumn();
    }
    
    // Find min count
    if (empty($counts)) {
        return ['letter' => 'A', 'barber_id' => null];
    }

    $min_count = min($counts);
    $selected_barber_id = null;
    $selected_letter = 'Z';
    
    foreach ($map as $id => $letter) {
        if ($counts[$id] == $min_count) {
            $selected_barber_id = $id;
            $selected_letter = $letter;
            break; // take the first one that matches min (prioritizes A over B)
        }
    }
    
    return ['letter' => $selected_letter, 'barber_id' => $selected_barber_id];
}

function generate_ticket_number($letter) {
    $pdo = get_db_connection();
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(no_antrean, 3) AS UNSIGNED)) as max_num FROM antrian WHERE no_antrean LIKE ? AND DATE(waktu_dibuat) = ?");
    $stmt->execute([$letter . '-%', $today]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $next_num = ($row && $row['max_num'] !== null) ? $row['max_num'] + 1 : 1;
    return $letter . '-' . str_pad($next_num, 2, '0', STR_PAD_LEFT);
}

/**
 * Take New Ticket
 * Wajib melampirkan customer_id (ID User yang sudah login)
 */
function take_queue_ticket($customer_name, $barber_id = null, $service_id = null, $customer_id = null) {
    $pdo = get_db_connection();

    // 1. Validasi Login di Sisi Server
    if (empty($customer_id)) {
        return [
            'status' => false, 
            'message' => 'Anda harus login terlebih dahulu untuk mengambil nomor antrean.'
        ];
    }

    // 2. Cegah pengambilan tiket ganda jika user masih dalam antrean aktif
    $stmtCheck = $pdo->prepare("SELECT no_antrean FROM antrian WHERE pelanggan_id = ? AND status_antrean IN ('waiting', 'serving') LIMIT 1");
    $stmtCheck->execute([$customer_id]);
    $active_queue = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($active_queue) {
        return [
            'status' => false,
            'message' => 'Anda sudah memiliki antrean aktif (Nomor: <b>' . htmlspecialchars($active_queue['no_antrean']) . '</b>). Selesaikan antrean tersebut terlebih dahulu.'
        ];
    }

    // 3. Tentukan Antrean (A, B, C)
    $queue_info = determine_queue_letter($barber_id);
    $selected_letter = $queue_info['letter'];
    $assigned_barber_id = $queue_info['barber_id'];
    
    $service_id = !empty($service_id) ? (int)$service_id : null;
    $ticket_no = generate_ticket_number($selected_letter);
    
    // 4. Hitung Estimasi Waktu Tunggu untuk Barisan Spesifik ini
    $stmtEst = $pdo->prepare("SELECT COUNT(*) as waiting_count FROM antrian WHERE barber_id = ? AND status_antrean = 'waiting'");
    $stmtEst->execute([$assigned_barber_id]);
    $rowEst = $stmtEst->fetch(PDO::FETCH_ASSOC);
    
    $waiting = ($rowEst && isset($rowEst['waiting_count'])) ? $rowEst['waiting_count'] : 0;
    $est_wait = $waiting * 15;

    // 5. Simpan Antrean
    $stmt = $pdo->prepare("INSERT INTO antrian (no_antrean, pelanggan_id, barber_id, layanan_id, status_antrean) VALUES (?, ?, ?, ?, 'waiting')");
    $success = $stmt->execute([$ticket_no, $customer_id, $assigned_barber_id, $service_id]);

    if ($success) {
        return [
            'status' => true, 
            'ticket_number' => $ticket_no, 
            'est_wait' => $est_wait
        ];
    }

    return ['status' => false, 'message' => 'Gagal mengambil nomor antrean. Silakan coba lagi.'];
}

/**
 * Get Currently Served Queue
 */
function get_current_serving_queue() {
    $pdo = get_db_connection();
    
    $stmt = $pdo->prepare("SELECT q.*, q.no_antrean as ticket_number, u.username as customer_name, COALESCE(b.nama, u_staff.fullname, u_staff.username, 'Bebas') as barber_name, s.nama_layanan as service_name 
                           FROM antrian q 
                           LEFT JOIN barber b ON q.barber_id = b.id 
                           LEFT JOIN layanan s ON q.layanan_id = s.id 
                           LEFT JOIN users u ON q.pelanggan_id = u.id_user
                           LEFT JOIN users u_staff ON q.served_by_user_id = u_staff.id_user
                           WHERE q.status_antrean = 'serving' 
                           ORDER BY q.waktu_dibuat DESC LIMIT 1");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get All Active Queues (Serving & Waiting)
 */
function get_active_queues() {
    $pdo = get_db_connection();
    
    $stmt = $pdo->prepare("SELECT q.*, q.no_antrean as ticket_number, u.username as customer_name, COALESCE(b.nama, u_staff.fullname, u_staff.username, 'Bebas') as barber_name, s.nama_layanan as service_name, s.harga as base_price, b.multiplier as barber_multiplier 
                           FROM antrian q 
                           LEFT JOIN barber b ON q.barber_id = b.id 
                           LEFT JOIN layanan s ON q.layanan_id = s.id 
                           LEFT JOIN users u ON q.pelanggan_id = u.id_user
                           LEFT JOIN users u_staff ON q.served_by_user_id = u_staff.id_user
                           WHERE q.status_antrean IN ('serving', 'waiting') 
                           ORDER BY q.id ASC");
    $stmt->execute();
    
    $queues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add estimated wait min for UI compatibility
    $wait_accum = 0;
    foreach ($queues as &$q) {
        if ($q['status_antrean'] == 'waiting') {
            $q['estimated_wait_min'] = $wait_accum;
            $wait_accum += 15;
        } else {
            $q['estimated_wait_min'] = 0;
        }
        $q['status'] = $q['status_antrean']; // map to status for UI
    }
    
    return $queues;
}

/**
 * Get All Barbers
 */
function get_all_barbers() {
    $pdo = get_db_connection();
    
    $stmt = $pdo->query("SELECT id, nama as name, COALESCE(kursi, 'Kursi A') as station, status FROM barber WHERE status = 'Aktif' OR status = 'aktif'");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get All Services
 */
function get_all_services() {
    $pdo = get_db_connection();
    
    $stmt = $pdo->query("SELECT id, nama_layanan as service_name, harga as price FROM layanan");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Update Queue Status (Aksi Barber / Admin)
 */
function update_queue_status($queue_id, $new_status) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("UPDATE antrian SET status_antrean = ? WHERE id = ?");
    return $stmt->execute([$new_status, $queue_id]);
}
?>
