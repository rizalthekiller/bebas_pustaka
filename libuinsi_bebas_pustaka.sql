-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 17 Nov 2025 pada 14.32
-- Versi server: 10.6.23-MariaDB-cll-lve
-- Versi PHP: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `libuinsi_bebas_pustaka`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_admin`
--

CREATE TABLE `tb_admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_admin`
--

INSERT INTO `tb_admin` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'superadmin', '$2y$10$qq7xkU9eitQZGqA/zQTRYuCqlvdJdNIZzbx.9rCQfpyLyNOxwUoDO', 'superadmin', '2025-11-15 05:58:11'),
(2, 'admin', '$2y$10$GUoqmpfpzIvyCDkiJM/jluMxTyu.hoMiw46T5G7ausGL29rVsxp32', 'admin', '2025-11-15 08:11:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_fakultas`
--

CREATE TABLE `tb_fakultas` (
  `id` int(11) NOT NULL,
  `nama_fakultas` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_fakultas`
--

INSERT INTO `tb_fakultas` (`id`, `nama_fakultas`, `created_at`) VALUES
(1, 'Fakultas Ushuluddin, Adab, dan Dakwah', '2025-11-15 05:58:11'),
(2, 'Fakultas Ekonomi dan Bisnis Islam', '2025-11-15 05:58:11'),
(3, 'Fakultas Tarbiyah dan Ilmu Keguruan', '2025-11-15 05:58:11'),
(4, 'Fakultas Syari\'ah', '2025-11-15 07:59:23'),
(5, 'Program Magister (S-2)', '2025-11-17 07:25:07'),
(6, 'Program Doktor (S-3)', '2025-11-17 07:28:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pengajuan`
--

CREATE TABLE `tb_pengajuan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `id_fakultas` int(11) NOT NULL,
  `id_prodi` int(11) NOT NULL,
  `jenjang` enum('S1','S2','S3') NOT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `buku1_judul` varchar(255) DEFAULT NULL,
  `buku1_penulis` varchar(100) DEFAULT NULL,
  `buku1_penerbit` varchar(100) DEFAULT NULL,
  `buku1_tahun` year(4) DEFAULT NULL,
  `buku1_isbn` varchar(20) DEFAULT NULL,
  `buku1_jumlah` int(11) DEFAULT 1,
  `buku2_judul` varchar(255) DEFAULT NULL,
  `buku2_penulis` varchar(100) DEFAULT NULL,
  `buku2_penerbit` varchar(100) DEFAULT NULL,
  `buku2_tahun` year(4) DEFAULT NULL,
  `buku2_isbn` varchar(20) DEFAULT NULL,
  `buku2_jumlah` int(11) DEFAULT 1,
  `buku3_judul` varchar(255) DEFAULT NULL,
  `buku3_penulis` varchar(100) DEFAULT NULL,
  `buku3_penerbit` varchar(100) DEFAULT NULL,
  `buku3_tahun` year(4) DEFAULT NULL,
  `buku3_isbn` varchar(20) DEFAULT NULL,
  `buku3_jumlah` int(11) DEFAULT 1,
  `status` enum('Menunggu Verifikasi','Disetujui','Ditolak','Selesai') DEFAULT 'Menunggu Verifikasi',
  `tanggal_pengajuan` timestamp NOT NULL DEFAULT current_timestamp(),
  `alasan_tolak` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_pengajuan`
--

INSERT INTO `tb_pengajuan` (`id`, `nama`, `nim`, `id_fakultas`, `id_prodi`, `jenjang`, `whatsapp`, `email`, `buku1_judul`, `buku1_penulis`, `buku1_penerbit`, `buku1_tahun`, `buku1_isbn`, `buku1_jumlah`, `buku2_judul`, `buku2_penulis`, `buku2_penerbit`, `buku2_tahun`, `buku2_isbn`, `buku2_jumlah`, `buku3_judul`, `buku3_penulis`, `buku3_penerbit`, `buku3_tahun`, `buku3_isbn`, `buku3_jumlah`, `status`, `tanggal_pengajuan`, `alasan_tolak`) VALUES
(5, 'RIZAL MAY SUWARNO, S.Kom', '09560079', 1, 1, 'S1', '085245623276', 'rizalthekiller@gmail.com', 'Penerapan Text To Speech Pada Sistem Notifikasi Twitter', 'Ruslan', 'Tiga Serangkai', '2024', '1234567', 1, '', '', '', '2000', '', 1, '', '', '', '2000', '', 1, 'Menunggu Verifikasi', '2025-11-17 07:30:41', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pengaturan_surat`
--

CREATE TABLE `tb_pengaturan_surat` (
  `id` int(11) NOT NULL,
  `kop_surat` varchar(255) DEFAULT NULL,
  `isi_surat` text DEFAULT NULL,
  `jabatan_pejabat` varchar(100) DEFAULT NULL,
  `nama_pejabat` varchar(100) DEFAULT NULL,
  `nip_pejabat` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_pengaturan_surat`
--

INSERT INTO `tb_pengaturan_surat` (`id`, `kop_surat`, `isi_surat`, `jabatan_pejabat`, `nama_pejabat`, `nip_pejabat`, `updated_at`) VALUES
(5, 'kop_691a8af4d1ee8.png', '<div style=\"text-align: center; margin-bottom: 20px;\"><img style=\"max-width: 100%; height: auto;\" src=\"{{KOP_SURAT}}\" alt=\"Kop Surat\"></div>\r\n<h2 style=\"text-align: center; margin-bottom: 10px; font-family: Arial, sans-serif; text-decoration: underline;\">SURAT KETERANGAN</h2>\r\n<div style=\"text-align: center; margin-bottom: 30px; font-family: Arial, sans-serif; font-size: 12px;\">Nomor : Perpus.B-{nomor_surat}/Un.21/1/PP.009/{bulan_romawi}/{tahun}</div>\r\n<div style=\"font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6; margin-bottom: 20px;\">Kepala Perpustakaan Universitas Islam Negeri Sultan Aji Muhammad Idris (UINSI) Samarinda menerangkan bahwa :</div>\r\n<table style=\"font-family: Arial, sans-serif; font-size: 12px; margin-bottom: 30px; margin-left: 40px; border-collapse: collapse;\">\r\n<tbody>\r\n<tr>\r\n<td style=\"padding: 5px 0; width: 120px;\">NAMA</td>\r\n<td style=\"padding: 5px 0;\">: {nama_mahasiswa}</td>\r\n</tr>\r\n<tr>\r\n<td style=\"padding: 5px 0;\">N.I.M.</td>\r\n<td style=\"padding: 5px 0;\">: {nim}</td>\r\n</tr>\r\n<tr>\r\n<td style=\"padding: 5px 0;\">FAKULTAS</td>\r\n<td style=\"padding: 5px 0;\">: {fakultas}</td>\r\n</tr>\r\n<tr>\r\n<td style=\"padding: 5px 0;\">PRODI</td>\r\n<td style=\"padding: 5px 0;\">: {prodi}</td>\r\n</tr>\r\n</tbody>\r\n</table>\r\n<div style=\"font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6; margin-bottom: 20px;\">Yang bersangkutan tidak memiliki pinjaman / tunggakan buku-buku pada Perpustakaan Universitas Islam Negeri Sultan Aji Muhammad Idris (UINSI) Samarinda</div>\r\n<div style=\"font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6; margin-bottom: 40px;\">Demikian Surat Keterangan ini diberikan, agar dapat dipergunakan sebagaimana mestinya.</div>\r\n<div style=\"float: right; text-align: left; width: 250px; font-family: Arial, sans-serif; font-size: 12px; margin-top: 60px;\">Samarinda, {tanggal_surat}<br>Mengetahui,<br>Plt. Kepala Perpustakaan<br><img style=\"width: 80px; height: 80px; margin: 10px 0;\" src=\"{{QR_CODE}}\" alt=\"QR Code\"><br>{nama_pejabat}<br>NIP. {nip_pejabat}</div>\r\n<div style=\"clear: both;\">&nbsp;</div>', 'Kepala Perpustakaan', 'La Anduke, S.Ag.', '197104042002121002', '2025-11-17 02:39:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_prodi`
--

CREATE TABLE `tb_prodi` (
  `id` int(11) NOT NULL,
  `nama_prodi` varchar(100) NOT NULL,
  `id_fakultas` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_prodi`
--

INSERT INTO `tb_prodi` (`id`, `nama_prodi`, `id_fakultas`, `created_at`) VALUES
(1, 'Sistem Informasi', 1, '2025-11-15 05:58:11'),
(2, 'Manajemen Dakwah', 1, '2025-11-15 05:58:11'),
(3, 'Manajemen Bisnis Syariah', 2, '2025-11-15 05:58:11'),
(4, 'Ekonomi Syariah', 2, '2025-11-15 05:58:11'),
(5, 'Pendidikan Agama Islam', 3, '2025-11-15 05:58:11'),
(6, 'Manajemen Pendidikan Islam', 3, '2025-11-15 05:58:11'),
(7, 'Hukum Keluarga', 4, '2025-11-15 07:59:43'),
(8, 'Perbankan Syariah', 2, '2025-11-17 07:16:27'),
(9, 'Hukum Tata Negara', 4, '2025-11-17 07:16:44'),
(10, 'Hukum Ekonomi Syariah', 4, '2025-11-17 07:17:05'),
(11, 'Pendidikan Guru Madrasah Ibtidayah', 3, '2025-11-17 07:19:01'),
(12, 'Pendidikan Bahasa Inggris', 3, '2025-11-17 07:19:27'),
(13, 'Pendidikan Bahasa Arab', 3, '2025-11-17 07:19:43'),
(14, 'Pendidikan Biologi', 3, '2025-11-17 07:20:09'),
(15, 'Pendidikan Islam Anak Usia Dini', 3, '2025-11-17 07:21:16'),
(16, 'Pendidikan Matematika', 3, '2025-11-17 07:21:45'),
(17, 'Komunikasi Penyiaran Islam', 1, '2025-11-17 07:22:34'),
(18, 'Bimbingan Konseling Islam', 1, '2025-11-17 07:23:23'),
(19, 'Ilmu Al-Quran dan Tafsir', 1, '2025-11-17 07:24:19'),
(20, 'Ilmu Hadis', 1, '2025-11-17 07:24:29'),
(21, 'Pendidikan Agama Islam (S2)', 5, '2025-11-17 07:25:36'),
(22, 'Manajemen Pendidikan Islam (S2)', 5, '2025-11-17 07:26:29'),
(23, 'Ekonomi Syariah (S2)', 5, '2025-11-17 07:26:50'),
(24, 'Hukum Keluarga (S2)', 5, '2025-11-17 07:27:12'),
(25, 'Komunikasi dan Penyiaran Islam (S2)', 5, '2025-11-17 07:27:34'),
(26, 'Pendidikan Islam Anak Usia Dini (S2)', 5, '2025-11-17 07:27:58'),
(27, 'Ilmu Al-Quran dan Tafsir (S2)', 5, '2025-11-17 07:28:27'),
(28, 'Pendidikan Agama Islam (S3)', 6, '2025-11-17 07:29:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_surat`
--

CREATE TABLE `tb_surat` (
  `id` int(11) NOT NULL,
  `id_pengajuan` int(11) NOT NULL,
  `nomor_surat` varchar(50) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `tb_admin`
--
ALTER TABLE `tb_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `tb_fakultas`
--
ALTER TABLE `tb_fakultas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tb_pengajuan`
--
ALTER TABLE `tb_pengajuan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `id_fakultas` (`id_fakultas`),
  ADD KEY `id_prodi` (`id_prodi`);

--
-- Indeks untuk tabel `tb_pengaturan_surat`
--
ALTER TABLE `tb_pengaturan_surat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tb_prodi`
--
ALTER TABLE `tb_prodi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_fakultas` (`id_fakultas`);

--
-- Indeks untuk tabel `tb_surat`
--
ALTER TABLE `tb_surat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengajuan` (`id_pengajuan`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `tb_admin`
--
ALTER TABLE `tb_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tb_fakultas`
--
ALTER TABLE `tb_fakultas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `tb_pengajuan`
--
ALTER TABLE `tb_pengajuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `tb_pengaturan_surat`
--
ALTER TABLE `tb_pengaturan_surat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `tb_prodi`
--
ALTER TABLE `tb_prodi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT untuk tabel `tb_surat`
--
ALTER TABLE `tb_surat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `tb_pengajuan`
--
ALTER TABLE `tb_pengajuan`
  ADD CONSTRAINT `tb_pengajuan_ibfk_1` FOREIGN KEY (`id_fakultas`) REFERENCES `tb_fakultas` (`id`),
  ADD CONSTRAINT `tb_pengajuan_ibfk_2` FOREIGN KEY (`id_prodi`) REFERENCES `tb_prodi` (`id`);

--
-- Ketidakleluasaan untuk tabel `tb_prodi`
--
ALTER TABLE `tb_prodi`
  ADD CONSTRAINT `tb_prodi_ibfk_1` FOREIGN KEY (`id_fakultas`) REFERENCES `tb_fakultas` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_surat`
--
ALTER TABLE `tb_surat`
  ADD CONSTRAINT `tb_surat_ibfk_1` FOREIGN KEY (`id_pengajuan`) REFERENCES `tb_pengajuan` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
