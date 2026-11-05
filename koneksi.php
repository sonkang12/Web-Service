<?php
// Koneksi MySQLi. Dipakai oleh semua endpoint di folder api/.
require_once __DIR__ . '/helpers.php';

mysqli_report(MYSQLI_REPORT_OFF); // error ditangani manual agar response tetap JSON

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    error_json(500, 'File config.php belum dibuat. Salin config.example.php menjadi config.php.');
}
$config = require $configFile;

$conn = @new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($conn->connect_errno) {
    error_json(500, 'Koneksi database gagal. Pastikan MySQL di XAMPP berjalan dan config.php benar.');
}
$conn->set_charset('utf8mb4');
