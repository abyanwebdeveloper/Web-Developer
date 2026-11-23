<?php
/**
 * Login - Absensi SDN Tulungrejo 01
 */
require_once __DIR__ . '/library/auth.php';

if (is_login()) {
    redirect('index.php');
}

$setting = get_pengaturan($conn);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Sesi tidak valid, silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } elseif (attempt_login($conn, $username, $password)) {
            redirect('index.php');
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Absensi <?= e($setting['nama_sekolah'] ?? 'SDN Tulungrejo 01') ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <h1>Absensi <?= e($setting['nama_sekolah'] ?? 'SDN Tulungrejo 01') ?></h1>
    <p class="sub">Sistem Absensi &amp; Petugas Piket &middot; TP <?= e($setting['tahun_pelajaran'] ?? '2025/2026') ?></p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required autofocus placeholder="admin">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button class="btn" style="width:100%">Masuk</button>
    </form>
  </div>
</div>
</body>
</html>
