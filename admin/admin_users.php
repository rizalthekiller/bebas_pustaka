<?php
require_once '../config.php';
require_once '../functions.php';

// Cek login dan role superadmin
requireAdminLogin();
if ($_SESSION['admin_role'] !== 'superadmin') {
    header('Location: dashboard.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $pdo = connectDB();

        try {
            switch ($_POST['action']) {
                case 'add_admin':
                    if (!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['role'])) {
                        $hashedPassword = password_hash(cleanInput($_POST['password']), PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO tb_admin (username, password, role) VALUES (?, ?, ?)");
                        $stmt->execute([cleanInput($_POST['username']), $hashedPassword, cleanInput($_POST['role'])]);
                        redirectWithMessage('admin_users.php', 'Admin berhasil ditambahkan', 'success');
                    }
                    break;

                case 'edit_admin':
                    if (!empty($_POST['id']) && !empty($_POST['username']) && !empty($_POST['role'])) {
                        $params = [cleanInput($_POST['username']), cleanInput($_POST['role']), (int)$_POST['id']];
                        $query = "UPDATE tb_admin SET username = ?, role = ?";

                        // Update password if provided
                        if (!empty($_POST['password'])) {
                            $hashedPassword = password_hash(cleanInput($_POST['password']), PASSWORD_DEFAULT);
                            $query .= ", password = ?";
                            array_splice($params, 2, 0, $hashedPassword);
                        }

                        $query .= " WHERE id = ?";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);
                        redirectWithMessage('admin_users.php', 'Admin berhasil diupdate', 'success');
                    }
                    break;

                case 'delete_admin':
                    if (!empty($_POST['id']) && (int)$_POST['id'] !== $_SESSION['admin_id']) {
                        $stmt = $pdo->prepare("DELETE FROM tb_admin WHERE id = ?");
                        $stmt->execute([(int)$_POST['id']]);
                        redirectWithMessage('admin_users.php', 'Admin berhasil dihapus', 'success');
                    } else {
                        redirectWithMessage('admin_users.php', 'Tidak dapat menghapus akun sendiri', 'danger');
                    }
                    break;
            }
        } catch (Exception $e) {
            redirectWithMessage('admin_users.php', 'Terjadi kesalahan: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get all admins
$pdo = connectDB();
$stmt = $pdo->query("SELECT * FROM tb_admin ORDER BY created_at DESC");
$admins = $stmt->fetchAll();

$page_title = "Kelola Admin - Sistem Bebas Perpustakaan";
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
                        <a class="nav-link" href="master_data.php"><i class="fas fa-database me-2"></i>Data Master</a>
                        <a class="nav-link" href="laporan.php"><i class="fas fa-chart-bar me-2"></i>Laporan</a>
                        <a class="nav-link active" href="admin_users.php"><i class="fas fa-users-cog me-2"></i>Kelola Admin</a>
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
                        <h2>Kelola Admin</h2>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>

                    <?php echo showMessage(); ?>

                    <!-- Add Admin Button -->
                    <div class="mb-4">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                            <i class="fas fa-plus me-2"></i>Tambah Admin
                        </button>
                    </div>

                    <!-- Admins Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Daftar Admin</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Tanggal Dibuat</th>
                                            <th width="20%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($admins)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Belum ada admin</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $no = 1; foreach ($admins as $admin): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $admin['role'] === 'superadmin' ? 'danger' : 'info'; ?>">
                                                            <?php echo htmlspecialchars($admin['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatTanggalIndonesia($admin['created_at']); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning" onclick="editAdmin(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>', '<?php echo htmlspecialchars($admin['role']); ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($admin['id'] !== $_SESSION['admin_id']): ?>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteAdmin(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
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

    <!-- Modal Tambah Admin -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_admin">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="admin">Admin</option>
                                <option value="superadmin">Super Admin</option>
                            </select>
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

    <!-- Modal Edit Admin -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_admin">
                        <input type="hidden" name="id" id="edit_admin_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Password Baru (kosongkan jika tidak diubah)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-control" id="edit_role" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="admin">Admin</option>
                                <option value="superadmin">Super Admin</option>
                            </select>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Admin
        function editAdmin(id, username, role) {
            document.getElementById('edit_admin_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
            new bootstrap.Modal(document.getElementById('editAdminModal')).show();
        }

        // Delete Admin
        function deleteAdmin(id, username) {
            if (confirm('Apakah Anda yakin ingin menghapus admin "' + username + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_admin">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
