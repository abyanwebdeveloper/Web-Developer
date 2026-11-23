<?php
/**
 * Helper bersama untuk seluruh halaman aplikasi TokoKita.
 * Dipakai oleh index.php, admin/, barcode/, dan laporan/.
 *
 * LOGIN DIHAPUS — aplikasi dibuka langsung tanpa login, tanpa username,
 * tanpa password. Semua halaman dianggap sedang dibuka oleh admin.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('money')) {
    function money($value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}

/** Pengguna selalu dianggap masuk — tidak ada login lagi. */
function currentUser(): ?array
{
    return [
        'id' => 1,
        'username' => 'admin',
        'nama' => 'Administrator Toko',
        'role' => 'admin',
    ];
}

function isLoggedIn(): bool
{
    return true;
}

function isAdmin(): bool
{
    return true;
}

/** Tidak ada login — semua halaman terbuka, parameter $loginUrl tidak dipakai. */
function requireLogin(string $loginUrl = 'login/'): void
{
    // sengaja dikosongkan: aplikasi dibuka tanpa login.
}

/** Tidak ada login — semua halaman dianggap milik admin. */
function requireRole(string $role, string $loginUrl = '../login/'): void
{
    // sengaja dikosongkan: aplikasi dibuka tanpa login.
}

/** URL dasar aplikasi tanpa garis miring di belakang — contoh: "" atau "/toko". */
function appBase(): string
{
    $doc = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $app = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    if ($doc === '' || strcasecmp(substr($app, 0, strlen($doc)), $doc) !== 0) {
        return '';
    }
    return rtrim(substr($app, strlen($doc)), '/');
}

/** Ubah path foto produk (mis. assets/produk/beras.jpg) menjadi URL yang bisa dibuka browser. */
function fotoUrl(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, '/')) {
        return $path;
    }
    return appBase() . '/' . ltrim(str_replace('\\', '/', $path), '/');
}

/** Tag <img> untuk foto produk — kosong bila produk belum punya foto (emoji tetap dipakai). */
function fotoImg(?array $produk, string $attr = ''): string
{
    $url = fotoUrl((string) ($produk['image'] ?? ''));
    if ($url === '') {
        return '';
    }
    $alt = h($produk['name'] ?? '');
    $extra = $attr === '' ? '' : ' ' . $attr;
    return '<img class="foto-produk" src="' . h($url) . '" alt="' . $alt . '" loading="lazy" onerror="this.remove()"' . $extra . '>';
}

function csrfToken(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function verifyCsrf(): bool
{
    return hash_equals(csrfToken(), (string) ($_POST['csrf'] ?? ''));
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Ambil (dan hapus) pesan sekali tampil. */
function takeFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function flashHtml(?array $flash): string
{
    if (!$flash) {
        return '';
    }
    $type = $flash['type'] === 'success' ? 'success' : 'danger';
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
        . h($flash['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>';
}

if (!function_exists('indoDate')) {
    function indoDate(string $date, bool $time = false): string
    {
        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $t = strtotime($date);
        return date('j', $t) . ' ' . $months[(int) date('n', $t)] . ' ' . date('Y', $t) . ($time ? ' · ' . date('H:i', $t) : '');
    }
}

if (!function_exists('isValidDate')) {
    function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('!Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
