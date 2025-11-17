<?php
require_once '../config.php';
require_once '../functions.php';

// Cek login dan role
requireAdminLogin();
if (!hasAdminRole('admin')) {
    header('Location: dashboard.php');
    exit;
}

// Get data for reports
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

// Get all books from pengajuan with student info
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
$books = $stmt->fetchAll();

$page_title = "Laporan - Sistem Bebas Perpustakaan";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); padding: 0.75rem 1rem; }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,.1); }
        .sidebar .nav-link.active { color: white; background: #0d6efd; }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="d-flex flex-column">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0"><i class="fas fa-university me-2"></i>Admin Panel</h5>
                        <small class="text-muted">Bebas Perpustakaan</small>
                    </div>
                    <nav class="nav flex-column py-3">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                        <a class="nav-link" href="pengajuan.php"><i class="fas fa-list me-2"></i>Kelola Pengajuan</a>
                        <a class="nav-link" href="surat.php"><i class="fas fa-file-alt me-2"></i>Kelola Surat</a>
                        <a class="nav-link" href="template.php"><i class="fas fa-cogs me-2"></i>Template Surat</a>
                        <a class="nav-link" href="master_data.php"><i class="fas fa-database me-2"></i>Data Master</a>
                        <a class="nav-link active" href="laporan.php"><i class="fas fa-chart-bar me-2"></i>Laporan</a>
                        <?php if ($_SESSION['admin_role'] === 'superadmin'): ?>
                        <a class="nav-link" href="admin_users.php"><i class="fas fa-users-cog me-2"></i>Kelola Admin</a>
                        <?php endif; ?>
                    </nav>
                    <div class="mt-auto p-3 border-top">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-user me-2"></i>
                            <span><?php echo $_SESSION['admin_username']; ?></span>
                        </div>
                        <a href="logout.php" class="btn btn-outline-light btn-sm w-100">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-0">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Laporan Sistem</h2>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>

                    <?php echo showMessage(); ?>

                    <!-- Filter Form -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Filter Laporan</h6>
                                </div>
                                <div class="card-body">
                                    <form method="GET" id="filterForm">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="form-label">Tipe Filter</label>
                                                <select class="form-select" name="filter_type" id="filterType">
                                                    <option value="all" <?php echo (!isset($_GET['filter_type']) || $_GET['filter_type'] == 'all') ? 'selected' : ''; ?>>Semua Data</option>
                                                    <option value="date" <?php echo (isset($_GET['filter_type']) && $_GET['filter_type'] == 'date') ? 'selected' : ''; ?>>Berdasarkan Tanggal</option>
                                                    <option value="month" <?php echo (isset($_GET['filter_type']) && $_GET['filter_type'] == 'month') ? 'selected' : ''; ?>>Berdasarkan Bulan</option>
                                                    <option value="year" <?php echo (isset($_GET['filter_type']) && $_GET['filter_type'] == 'year') ? 'selected' : ''; ?>>Berdasarkan Tahun</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3" id="dateField" style="display: none;">
                                                <label class="form-label">Tanggal</label>
                                                <input type="date" class="form-control" name="date" value="<?php echo isset($_GET['date']) ? $_GET['date'] : ''; ?>">
                                            </div>
                                            <div class="col-md-3" id="monthField" style="display: none;">
                                                <label class="form-label">Bulan</label>
                                                <select class="form-select" name="month">
                                                    <?php for($i=1; $i<=12; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo (isset($_GET['month']) && $_GET['month'] == $i) ? 'selected' : ''; ?>><?php echo date('F', mktime(0,0,0,$i,1)); ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3" id="yearField" style="display: none;">
                                                <label class="form-label">Tahun</label>
                                                <select class="form-select" name="year">
                                                    <?php for($i=date('Y'); $i>=2020; $i--): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo (isset($_GET['year']) && $_GET['year'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary me-2">
                                                    <i class="fas fa-filter me-2"></i>Filter
                                                </button>
                                                <a href="laporan.php" class="btn btn-secondary">
                                                    <i class="fas fa-times me-2"></i>Reset
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <?php
                                $exportParams = '';
                                if (isset($_GET['filter_type'])) {
                                    $exportParams = '?filter_type=' . urlencode($_GET['filter_type']);
                                    if (isset($_GET['date'])) $exportParams .= '&date=' . urlencode($_GET['date']);
                                    if (isset($_GET['month'])) $exportParams .= '&month=' . urlencode($_GET['month']);
                                    if (isset($_GET['year'])) $exportParams .= '&year=' . urlencode($_GET['year']);
                                }
                                ?>
                                <a href="export_laporan_excel.php<?php echo $exportParams; ?>" class="btn btn-success">
                                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                                </a>
                                <a href="export_laporan_pdf.php<?php echo $exportParams; ?>" class="btn btn-danger">
                                    <i class="fas fa-file-pdf me-2"></i>Export to PDF
                                </a>
                            </div>
                        </div>
                    </div>



                    <!-- Books Report -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Laporan Buku</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
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
                                        <tbody>
                                            <?php if (empty($books)): ?>
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted">Belum ada data buku</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php $no = 1; ?>
                                                <?php foreach ($books as $book): ?>
                                                    <tr>
                                                        <td><?php echo $no++; ?></td>
                                                        <td><?php echo htmlspecialchars($book['judul']); ?></td>
                                                        <td><?php echo htmlspecialchars($book['pengarang']); ?></td>
                                                        <td><?php echo htmlspecialchars($book['penerbit']); ?></td>
                                                        <td><?php echo htmlspecialchars($book['tahun']); ?></td>
                                                        <td>-</td>
                                                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                                        <td><?php echo htmlspecialchars($book['mahasiswa']); ?></td>
                                                        <td><?php echo htmlspecialchars($book['nim']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('filterType').addEventListener('change', function() {
            const filterType = this.value;
            document.getElementById('dateField').style.display = filterType === 'date' ? 'block' : 'none';
            document.getElementById('monthField').style.display = filterType === 'month' ? 'block' : 'none';
            document.getElementById('yearField').style.display = (filterType === 'month' || filterType === 'year') ? 'block' : 'none';
        });

        // Initialize on page load
        document.getElementById('filterType').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
