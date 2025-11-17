<?php
require_once 'config.php';
require_once 'functions.php';

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifikasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Token CSRF tidak valid');
    }

    // Bersihkan input
    $nama = cleanInput($_POST['nama']);
    $nim = cleanInput($_POST['nim']);
    $id_fakultas = (int)$_POST['fakultas'];
    $id_prodi = isset($_POST['prodi']) ? (int)$_POST['prodi'] : 0;
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
    $success = '';

    if (empty($nama)) $errors[] = 'Nama lengkap harus diisi';
    if (!isValidNIM($nim)) $errors[] = 'NIM tidak valid';
    if (cekPengajuanAktif($nim)) $errors[] = 'Anda memiliki pengajuan yang sedang diproses';
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
        // Simpan ke database
        $pdo = connectDB();
        $sql = "INSERT INTO tb_pengajuan (nama, nim, id_fakultas, id_prodi, jenjang, whatsapp, email, " .
               "buku1_judul, buku1_penulis, buku1_penerbit, buku1_tahun, buku1_isbn, buku1_jumlah, " .
               "buku2_judul, buku2_penulis, buku2_penerbit, buku2_tahun, buku2_isbn, buku2_jumlah, " .
               "buku3_judul, buku3_penulis, buku3_penerbit, buku3_tahun, buku3_isbn, buku3_jumlah) " .
               "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $success = 'Data berhasil disimpan. Terima kasih atas pengajuannya.';
        } catch (PDOException $e) {
            error_log('Database error in index.php: ' . $e->getMessage());
            $errors[] = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi atau hubungi administrator.';
        }
    }
}

// Ambil data fakultas
$fakultas = getFakultas();

// Ambil data prodi untuk dropdown
$prodi = getProdiByFakultas(null);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Bebas Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body {
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #ffffff;
        }
        .container {
            animation: fadeIn 1s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-section {
            margin-bottom: 2rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            color: #333333;
        }
        .form-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
        }
        .buku-section {
            border-left: 5px solid #667eea;
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
            background: rgba(102, 126, 234, 0.05);
            padding: 1rem;
            border-radius: 10px;
        }
        h4, h5 {
            color: #333333;
        }
        .form-label {
            color: #555555;
            font-weight: 500;
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            background: linear-gradient(45deg, #764ba2, #667eea);
        }
        .display-5 {
            color: #ffffff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5), 0 0 25px rgba(102, 126, 234, 0.5);
            font-weight: 700;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            background-color: #ffffff;
            color: #333333;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-control::placeholder {
            color: #999;
        }
        .alert {
            border-radius: 15px;
            border: none;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .btn-outline-primary {
            border-radius: 25px;
            border-color: #667eea;
            color: #667eea;
            background-color: rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }
        .btn-outline-primary:hover {
            background: #667eea;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="fas fa-university fa-4x text-white"></i>
                    </div>
                    <h1 class="display-5 fw-bold">Pengajuan Bebas Perpustakaan</h1>
                    <p class="lead" style="color: #ffffff; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">Silakan isi formulir di bawah ini untuk mengajukan surat keterangan bebas perpustakaan</p>
                    <p class="text-white mt-3" style="font-size: 1.2rem; font-weight: 600; text-shadow: 2px 2px 4px rgba(0,0,0,0.7);">UPT. Perpustakaan UINSI Samarinda</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php else: ?>

                <form method="POST" id="pengajuanForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <!-- Data Diri -->
                    <div class="form-section">
                        <h4 class="mb-3"><i class="fas fa-user me-2"></i>Data Diri</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama" class="form-label">Nama Lengkap *</label>
                                <input type="text" class="form-control" id="nama" name="nama" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nim" class="form-label">NIM *</label>
                                <input type="text" class="form-control" id="nim" name="nim" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fakultas" class="form-label">Fakultas *</label>
                                <select class="form-select" id="fakultas" name="fakultas" required>
                                    <option value="">Pilih Fakultas</option>
                                    <?php foreach ($fakultas as $f): ?>
                                        <option value="<?php echo $f['id']; ?>"><?php echo $f['nama_fakultas']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prodi" class="form-label">Program Studi *</label>
                                <select class="form-select" id="prodi" name="prodi" required disabled>
                                    <option value="">Pilih Program Studi</option>
                                    <?php foreach ($prodi as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" data-fakultas="<?php echo $p['id_fakultas']; ?>"><?php echo htmlspecialchars($p['nama_prodi']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Jenjang Pendidikan *</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="jenjang" id="s1" value="S1" required>
                                    <label class="form-check-label" for="s1">S1</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="jenjang" id="s2" value="S2">
                                    <label class="form-check-label" for="s2">S2</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="jenjang" id="s3" value="S3">
                                    <label class="form-check-label" for="s3">S3</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="whatsapp" class="form-label">Nomor WhatsApp *</label>
                                <input type="text" class="form-control" id="whatsapp" name="whatsapp" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="email" class="form-label">Email Aktif *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                    </div>

                    <!-- Data Buku Sumbangan -->
                    <div class="form-section">
                        <h4 class="mb-3"><i class="fas fa-book me-2"></i>Data Buku Sumbangan</h4>
                        <p class="text-muted">Minimal 1 judul buku untuk S1, 2 judul buku untuk S2/S3</p>

                        <div id="bukuContainer">
                            <!-- Buku 1 -->
                            <div class="buku-section" data-buku="1">
                                <h5>Buku 1</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Judul Buku *</label>
                                        <input type="text" class="form-control" name="buku1_judul" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Penulis *</label>
                                        <input type="text" class="form-control" name="buku1_penulis" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Penerbit *</label>
                                        <input type="text" class="form-control" name="buku1_penerbit" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Tahun Terbit *</label>
                                        <input type="number" class="form-control" name="buku1_tahun" min="1900" max="<?php echo date('Y'); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Jumlah Eksemplar</label>
                                        <input type="number" class="form-control" name="buku1_jumlah" value="1" min="1">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" class="form-control" name="buku1_isbn">
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-primary" id="tambahBuku" style="display: none;">
                            <i class="fas fa-plus me-2"></i>Tambah Buku
                        </button>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Ajukan
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Dropdown dinamis prodi
            $('#fakultas').change(function() {
                var fakultasId = $(this).val();
                var prodiSelect = $('#prodi');
                var options = prodiSelect.find('option');

                if (fakultasId) {
                    options.each(function() {
                        if ($(this).val() === '') return; // Skip "Pilih Program Studi" option
                        if ($(this).data('fakultas') == fakultasId) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                    prodiSelect.prop('disabled', false);
                    prodiSelect.val(''); // Reset selection
                } else {
                    options.hide();
                    prodiSelect.prop('disabled', true);
                    prodiSelect.val('');
                }
            });

            // Logika jenjang dan jumlah buku
            $('input[name="jenjang"]').change(function() {
                var jenjang = $(this).val();
                var minBuku = (jenjang === 'S1') ? 1 : 2;
                var tambahBtn = $('#tambahBuku');

                // Reset buku
                $('.buku-section').slice(1).remove();
                tambahBtn.hide();

                // Tambah buku jika perlu
                if (minBuku > 1) {
                    tambahBuku();
                    tambahBuku(); // Tambah satu lagi untuk S2/S3
                }
            });

            // Fungsi tambah buku
            function tambahBuku() {
                var bukuCount = $('.buku-section').length + 1;
                if (bukuCount <= 3) {
                    var bukuHtml = `
                        <div class="buku-section" data-buku="${bukuCount}">
                            <h5>Buku ${bukuCount} <button type="button" class="btn btn-sm btn-outline-danger ms-2 hapus-buku"><i class="fas fa-trash"></i></button></h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Judul Buku *</label>
                                    <input type="text" class="form-control" name="buku${bukuCount}_judul" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Penulis *</label>
                                    <input type="text" class="form-control" name="buku${bukuCount}_penulis" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Penerbit *</label>
                                    <input type="text" class="form-control" name="buku${bukuCount}_penerbit" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tahun Terbit *</label>
                                    <input type="number" class="form-control" name="buku${bukuCount}_tahun" min="1900" max="${new Date().getFullYear()}" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Jumlah Eksemplar</label>
                                    <input type="number" class="form-control" name="buku${bukuCount}_jumlah" value="1" min="1">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ISBN</label>
                                <input type="text" class="form-control" name="buku${bukuCount}_isbn">
                            </div>
                        </div>
                    `;
                    $('#bukuContainer').append(bukuHtml);
                }

                if ($('.buku-section').length >= 3) {
                    $('#tambahBuku').hide();
                }
            }

            // Event untuk tombol tambah buku
            $('#tambahBuku').click(tambahBuku);

            // Event untuk hapus buku
            $(document).on('click', '.hapus-buku', function() {
                $(this).closest('.buku-section').remove();
                $('#tambahBuku').show();
            });

            // Validasi form
            $('#pengajuanForm').submit(function(e) {
                var jenjang = $('input[name="jenjang"]:checked').val();
                var bukuCount = $('.buku-section').length;
                var minBuku = (jenjang === 'S1') ? 1 : 2;

                if (bukuCount < minBuku) {
                    e.preventDefault();
                    alert(`Minimal ${minBuku} judul buku harus diisi untuk jenjang ${jenjang}`);
                }
            });
        });
    </script>
</body>
</html>
