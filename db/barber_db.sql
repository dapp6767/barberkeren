-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 29 Jul 2026 pada 03.47
-- Versi server: 8.4.3
-- Versi PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `barber_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `antrian`
--

CREATE TABLE `antrian` (
  `id` int NOT NULL,
  `pelanggan_id` int NOT NULL,
  `barber_id` int DEFAULT NULL,
  `served_by_user_id` int DEFAULT NULL,
  `layanan_id` int NOT NULL,
  `no_antrean` varchar(10) NOT NULL,
  `status_antrean` varchar(30) DEFAULT NULL,
  `waktu_dibuat` datetime(6) DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `antrian`
--

INSERT INTO `antrian` (`id`, `pelanggan_id`, `barber_id`, `served_by_user_id`, `layanan_id`, `no_antrean`, `status_antrean`, `waktu_dibuat`) VALUES
(1, 2, 2, NULL, 1, 'A-01', 'completed', '2026-07-22 12:51:12.763679'),
(2, 1, NULL, NULL, 1, 'A-01', 'completed', '2026-07-23 11:41:50.679594'),
(3, 3, 2, NULL, 1, 'A-02', 'completed', '2026-07-23 12:00:48.549237'),
(4, 9, 2, NULL, 1, 'A-03', 'completed', '2026-07-23 12:03:30.769119'),
(5, 10, 2, NULL, 1, 'A-04', 'completed', '2026-07-23 14:36:56.486085'),
(6, 3, 2, NULL, 1, 'A-05', 'review', '2026-07-23 14:37:20.562399'),
(7, 10, 2, NULL, 5, 'A-06', 'completed', '2026-07-23 15:14:02.683317'),
(8, 1, 2, NULL, 1, 'A-07', 'completed', '2026-07-23 16:42:16.200817'),
(9, 1, NULL, NULL, 4, 'A-08', 'payment', '2026-07-23 16:47:51.010340'),
(10, 10, 2, NULL, 8, 'A-01', 'completed', '2026-07-24 11:12:19.136806'),
(11, 2, 2, NULL, 6, 'A-02', 'review', '2026-07-24 16:59:45.348083'),
(12, 11, 2, NULL, 1, 'A-01', 'review', '2026-07-25 08:13:06.178789'),
(13, 10, NULL, NULL, 4, 'A-01', 'completed', '2026-07-27 10:20:27.534275'),
(14, 13, NULL, 2, 4, 'A-02', 'completed', '2026-07-27 10:56:02.426405'),
(15, 10, 2, 2, 1, 'A-03', 'completed', '2026-07-27 13:32:07.688471'),
(16, 13, 2, 2, 5, 'A-04', 'completed', '2026-07-27 13:33:34.519676');

-- --------------------------------------------------------

--
-- Struktur dari tabel `barber`
--

CREATE TABLE `barber` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `nama` varchar(30) NOT NULL,
  `spesialisasi` varchar(10) NOT NULL,
  `status` varchar(6) NOT NULL,
  `tingkatan` varchar(20) NOT NULL DEFAULT 'Junior',
  `multiplier` decimal(3,1) NOT NULL DEFAULT '1.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `barber`
--

INSERT INTO `barber` (`id`, `user_id`, `nama`, `spesialisasi`, `status`, `tingkatan`, `multiplier`) VALUES
(2, 4, 'Viktor K.', 'Fade', 'Aktif', 'Junior', 1.0),
(3, 5, 'Julian S.', 'Beard', 'Aktif', 'Junior', 1.0),
(4, 6, 'Marco Rossi', 'Classic', 'Aktif', 'Junior', 1.0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `layanan`
--

CREATE TABLE `layanan` (
  `id` int NOT NULL,
  `nama_layanan` varchar(15) NOT NULL,
  `harga` int NOT NULL,
  `deskripsi` text,
  `durasi` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `layanan`
--

INSERT INTO `layanan` (`id`, `nama_layanan`, `harga`, `deskripsi`, `durasi`) VALUES
(1, 'Pridecut', 50000, 'Potong Rambut (Haircut)\nCuci Rambut (Hair Wash)\nHair Tonic\nPijat (Massage)\nHanduk Hangat (Warm Towel)\nStyling Pomade', 30),
(2, 'Maxcut', 75000, 'Potong Rambut (Haircut)\nCuci Rambut (Hair Wash)\nHair Tonic\nPijat (Massage) & Handuk Hangat\nStyling Pomade\nMasker Mata (Eye Patch)\nMasker Wajah (Face Mask)', 45),
(3, 'Hair Coloring', 100000, NULL, 60),
(4, 'Hairlight', 250000, NULL, 90),
(5, 'Full Hairlight', 300000, NULL, 120),
(6, 'Hair Tattoo', 45000, NULL, 30),
(7, 'Shave', 20000, NULL, 15),
(8, 'Korean Wave', 200000, NULL, 90);

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int NOT NULL,
  `antrian_id` int NOT NULL,
  `total_harga` int NOT NULL,
  `metode_pembayaran` varchar(20) DEFAULT NULL,
  `status_pembayaran` varchar(11) NOT NULL,
  `waktu_bayar` datetime(6) DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id`, `antrian_id`, `total_harga`, `metode_pembayaran`, `status_pembayaran`, `waktu_bayar`) VALUES
(1, 1, 45000, NULL, 'lunas', '2026-07-22 12:51:30.000000'),
(2, 2, 45000, 'QRIS', 'lunas', '2026-07-23 11:57:14.000000'),
(3, 3, 45000, 'Cash', 'lunas', '2026-07-23 12:01:07.000000'),
(4, 4, 45000, 'Cash', 'lunas', '2026-07-23 12:03:46.000000'),
(5, 5, 50000, 'Transfer Bank', 'lunas', '2026-07-23 15:05:36.000000'),
(6, 6, 50000, 'Cash', 'lunas', '2026-07-23 15:13:06.000000'),
(7, 7, 300000, 'Cash', 'lunas', '2026-07-23 15:14:56.000000'),
(8, 8, 50000, 'Cash', 'lunas', '2026-07-23 16:43:04.000000'),
(9, 10, 200000, 'Transfer Bank', 'lunas', '2026-07-24 11:13:21.000000'),
(10, 11, 45000, 'Cash', 'lunas', '2026-07-24 17:26:42.000000'),
(11, 12, 50000, 'Transfer Bank', 'lunas', '2026-07-25 08:13:54.000000'),
(12, 13, 250000, 'Cash', 'lunas', '2026-07-27 10:20:59.000000'),
(13, 14, 250000, 'QRIS', 'lunas', '2026-07-27 11:06:37.000000'),
(14, 15, 50000, 'QRIS', 'lunas', '2026-07-27 13:33:08.000000'),
(15, 16, 300000, 'Transfer Bank', 'lunas', '2026-07-27 13:34:21.000000');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ulasan`
--

CREATE TABLE `ulasan` (
  `id` int NOT NULL,
  `antrian_id` int NOT NULL,
  `pelanggan_id` int NOT NULL,
  `rating` int NOT NULL,
  `komentar` text,
  `waktu` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `ulasan`
--

INSERT INTO `ulasan` (`id`, `antrian_id`, `pelanggan_id`, `rating`, `komentar`, `waktu`) VALUES
(1, 2, 1, 5, 'keren\r\n', '2026-07-23 11:57:39'),
(2, 3, 3, 5, 'bagus', '2026-07-23 12:01:23'),
(3, 4, 9, 5, 'hebat', '2026-07-23 12:04:23'),
(4, 5, 10, 5, 'tempat nya bagus dan pelayanannya hebat\r\n', '2026-07-23 15:13:55'),
(5, 7, 10, 5, 'keren', '2026-07-23 16:44:48'),
(6, 8, 1, 5, 'a', '2026-07-23 16:47:43'),
(7, 10, 10, 5, 'anjay', '2026-07-24 11:16:27'),
(8, 13, 10, 5, 'keren keren\r\n', '2026-07-27 10:21:35'),
(9, 14, 13, 1, 'jelek', '2026-07-27 13:33:25'),
(10, 16, 13, 5, 'bagus', '2026-07-27 13:34:50'),
(11, 15, 10, 5, 'keren', '2026-07-27 14:46:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `username` varchar(30) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(11) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `fullname`, `username`, `email`, `phone`, `password`, `role`, `remember_token`, `reset_token`, `reset_expires`) VALUES
(1, 'admin dapa', 'admin', 'dapa09@gmail.com', '0857', '$2y$10$geQN3tGuI2k6TSaRDLdGB.KUxf86V0P4zFbx6NmgrrIa5eFYwoLWO', 'admin', '7e1a9779734577e18e39c8e1fedee2c819409a2f575df82e950f603276951ddc', NULL, NULL),
(2, NULL, 'barber1', NULL, NULL, '$2y$10$VqiGqkFumQT63ImV4/hvzembArBcgpDp125FqVcnB/E4uDuL..Mri', 'barber', NULL, NULL, NULL),
(3, NULL, 'pelanggan1', NULL, NULL, '$2y$10$3Y.Ldjxe81UCX0bVlC19FuKRfXm0gSi75wESdHALIoI7syNYepniu', 'pelanggan', NULL, NULL, NULL),
(7, NULL, 'pelanggan', NULL, NULL, 'pelanggan', 'pelanggan', NULL, NULL, NULL),
(8, 'dapa', 'dapp', 'dapp123@gmail.com', '6767', '123', 'pelanggan', NULL, NULL, NULL),
(9, 'orang', 'orang', 'orang@gmail.com', '00000', '$2y$10$1fGh/9sOMI/3EvBvuHC0Kus/2kpG6DA9XRX7VFiceHmqkXD6XGrdG', 'pelanggan', NULL, NULL, NULL),
(10, 'daffa', 'daffa', 'daffa@gmail.com', '11111', '$2y$10$txi3mnwT1PU2MLSbpbqKmuMAJxGIuBQFQS32VBGzqI4OBeI10tyyW', 'pelanggan', NULL, NULL, NULL),
(11, 'dappp', 'dappp', 'dappp123@gmail.com', '123', '$2y$10$hNgmItbyf13X6GiFM75rG.IZYpNbIMUPk20ulGbkY1knWeqg7e7mO', 'pelanggan', NULL, NULL, NULL),
(12, 'orang', 'barber2', 'barber2@gmail.com', '11', '$2y$10$06QwiB3559aIQfiAFQoP4.shRCqob3OJmIOwuzwP4lA66HG2ZcNQK', 'barber', NULL, NULL, NULL),
(13, 'aku', 'aku', 'aku@gmail.com', '00000', '$2y$10$CcDKewB7jTkdAj1oGNl1H.RNi2Z/57.JfXHRYeLKzlDAgmsRQSXba', 'pelanggan', NULL, NULL, NULL),
(14, 'dap', 'pa', 'pa@gmail.com', '123', '$2y$10$bEb21ZadbrdSgP4G2o.oS.e4bLd8JmF3sPfI80q1q6tl7P5paQwuq', 'pelanggan', NULL, NULL, NULL);

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `antrian`
--
ALTER TABLE `antrian`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `barber`
--
ALTER TABLE `barber`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `antrian`
--
ALTER TABLE `antrian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `barber`
--
ALTER TABLE `barber`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
