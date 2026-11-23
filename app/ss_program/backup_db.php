<?php
/**
 * Script: backup_db.php
 * Fungsi : backup database petugas_piket ke folder database/backups/
 *
 * Cara pakai (CLI):
 *   php ss_program/backup_db.php
 *
 * Atau lewat browser (login admin):
 *   http://localhost/app/ss_program/backup_db.php
 */

require_once __DIR__ . '/../config/koneksi.php';

// Jika diakses lewat browser, wajib login admin. Lewat CLI (cron) tetap bebas.
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/../library/auth.php';
    if (!is_login() || current_user()['hak_akses'] !== 'admin') {
        http_response_code(403);
        die('Akses ditolak. Halaman ini khusus admin.');
    }
}

$backupDir = __DIR__ . '/../database/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$file = $backupDir . '/petugas_piket_' . date('Ymd_His') . '.sql';

// Coba pakai mysqldump XAMPP jika tersedia
$mysqldump = 'E:/xampp/mysql/bin/mysqldump.exe';
if (PHP_OS_FAMILY !== 'Windows') {
    $mysqldump = 'mysqldump';
}

if (PHP_SAPI === 'cli' && (PHP_OS_FAMILY === 'Windows' ? file_exists($mysqldump) : true)) {
    $cmd = sprintf(
        '"%s" -u %s %s %s > "%s"',
        $mysqldump,
        DB_USER,
        DB_PASS === '' ? '' : '-p"' . DB_PASS . '"',
        DB_NAME,
        $file
    );
    exec($cmd, $out, $code);
    if ($code === 0 && file_exists($file)) {
        echo "Backup berhasil: " . realpath($file) . " (" . filesize($file) . " bytes)\n";
        exit(0);
    }
    echo "mysqldump gagal (kode $code), fallback ke dump PHP...\n";
}

// Fallback: dump tabel lewat PHP (aman untuk browser)
$tables = $conn->query("SHOW TABLES")->fetch_all(MYSQLI_NUM);
$sql = "-- Backup database petugas_piket\n-- " . date('Y-m-d H:i:s') . "\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $t) {
    $table = $t[0];
    $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
    $sql .= "DROP TABLE IF EXISTS `$table`;\n" . $create[1] . ";\n\n";

    $rows = $conn->query("SELECT * FROM `$table`");
    while ($row = $rows->fetch_row()) {
        $vals = array_map(function ($v) use ($conn) {
            return $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
        }, $row);
        $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $vals) . ");\n";
    }
    $sql .= "\n";
}
$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

file_put_contents($file, $sql);
echo "Backup berhasil: " . realpath($file) . " (" . filesize($file) . " bytes)\n";
$conn->close();
