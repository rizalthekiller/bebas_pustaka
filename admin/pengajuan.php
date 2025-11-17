<?php
require_once '../config.php';
require_once '../functions.php';

// Cek login dan role
requireAdminLogin();
if (!hasAdminRole('admin')) {
    header('Location: dashboard.php');
    exit;
}

// Proses aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verifikasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Token CSRF tidak valid');
    }

    $id = (int)$_POST['id'];

    if ($_POST['action'] === 'setujui') {
        $pdo = connectDB();

        // Ambil data mahasiswa untuk email
        $stmt = $pdo->prepare("SELECT nama, email FROM tb_pengajuan WHERE id = ?");
        $stmt->execute([$id]);
        $mahasiswa = $stmt->fetch();

        $stmt = $pdo->prepare("UPDATE tb_pengajuan SET status = 'Disetujui' WHERE id = ?");
        $stmt->execute([$id]);

        // Kirim email notifikasi persetujuan
        if ($mahasiswa) {
            kirimEmailPersetujuan($mahasiswa['email'], $mahasiswa['nama']);
        }

        // Redirect ke halaman buat surat
        redirectWithMessage('surat.php?action=buat&id=' . $id, 'Pengajuan berhasil disetujui. Silakan buat surat keterangan.', 'success');
    } elseif ($_POST['action'] === 'tolak') {
        $alasan = cleanInput($_POST['alasan_tolak']);
        if (empty($alasan)) {
            $error = 'Alasan penolakan harus diisi';
        } else {
            $pdo = connectDB();
            $stmt = $pdo->prepare("UPDATE tb_pengajuan SET status = 'Ditolak', alasan_tolak = ? WHERE id = ?");
            $stmt->execute([$alasan, $id]);

            // Ambil data mahasiswa untuk email
            $stmt = $pdo->prepare("SELECT nama, email FROM tb_pengajuan WHERE id = ?");
            $stmt->execute([$id]);
            $mahasiswa = $stmt->fetch();

            // Kirim email notifikasi penolakan
            if ($mahasiswa) {
                kirimEmailPenolakan($mahasiswa['email'], $mahasiswa['nama'], $alasan);
            }

            redirectWithMessage('pengajuan.php', 'Pengajuan berhasil ditolak dan notifikasi email telah dikirim', 'success');
        }
    } elseif ($_POST['action'] === 'edit_pengajuan') {
        // Edit pengajuan
        $nama = cleanInput($_POST['nama']);
        $nim = cleanInput($_POST['nim']);
        $id_fakultas = (int)$_POST['fakultas'];
        $id_prodi = (int)$_POST['prodi'];
        $jenjang = cleanInput($_POST['jenjang']);
        $whatsapp = cleanInput($_POST['whatsapp']);
        $email = cleanInput($_POST['email']);

        // Data buku
        $buku = [];
        for ($i = 1; $i <= 3; $i++) {
            if (!empty($_POST["buku{$i}_judul"])) {
                $buku[$i] = [
                    'judul' => cleanInput($_POST["buku{$i}_judul"]),
                    'penulis' => cleanInput($_POST["buku{$i}_penulis"]),
                    'penerbit' => cleanInput($_POST["buku{$i}_penerbit"]),
                    'tahun' => (int)$_POST["buku{$i}_tahun"],
                    'isbn' => cleanInput($_POST["buku{$i}_isbn"]),
                    'jumlah' => (int)$_POST["buku{$i}_jumlah"]
                ];
            }
        }

        // Validasi
        $errors = [];
        if (empty($nama)) $errors[] = 'Nama lengkap harus diisi';
        if (!isValidNIM($nim)) $errors[] = 'NIM tidak valid';
        if (empty($id_fakultas)) $errors[] = 'Fakultas harus dipilih';
        if (empty($id_prodi)) $errors[] = 'Program studi harus dipilih';
        if (!in_array($jenjang, ['S1', 'S2', 'S3'])) $errors[] = 'Jenjang pendidikan tidak valid';
        if (empty($whatsapp)) $errors[] = 'Nomor WhatsApp harus diisi';
        if (!isValidWhatsApp($whatsapp)) $errors[] = 'Nomor WhatsApp tidak valid';
        if (!isValidEmail($email)) $errors[] = 'Email tidak valid';

        // Validasi buku berdasarkan jenjang
        $minBuku = ($jenjang == 'S1') ? 1 : 2;
        if (count($buku) < $minBuku) {
            $errors[] = "Minimal $minBuku judul buku harus diisi untuk jenjang $jenjang";
        }

        // Validasi tahun buku
        foreach ($buku as $b) {
            if (!isValidTahunBuku($b['tahun'])) {
                $errors[] = 'Tahun terbit buku maksimal 3 tahun ke belakang';
            }
        }

        if (empty($errors)) {
            $pdo = connectDB();
            $sql = "UPDATE tb_pengajuan SET nama = ?, nim = ?, id_fakultas = ?, id_prodi = ?, jenjang = ?, whatsapp = ?, email = ?, " .
                   "buku1_judul = ?, buku1_penulis = ?, buku1_penerbit = ?, buku1_tahun = ?, buku1_isbn = ?, buku1_jumlah = ?, " .
                   "buku2_judul = ?, buku2_penulis = ?, buku2_penerbit = ?, buku2_tahun = ?, buku2_isbn = ?, buku2_jumlah = ?, " .
                   "buku3_judul = ?, buku3_penulis = ?, buku3_penerbit = ?, buku3_tahun = ?, buku3_isbn = ?, buku3_jumlah = ? WHERE id = ?";
            $params = [$nama, $nim, $id_fakultas, $id_prodi, $jenjang, $whatsapp, $email];
            for ($i = 1; $i <= 3; $i++) {
                if (isset($buku[$i])) {
                    $params = array_merge($params, [
                        $buku[$i]['judul'], $buku[$i]['penulis'], $buku[$i]['penerbit'],
                        $buku[$i]['tahun'], $buku[$i]['isbn'], $buku[$i]['jumlah']
                    ]);
                } else {
                    $params = array_merge($params, ['', '', '', 0, '', 1]);
                }
            }
            $params[] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            redirectWithMessage('pengajuan.php', 'Pengajuan berhasil diupdate', 'success');
        } else {
            $error = implode('<br>', $errors);
        }
    } elseif ($_POST['action'] === 'ubah_status') {
        $new_status = cleanInput($_POST['new_status']);
        $valid_statuses = ['Menunggu Verifikasi', 'Disetujui', 'Ditolak', 'Selesai'];

        if (!in_array($new_status, $valid_statuses)) {
            $error = 'Status tidak valid';
        } else {
            $pdo = connectDB();

            // If changing to Selesai, check if there's already a letter
            if ($new_status === 'Selesai') {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tb_surat WHERE id_pengajuan = ?");
                $stmt->execute([$id]);
                $surat_count = $stmt->fetch()['count'];

                if ($surat_count == 0) {
                    $error = 'Tidak dapat mengubah status ke Selesai karena belum ada surat yang dibuat.';
                } else {
                    $stmt = $pdo->prepare("UPDATE tb_pengajuan SET status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $id]);
                    redirectWithMessage('pengajuan.php', 'Status pengajuan berhasil diubah menjadi Selesai', 'success');
                }
            } else {
                $stmt = $pdo->prepare("UPDATE tb_pengajuan SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $id]);
                redirectWithMessage('pengajuan.php', 'Status pengajuan berhasil diubah', 'success');
            }
        }
    } elseif ($_POST['action'] === 'delete_pengajuan') {
        $pdo = connectDB();

        // Check if pengajuan has associated surat
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tb_surat WHERE id_pengajuan = ?");
        $stmt->execute([$id]);
        $surat_count = $stmt->fetch()['count'];

        if ($surat_count > 0) {
            $error = 'Tidak dapat menghapus pengajuan yang sudah memiliki surat. Hapus surat terlebih dahulu.';
        } else {
            // Delete pengajuan
            $stmt = $pdo->prepare("DELETE FROM tb_pengajuan WHERE id = ?");
            $stmt->execute([$id]);
            redirectWithMessage('pengajuan.php', 'Pengajuan berhasil dihapus', 'success');
        }
    }
}

// Filter dan search
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
$fakultas_filter = isset($_GET['fakultas']) ? (int)$_GET['fakultas'] : 0;

// Pagination
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Pastikan page minimal 1
$offset = ($page - 1) * $limit;

// Query pengajuan
$pdo = connectDB();

// Build WHERE clause
$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (p.nama LIKE ? OR p.nim LIKE ? OR p.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $whereClause .= " AND p.status = ?";
    $params[] = $status_filter;
}

if ($fakultas_filter > 0) {
    $whereClause .= " AND p.id_fakultas = ?";
    $params[] = $fakultas_filter;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM tb_pengajuan p
               LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
               LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get paginated data
$query = "SELECT p.*, f.nama_fakultas, pr.nama_prodi FROM tb_pengajuan p
          LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
          LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id $whereClause
          ORDER BY p.tanggal_pengajuan DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pengajuan = $stmt->fetchAll();

// Ambil data fakultas untuk filter
$fakultas = getFakultas();

// Ambil data prodi untuk edit modal
$prodi = getProdiByFakultas(null);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengajuan - Bebas Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); padding: 0.75rem 1rem; }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,.1); }
        .sidebar .nav-link.active { color: white; background: #0d6efd; }
        .table-responsive { border-radius: 10px; overflow: hidden; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.8rem; }
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
                        <a class="nav-link active" href="pengajuan.php"><i class="fas fa-list me-2"></i>Kelola Pengajuan</a>
                        <a class="nav-link" href="surat.php"><i class="fas fa-file-alt me-2"></i>Kelola Surat</a>
                        <a class="nav-link" href="template.php"><i class="fas fa-cogs me-2"></i>Template Surat</a>
                        <a class="nav-link" href="master_data.php"><i class="fas fa-database me-2"></i>Data Master</a>
                        <a class="nav-link" href="laporan.php"><i class="fas fa-chart-bar me-2"></i>Laporan</a>
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
                        <h2>Kelola Pengajuan</h2>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>

                    <?php echo showMessage(); ?>

                    <!-- Filter dan Search -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Cari</label>
                                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nama, NIM, atau Email">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="Menunggu Verifikasi" <?php echo $status_filter === 'Menunggu Verifikasi' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                                        <option value="Disetujui" <?php echo $status_filter === 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                                        <option value="Ditolak" <?php echo $status_filter === 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                        <option value="Selesai" <?php echo $status_filter === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Fakultas</label>
                                    <select class="form-select" name="fakultas">
                                        <option value="">Semua Fakultas</option>
                                        <?php foreach ($fakultas as $f): ?>
                                            <option value="<?php echo $f['id']; ?>" <?php echo $fakultas_filter == $f['id'] ? 'selected' : ''; ?>><?php echo $f['nama_fakultas']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                    <a href="pengajuan.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabel Pengajuan -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>No.</th>
                                    <th>Tanggal</th>
                                    <th>Nama</th>
                                    <th>NIM</th>
                                    <th>Fakultas</th>
                                    <th>Prodi</th>
                                    <th>Jenjang</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pengajuan)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">Tidak ada data pengajuan</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = $offset + 1; ?>
                                    <?php foreach ($pengajuan as $p): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($p['tanggal_pengajuan'])); ?></td>
                                            <td><?php echo htmlspecialchars($p['nama']); ?></td>
                                            <td><?php echo htmlspecialchars($p['nim']); ?></td>
                                            <td><?php echo htmlspecialchars($p['nama_fakultas']); ?></td>
                                            <td><?php echo htmlspecialchars($p['nama_prodi']); ?></td>
                                            <td><?php echo $p['jenjang']; ?></td>
                                            <td>
                                                <span class="badge status-badge bg-<?php
                                                    echo $p['status'] === 'Menunggu Verifikasi' ? 'warning' :
                                                         ($p['status'] === 'Disetujui' ? 'success' :
                                                          ($p['status'] === 'Ditolak' ? 'danger' : 'secondary'));
                                                ?>">
                                                    <?php echo $p['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="#" onclick="viewDetail(<?php echo $p['id']; ?>)">
                                                                <i class="fas fa-eye me-2"></i>Lihat Detail
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="#" onclick="editPengajuan(<?php echo $p['id']; ?>)">
                                                                <i class="fas fa-edit me-2"></i>Edit Pengajuan
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php
                                                            $available_statuses = ['Menunggu Verifikasi', 'Disetujui', 'Ditolak', 'Selesai'];
                                                            foreach ($available_statuses as $status) {
                                                                if ($status !== $p['status']) {
                                                                    $status_class = '';
                                                                    if ($status === 'Menunggu Verifikasi') $status_class = 'text-warning';
                                                                    elseif ($status === 'Disetujui') $status_class = 'text-success';
                                                                    elseif ($status === 'Ditolak') $status_class = 'text-danger';
                                                                    elseif ($status === 'Selesai') $status_class = 'text-secondary';
                                                                    echo "<li><a class='dropdown-item {$status_class}' href='#' onclick='ubahStatus({$p['id']}, \"{$status}\", \"{$p['nama']}\")'>{$status}</a></li>";
                                                                }
                                                            }
                                                            ?>
                                                        </ul>
                                                    </div>
                                                    <?php if ($p['status'] === 'Menunggu Verifikasi'): ?>
                                                    <button class="btn btn-sm btn-warning" onclick="prosesPengajuan(<?php echo $p['id']; ?>)">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <?php elseif ($p['status'] === 'Disetujui'): ?>
                                                    <a href="surat.php?action=buat&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-file-alt"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-danger" onclick="deletePengajuan(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nama']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <!-- Previous button -->
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="fas fa-chevron-left"></i> Sebelumnya
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <i class="fas fa-chevron-left"></i> Sebelumnya
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                // Show first page if not in range
                                if ($startPage > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                    if ($startPage > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }

                                // Show page numbers
                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    $activeClass = ($i == $page) ? ' active' : '';
                                    echo '<li class="page-item' . $activeClass . '">';
                                    echo '<a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a>';
                                    echo '</li>';
                                }

                                // Show last page if not in range
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '">' . $totalPages . '</a></li>';
                                }
                                ?>

                                <!-- Next button -->
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            Selanjutnya <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            Selanjutnya <i class="fas fa-chevron-right"></i>
                                        </span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>

                        <!-- Pagination info -->
                        <div class="text-center text-muted mt-2">
                            Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $totalRecords); ?> dari <?php echo $totalRecords; ?> data
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Pengajuan -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content akan diisi oleh JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tolak Pengajuan -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="tolak">
                    <input type="hidden" name="id" id="rejectId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="alasan_tolak" class="form-label">Alasan Penolakan *</label>
                            <textarea class="form-control" id="alasan_tolak" name="alasan_tolak" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Proses Pengajuan -->
    <div class="modal fade" id="prosesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Proses Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p>Pilih aksi untuk pengajuan ini:</p>
                    <button type="button" class="btn btn-success me-2" onclick="confirmApprove()">Setujui Pengajuan</button>
                    <button type="button" class="btn btn-danger" onclick="confirmReject()">Tolak Pengajuan</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal Edit Pengajuan -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit_pengajuan">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-body">
                        <!-- Data Pribadi -->
                        <h6 class="mb-3">Data Pribadi</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_nama" class="form-label">Nama Lengkap *</label>
                                <input type="text" class="form-control" id="edit_nama" name="nama" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_nim" class="form-label">NIM *</label>
                                <input type="text" class="form-control" id="edit_nim" name="nim" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_fakultas" class="form-label">Fakultas *</label>
                                <select class="form-select" id="edit_fakultas" name="fakultas" required>
                                    <option value="">Pilih Fakultas</option>
                                    <?php foreach ($fakultas as $f): ?>
                                        <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['nama_fakultas']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_prodi" class="form-label">Program Studi *</label>
                                <select class="form-select" id="edit_prodi" name="prodi" required>
                                    <option value="">Pilih Program Studi</option>
                                    <?php foreach ($prodi as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" data-fakultas="<?php echo $p['id_fakultas']; ?>"><?php echo htmlspecialchars($p['nama_prodi']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Jenjang Pendidikan *</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="jenjang" id="edit_s1" value="S1" required>
                                        <label class="form-check-label" for="edit_s1">S1</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="jenjang" id="edit_s2" value="S2">
                                        <label class="form-check-label" for="edit_s2">S2</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="jenjang" id="edit_s3" value="S3">
                                        <label class="form-check-label" for="edit_s3">S3</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_whatsapp" class="form-label">Nomor WhatsApp *</label>
                                <input type="text" class="form-control" id="edit_whatsapp" name="whatsapp" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email Aktif *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>

                        <!-- Buku Sumbangan -->
                        <h6 class="mb-3">Buku Sumbangan</h6>
                        <div id="editBukuContainer">
                            <!-- Buku sections will be added here -->
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="editTambahBuku" disabled>Tambah Buku</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.js"></script>
    <script>
        function viewDetail(id) {
            fetch('get_detail_pengajuan.php?id=' + id)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('detailContent').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('detailModal')).show();
                });
        }

        function approvePengajuan(id) {
            Swal.fire({
                title: 'Setujui Pengajuan?',
                text: 'Pengajuan akan disetujui dan mahasiswa akan menerima notifikasi.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Setujui',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="setujui">
                        <input type="hidden" name="id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function rejectPengajuan(id) {
            document.getElementById('rejectId').value = id;
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }

        let currentPengajuanId = null;

        function prosesPengajuan(id) {
            currentPengajuanId = id;
            new bootstrap.Modal(document.getElementById('prosesModal')).show();
        }

        function confirmApprove() {
            if (currentPengajuanId) {
                bootstrap.Modal.getInstance(document.getElementById('prosesModal')).hide();
                approvePengajuan(currentPengajuanId);
            }
        }

        function confirmReject() {
            if (currentPengajuanId) {
                bootstrap.Modal.getInstance(document.getElementById('prosesModal')).hide();
                rejectPengajuan(currentPengajuanId);
            }
        }


        function editPengajuan(id) {
            // Fetch pengajuan data
            fetch('get_detail_pengajuan.php?id=' + id + '&edit=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form fields
                        document.getElementById('editId').value = data.pengajuan.id;
                        document.getElementById('edit_nama').value = data.pengajuan.nama;
                        document.getElementById('edit_nim').value = data.pengajuan.nim;
                        document.getElementById('edit_fakultas').value = data.pengajuan.id_fakultas;
                        document.getElementById('edit_whatsapp').value = data.pengajuan.whatsapp;
                        document.getElementById('edit_email').value = data.pengajuan.email;
                
                        // Set jenjang radio button
                        document.querySelector(`input[name="jenjang"][value="${data.pengajuan.jenjang}"]`).checked = true;

                        // Update prodi options based on fakultas
                        updateEditProdiOptions(data.pengajuan.id_fakultas, data.pengajuan.id_prodi);

                        // Update tambah buku button based on jenjang
                        updateEditTambahBukuButton();
                
                        // Populate books
                        const bukuContainer = document.getElementById('editBukuContainer');
                        bukuContainer.innerHTML = '';
                        for (let i = 1; i <= 3; i++) {
                            const judul = data.pengajuan[`buku${i}_judul`];
                            if (judul) {
                                addEditBukuSection(i, {
                                    judul: data.pengajuan[`buku${i}_judul`],
                                    penulis: data.pengajuan[`buku${i}_penulis`],
                                    penerbit: data.pengajuan[`buku${i}_penerbit`],
                                    tahun: data.pengajuan[`buku${i}_tahun`],
                                    isbn: data.pengajuan[`buku${i}_isbn`],
                                    jumlah: data.pengajuan[`buku${i}_jumlah`]
                                });
                            }
                        }
                
                        // Show modal
                        new bootstrap.Modal(document.getElementById('editModal')).show();
                    } else {
                        alert('Gagal memuat data pengajuan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat data');
                });
        }

        function updateEditProdiOptions(fakultasId, selectedProdiId = null) {
            const prodiSelect = document.getElementById('edit_prodi');
            const options = prodiSelect.querySelectorAll('option');

            options.forEach(option => {
                if (option.value === '') return; // Skip "Pilih Program Studi" option
                if (option.getAttribute('data-fakultas') == fakultasId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });

            // Set selected prodi if provided
            if (selectedProdiId) {
                prodiSelect.value = selectedProdiId;
            } else {
                prodiSelect.value = '';
            }
        }

        // Handle fakultas change in edit modal
        document.getElementById('edit_fakultas').addEventListener('change', function() {
            updateEditProdiOptions(this.value);
        });

        // Handle jenjang change in edit modal
        document.querySelectorAll('input[name="jenjang"]').forEach(radio => {
            radio.addEventListener('change', function() {
                updateEditTambahBukuButton();
            });
        });

        function addEditBukuSection(num, data = {}) {
            const container = document.getElementById('editBukuContainer');
            const section = document.createElement('div');
            section.className = 'edit-buku-section card mb-3';
            section.innerHTML = `
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Buku ${num}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEditBukuSection(this)">Hapus</button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Judul Buku *</label>
                            <input type="text" class="form-control" name="buku${num}_judul" value="${data.judul || ''}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Penulis *</label>
                            <input type="text" class="form-control" name="buku${num}_penulis" value="${data.penulis || ''}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Penerbit *</label>
                            <input type="text" class="form-control" name="buku${num}_penerbit" value="${data.penerbit || ''}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tahun Terbit *</label>
                            <input type="number" class="form-control" name="buku${num}_tahun" value="${data.tahun || ''}" min="1900" max="${new Date().getFullYear()}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jumlah Eksemplar *</label>
                            <input type="number" class="form-control" name="buku${num}_jumlah" value="${data.jumlah || 1}" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" class="form-control" name="buku${num}_isbn" value="${data.isbn || ''}">
                    </div>
                </div>
            `;
            container.appendChild(section);
            updateEditTambahBukuButton();
        }

        function removeEditBukuSection(button) {
            button.closest('.edit-buku-section').remove();
            updateEditTambahBukuButton();
        }

        function updateEditTambahBukuButton() {
            const count = document.querySelectorAll('.edit-buku-section').length;
            const button = document.getElementById('editTambahBuku');
            const jenjang = document.querySelector('input[name="jenjang"]:checked');

            // Disable button for S1, enable for S2/S3
            if (jenjang && jenjang.value === 'S1') {
                button.disabled = true;
                button.style.display = 'none';
            } else {
                button.disabled = count >= 3;
                button.style.display = count >= 3 ? 'none' : 'inline-block';
            }
        }

        document.getElementById('editTambahBuku').addEventListener('click', function() {
            const count = document.querySelectorAll('.edit-buku-section').length + 1;
            if (count <= 3) {
                addEditBukuSection(count, {});
            }
        });

        function ubahStatus(id, newStatus, nama) {
            let statusText = '';
            let iconColor = '';

            switch(newStatus) {
                case 'Menunggu Verifikasi':
                    statusText = 'Menunggu Verifikasi';
                    iconColor = '#ffc107';
                    break;
                case 'Disetujui':
                    statusText = 'Disetujui';
                    iconColor = '#28a745';
                    break;
                case 'Ditolak':
                    statusText = 'Ditolak';
                    iconColor = '#dc3545';
                    break;
                case 'Selesai':
                    statusText = 'Selesai';
                    iconColor = '#6c757d';
                    break;
            }

            Swal.fire({
                title: 'Ubah Status Pengajuan?',
                text: `Ubah status pengajuan ${nama} menjadi "${statusText}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: iconColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Ubah',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="ubah_status">
                        <input type="hidden" name="id" value="${id}">
                        <input type="hidden" name="new_status" value="${newStatus}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function deletePengajuan(id, nama) {
            Swal.fire({
                title: 'Hapus Pengajuan?',
                text: `Apakah Anda yakin ingin menghapus pengajuan dari ${nama}? Tindakan ini tidak dapat dibatalkan.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="delete_pengajuan">
                        <input type="hidden" name="id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
