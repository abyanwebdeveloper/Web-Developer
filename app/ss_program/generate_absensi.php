<?php
/**
 * Script: generate_absensi.php
 * Fungsi : membuat baris absensi 'H' otomatis untuk semua siswa aktif
 *          pada hari sekolah (Senin-Jumat) yang belum punya data.
 *
 * Cara pakai (CLI) :
 *   php ss_program/generate_absensi.php                 -> generate hari ini
 *   php ss_program/generate_absensi.php 2026-10-13      -> generate tanggal tertentu
 *
 * Bisa juga dipanggil lewat browser (login admin):
 *   http://localhost/app/ss_program/generate_absensi.php
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

$tgl = $argv[1] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
    fwrite(STDERR, "Format tanggal salah. Gunakan YYYY-MM-DD.\n");
    exit(1);
}

// Hanya hari sekolah
$day = (int)date('w', strtotime($tgl)); // 0=Minggu ... 6=Sabtu
if ($day === 0 || $day === 6) {
    echo "Tanggal $tgl adalah akhir pekan, tidak ada absensi.\n";
    exit(0);
}

$siswa = $conn->query("SELECT nis FROM siswa WHERE aktif = 1")->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare(
    "INSERT INTO absensi (tgl_absen, nis, status, keterangan, id_user)
     VALUES (?, ?, 'H', 'Otomatis (generate_absensi)', NULL)
     ON DUPLICATE KEY UPDATE id_user = id_user"
);

$created = 0;
foreach ($siswa as $s) {
    $stmt->bind_param('ss', $tgl, $s['nis']);
    if ($stmt->execute() && $stmt->affected_rows > 0) $created++;
}
$stmt->close();

echo "Selesai. Tanggal $tgl: $created siswa diabsen 'Hadir' (baru).\n";
$conn->close();
