<?php
require_once '../config.php';
require_once '../functions.php';

// Cek login dan role
requireAdminLogin();
if (!hasAdminRole('admin')) {
    header('Location: dashboard.php');
    exit;
}

// Proses form - Template sekarang disimpan dalam file, bukan database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifikasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Token CSRF tidak valid');
    }

    // Upload kop surat jika ada
    $kop_surat = '';
    if (isset($_FILES['kop_surat']) && $_FILES['kop_surat']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['kop_surat']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // Buat direktori jika belum ada
            $upload_dir = '../uploads/kop_surat/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $new_filename = uniqid('kop_') . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['kop_surat']['tmp_name'], $upload_path)) {
                $kop_surat = $new_filename;
            } else {
                redirectWithMessage('template.php', 'Gagal mengupload kop surat', 'danger');
                exit();
            }
        } else {
            redirectWithMessage('template.php', 'Format file kop surat tidak didukung', 'danger');
            exit();
        }
    }

    // Data form - hanya untuk preview, tidak disimpan ke database
    $isi_surat = $_POST['isi_surat'];
    $jabatan_pejabat = cleanInput($_POST['jabatan_pejabat']);
    $nama_pejabat = cleanInput($_POST['nama_pejabat']);
    $nip_pejabat = cleanInput($_POST['nip_pejabat']);

    // Update existing record in database (no insert)
    $pdo = connectDB();
    $pengaturan = getPengaturanSurat();
    if ($pengaturan) {
        $stmt = $pdo->prepare("UPDATE tb_pengaturan_surat SET kop_surat = ?, isi_surat = ?, jabatan_pejabat = ?, nama_pejabat = ?, nip_pejabat = ? WHERE id = ?");
        $stmt->execute([$kop_surat, $isi_surat, $jabatan_pejabat, $nama_pejabat, $nip_pejabat, $pengaturan['id']]);
    } else {
        redirectWithMessage('template.php', 'Tidak ada record pengaturan surat untuk diupdate', 'danger');
        exit;
    }

    redirectWithMessage('template.php', 'Template surat berhasil disimpan', 'success');
}

// Ambil pengaturan surat terakhir
$pengaturan = getPengaturanSurat();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Surat - Bebas Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="../assets/js/tinymce.min.js"></script>
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); padding: 0.75rem 1rem; }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,.1); }
        .sidebar .nav-link.active { color: white; background: #0d6efd; }
        .template-section { margin-bottom: 2rem; }
        .placeholder-info { background: #e7f3ff; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .placeholder-tag { background: #cce5ff; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
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
                        <a class="nav-link active" href="template.php"><i class="fas fa-cogs me-2"></i>Template Surat</a>
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
                        <h2>Template Surat</h2>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>

                    <?php echo showMessage(); ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <!-- Upload Kop Surat -->
                        <div class="template-section">
                            <h4><i class="fas fa-image me-2"></i>Upload Kop Surat</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="kop_surat" class="form-label">Pilih File Kop Surat</label>
                                        <input type="file" class="form-control" id="kop_surat" name="kop_surat" accept="image/*">
                                        <div class="form-text">Format: JPG, PNG, GIF. Maksimal 2MB</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Preview Kop Surat</label>
                                        <div id="kopPreview" class="border p-3 text-center" style="min-height: 150px;">
                                            <?php if ($pengaturan && !empty($pengaturan['kop_surat'])): ?>
                                                <img src="../uploads/kop_surat/<?php echo $pengaturan['kop_surat']; ?>" class="img-fluid" style="max-height: 120px;">
                                            <?php else: ?>
                                                <div class="text-muted">
                                                    <i class="fas fa-image fa-2x mb-2"></i>
                                                    <p>Belum ada kop surat</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Isi Surat -->
                        <div class="template-section">
                            <h4><i class="fas fa-edit me-2"></i>Isi Surat</h4>
                            <div class="placeholder-info">
                                <h6>Placeholder yang tersedia:</h6>
                                <p>Gunakan placeholder berikut dalam isi surat. Placeholder akan otomatis diganti dengan data mahasiswa saat generate PDF.</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><span class="placeholder-tag">{nama_mahasiswa}</span> - Nama lengkap mahasiswa</p>
                                        <p><span class="placeholder-tag">{nim}</span> - Nomor Induk Mahasiswa</p>
                                        <p><span class="placeholder-tag">{fakultas}</span> - Nama fakultas</p>
                                        <p><span class="placeholder-tag">{prodi}</span> - Nama program studi</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><span class="placeholder-tag">{nomor_surat}</span> - Nomor surat</p>
                                        <p><span class="placeholder-tag">{tanggal_surat}</span> - Tanggal surat</p>
                                        <p><span class="placeholder-tag">{nama_pejabat}</span> - Nama pejabat</p>
                                        <p><span class="placeholder-tag">{nip_pejabat}</span> - NIP pejabat</p>
                                    </div>
                                </div>
                            </div>
                            <textarea id="isi_surat" name="isi_surat" class="form-control" rows="15"><?php
                                echo $pengaturan ? htmlspecialchars($pengaturan['isi_surat']) : '';
                            ?></textarea>
                        </div>

                        <!-- Data Pejabat -->
                        <div class="template-section">
                            <h4><i class="fas fa-user-tie me-2"></i>Data Pejabat Penandatangan</h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="jabatan_pejabat" class="form-label">Jabatan *</label>
                                    <input type="text" class="form-control" id="jabatan_pejabat" name="jabatan_pejabat"
                                           value="<?php echo $pengaturan ? htmlspecialchars($pengaturan['jabatan_pejabat']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nama_pejabat" class="form-label">Nama Lengkap *</label>
                                    <input type="text" class="form-control" id="nama_pejabat" name="nama_pejabat"
                                           value="<?php echo $pengaturan ? htmlspecialchars($pengaturan['nama_pejabat']) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="nip_pejabat" class="form-label">NIP</label>
                                <input type="text" class="form-control" id="nip_pejabat" name="nip_pejabat"
                                       value="<?php echo $pengaturan ? htmlspecialchars($pengaturan['nip_pejabat']) : ''; ?>">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Template
                            </button>
                            <a href="template.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // TinyMCE
        tinymce.init({
            selector: '#isi_surat',
            height: 400,
            menubar: false,
            plugins: 'lists link image code',
            toolbar: 'bold italic underline | bullist numlist | link | code',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
            base_url: '../assets/js/tinymce/js/tinymce',
            suffix: '.min'
        });

        // Preview kop surat
        document.getElementById('kop_surat').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('kopPreview').innerHTML = '<img src="' + e.target.result + '" class="img-fluid" style="max-height: 120px;">';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
