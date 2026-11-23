<?php
/**
 * Dashboard - statistik kehadiran
 */
$page = 'dashboard';
require_once __DIR__ . '/library/header.php';

$user = current_user();

$totalSiswa = (int)$conn->query("SELECT COUNT(*) j FROM siswa WHERE aktif = 1")->fetch_assoc()['j'];
$totalGuru  = (int)$conn->query("SELECT COUNT(*) j FROM users WHERE hak_akses = 'guru' AND aktif = 1")->fetch_assoc()['j'];
$hariIni    = date('Y-m-d');

$absenHariIni = (int)$conn->query(
    "SELECT COUNT(*) j FROM absensi WHERE tgl_absen = '" . $conn->real_escape_string($hariIni) . "'"
)->fetch_assoc()['j'];

$hadirHariIni = (int)$conn->query(
    "SELECT COUNT(*) j FROM absensi WHERE tgl_absen = '" . $conn->real_escape_string($hariIni) . "' AND status = 'H'"
)->fetch_assoc()['j'];

$persenHadir = $absenHariIni > 0 ? round($hadirHariIni / $absenHariIni * 100, 1) : 0;

// Rekap bulan berjalan per rombel
$bulan = date('Y-m');
$rekapRombel = $conn->query(
    "SELECT r.nama_rombel,
            COUNT(*) AS total,
            SUM(CASE WHEN a.status='H' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN a.status='I' THEN 1 ELSE 0 END) AS izin,
            SUM(CASE WHEN a.status='S' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN a.status='A' THEN 1 ELSE 0 END) AS alpa
     FROM absensi a
     JOIN siswa s ON s.nis = a.nis
     JOIN rombel r ON r.id_rombel = s.id_rombel
     WHERE DATE_FORMAT(a.tgl_absen, '%Y-%m') = '" . $conn->real_escape_string($bulan) . "'
     GROUP BY r.id_rombel, r.nama_rombel
     ORDER BY r.tingkat"
)->fetch_all(MYSQLI_ASSOC);

// Jadwal piket hari ini
$hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
$hariIniNama = $hariMap[(int)date('w')] ?? '';
$piketHariIni = [];
if ($hariIniNama) {
    $stmt = $conn->prepare("SELECT p.*, u.nama FROM piket p JOIN users u ON u.id_user = p.id_user WHERE p.hari = ? ORDER BY p.jam_masuk");
    $stmt->bind_param('s', $hariIniNama);
    $stmt->execute();
    $piketHariIni = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 5 absensi terakhir
$terakhir = $conn->query(
    "SELECT a.*, s.nama, r.nama_rombel FROM absensi a
     JOIN siswa s ON s.nis = a.nis
     JOIN rombel r ON r.id_rombel = s.id_rombel
     ORDER BY a.id_absen DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);
?>
<div class="grid grid-4" style="margin-bottom:18px">
  <div class="stat">
    <div><div class="num"><?= $totalSiswa ?></div><div class="lbl">Siswa Aktif</div></div>
    <div class="ico">🎒</div>
  </div>
  <div class="stat">
    <div><div class="num"><?= $absenHariIni ?></div><div class="lbl">Absen Hari Ini</div></div>
    <div class="ico">📝</div>
  </div>
  <div class="stat">
    <div><div class="num"><?= $persenHadir ?>%</div><div class="lbl">Kehadiran Hari Ini</div></div>
    <div class="ico">✅</div>
  </div>
  <div class="stat">
    <div><div class="num"><?= $totalGuru ?></div><div class="lbl">Guru / Petugas</div></div>
    <div class="ico">👨‍🏫</div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <h2>Rekap Kehadiran Bulan <?= tanggal_indo($bulan . '-01') ?> (per Rombel)</h2>
    <?php if (!$rekapRombel): ?>
      <p style="color:var(--muted)">Belum ada data absensi bulan ini. Mulai input di menu <a href="absensi.php">Input Absensi</a>.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Rombel</th><th class="center">H</th><th class="center">I</th><th class="center">S</th><th class="center">A</th><th class="center">%</th></tr>
          </thead>
          <tbody>
          <?php foreach ($rekapRombel as $r): ?>
            <?php $p = $r['total'] > 0 ? round($r['hadir'] / $r['total'] * 100, 1) : 0; ?>
            <tr>
              <td><?= e($r['nama_rombel']) ?></td>
              <td class="center"><?= (int)$r['hadir'] ?></td>
              <td class="center"><?= (int)$r['izin'] ?></td>
              <td class="center"><?= (int)$r['sakit'] ?></td>
              <td class="center"><?= (int)$r['alpa'] ?></td>
              <td class="center"><strong><?= $p ?>%</strong></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Piket Hari Ini (<?= e($hariIniNama ?: '-') ?>)</h2>
    <?php if (!$piketHariIni): ?>
      <p style="color:var(--muted)">Tidak ada petugas piket untuk hari ini.</p>
    <?php else: ?>
      <?php foreach ($piketHariIni as $p): ?>
        <div class="bar-row" style="margin-bottom:10px">
          <div style="flex:1">
            <strong><?= e($p['nama']) ?></strong><br>
            <small style="color:var(--muted)"><?= e($p['pos']) ?> &middot; <?= e(substr($p['jam_masuk'], 0, 5)) ?> - <?= e(substr($p['jam_pulang'], 0, 5)) ?></small>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <a class="btn btn-outline btn-sm" href="piket/index.php">Lihat Jadwal Lengkap</a>
  </div>
</div>

<div class="card">
  <h2>Absensi Terakhir</h2>
  <?php if (!$terakhir): ?>
    <p style="color:var(--muted)">Belum ada data absensi.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tanggal</th><th>NIS</th><th>Nama</th><th>Rombel</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($terakhir as $t): list($bt, $bc) = status_badge($t['status']); ?>
          <tr>
            <td><?= tanggal_indo($t['tgl_absen']) ?></td>
            <td><?= e($t['nis']) ?></td>
            <td><?= e($t['nama']) ?></td>
            <td><?= e($t['nama_rombel']) ?></td>
            <td><span class="badge <?= $bc ?>"><?= e($bt) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/library/footer.php'; ?>
