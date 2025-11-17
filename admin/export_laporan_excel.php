<?php
require_once '../config.php';
require_once '../functions.php';

// Cek login dan role
requireAdminLogin();
if (!hasAdminRole('admin')) {
    header('Location: dashboard.php');
    exit;
}

$pdo = connectDB();

// Build WHERE clause for date filtering
$whereClause = "";
if (isset($_GET['filter_type']) && $_GET['filter_type'] != 'all') {
    switch ($_GET['filter_type']) {
        case 'date':
            if (!empty($_GET['date'])) {
                $whereClause = " AND DATE(p.tanggal_pengajuan) = '" . $_GET['date'] . "'";
            }
            break;
        case 'month':
            if (!empty($_GET['month']) && !empty($_GET['year'])) {
                $whereClause = " AND MONTH(p.tanggal_pengajuan) = " . (int)$_GET['month'] . " AND YEAR(p.tanggal_pengajuan) = " . (int)$_GET['year'];
            }
            break;
        case 'year':
            if (!empty($_GET['year'])) {
                $whereClause = " AND YEAR(p.tanggal_pengajuan) = " . (int)$_GET['year'];
            }
            break;
    }
}

// Get all books with student info
$query = "
    SELECT p.nama as mahasiswa, p.nim, buku1_judul as judul, buku1_penulis as pengarang, buku1_penerbit as penerbit, buku1_tahun as tahun, buku1_isbn as isbn
    FROM tb_pengajuan p
    WHERE buku1_judul IS NOT NULL AND buku1_judul != '' $whereClause
    UNION
    SELECT p.nama, p.nim, buku2_judul, buku2_penulis, buku2_penerbit, buku2_tahun, buku2_isbn
    FROM tb_pengajuan p
    WHERE buku2_judul IS NOT NULL AND buku2_judul != '' $whereClause
    UNION
    SELECT p.nama, p.nim, buku3_judul, buku3_penulis, buku3_penerbit, buku3_tahun, buku3_isbn
    FROM tb_pengajuan p
    WHERE buku3_judul IS NOT NULL AND buku3_judul != '' $whereClause
    ORDER BY nim, judul
";
$stmt = $pdo->query($query);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build filename with filter info
$filename = 'laporan_buku';
if (isset($_GET['filter_type']) && $_GET['filter_type'] != 'all') {
    switch ($_GET['filter_type']) {
        case 'date':
            if (!empty($_GET['date'])) {
                $filename .= '_' . $_GET['date'];
            }
            break;
        case 'month':
            if (!empty($_GET['month']) && !empty($_GET['year'])) {
                $filename .= '_' . $_GET['year'] . '-' . str_pad($_GET['month'], 2, '0', STR_PAD_LEFT);
            }
            break;
        case 'year':
            if (!empty($_GET['year'])) {
                $filename .= '_' . $_GET['year'];
            }
            break;
    }
} else {
    $filename .= '_' . date('Y-m-d');
}
$filename .= '.csv';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Create output stream
$output = fopen('php://output', 'w');

// Write BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header
fputcsv($output, ['No.', 'Judul', 'Pengarang', 'Penerbit', 'Tahun', 'Kota Terbit', 'ISBN', 'Mahasiswa', 'NIM']);

// Write data
$no = 1;
foreach ($books as $book) {
    fputcsv($output, [
        $no++,
        $book['judul'],
        $book['pengarang'],
        $book['penerbit'],
        $book['tahun'],
        '-', // Kota Terbit placeholder
        $book['isbn'],
        $book['mahasiswa'],
        $book['nim']
    ]);
}

fclose($output);
exit;