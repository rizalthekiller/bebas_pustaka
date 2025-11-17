<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../vendor/autoload.php';

// Cek login dan role
requireAdminLogin();
if (!hasAdminRole('admin')) {
    header('Location: dashboard.php');
    exit;
}

$pdo = connectDB();

// Search and pagination parameters for letters list
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

// Proses preview surat
if (isset($_GET['action']) && $_GET['action'] === 'preview' && isset($_GET['id'])) {
    $id_surat = (int)$_GET['id'];

    $pdo = connectDB();
    $pdf_content = generatePDF($id_surat, false);

    // Output HTML dengan embedded PDF
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Preview Surat - Bebas Perpustakaan</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body>
        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Preview Surat</h2>
                <a href="surat.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Surat
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <object data="data:application/pdf;base64,<?php echo base64_encode($pdf_content); ?>" type="application/pdf" width="100%" height="600px">
                        <div class="alert alert-warning">
                            Browser Anda tidak mendukung preview PDF.
                            <a href="surat.php?action=cetak&id=<?php echo $id_surat; ?>" class="btn btn-primary btn-sm ms-2" target="_blank">
                                <i class="fas fa-download me-2"></i>Download PDF
                            </a>
                        </div>
                    </object>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <a href="surat.php?action=cetak&id=<?php echo $id_surat; ?>" class="btn btn-success" target="_blank">
                            <i class="fas fa-download me-2"></i>Download PDF
                        </a>
                        <a href="surat.php?action=kirim_email&id=<?php echo $id_surat; ?>" class="btn btn-info">
                            <i class="fas fa-envelope me-2"></i>Kirim Email
                        </a>
                        <a href="surat.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Fix dropdown positioning for single row tables
            document.addEventListener('DOMContentLoaded', function() {
                // Ensure dropdowns work properly
                var dropdowns = document.querySelectorAll('.dropdown-toggle');
                dropdowns.forEach(function(dropdown) {
                    dropdown.addEventListener('click', function(e) {
                        e.stopPropagation();
                        var menu = this.nextElementSibling;
                        if (menu && menu.classList.contains('dropdown-menu')) {
                            // Toggle visibility
                            var isVisible = menu.classList.contains('show');
                            // Hide all other dropdowns first
                            document.querySelectorAll('.dropdown-menu.show').forEach(function(m) {
                                m.classList.remove('show');
                            });
                            // Toggle current dropdown
                            if (!isVisible) {
                                menu.classList.add('show');
                            }
                        }
                    });
                });
    
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                        menu.classList.remove('show');
                    });
                });
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

// Proses preview surat (temporary)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'preview_surat') {
    // Verifikasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid']);
        exit();
    }

    $id_pengajuan = (int)$_POST['id_pengajuan'];
    $nomor_surat = cleanInput($_POST['nomor_surat']);
    $tanggal_surat = cleanInput($_POST['tanggal_surat']);

    // Validasi: cek apakah NIM sudah memiliki surat
    $stmt = $pdo->prepare("SELECT p.nim FROM tb_pengajuan p WHERE p.id = ?");
    $stmt->execute([$id_pengajuan]);
    $pengajuan = $stmt->fetch();

    if ($pengajuan) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tb_surat s
                              LEFT JOIN tb_pengajuan p ON s.id_pengajuan = p.id
                              WHERE p.nim = ?");
        $stmt->execute([$pengajuan['nim']]);
        $existing_surat = $stmt->fetch()['count'];

        if ($existing_surat > 0) {
            echo json_encode(['success' => false, 'message' => 'Mahasiswa dengan NIM ini sudah memiliki surat. Setiap NIM hanya boleh memiliki 1 surat.']);
            exit();
        }
    }

    // Validasi nomor surat
    $tahun = date('Y', strtotime($tanggal_surat));
    if (cekNomorSuratDuplikat($nomor_surat, $tahun)) {
        echo json_encode(['success' => false, 'message' => 'Nomor surat sudah digunakan pada tahun yang sama']);
        exit();
    }

    $pdo = connectDB();

    // Generate QR code temporary
    $qr_code = uniqid('preview_') . '.png';
    $qr_path = '../uploads/qr_codes/' . $qr_code;

    // Buat direktori jika belum ada
    if (!is_dir('../uploads/qr_codes/')) {
        mkdir('../uploads/qr_codes/', 0755, true);
    }

    // Generate QR code yang mengarah ke halaman verifikasi dengan validasi
    $hash = hash('sha256', $id_surat . QR_SECRET);
    $qrData = BASE_URL . '/verifikasi_surat.php?id=' . $id_surat . '&hash=' . $hash;

    try {
        $qrCode = Endroid\QrCode\Builder\Builder::create()
            ->writer(new Endroid\QrCode\Writer\PngWriter())
            ->data($qrData)
            ->size(150)
            ->margin(10)
            ->build();

        $qrCode->saveToFile($qr_path);

        // Simpan data surat temporary (akan dihapus setelah preview)
        $stmt = $pdo->prepare("INSERT INTO tb_surat (id_pengajuan, nomor_surat, tanggal_surat, qr_code) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_pengajuan, $nomor_surat, $tanggal_surat, $qr_code]);

        $id_surat = $pdo->lastInsertId();

        echo json_encode(['success' => true, 'id_surat' => $id_surat]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal membuat QR code: ' . $e->getMessage()]);
        exit();
    }
}

// Proses pembuatan surat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buat_surat') {
    // Verifikasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Token CSRF tidak valid');
    }

    $id_pengajuan = (int)$_POST['id_pengajuan'];
    $nomor_surat = cleanInput($_POST['nomor_surat']);
    $tanggal_surat = cleanInput($_POST['tanggal_surat']);

    // Validasi: cek apakah NIM sudah memiliki surat
    $stmt = $pdo->prepare("SELECT p.nim FROM tb_pengajuan p WHERE p.id = ?");
    $stmt->execute([$id_pengajuan]);
    $pengajuan = $stmt->fetch();

    if ($pengajuan) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tb_surat s
                              LEFT JOIN tb_pengajuan p ON s.id_pengajuan = p.id
                              WHERE p.nim = ?");
        $stmt->execute([$pengajuan['nim']]);
        $existing_surat = $stmt->fetch()['count'];

        if ($existing_surat > 0) {
            $error = 'Mahasiswa dengan NIM ini sudah memiliki surat. Setiap NIM hanya boleh memiliki 1 surat.';
        }
    }

    // Validasi nomor surat
    $tahun = date('Y', strtotime($tanggal_surat));
    if (cekNomorSuratDuplikat($nomor_surat, $tahun)) {
        $error = 'Nomor surat sudah digunakan pada tahun yang sama';
    }

    if (!isset($error)) {
        // Simpan data surat dulu
        $stmt = $pdo->prepare("INSERT INTO tb_surat (id_pengajuan, nomor_surat, tanggal_surat) VALUES (?, ?, ?)");
        $stmt->execute([$id_pengajuan, $nomor_surat, $tanggal_surat]);

        $id_surat = $pdo->lastInsertId();

        // Generate QR code
        $qr_code = uniqid('surat_') . '.png';
        $qr_path = '../uploads/qr_codes/' . $qr_code;

        // Buat direktori jika belum ada
        if (!is_dir('../uploads/qr_codes/')) {
            mkdir('../uploads/qr_codes/', 0755, true);
        }

        // Generate QR code yang mengarah ke halaman verifikasi dengan validasi
        $hash = hash('sha256', $id_surat . QR_SECRET);
        $qrData = BASE_URL . '/verifikasi_surat.php?id=' . $id_surat . '&hash=' . $hash;

        try {
            $qrCode = Endroid\QrCode\Builder\Builder::create()
                ->writer(new Endroid\QrCode\Writer\PngWriter())
                ->data($qrData)
                ->size(150)
                ->margin(10)
                ->build();

            $qrCode->saveToFile($qr_path);

            // Update QR code
            $stmt = $pdo->prepare("UPDATE tb_surat SET qr_code = ? WHERE id = ?");
            $stmt->execute([$qr_code, $id_surat]);

            // Update status pengajuan menjadi Selesai
            $stmt = $pdo->prepare("UPDATE tb_pengajuan SET status = 'Selesai' WHERE id = ?");
            $stmt->execute([$id_pengajuan]);

            redirectWithMessage('surat.php', 'Surat berhasil dibuat', 'success');
        } catch (Exception $e) {
            $error = 'Gagal membuat QR code: ' . $e->getMessage();
        }
    }
} elseif (isset($_POST['action']) && $_POST['action'] === 'edit_surat') {
    $id_surat = (int)$_POST['id_surat'];
    $nomor_surat = cleanInput($_POST['nomor_surat']);
    $tanggal_surat = cleanInput($_POST['tanggal_surat']);

    // Validasi nomor surat - allow same number for updating date
    // Removed duplicate check to allow updating date with same number

    // Update surat
    $stmt = $pdo->prepare("UPDATE tb_surat SET nomor_surat = ?, tanggal_surat = ? WHERE id = ?");
    $stmt->execute([$nomor_surat, $tanggal_surat, $id_surat]);

    // Regenerate QR code
    $qr_code = uniqid('surat_') . '.png';
    $qr_path = '../uploads/qr_codes/' . $qr_code;

    // Buat direktori jika belum ada
    if (!is_dir('../uploads/qr_codes/')) {
        mkdir('../uploads/qr_codes/', 0755, true);
    }

    // Generate QR code yang mengarah ke halaman verifikasi dengan validasi
    $hash = hash('sha256', $id_surat . QR_SECRET);
    $qrData = BASE_URL . '/verifikasi_surat.php?id=' . $id_surat . '&hash=' . $hash;

    try {
        $qrCode = Endroid\QrCode\Builder\Builder::create()
            ->writer(new Endroid\QrCode\Writer\PngWriter())
            ->data($qrData)
            ->size(150)
            ->margin(10)
            ->build();

        $qrCode->saveToFile($qr_path);

        // Update QR code
        $stmt = $pdo->prepare("UPDATE tb_surat SET qr_code = ? WHERE id = ?");
        $stmt->execute([$qr_code, $id_surat]);

        redirectWithMessage('surat.php', 'Surat berhasil diupdate', 'success');
    } catch (Exception $e) {
        $error = 'Gagal membuat QR code: ' . $e->getMessage();
    }
}

// Proses cetak/download PDF
if (isset($_GET['action']) && $_GET['action'] === 'cetak' && isset($_GET['id'])) {
    $id_surat = (int)$_GET['id'];
    generatePDF($id_surat, true); // true untuk download
    exit();
}

// Proses kirim email
if (isset($_GET['action']) && $_GET['action'] === 'kirim_email' && isset($_GET['id'])) {
    $id_surat = (int)$_GET['id'];
    kirimEmailSurat($id_surat);
    redirectWithMessage('surat.php', 'Surat berhasil dikirim via email', 'success');
    exit();
}

// Proses hapus surat
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_surat = (int)$_GET['id'];

    // Get surat data untuk menghapus file QR code
    $stmt = $pdo->prepare("SELECT qr_code FROM tb_surat WHERE id = ?");
    $stmt->execute([$id_surat]);
    $surat = $stmt->fetch();

    if ($surat) {
        // Hapus file QR code jika ada
        if (!empty($surat['qr_code'])) {
            $qr_path = '../uploads/qr_codes/' . $surat['qr_code'];
            if (file_exists($qr_path)) {
                unlink($qr_path);
            }
        }

        // Hapus record surat
        $stmt = $pdo->prepare("DELETE FROM tb_surat WHERE id = ?");
        $stmt->execute([$id_surat]);

        redirectWithMessage('surat.php', 'Surat berhasil dihapus', 'success');
    } else {
        redirectWithMessage('surat.php', 'Surat tidak ditemukan', 'danger');
    }
    exit();
}

// Ambil pengajuan yang disetujui dan belum ada suratnya
$pdo = connectDB();
$stmt = $pdo->query("SELECT p.*, f.nama_fakultas, pr.nama_prodi FROM tb_pengajuan p
                     LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
                     LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id
                     WHERE p.status = 'Disetujui' AND NOT EXISTS (
                         SELECT 1 FROM tb_surat s WHERE s.id_pengajuan = p.id
                     )
                     ORDER BY p.tanggal_pengajuan DESC");
$approved_submissions = $stmt->fetchAll();

// Build WHERE clause for search
$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (p.nama LIKE ? OR p.nim LIKE ? OR s.nomor_surat LIKE ? OR p.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM tb_surat s
               LEFT JOIN tb_pengajuan p ON s.id_pengajuan = p.id
               LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
               LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Ambil surat yang sudah dibuat dengan pagination dan search
$query = "SELECT s.*, p.nama, p.nim, f.nama_fakultas, pr.nama_prodi FROM tb_surat s
          LEFT JOIN tb_pengajuan p ON s.id_pengajuan = p.id
          LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
          LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id $whereClause
          ORDER BY s.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$surat_list = $stmt->fetchAll();

// Cek jika ada request untuk form buat surat
$show_form = false;
$form_data = null;
if (isset($_GET['action']) && $_GET['action'] === 'buat' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT p.*, f.nama_fakultas, pr.nama_prodi FROM tb_pengajuan p
                           LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
                           LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id
                           WHERE p.id = ? AND p.status = 'Disetujui'");
    $stmt->execute([$id]);
    $form_data = $stmt->fetch();
    $show_form = $form_data !== false;
}

// Cek jika ada request untuk form edit surat
$edit_form = false;
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT s.*, p.nama, p.nim, f.nama_fakultas, pr.nama_prodi FROM tb_surat s
                           LEFT JOIN tb_pengajuan p ON s.id_pengajuan = p.id
                           LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
                           LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id
                           WHERE s.id = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch();
    $edit_form = $edit_data !== false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Surat - Bebas Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); padding: 0.75rem 1rem; }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,.1); }
        .sidebar .nav-link.active { color: white; background: #0d6efd; }
        .table-responsive { border-radius: 10px; overflow: hidden; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .dropdown-menu { z-index: 1050; min-width: 180px; }
        .dropdown { position: static; }
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
                        <a class="nav-link active" href="surat.php"><i class="fas fa-file-alt me-2"></i>Kelola Surat</a>
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
                        <h2>Kelola Surat</h2>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>

                    <?php echo showMessage(); ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($show_form && $form_data): ?>
                        <!-- Form Buat Surat -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Buat Surat untuk <?php echo htmlspecialchars($form_data['nama']); ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="buat_surat">
                                    <input type="hidden" name="id_pengajuan" value="<?php echo $form_data['id']; ?>">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nomor Surat *</label>
                                            <input type="text" class="form-control" name="nomor_surat" required
                                                   placeholder="Contoh: 001">
                                            <div class="form-text">Nomor urut surat (3 digit)</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tanggal Surat *</label>
                                            <input type="date" class="form-control" name="tanggal_surat" required
                                                   value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Preview Data Mahasiswa</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nama:</strong> <?php echo htmlspecialchars($form_data['nama']); ?></p>
                                                <p><strong>NIM:</strong> <?php echo htmlspecialchars($form_data['nim']); ?></p>
                                                <p><strong>Fakultas:</strong> <?php echo htmlspecialchars($form_data['nama_fakultas']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Program Studi:</strong> <?php echo htmlspecialchars($form_data['nama_prodi']); ?></p>
                                                <p><strong>Jenjang:</strong> <?php echo $form_data['jenjang']; ?></p>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($form_data['email']); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Buat Surat
                                        </button>
                                        <button type="button" class="btn btn-info" onclick="previewSurat()">
                                            <i class="fas fa-eye me-2"></i>Preview Surat
                                        </button>
                                        <a href="surat.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Batal
                                        </a>
                                    </div>

                                    <script>
                                    function previewSurat() {
                                        const nomorSurat = document.querySelector('input[name="nomor_surat"]').value;
                                        const tanggalSurat = document.querySelector('input[name="tanggal_surat"]').value;

                                        if (!nomorSurat || !tanggalSurat) {
                                            alert('Harap isi nomor surat dan tanggal surat terlebih dahulu');
                                            return;
                                        }

                                        // Simpan data preview ke session
                                        const formData = new FormData();
                                        formData.append('action', 'preview_surat');
                                        formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
                                        formData.append('id_pengajuan', '<?php echo $form_data['id']; ?>');
                                        formData.append('nomor_surat', nomorSurat);
                                        formData.append('tanggal_surat', tanggalSurat);

                                        fetch('surat.php', {
                                            method: 'POST',
                                            body: formData
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                window.open('surat.php?action=preview&id=' + data.id_surat, '_blank');
                                            } else {
                                                alert('Gagal membuat preview: ' + data.message);
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error:', error);
                                            alert('Terjadi kesalahan saat membuat preview');
                                        });
                                    }
                                    </script>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($edit_form && $edit_data): ?>
                        <!-- Form Edit Surat -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Edit Surat untuk <?php echo htmlspecialchars($edit_data['nama']); ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="edit_surat">
                                    <input type="hidden" name="id_surat" value="<?php echo $edit_data['id']; ?>">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nomor Surat *</label>
                                            <input type="text" class="form-control" name="nomor_surat" required
                                                   value="<?php echo htmlspecialchars($edit_data['nomor_surat']); ?>">
                                            <div class="form-text">Nomor urut surat</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tanggal Surat *</label>
                                            <input type="date" class="form-control" name="tanggal_surat" required
                                                   value="<?php echo $edit_data['tanggal_surat']; ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Preview Data Mahasiswa</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nama:</strong> <?php echo htmlspecialchars($edit_data['nama']); ?></p>
                                                <p><strong>NIM:</strong> <?php echo htmlspecialchars($edit_data['nim']); ?></p>
                                                <p><strong>Fakultas:</strong> <?php echo htmlspecialchars($edit_data['nama_fakultas']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Program Studi:</strong> <?php echo htmlspecialchars($edit_data['nama_prodi']); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Surat
                                        </button>
                                        <a href="surat.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Batal
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Pengajuan yang Menunggu Surat -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Pengajuan Disetujui - Belum Ada Surat</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($approved_submissions)): ?>
                                <p class="text-muted mb-0">Tidak ada pengajuan yang menunggu pembuatan surat</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Tanggal</th>
                                                <th>Nama</th>
                                                <th>NIM</th>
                                                <th>Fakultas</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; ?>
                                            <?php foreach ($approved_submissions as $submission): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo formatTanggalIndonesia($submission['tanggal_pengajuan']); ?></td>
                                                    <td><?php echo htmlspecialchars($submission['nama']); ?></td>
                                                    <td><?php echo htmlspecialchars($submission['nim']); ?></td>
                                                    <td><?php echo htmlspecialchars($submission['nama_fakultas']); ?></td>
                                                    <td>
                                                        <a href="surat.php?action=buat&id=<?php echo $submission['id']; ?>" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-plus me-2"></i>Buat Surat
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
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
                                        Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $totalRecords); ?> dari <?php echo $totalRecords; ?> surat
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Daftar Surat -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Daftar Surat yang Telah Dibuat</h5>
                        </div>
                        <div class="card-body">
                            <!-- Search Form -->
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <form method="GET" class="d-flex gap-2">
                                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                               placeholder="Cari nama, NIM, nomor surat, atau email...">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-2"></i>Cari
                                        </button>
                                        <?php if (!empty($search)): ?>
                                            <a href="surat.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-2"></i>Reset
                                            </a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                                <div class="col-md-4 text-end">
                                    <small class="text-muted">
                                        Total: <?php echo $totalRecords; ?> surat
                                    </small>
                                </div>
                            </div>
                            <?php if (empty($surat_list)): ?>
                                <p class="text-muted mb-0">Belum ada surat yang dibuat</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Tanggal Dibuat</th>
                                                <th>Nomor Surat</th>
                                                <th>Nama</th>
                                                <th>NIM</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = $offset + 1; ?>
                                            <?php foreach ($surat_list as $surat): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo formatTanggalIndonesia($surat['created_at']); ?></td>
                                                    <td><?php echo htmlspecialchars($surat['nomor_surat']); ?></td>
                                                    <td><?php echo htmlspecialchars($surat['nama']); ?></td>
                                                    <td><?php echo htmlspecialchars($surat['nim']); ?></td>
                                                    <td>
                                                        <a href="surat.php?action=edit&id=<?php echo $surat['id']; ?>" class="btn btn-warning btn-sm me-1">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </a>
                                                        <a href="surat.php?action=preview&id=<?php echo $surat['id']; ?>" class="btn btn-success btn-sm me-1" target="_blank">
                                                            <i class="fas fa-print me-2"></i>Cetak PDF
                                                        </a>
                                                        <a href="surat.php?action=kirim_email&id=<?php echo $surat['id']; ?>" class="btn btn-info btn-sm me-1">
                                                            <i class="fas fa-envelope me-2"></i>Kirim Email
                                                        </a>
                                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $surat['id']; ?>, '<?php echo htmlspecialchars($surat['nama']); ?>')" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash me-2"></i>Hapus
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus surat untuk ' + nama + '?\n\nTindakan ini tidak dapat dibatalkan.')) {
                window.location.href = 'surat.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>
