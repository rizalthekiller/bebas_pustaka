<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../vendor/autoload.php'; // Assuming composer autoload

use Dompdf\Dompdf;

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

// Generate HTML for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Buku</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>Laporan Buku</h2>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Judul</th>
                <th>Pengarang</th>
                <th>Penerbit</th>
                <th>Tahun</th>
                <th>Kota Terbit</th>
                <th>ISBN</th>
                <th>Mahasiswa</th>
                <th>NIM</th>
            </tr>
        </thead>
        <tbody>';

if (!empty($books)) {
    $no = 1;
    foreach ($books as $book) {
        $html .= '<tr>';
        $html .= '<td>' . $no++ . '</td>';
        $html .= '<td>' . htmlspecialchars($book['judul']) . '</td>';
        $html .= '<td>' . htmlspecialchars($book['pengarang']) . '</td>';
        $html .= '<td>' . htmlspecialchars($book['penerbit']) . '</td>';
        $html .= '<td>' . htmlspecialchars($book['tahun']) . '</td>';
        $html .= '<td>-</td>';
        $html .= '<td>' . htmlspecialchars($book['isbn']) . '</td>';
        $html .= '<td>' . htmlspecialchars($book['mahasiswa']) . '</td>';
        $html .= '<td>' . htmlspecialchars($book['nim']) . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="9" style="text-align: center;">Belum ada data buku</td></tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// Create PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

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
$filename .= '.pdf';

// Output PDF
$dompdf->stream($filename, ['Attachment' => true]);
exit;