-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 15 Nov 2025 pada 10.56
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bebas_perpustakaan`
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
(1, 'Fakultas Teknik', '2025-11-15 05:58:11'),
(2, 'Fakultas Ekonomi', '2025-11-15 05:58:11'),
(3, 'Fakultas Ilmu Sosial dan Politik', '2025-11-15 05:58:11'),
(4, 'Fakultas Syari\'ah', '2025-11-15 07:59:23');

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
(1, 'RIZAL MAY SUWARNO, S.Kom', '09560079', 2, 4, 'S1', '085245623276', 'rizalthekiller@gmail.com', 'Penerapan', 'A', 'S', '2025', '12345', 1, '', '', '', '2000', '', 1, '', '', '', '2000', '', 1, 'Selesai', '2025-11-15 06:03:07', NULL),
(2, 'HANUM MASAYU PURNAMASARI', '09560080', 3, 6, 'S1', '085245623276', 'rizalthekiller@gmail.com', 'Penerapan', 'A', 'S', '2025', '12345', 1, '', '', '', '2000', '', 1, '', '', '', '2000', '', 1, 'Selesai', '2025-11-15 07:05:45', NULL),
(3, 'ABDUL MUNIR, S.Pd, M.Pd.', '09560081', 2, 4, 'S1', '085245623276', 'rizalthekiller@gmail.com', 'Penerapan', 'A', 'S', '2025', '12345', 1, '', '', '', '2000', '', 1, '', '', '', '2000', '', 1, 'Selesai', '2025-11-15 09:07:07', NULL);

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
(3, '', 'SURAT KETERANGAN\r\nNomor : Perpus.B-{nomor_surat}/Un.21/1/PP.009/{bulan}/{tahun}\r\n\r\nKepala Perpustakaan Universitas Islam Negeri Sultan Aji Muhammad Idris (UINSI) Samarinda menerangkan bahwa :\r\n\r\nNAMA		: {nama_mahasiswa}\r\nN.I.M.		: {nim}\r\nFAKULTAS	: {fakultas}\r\nPRODI		: {prodi}\r\n\r\nYang bersangkutan tidak memiliki pinjaman / tunggakan buku-buku pada Perpustakaan Universitas Islam Negeri Sultan Aji Muhammad Idris (UINSI) Samarinda\r\nDemikian Surat Keterangan ini diberikan, agar dapat dipergunakan sebagaimana mestinya.\r\n\r\n							Samarinda, {tanggal_surat}\r\n							Mengetahui,\r\n							{jabatan_pejabat}\r\n\r\n\r\n								{nama_pejabat}\r\n								NIP. {nip_pejabat}', 'Plt. Kepala Perpustakaan', 'La Anduke, S.Ag.', '197104042002121002', '2025-11-15 06:01:11'),
(4, 'kop_6918176e0909d.png', '\r\n<div style=\"text-align: center; margin-bottom: 20px;\">\r\n    <img src=\"{{KOP_SURAT}}\" style=\"max-width: 100%; height: auto;\" alt=\"Kop Surat\">\r\n</div>\r\n\r\n<h2 style=\"text-align: center; margin-bottom: 10px; font-family: Arial, sans-serif; text-decoration: underline;\">SURAT KETERANGAN</h2>\r\n\r\n<div style=\"text-align: center; margin-bottom: 30px; font-family: Arial, sans-serif; font-size: 12px;\">\r\n    Nomor : Perpus.B-{nomor_surat}/Un.21/1/PP.009/{bulan_romawi}/{tahun}\r\n</div>\r\n\r\n<div style=\"font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6; margin-bottom: 20px;\">\r\n    Kepala Perpustakaan Universitas Islam Negeri Sultan Aji Muhammad Idris (UINSI) Samarinda menerangkan bahwa :\r\n</div>\r\n\r\n<table style=\"font-family: Arial, sans-serif; font-size: 12px; margin-bottom: 30px; margin-left: 40px; border-collapse: collapse;\">\r\n    <tr>\r\n        <td style=\"padding: 5px 0; width: 120px;\">NAMA</td>\r\n        <td style=\"padding: 5px 0;\">: {nama_mahasiswa}</td>\r\n    </tr>\r\n    <tr>\r\n        <td style=\"padding: 5px 0;\">N.I.M.</td>\r\n        <td style=\"padding: 5px 0;\">: {nim}</td>\r\n    </tr>\r\n    <tr>\r\n        <td style=\"padding: 5px 0;\">FAKULTAS</td>\r\n        <td style=\"padding: 5px 0;\">: {fakultas}</td>\r\n    </tr>\r\n    <tr>\r\n        <td style=\"padding: 5px 0;\">PRODI</td>\r\n        <td style=\"padding: 5px 0;\">: {prodi}</td>\r\n    </tr>\r\n</table>\r\n\r\n<div style=\"font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6; margin-bottom: 20px;\">\r\n    Yang bersangkutan tidak memiliki pinjaman / tunggakan buku-buku pada Perpustakaan Universitas Islam Negeri Sultan Aji Muhammad Idris (UINSI) Samarinda\r\n</div>\r\n\r\n<div style=\"font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6; margin-bottom: 40px;\">\r\n    Demikian Surat Keterangan ini diberikan, agar dapat dipergunakan sebagaimana mestinya.\r\n</div>\r\n\r\n<div style=\"float: right; text-align: left; width: 250px; font-family: Arial, sans-serif; font-size: 12px; margin-top: 60px;\">\r\n    Samarinda, {tanggal_surat}<br>\r\n    Mengetahui,<br>\r\n    Plt. Kepala Perpustakaan<br>\r\n    <img src=\"{{QR_CODE}}\" style=\"width: 80px; height: 80px; margin: 10px 0;\" alt=\"QR Code\"><br>\r\n    {nama_pejabat}<br>\r\n    NIP. {nip_pejabat}\r\n</div>\r\n\r\n<div style=\"clear: both;\"></div>\r\n', 'Plt. Kepala Perpustakaan', 'La Anduke, S.Ag.', '197104042002121002', '2025-11-15 08:59:15');

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
(1, 'Teknik Informatika', 1, '2025-11-15 05:58:11'),
(2, 'Teknik Sipil', 1, '2025-11-15 05:58:11'),
(3, 'Manajemen', 2, '2025-11-15 05:58:11'),
(4, 'Akuntansi', 2, '2025-11-15 05:58:11'),
(5, 'Ilmu Komunikasi', 3, '2025-11-15 05:58:11'),
(6, 'Ilmu Administrasi Negara', 3, '2025-11-15 05:58:11'),
(7, 'Hukum Keluarga', 4, '2025-11-15 07:59:43');

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
-- Dumping data untuk tabel `tb_surat`
--

INSERT INTO `tb_surat` (`id`, `id_pengajuan`, `nomor_surat`, `tanggal_surat`, `qr_code`, `created_at`) VALUES
(1, 1, '1123', '2025-11-15', 'preview_6918180738b64.png', '2025-11-15 06:04:55'),
(2, 2, '1124', '2025-11-15', 'surat_6918266f62736.png', '2025-11-15 07:06:23'),
(3, 1, '1125', '2025-11-15', 'surat_6918296340e22.png', '2025-11-15 07:18:59'),
(4, 3, '1120', '2025-11-15', 'surat_6918451e99ece.png', '2025-11-15 09:10:06');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `tb_pengajuan`
--
ALTER TABLE `tb_pengajuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `tb_pengaturan_surat`
--
ALTER TABLE `tb_pengaturan_surat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `tb_prodi`
--
ALTER TABLE `tb_prodi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `tb_surat`
--
ALTER TABLE `tb_surat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
