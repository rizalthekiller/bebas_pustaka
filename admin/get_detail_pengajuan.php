<?php
require_once '../config.php';
require_once '../functions.php';

// Cek login
requireAdminLogin();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo = connectDB();

    $stmt = $pdo->prepare("SELECT p.*, f.nama_fakultas, pr.nama_prodi FROM tb_pengajuan p
                          LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
                          LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    $pengajuan = $stmt->fetch();

    // Return JSON for edit functionality
    if (isset($_GET['edit']) && $_GET['edit'] == '1') {
        header('Content-Type: application/json');
        if ($pengajuan) {
            echo json_encode(['success' => true, 'pengajuan' => $pengajuan]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data pengajuan tidak ditemukan']);
        }
        exit();
    }

    if ($pengajuan) {
        ?>
        <div class="row">
            <div class="col-md-6">
                <h6>Data Diri</h6>
                <table class="table table-sm">
                    <tr><td><strong>Nama:</strong></td><td><?php echo htmlspecialchars($pengajuan['nama']); ?></td></tr>
                    <tr><td><strong>NIM:</strong></td><td><?php echo htmlspecialchars($pengajuan['nim']); ?></td></tr>
                    <tr><td><strong>Fakultas:</strong></td><td><?php echo htmlspecialchars($pengajuan['nama_fakultas']); ?></td></tr>
                    <tr><td><strong>Program Studi:</strong></td><td><?php echo htmlspecialchars($pengajuan['nama_prodi']); ?></td></tr>
                    <tr><td><strong>Jenjang:</strong></td><td><?php echo $pengajuan['jenjang']; ?></td></tr>
                    <tr><td><strong>WhatsApp:</strong></td><td><?php echo htmlspecialchars($pengajuan['whatsapp']); ?></td></tr>
                    <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($pengajuan['email']); ?></td></tr>
                    <tr><td><strong>Tanggal Pengajuan:</strong></td><td><?php echo formatTanggalIndonesia($pengajuan['tanggal_pengajuan']); ?></td></tr>
                    <tr><td><strong>Status:</strong></td><td>
                        <span class="badge bg-<?php
                            echo $pengajuan['status'] === 'Menunggu Verifikasi' ? 'warning' :
                                 ($pengajuan['status'] === 'Disetujui' ? 'success' :
                                  ($pengajuan['status'] === 'Ditolak' ? 'danger' : 'secondary'));
                        ?>"><?php echo $pengajuan['status']; ?></span>
                    </td></tr>
                    <?php if ($pengajuan['status'] === 'Ditolak' && !empty($pengajuan['alasan_tolak'])): ?>
                    <tr><td><strong>Alasan Penolakan:</strong></td><td><?php echo htmlspecialchars($pengajuan['alasan_tolak']); ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Data Buku Sumbangan</h6>
                <?php
                $buku = [];
                for ($i = 1; $i <= 3; $i++) {
                    if (!empty($pengajuan["buku{$i}_judul"])) {
                        $buku[] = [
                            'judul' => $pengajuan["buku{$i}_judul"],
                            'penulis' => $pengajuan["buku{$i}_penulis"],
                            'penerbit' => $pengajuan["buku{$i}_penerbit"],
                            'tahun' => $pengajuan["buku{$i}_tahun"],
                            'isbn' => $pengajuan["buku{$i}_isbn"],
                            'jumlah' => $pengajuan["buku{$i}_jumlah"]
                        ];
                    }
                }

                if (empty($buku)) {
                    echo '<p class="text-muted">Tidak ada data buku</p>';
                } else {
                    foreach ($buku as $index => $b) {
                        echo "<h6>Buku " . ($index + 1) . "</h6>";
                        echo "<table class='table table-sm mb-3'>";
                        echo "<tr><td><strong>Judul:</strong></td><td>" . htmlspecialchars($b['judul']) . "</td></tr>";
                        echo "<tr><td><strong>Penulis:</strong></td><td>" . htmlspecialchars($b['penulis']) . "</td></tr>";
                        echo "<tr><td><strong>Penerbit:</strong></td><td>" . htmlspecialchars($b['penerbit']) . "</td></tr>";
                        echo "<tr><td><strong>Tahun:</strong></td><td>" . $b['tahun'] . "</td></tr>";
                        echo "<tr><td><strong>ISBN:</strong></td><td>" . htmlspecialchars($b['isbn']) . "</td></tr>";
                        echo "<tr><td><strong>Jumlah:</strong></td><td>" . $b['jumlah'] . " eksemplar</td></tr>";
                        echo "</table>";
                    }
                }
                ?>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Data pengajuan tidak ditemukan</div>';
    }
}
?>
