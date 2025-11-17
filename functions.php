<?php
define('BASE_PATH', dirname(__FILE__) . '/');

// Fungsi untuk cek apakah admin sudah login
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Fungsi untuk memaksa login admin
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Fungsi logout admin
function adminLogout() {
    // Hapus semua data session
    $_SESSION = [];

    // Hapus cookie session jika ada
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy session
    session_destroy();

    // Redirect ke halaman login
    header('Location: login.php');
    exit();
}

// Fungsi untuk cek role admin
function hasAdminRole($requiredRole) {
    if (!isAdminLoggedIn()) {
        return false;
    }

    $userRole = $_SESSION['admin_role'] ?? 'admin';
    if ($userRole === 'superadmin') {
        return true; // superadmin bisa akses semua
    }

    return $userRole === $requiredRole;
}

// Fungsi untuk redirect dengan pesan
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit();
}

// Fungsi untuk menampilkan pesan
function showMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'success';
        unset($_SESSION['message'], $_SESSION['message_type']);

        $alertClass = $type === 'success' ? 'alert-success' : 'alert-danger';
        return "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
                    $message
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
    return '';
}

// Fungsi untuk mendapatkan data fakultas
function getFakultas() {
    $pdo = connectDB();
    $stmt = $pdo->query("SELECT * FROM tb_fakultas ORDER BY nama_fakultas");
    return $stmt->fetchAll();
}

// Fungsi untuk mendapatkan data prodi berdasarkan fakultas
function getProdiByFakultas($fakultasId = null) {
    $pdo = connectDB();
    if ($fakultasId) {
        $stmt = $pdo->prepare("SELECT * FROM tb_prodi WHERE id_fakultas = ? ORDER BY nama_prodi");
        $stmt->execute([$fakultasId]);
    } else {
        $stmt = $pdo->query("SELECT * FROM tb_prodi ORDER BY nama_prodi");
    }
    return $stmt->fetchAll();
}

// Fungsi validasi NIM (contoh: harus angka, panjang tertentu)
function isValidNIM($nim) {
    // NIM harus berupa angka atau alfanumerik dan panjang 5-12 karakter
    return ctype_alnum($nim) && strlen($nim) >= 5 && strlen($nim) <= 12;
}

// Fungsi cek apakah ada pengajuan aktif untuk NIM tertentu
function cekPengajuanAktif($nim) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tb_pengajuan WHERE nim = ? AND status IN ('Menunggu Verifikasi', 'Disetujui')");
    $stmt->execute([$nim]);
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

// Fungsi validasi nomor WhatsApp
function isValidWhatsApp($whatsapp) {
    // Hapus semua karakter non-digit
    $whatsapp = preg_replace('/\D/', '', $whatsapp);

    // Nomor WhatsApp Indonesia harus dimulai dengan 62 atau 0, panjang 10-13 digit
    if (preg_match('/^(62|0)/', $whatsapp)) {
        $whatsapp = preg_replace('/^62/', '', $whatsapp);
        $whatsapp = preg_replace('/^0/', '', $whatsapp);
        return strlen($whatsapp) >= 9 && strlen($whatsapp) <= 12;
    }

    return false;
}

// Fungsi validasi email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Fungsi validasi tahun buku (maksimal 3 tahun ke belakang)
function isValidTahunBuku($tahun) {
    $currentYear = date('Y');
    return $tahun >= 1900 && $tahun <= $currentYear && ($currentYear - $tahun) <= 3;
}

// Fungsi untuk menampilkan data dengan aman di HTML
function displaySafe($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Fungsi format tanggal Indonesia
function formatTanggalIndonesia($tanggal) {
    if (empty($tanggal)) return '-';

    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $date = date_create($tanggal);
    if (!$date) return $tanggal;

    $hari = date_format($date, 'd');
    $bulan_num = (int)date_format($date, 'm');
    $tahun = date_format($date, 'Y');

    return $hari . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
}

// Fungsi untuk mendapatkan bulan dalam format Romawi
function getRomanMonth($month) {
    $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    return $roman[$month - 1] ?? '';
}

// Fungsi cek nomor surat duplikat
function cekNomorSuratDuplikat($nomor_surat, $tahun) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tb_surat WHERE nomor_surat = ? AND YEAR(tanggal_surat) = ?");
    $stmt->execute([$nomor_surat, $tahun]);
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

// Fungsi generate PDF surat
function generatePDF($id_surat, $download = true) {
    require_once BASE_PATH . 'vendor/autoload.php';

    $pdo = connectDB();

    // Ambil data surat
    $stmt = $pdo->prepare("SELECT s.*, p.*, f.nama_fakultas, pr.nama_prodi FROM tb_surat s
                          LEFT JOIN tb_pengajuan p ON s.id_pengajuan = p.id
                          LEFT JOIN tb_fakultas f ON p.id_fakultas = f.id
                          LEFT JOIN tb_prodi pr ON p.id_prodi = pr.id
                          WHERE s.id = ?");
    $stmt->execute([$id_surat]);
    $data = $stmt->fetch();

    if (!$data) {
        die('Data surat tidak ditemukan');
    }

    // Ambil template surat
    $stmt = $pdo->query("SELECT * FROM tb_pengaturan_surat ORDER BY id DESC LIMIT 1");
    $template = $stmt->fetch();

    if (!$template) {
        die('Template surat tidak ditemukan');
    }

    // Load template HTML
    $html = $template['isi_surat'];

    // Extract month and year from tanggal_surat
    $month = date('n', strtotime($data['tanggal_surat']));
    $year = date('Y', strtotime($data['tanggal_surat']));
    $roman_month = getRomanMonth($month);

    // Replace placeholders
    $replacements = [
        '{nama_mahasiswa}' => $data['nama'],
        '{nim}' => $data['nim'],
        '{fakultas}' => $data['nama_fakultas'],
        '{prodi}' => $data['nama_prodi'],
        '{nomor_surat}' => $data['nomor_surat'],
        '{tanggal_surat}' => formatTanggalIndonesia($data['tanggal_surat']),
        '{bulan_romawi}' => $roman_month,
        '{tahun}' => $year,
        '{nama_pejabat}' => $template['nama_pejabat'] ?? 'Nama Pejabat',
        '{nip_pejabat}' => $template['nip_pejabat'] ?? 'NIP Pejabat',
    ];


    // QR Code path
    $qr_path = BASE_PATH . 'uploads/qr_codes/' . $data['qr_code'];
    if (!empty($data['qr_code']) && file_exists($qr_path) && is_file($qr_path)) {
        $qr_data = 'data:image/png;base64,' . base64_encode(file_get_contents($qr_path));
        $replacements['{{QR_CODE}}'] = $qr_data;
    } else {
        $replacements['{{QR_CODE}}'] = '';
    }

    // Kop surat
    $kop_path = BASE_PATH . 'uploads/kop_surat/' . $template['kop_surat'];
    if (!empty($template['kop_surat']) && file_exists($kop_path) && is_file($kop_path)) {
        $kop_data = 'data:image/png;base64,' . base64_encode(file_get_contents($kop_path));
        $replacements['{{KOP_SURAT}}'] = $kop_data;
    } else {
        $replacements['{{KOP_SURAT}}'] = '';
    }

    $html = str_replace(array_keys($replacements), array_values($replacements), $html);

    // Generate PDF using Dompdf
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);

    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render PDF
    $dompdf->render();

    if ($download) {
        $filename = 'Surat_Bebas_Pustaka_' . $data['nim'] . '.pdf';
        $dompdf->stream($filename, array('Attachment' => true));
    } else {
        return $dompdf->output();
    }
}

// Fungsi kirim email surat
function kirimEmailSurat($id_surat) {
    $pdo = connectDB();

    // Ambil data surat
    $stmt = $pdo->prepare("SELECT s.*, p.nama, p.email FROM tb_surat s
                          LEFT JOIN tb_pengajuan p ON s.id_pengajuan = p.id
                          WHERE s.id = ?");
    $stmt->execute([$id_surat]);
    $data = $stmt->fetch();

    if (!$data) {
        throw new Exception('Data surat tidak ditemukan');
    }

    // Generate PDF
    $pdf_content = generatePDF($id_surat, false);

    // Kirim email (implementasi sederhana)
    $to = $data['email'];
    $subject = 'Surat Keterangan Bebas Perpustakaan - ' . $data['nama'];
    $message = "Yth. {$data['nama']},\n\n"
             . "Terlampir adalah Surat Keterangan Bebas Perpustakaan Anda.\n\n"
             . "Salam,\n"
             . "Admin Bebas Perpustakaan";

    $headers = "From: admin@bebasperpustakaan.com\r\n";
    $headers .= "Reply-To: admin@bebasperpustakaan.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"boundary123\"\r\n";

    $body = "--boundary123\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= $message . "\r\n\r\n";
    $body .= "--boundary123\r\n";
    $body .= "Content-Type: application/pdf; name=\"Surat_Bebas_Pustaka.pdf\"\r\n";
    $body .= "Content-Disposition: attachment; filename=\"Surat_Bebas_Pustaka.pdf\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($pdf_content)) . "\r\n";
    $body .= "--boundary123--";

    // Untuk demo, kita hanya log saja (karena tidak ada konfigurasi email)
    error_log("Email would be sent to: $to with subject: $subject");

    // Dalam implementasi nyata, gunakan:
    // mail($to, $subject, $body, $headers);
}

// Fungsi mendapatkan pengaturan surat
function getPengaturanSurat() {
    $pdo = connectDB();
    $stmt = $pdo->query("SELECT * FROM tb_pengaturan_surat ORDER BY id DESC LIMIT 1");
    return $stmt->fetch();
}

// Fungsi kirim email notifikasi persetujuan
function kirimEmailPersetujuan($email, $nama) {
    require_once BASE_PATH . 'vendor/autoload.php';
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Konfigurasi server email
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Ganti dengan SMTP server Anda
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Ganti dengan email Anda
        $mail->Password = 'your-app-password'; // Ganti dengan app password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Pengaturan email
        $mail->setFrom('admin@bebasperpustakaan.com', 'Admin Bebas Perpustakaan');
        $mail->addAddress($email, $nama);

        $mail->isHTML(true);
        $mail->Subject = 'Pengajuan Bebas Perpustakaan Disetujui';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #28a745;'>Selamat! Pengajuan Anda Disetujui</h2>
                <p>Yth. <strong>{$nama}</strong>,</p>
                <p>Dengan hormat, kami informasikan bahwa pengajuan Bebas Perpustakaan Anda telah <strong>DISETUJUI</strong> oleh admin.</p>
                <p>Selanjutnya, admin akan membuat surat keterangan Bebas Perpustakaan dan mengirimkannya melalui email atau Anda dapat mengambilnya di bagian administrasi.</p>
                <p>Terima kasih atas partisipasi Anda dalam program sumbangan buku perpustakaan.</p>
                <br>
                <p>Salam,<br>
                <strong>Admin Bebas Perpustakaan</strong></p>
            </div>
        ";
        $mail->AltBody = "Selamat! Pengajuan Bebas Perpustakaan Anda telah DISETUJUI. Admin akan segera memproses surat keterangan Anda.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error untuk debugging
        error_log("Email persetujuan gagal dikirim ke {$email}: " . $mail->ErrorInfo);
        return false;
    }
}

// Fungsi kirim email notifikasi penolakan
function kirimEmailPenolakan($email, $nama, $alasan) {
    require_once BASE_PATH . 'vendor/autoload.php';
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Konfigurasi server email
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Ganti dengan SMTP server Anda
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Ganti dengan email Anda
        $mail->Password = 'your-app-password'; // Ganti dengan app password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Pengaturan email
        $mail->setFrom('admin@bebasperpustakaan.com', 'Admin Bebas Perpustakaan');
        $mail->addAddress($email, $nama);

        $mail->isHTML(true);
        $mail->Subject = 'Pengajuan Bebas Perpustakaan Ditolak';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #dc3545;'>Maaf, Pengajuan Anda Ditolak</h2>
                <p>Yth. <strong>{$nama}</strong>,</p>
                <p>Dengan hormat, kami informasikan bahwa pengajuan Bebas Perpustakaan Anda telah <strong>DITOLAK</strong> dengan alasan sebagai berikut:</p>
                <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>
                    <strong>Alasan Penolakan:</strong><br>
                    {$alasan}
                </div>
                <p>Anda dapat memperbaiki pengajuan Anda dan mengajukan kembali dengan memperhatikan alasan penolakan di atas.</p>
                <p>Untuk informasi lebih lanjut, silakan hubungi bagian administrasi.</p>
                <br>
                <p>Salam,<br>
                <strong>Admin Bebas Perpustakaan</strong></p>
            </div>
        ";
        $mail->AltBody = "Maaf, pengajuan Bebas Perpustakaan Anda DITOLAK dengan alasan: {$alasan}. Silakan perbaiki dan ajukan kembali.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error untuk debugging
        error_log("Email penolakan gagal dikirim ke {$email}: " . $mail->ErrorInfo);
        return false;
    }
}
?>