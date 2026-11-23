<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "databasesiswa";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("Tidak bisa terkoneksi ke database");
}

$sql = "SELECT * FROM tabelsiswa ORDER BY id DESC";
if (isset($_GET['cari']) && trim($_GET['cari']) != '') {
    $k = mysqli_real_escape_string($koneksi, trim($_GET['cari']));
    $sql = "SELECT * FROM tabelsiswa WHERE nama LIKE '%$k%' OR kelas LIKE '%$k%' OR nohp LIKE '%$k%' ORDER BY id DESC";
}
$q = mysqli_query($koneksi, $sql);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=data_siswa.csv');

$output = fopen('php://output', 'w');
fputcsv($output, array('ID', 'Nama', 'Kelas', 'No HP'));
while ($r = mysqli_fetch_assoc($q)) {
    fputcsv($output, array($r['id'], $r['nama'], $r['kelas'], $r['nohp']));
}
fclose($output);
exit;
