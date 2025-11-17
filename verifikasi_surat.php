<?php
require_once 'config.php';
require_once 'functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hash = isset($_GET['hash']) ? $_GET['hash'] : '';

$isValid = false;
$surat = null;

if ($id > 0 && !empty($hash)) {
    $pdo = connectDB();

    // Get surat data
    $stmt = $pdo->prepare("SELECT s.*, p.nama, p.nim, f.nama_fakultas, pr.nama_prodi FROM tb_surat s
                           LEFT JOIN tb_pengajuan p ON s.id_pengajuan = p.id
                           LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
                           LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id
                           WHERE s.id = ?");
    $stmt->execute([$id]);
    $surat = $stmt->fetch();

    if ($surat) {
        // Verify hash
        $expectedHash = hash('sha256', $id . QR_SECRET);
        if ($hash === $expectedHash) {
            $isValid = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Surat - Bebas Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .card-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            text-align: center;
            padding: 2rem;
        }
        .card-header.invalid {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        }
        .card-body {
            padding: 2rem;
        }
        .valid-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        .invalid-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .surat-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-card">
            <div class="card-header <?php echo $isValid ? '' : 'invalid'; ?>">
                <h2 class="mb-0">
                    <i class="fas <?php echo $isValid ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i>
                    <?php echo $isValid ? 'Surat Valid' : 'Surat Tidak Valid'; ?>
                </h2>
                <p class="mb-0 mt-2">
                    <?php echo $isValid ? 'Surat keterangan bebas perpustakaan ini telah diverifikasi dan valid.' : 'Surat tidak dapat diverifikasi. Mungkin surat telah dimodifikasi atau tidak valid.'; ?>
                </p>
            </div>
            <div class="card-body text-center">
                <?php if ($isValid && $surat): ?>
                    <div class="valid-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="text-success mb-4">Verifikasi Berhasil</h4>
                    <p class="text-muted mb-4">
                        Surat keterangan bebas perpustakaan ini dikeluarkan oleh Universitas Islam Negeri Sultan Aji Muhammad Idris Samarinda
                        dan telah diverifikasi secara digital.
                    </p>

                    <div class="surat-details">
                        <h5 class="text-center mb-3">Detail Surat</h5>
                        <div class="detail-row">
                            <span class="detail-label">Nomor Surat:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($surat['nomor_surat']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tanggal Surat:</span>
                            <span class="detail-value"><?php echo formatTanggalIndonesia($surat['tanggal_surat']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nama Mahasiswa:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($surat['nama']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">NIM:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($surat['nim']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Fakultas:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($surat['nama_fakultas']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Program Studi:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($surat['nama_prodi']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tanggal Verifikasi:</span>
                            <span class="detail-value"><?php echo date('d/m/Y H:i:s'); ?></span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Verifikasi ini menunjukkan bahwa surat ini asli dan dikeluarkan oleh sistem resmi universitas.
                        </small>
                    </div>
                <?php else: ?>
                    <div class="invalid-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4 class="text-danger mb-4">Verifikasi Gagal</h4>
                    <p class="text-muted mb-4">
                        Kode QR yang dipindai tidak valid atau surat telah kadaluarsa.
                        Silakan hubungi bagian administrasi perpustakaan untuk verifikasi manual.
                    </p>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Perhatian:</strong> Surat ini mungkin telah dimodifikasi atau tidak dikeluarkan oleh sistem resmi.
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>