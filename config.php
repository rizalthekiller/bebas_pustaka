<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Ganti dengan username database Anda
define('DB_PASS', ''); // Ganti dengan password database Anda
define('DB_NAME', 'bebas_pustaka');

// Koneksi Database
function connectDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

// Fungsi untuk membersihkan input (untuk penyimpanan database)
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

// Fungsi untuk generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fungsi untuk verifikasi CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Base URL untuk aplikasi (sesuaikan dengan instalasi Anda)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = str_replace('/index.php', '', $scriptName);
$basePath = str_replace('/admin/', '', $basePath);
$basePath = str_replace('/config.php', '', $basePath);

define('BASE_URL', $protocol . '://' . $host . $basePath);

// Secret key untuk QR code validation
define('QR_SECRET', 'bebas_pustaka_secret_key_2024');

// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
