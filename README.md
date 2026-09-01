# 💈 Barber Keren - Sistem Manajemen & Antrean Barbershop Online

**Barber Keren** adalah aplikasi web manajemen barbershop modern yang dirancang untuk memudahkan pemesanan antrean online, pengelolaan layanan, manajemen kapster/barber, sistem pembayaran, hingga ulasan pelanggan secara *real-time*.

---

## ✨ Fitur Utama

### 🧑‍🦱 1. Panel Pelanggan (Customer)
* **Pemesanan Antrean Online**: Pilih jenis layanan cukur, durasi, dan barber favorit.
* **Manajemen Antrean Real-time**: Pantau status antrean dari smartphone tanpa perlu menunggu lama di lokasi.
* **Riwayat & Ulasan**: Memberikan ulasan dan rating ulasan setelah selesai dicukur.
* **Status Pembayaran**: Konfirmasi pembayaran transaksi antrean secara aman.

### ✂️ 2. Panel Petugas / Barber (Capster)
* **Manajemen Antrean Servis**: Mengatur giliran memotong rambut pelanggan (`serving`, `completed`, `review`).
* **Level & Spesialisasi**: Sistem tingkatan barber (Junior, Senior, Master) beserta multiplier harga.

### 🛡️ 3. Panel Admin (Manajemen Barbershop)
* **Dashboard Monitoring**: Pantau seluruh antrean harian dan statistik transaksi.
* **Manajemen Layanan & Tarif**: Tambah, ubah, dan hapus jenis layanan serta durasi pengerjaan.
* **Sistem Notifikasi Admin**: Notifikasi otomatis setiap ada registrasi atau pesanan antrean baru.

### 🤖 4. Tools Otomasi Tambahan
* **AutoLogin GSuite (`/autologin-gsuite`)**: Tools otomatis berbasis Python & Playwright untuk mengelola banyak akun Google Workspace/GSuite sekaligus ke profil Chrome.

---

## 🛠️ Teknologi yang Digunakan

* **Backend**: PHP 8.x (PDO)
* **Database**: MySQL 8.x
* **Frontend**: HTML5, Vanilla CSS3, JavaScript (ES6+), Tailwind / Next.js UI Components
* **Server**: Apache / Nginx (Laragon / XAMPP)
* **Tools**: Python 3.11+ & Playwright *(untuk modul autologin-gsuite)*

---

## 📁 Struktur Direktori

```text
barber_keren/
├── api_check_payment.php   # API pengecekan pembayaran
├── asset/                  # Gambar, stylesheet CSS, dan skrip JavaScript
├── auth/                   # Modul autentikasi (Login, Register, Reset Password)
├── autologin-gsuite/       # Tool otomatisasi login GSuite (Python)
├── config/                 # Konfigurasi database & sistem
│   └── database.php
├── db/                     # Script SQL Basis Data
│   └── barber_db.sql
├── frontend/               # komponen UI Next.js / React
├── functions/              # Fungsi helper, auth, & manajemen antrean
├── index.php               # Halaman utama / Landing Page
├── pelanggan/              # Dashboard pelanggan
├── petugas/                # Dashboard admin & barber
└── ulasan.php              # Halaman ulasan & rating pelanggan
```

---

## 🚀 Cara Instalasi & Penggunaan Lokal

### Prasyarat:
* [Laragon](https://laragon.org/) atau [XAMPP](https://www.apachefriends.org/) (PHP 8.x + MySQL).
* Web Browser (Google Chrome disarankan).

### Langkah Instalasi:

1. **Clone Repository ini** ke folder root web server Anda (`www` atau `htdocs`):
   ```bash
   git clone https://github.com/dapp6767/barberkeren.git
   ```

2. **Impor Database**:
   * Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
   * Buat database baru bernama **`barber_db`**.
   * Impor file `db/barber_db.sql` yang ada di dalam folder proyek.

3. **Konfigurasi Database** *(Opsional)*:
   * Salin file `.env.example` menjadi `.env`:
     ```bash
     cp .env.example .env
     ```
   * Sesuaikan kredensial database (DB_HOST, DB_NAME, DB_USER, DB_PASS) di dalam `.env` jika berbeda dengan default Laragon (`127.0.0.1` / `root` / tanpa password).
   * File `.env` sudah otomatis diabaikan oleh Git (`.gitignore`) sehingga aman dan tidak akan ter-upload ke publik/GitHub.

4. **Jalankan Aplikasi**:
   * Buka browser dan kunjungi: `http://localhost/barber_keren`

---

## 📄 Lisensi & Kredit

Dikembangkan oleh **dapp6767** & Tim.  
Hak Cipta © 2026 **Barber Keren**. All Rights Reserved.
