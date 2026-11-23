<?php
/**
 * Bagian atas layout admin. Variabel yang diharapkan sudah di-set pemanggil:
 *   $pageTitle (string), $pageKey (string kunci menu aktif)
 * Pemanggil sudah me-require config.php & auth.php serta memanggil requireRole('admin').
 */
$user = currentUser();
$menu = [
    'dashboard' => ['Dasbor', '▦', 'index.php'],
    'produk' => ['Kelola Produk', '◫', 'produk.php'],
    'transaksi' => ['Transaksi', '↺', 'transaksi.php'],
    'pengguna' => ['Pengguna', '☺', 'pengguna.php'],
    'laporan' => ['Laporan', '↗', '../laporan/'],
    'barcode' => ['Barcode', '▮', '../barcode/'],
    'kasir' => ['Aplikasi Kasir', '⌘', '../index.php'],
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h(csrfToken()) ?>">
    <title><?= h($pageTitle) ?> — Admin TokoKita</title>
    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="d-flex flex-lg-row flex-column">
    <aside class="admin-sidebar">
        <a class="brand" href="index.php"><span class="mark">🛍️</span><span>Toko<span>Kita</span></span></a>
        <div class="nav-caption">MENU ADMIN</div>
        <?php foreach ($menu as $key => [$label, $ico, $url]): ?>
            <a class="side-link <?= $pageKey === $key ? 'active' : '' ?>" href="<?= h($url) ?>"><span class="ico"><?= h($ico) ?></span><?= h($label) ?></a>
        <?php endforeach; ?>
        <div class="sidebar-foot">
            <strong><?= h($user['nama']) ?></strong>
            <small><?= h(ucfirst($user['role'])) ?> · Tanpa login</small>
        </div>
    </aside>
    <div class="flex-grow-1">
        <main class="admin-main">
