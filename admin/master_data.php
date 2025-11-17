<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();

// Cek role admin
if (!hasAdminRole('admin')) {
    header('Location: dashboard.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $pdo = connectDB();

        try {
            switch ($_POST['action']) {
                case 'add_fakultas':
                    if (!empty($_POST['nama_fakultas'])) {
                        $stmt = $pdo->prepare("INSERT INTO tb_fakultas (nama_fakultas) VALUES (?)");
                        $stmt->execute([cleanInput($_POST['nama_fakultas'])]);
                        redirectWithMessage('master_data.php', 'Fakultas berhasil ditambahkan', 'success');
                    }
                    break;

                case 'edit_fakultas':
                    if (!empty($_POST['id']) && !empty($_POST['nama_fakultas'])) {
                        $stmt = $pdo->prepare("UPDATE tb_fakultas SET nama_fakultas = ? WHERE id = ?");
                        $stmt->execute([cleanInput($_POST['nama_fakultas']), (int)$_POST['id']]);
                        redirectWithMessage('master_data.php', 'Fakultas berhasil diupdate', 'success');
                    }
                    break;

                case 'delete_fakultas':
                    if (!empty($_POST['id'])) {
                        $stmt = $pdo->prepare("DELETE FROM tb_fakultas WHERE id = ?");
                        $stmt->execute([(int)$_POST['id']]);
                        redirectWithMessage('master_data.php', 'Fakultas berhasil dihapus', 'success');
                    }
                    break;

                case 'add_prodi':
                    if (!empty($_POST['nama_prodi']) && !empty($_POST['id_fakultas'])) {
                        $stmt = $pdo->prepare("INSERT INTO tb_prodi (nama_prodi, id_fakultas) VALUES (?, ?)");
                        $stmt->execute([cleanInput($_POST['nama_prodi']), (int)$_POST['id_fakultas']]);
                        redirectWithMessage('master_data.php', 'Program Studi berhasil ditambahkan', 'success');
                    }
                    break;

                case 'edit_prodi':
                    if (!empty($_POST['id']) && !empty($_POST['nama_prodi']) && !empty($_POST['id_fakultas'])) {
                        $stmt = $pdo->prepare("UPDATE tb_prodi SET nama_prodi = ?, id_fakultas = ? WHERE id = ?");
                        $stmt->execute([cleanInput($_POST['nama_prodi']), (int)$_POST['id_fakultas'], (int)$_POST['id']]);
                        redirectWithMessage('master_data.php', 'Program Studi berhasil diupdate', 'success');
                    }
                    break;

                case 'delete_prodi':
                    if (!empty($_POST['id'])) {
                        $stmt = $pdo->prepare("DELETE FROM tb_prodi WHERE id = ?");
                        $stmt->execute([(int)$_POST['id']]);
                        redirectWithMessage('master_data.php', 'Program Studi berhasil dihapus', 'success');
                    }
                    break;
            }
        } catch (Exception $e) {
            redirectWithMessage('master_data.php', 'Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        }
    }
}

// Pagination settings
$limit = 10;

// Fakultas pagination
$fakultas_page = isset($_GET['fakultas_page']) ? max(1, (int)$_GET['fakultas_page']) : 1;
$fakultas_offset = ($fakultas_page - 1) * $limit;

// Prodi pagination
$prodi_page = isset($_GET['prodi_page']) ? max(1, (int)$_GET['prodi_page']) : 1;
$prodi_offset = ($prodi_page - 1) * $limit;

// Get data with pagination
$pdo = connectDB();

// Get total counts
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tb_fakultas");
$total_fakultas = $stmt->fetch()['total'];
$total_fakultas_pages = ceil($total_fakultas / $limit);

$stmt = $pdo->query("SELECT COUNT(*) as total FROM tb_prodi");
$total_prodi = $stmt->fetch()['total'];
$total_prodi_pages = ceil($total_prodi / $limit);

// Get paginated fakultas
$stmt = $pdo->prepare("SELECT * FROM tb_fakultas ORDER BY nama_fakultas LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $fakultas_offset, PDO::PARAM_INT);
$stmt->execute();
$fakultas = $stmt->fetchAll();

// Get paginated prodi with fakultas names
$stmt = $pdo->prepare("SELECT p.*, f.nama_fakultas FROM tb_prodi p LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id ORDER BY f.nama_fakultas, p.nama_prodi LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $prodi_offset, PDO::PARAM_INT);
$stmt->execute();
$prodi_with_fakultas = $stmt->fetchAll();

$page_title = "Data Master - Sistem Bebas Perpustakaan";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); padding: 0.75rem 1rem; }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,.1); }
        .sidebar .nav-link.active { color: white; background: #0d6efd; }
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
                        <a class="nav-link active" href="master_data.php"><i class="fas fa-database me-2"></i>Data Master</a>
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
                        <h2>Data Master</h2>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>

                    <?php echo showMessage(); ?>

                    <!-- Fakultas Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="mb-0">
                                        <i class="fas fa-university me-2"></i>Data Fakultas
                                        <button class="btn btn-primary btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#addFakultasModal">
                                            <i class="fas fa-plus"></i> Tambah Fakultas
                                        </button>
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th width="5%">No</th>
                                                    <th>Nama Fakultas</th>
                                                    <th width="15%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($fakultas)): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted">Belum ada data fakultas</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php $no = ($fakultas_page - 1) * $limit + 1; foreach ($fakultas as $f): ?>
                                                        <tr>
                                                            <td><?php echo $no++; ?></td>
                                                            <td><?php echo htmlspecialchars($f['nama_fakultas']); ?></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-warning" onclick="editFakultas(<?php echo $f['id']; ?>, '<?php echo htmlspecialchars($f['nama_fakultas']); ?>')">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-danger" onclick="deleteFakultas(<?php echo $f['id']; ?>, '<?php echo htmlspecialchars($f['nama_fakultas']); ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if ($total_fakultas_pages > 1): ?>
                                    <nav aria-label="Fakultas pagination" class="mt-3">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($fakultas_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?fakultas_page=<?php echo $fakultas_page - 1; ?>&prodi_page=<?php echo $prodi_page; ?>">Previous</a>
                                            </li>
                                            <?php endif; ?>
                                            <?php for ($i = 1; $i <= $total_fakultas_pages; $i++): ?>
                                            <li class="page-item<?php echo ($i == $fakultas_page) ? ' active' : ''; ?>">
                                                <a class="page-link" href="?fakultas_page=<?php echo $i; ?>&prodi_page=<?php echo $prodi_page; ?>"><?php echo $i; ?></a>
                                            </li>
                                            <?php endfor; ?>
                                            <?php if ($fakultas_page < $total_fakultas_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?fakultas_page=<?php echo $fakultas_page + 1; ?>&prodi_page=<?php echo $prodi_page; ?>">Next</a>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Program Studi Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="mb-0">
                                        <i class="fas fa-graduation-cap me-2"></i>Data Program Studi
                                        <button class="btn btn-success btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#addProdiModal">
                                            <i class="fas fa-plus"></i> Tambah Program Studi
                                        </button>
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th width="5%">No</th>
                                                    <th>Program Studi</th>
                                                    <th>Fakultas</th>
                                                    <th width="15%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($prodi_with_fakultas)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">Belum ada data program studi</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php $no = ($prodi_page - 1) * $limit + 1; foreach ($prodi_with_fakultas as $p): ?>
                                                        <tr>
                                                            <td><?php echo $no++; ?></td>
                                                            <td><?php echo htmlspecialchars($p['nama_prodi']); ?></td>
                                                            <td><?php echo htmlspecialchars($p['nama_fakultas']); ?></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-warning" onclick="editProdi(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nama_prodi']); ?>', <?php echo $p['id_fakultas']; ?>)">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-danger" onclick="deleteProdi(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nama_prodi']); ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if ($total_prodi_pages > 1): ?>
                                    <nav aria-label="Program Studi pagination" class="mt-3">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($prodi_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?fakultas_page=<?php echo $fakultas_page; ?>&prodi_page=<?php echo $prodi_page - 1; ?>">Previous</a>
                                            </li>
                                            <?php endif; ?>
                                            <?php for ($i = 1; $i <= $total_prodi_pages; $i++): ?>
                                            <li class="page-item<?php echo ($i == $prodi_page) ? ' active' : ''; ?>">
                                                <a class="page-link" href="?fakultas_page=<?php echo $fakultas_page; ?>&prodi_page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                            <?php endfor; ?>
                                            <?php if ($prodi_page < $total_prodi_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?fakultas_page=<?php echo $fakultas_page; ?>&prodi_page=<?php echo $prodi_page + 1; ?>">Next</a>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Fakultas -->
    <div class="modal fade" id="addFakultasModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Fakultas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_fakultas">
                        <div class="mb-3">
                            <label for="nama_fakultas" class="form-label">Nama Fakultas</label>
                            <input type="text" class="form-control" id="nama_fakultas" name="nama_fakultas" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Fakultas -->
    <div class="modal fade" id="editFakultasModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Fakultas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_fakultas">
                        <input type="hidden" name="id" id="edit_fakultas_id">
                        <div class="mb-3">
                            <label for="edit_nama_fakultas" class="form-label">Nama Fakultas</label>
                            <input type="text" class="form-control" id="edit_nama_fakultas" name="nama_fakultas" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Program Studi -->
    <div class="modal fade" id="addProdiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Program Studi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_prodi">
                        <div class="mb-3">
                            <label for="nama_prodi" class="form-label">Nama Program Studi</label>
                            <input type="text" class="form-control" id="nama_prodi" name="nama_prodi" required>
                        </div>
                        <div class="mb-3">
                            <label for="id_fakultas" class="form-label">Fakultas</label>
                            <select class="form-control" id="id_fakultas" name="id_fakultas" required>
                                <option value="">Pilih Fakultas</option>
                                <?php foreach ($fakultas as $f): ?>
                                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['nama_fakultas']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Program Studi -->
    <div class="modal fade" id="editProdiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Program Studi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_prodi">
                        <input type="hidden" name="id" id="edit_prodi_id">
                        <div class="mb-3">
                            <label for="edit_nama_prodi" class="form-label">Nama Program Studi</label>
                            <input type="text" class="form-control" id="edit_nama_prodi" name="nama_prodi" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_fakultas" class="form-label">Fakultas</label>
                            <select class="form-control" id="edit_id_fakultas" name="id_fakultas" required>
                                <option value="">Pilih Fakultas</option>
                                <?php foreach ($fakultas as $f): ?>
                                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['nama_fakultas']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Fakultas
        function editFakultas(id, nama) {
            document.getElementById('edit_fakultas_id').value = id;
            document.getElementById('edit_nama_fakultas').value = nama;
            new bootstrap.Modal(document.getElementById('editFakultasModal')).show();
        }

        // Delete Fakultas
        function deleteFakultas(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus fakultas "' + nama + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_fakultas">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Edit Program Studi
        function editProdi(id, nama, idFakultas) {
            document.getElementById('edit_prodi_id').value = id;
            document.getElementById('edit_nama_prodi').value = nama;
            document.getElementById('edit_id_fakultas').value = idFakultas;
            new bootstrap.Modal(document.getElementById('editProdiModal')).show();
        }

        // Delete Program Studi
        function deleteProdi(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus program studi "' + nama + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_prodi">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
