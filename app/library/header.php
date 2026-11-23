<?php
/**
 * Layout header - sidebar + topbar
 * Variabel: $page (judul halaman aktif), $conn
 */
require_once __DIR__ . '/auth.php';
require_login();

$user = current_user();
$setting = get_pengaturan($conn);
$page = $page ?? '';

$menu = [
    'dashboard' => ['Dashboard', 'index.php', 'all'],
    'absensi'   => ['Input Absensi', 'absensi.php', 'all'],
    'rekap'     => ['Rekap Kehadiran', 'rekap.php', 'all'],
    'siswa'     => ['Data Siswa', 'siswa.php', 'all'],
    'rombel'    => ['Data Rombel', 'rombel.php', 'admin'],
    'piket'     => ['Jadwal Piket', 'piket/index.php', 'all'],
    'users'     => ['Pengguna', 'users.php', 'admin'],
    'pengaturan'=> ['Pengaturan', 'pengaturan.php', 'admin'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page ?: 'Dashboard') ?> - Absensi <?= e($setting['nama_sekolah'] ?? 'SDN Tulungrejo 01') ?></title>
<link rel="stylesheet" href="/app/assets/css/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-logo">A</div>
      <div>
        <strong>Absensi Online</strong><br>
        <small><?= e($setting['nama_sekolah'] ?? 'SDN Tulungrejo 01') ?></small>
      </div>
    </div>
    <nav class="nav">
      <?php foreach ($menu as $key => $m): ?>
        <?php if ($m[2] === 'admin' && $user['hak_akses'] !== 'admin') continue; ?>
        <a href="<?= e($m[1]) ?>" class="<?= $page === $key ? 'active' : '' ?>"><?= e($m[0]) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="btn-logout" href="logout.php">Keluar</a>
  </aside>

  <main class="content">
    <header class="topbar">
      <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">&#9776;</button>
      <h1><?= e($page ?: 'Dashboard') ?></h1>
      <div class="user-box">
        <span class="dot"></span>
        <div>
          <strong><?= e($user['nama']) ?></strong><br>
          <small><?= e(ucfirst($user['hak_akses'])) ?></small>
        </div>
      </div>
    </header>

    <?php if ($f = get_flash()): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endif; ?>
