<?php
/**
 * Koneksi Database - Absensi SDN Tulungrejo 01
 */
define('DB_HOST', '127.0.0.1'); // pakai 127.0.0.1 (bukan 'localhost') agar mysqlnd tidak hang di Windows
define('DB_USER', 'root');
define('DB_PASS', '');          // default XAMPP: kosong
define('DB_NAME', 'petugas_piket');

// PHP 8.1+ membuat mysqli melempar exception; matikan agar kembali ke gaya lama
// (dicek lewat connect_error) supaya pesan galat tidak bocor ke pengguna.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306);

if ($conn->connect_errno) {
    http_response_code(500);
    die('Koneksi database gagal. Pastikan layanan MySQL XAMPP berjalan. (kode: ' . $conn->connect_errno . ')');
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+07:00'");
