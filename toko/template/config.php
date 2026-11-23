<?php
// Ubah kredensial ini bila konfigurasi MySQL XAMPP Anda berbeda.
date_default_timezone_set('Asia/Jakarta');
$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbUser = 'root';
$dbPassword = '';

try {
    $koneksi = new PDO(
        "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
    $koneksi->exec('CREATE DATABASE IF NOT EXISTS `toko` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $koneksi->exec('USE `toko`');

    // Instalasi sekali jalan pada database kosong. Data yang sudah ada tidak ditimpa.
    if (!$koneksi->query("SHOW TABLES LIKE 'products'")->fetch()) {
        $schema = file_get_contents(__DIR__ . '/toko.sql');
        if ($schema === false) {
            throw new RuntimeException('Berkas skema database tidak ditemukan.');
        }
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $schema) as $statement) {
            if (trim($statement) !== '') {
                $koneksi->exec($statement);
            }
        }
    }

    // Migrasi ringan: database lama yang belum punya kolom foto produk ditambahi.
    if ($koneksi->query("SHOW TABLES LIKE 'products'")->fetch()
        && !$koneksi->query("SHOW COLUMNS FROM products LIKE 'image'")->fetch()) {
        $koneksi->exec("ALTER TABLE products ADD COLUMN image VARCHAR(255) NOT NULL DEFAULT '' AFTER emoji");
    }

    // Migrasi ringan: database lama yang belum punya tabel users dibuatkan
    // beserta akun pendukung. Login sudah dihapus — akun tidak dipakai untuk masuk.
    if (!$koneksi->query("SHOW TABLES LIKE 'users'")->fetch()) {
        $koneksi->exec("CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(30) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            nama VARCHAR(80) NOT NULL,
            role ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $stmt = $koneksi->prepare("INSERT INTO users (username, password, nama, role) VALUES (?,'',?,?)");
        $stmt->execute(['admin', 'Administrator Toko', 'admin']);
        $stmt->execute(['kasir', 'Kasir Utama', 'kasir']);
    }
} catch (Throwable $error) {
    error_log('Toko database: ' . $error->getMessage());
    http_response_code(503);
    exit('<!doctype html><html lang="id"><meta charset="utf-8"><title>Database belum siap</title><body style="font:16px system-ui;max-width:560px;margin:12vh auto;padding:24px;color:#18332f"><h1>Database belum siap</h1><p>Jalankan Apache dan MySQL di XAMPP, lalu muat ulang halaman. Jika MySQL memakai kata sandi atau port lain, sesuaikan <code>template/config.php</code>.</p></body></html>');
}
