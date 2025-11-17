<?php
require_once 'config.php';

echo "BASE_URL: " . BASE_URL . "<br>";
echo "Protocol: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "<br>";
echo "Host: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Script Dir: " . dirname($_SERVER['SCRIPT_NAME']) . "<br>";
echo "Full URL should be: " . BASE_URL . "/verifikasi_surat.php?id=1&hash=test";
?>