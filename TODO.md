# TODO List - Sistem Informasi Bebas Perpustakaan

## 1. Setup Database dan Konfigurasi
- [x] Buat file config.php untuk koneksi database
- [x] Buat file database.sql untuk schema tabel
- [x] Buat tabel tb_fakultas, tb_prodi, tb_pengajuan, tb_surat, tb_pengaturan_surat, tb_admin

## 2. Struktur Proyek dan File Dasar
- [x] Buat folder assets/css, assets/js, uploads/kop_surat, admin/
- [x] Buat file functions.php untuk helper functions
- [x] Buat file index.php untuk form publik mahasiswa

## 3. Modul Mahasiswa (Form Publik)
- [x] Implementasi form pengajuan dengan validasi frontend
- [x] Logika dropdown dinamis fakultas-prodi
- [x] Validasi backend dan penyimpanan data pengajuan
- [x] Halaman terima kasih setelah submit

## 4. Modul Admin - Login dan Dashboard
- [x] Buat halaman login admin dengan keamanan
- [x] Buat dashboard admin dengan widget dan grafik
- [x] Implementasi session management untuk admin

## 5. Manajemen Pengajuan Admin
- [x] Tabel pengajuan dengan search dan filter
- [x] Detail pengajuan dan aksi verifikasi/setujui/tolak
- [x] Notifikasi email untuk penolakan dan persetujuan

## 6. Manajemen Surat dan Cetak
- [x] Form pembuatan surat dengan validasi nomor surat
- [x] Generate PDF dinamis dengan Dompdf
- [x] Integrasi QR code untuk verifikasi surat
- [x] Opsi cetak dan kirim email surat

## 7. Kelola Template Surat
- [x] Upload dan preview kop surat
- [x] WYSIWYG editor untuk isi surat dengan placeholder
- [x] Form data pejabat penandatangan
- [x] Simpan pengaturan ke database

## 8. Data Master dan Laporan
- [ ] CRUD fakultas dan program studi
- [ ] Halaman laporan dengan filter dan export Excel
- [ ] Manajemen user admin (untuk superadmin)

## 9. Integrasi Library dan Fitur Tambahan
- [x] Install dan integrasi Dompdf untuk PDF
- [x] Install dan integrasi PHPMailer untuk email
- [x] Install dan integrasi library QR code
- [x] Install TinyMCE untuk editor WYSIWYG
- [x] Install SweetAlert2 untuk notifikasi

## 10. Keamanan dan Optimasi
- [ ] Implementasi CSRF protection
- [ ] Rate limiting untuk pencegahan spam
- [ ] Validasi input dan pencegahan XSS/SQL injection
- [ ] Optimasi UI/UX responsif dengan Bootstrap

## 11. Testing dan Finalisasi
- [ ] Testing semua fitur end-to-end
- [ ] Debugging dan perbaikan bug
- [ ] Dokumentasi dan panduan pengguna
