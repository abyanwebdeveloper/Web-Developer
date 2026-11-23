<?php
/**
 * Fungsi bantu umum - Absensi SDN Tulungrejo 01
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';

/** Escape output (cegah XSS) */
function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/** Redirect lalu berhenti */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/** Pesan flash antar halaman */
function set_flash($type, $msg)
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash()
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/** Tanggal Indonesia: 2026-10-13 -> Selasa, 13 Oktober 2026 */
function tanggal_indo($tanggal)
{
    if (!$tanggal) return '-';
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $ts = strtotime($tanggal);
    return $hari[date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/** Ambil pengaturan sekolah (1 baris) */
function get_pengaturan($conn)
{
    $r = $conn->query("SELECT * FROM pengaturan ORDER BY id ASC LIMIT 1")->fetch_assoc();
    return $r ?: [];
}

/** Cek status login */
function is_login()
{
    return !empty($_SESSION['user']);
}

/** Data user login */
function current_user()
{
    return $_SESSION['user'] ?? null;
}

/** Hanya admin yang boleh */
function require_admin()
{
    if (!is_login() || current_user()['hak_akses'] !== 'admin') {
        set_flash('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect('/app/index.php');
    }
}

/** Warna badge status absensi */
function status_badge($s)
{
    $map = [
        'H' => ['Hadir', 'badge-success'],
        'I' => ['Izin', 'badge-warning'],
        'S' => ['Sakit', 'badge-info'],
        'A' => ['Alpa', 'badge-danger'],
    ];
    return $map[$s] ?? ['-', 'badge-secondary'];
}
