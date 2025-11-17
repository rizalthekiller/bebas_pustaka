<?php
require_once '../config.php';
require_once '../functions.php';

// Cek login
requireAdminLogin();

// Ambil data statistik
$pdo = connectDB();

// Statistik hari ini
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tb_pengajuan WHERE DATE(tanggal_pengajuan) = ?");
$stmt->execute([$today]);
$todayCount = $stmt->fetch()['count'];

// Pengajuan menunggu verifikasi
$stmt = $pdo->query("SELECT COUNT(*) as count FROM tb_pengajuan WHERE status = 'Menunggu Verifikasi'");
$pendingCount = $stmt->fetch()['count'];

// Pengajuan disetujui bulan ini
$currentMonth = date('Y-m');
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tb_pengajuan WHERE status = 'Disetujui' AND DATE_FORMAT(tanggal_pengajuan, '%Y-%m') = ?");
$stmt->execute([$currentMonth]);
$approvedThisMonth = $stmt->fetch()['count'];

// 5 pengajuan terbaru
$stmt = $pdo->query("SELECT p.*, f.nama_fakultas, pr.nama_prodi FROM tb_pengajuan p
                     LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
                     LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id
                     ORDER BY p.tanggal_pengajuan DESC LIMIT 5");
$recentSubmissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Bebas Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); padding: 0.75rem 1rem; }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,.1); }
        .sidebar .nav-link.active { color: white; background: #0d6efd; }
        .stat-card { border-radius: 10px; border: none; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
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
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                        <a class="nav-link" href="pengajuan.php"><i class="fas fa-list me-2"></i>Kelola Pengajuan</a>
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
                    <h2>Dashboard</h2>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-primary text-white me-3"><i class="fas fa-calendar-day"></i></div>
                                    <div><h4 class="mb-0"><?php echo $todayCount; ?></h4><small class="text-muted">Pengajuan Hari Ini</small></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-warning text-white me-3"><i class="fas fa-clock"></i></div>
                                    <div><h4 class="mb-0"><?php echo $pendingCount; ?></h4><small class="text-muted">Menunggu Verifikasi</small></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-success text-white me-3"><i class="fas fa-check-circle"></i></div>
                                    <div><h4 class="mb-0"><?php echo $approvedThisMonth; ?></h4><small class="text-muted">Disetujui Bulan Ini</small></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-info text-white me-3"><i class="fas fa-file-alt"></i></div>
                                    <div><h4 class="mb-0"><?php echo count($recentSubmissions); ?></h4><small class="text-muted">Total Pengajuan</small></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-4">
                            <div class="card stat-card">
                                <div class="card-header"><h5 class="mb-0">Statistik Pengajuan per Bulan</h5></div>
                                <div class="card-body"><canvas id="monthlyChart" height="200"></canvas></div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card stat-card">
                                <div class="card-header"><h5 class="mb-0">Pengajuan Terbaru</h5></div>
                                <div class="card-body p-0">
                                    <?php if (empty($recentSubmissions)): ?>
                                        <div class="p-3 text-center text-muted"><i class="fas fa-inbox fa-2x mb-2"></i><p>Belum ada pengajuan</p></div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($recentSubmissions as $submission): ?>
                                                <div class="list-group-item px-3 py-3">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div><h6 class="mb-1"><?php echo htmlspecialchars($submission['nama']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($submission['nim']); ?> | <?php echo htmlspecialchars($submission['nama_fakultas']); ?></small></div>
                                                        <span class="badge bg-<?php echo $submission['status'] === 'Menunggu Verifikasi' ? 'warning' : ($submission['status'] === 'Disetujui' ? 'success' : 'danger'); ?> rounded-pill"><?php echo $submission['status']; ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
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
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(ctx, {
            type: 'line',
            data: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                    datasets: [{ label: 'Pengajuan', data: [12, 19, 3, 5, 2, 3, 9, 15, 8, 12, 6, 10], borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.1)', tension: 0.4, fill: true }] },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    </script>
</body>
</html>
