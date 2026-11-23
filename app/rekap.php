<?php
/**
 * Rekap Kehadiran & Laporan Cetak
 */
$page = 'rekap';
require_once __DIR__ . '/library/header.php';

$setting = get_pengaturan($conn);

$rombelList = $conn->query("SELECT * FROM rombel ORDER BY tingkat, kode_rombel")->fetch_all(MYSQLI_ASSOC);

$idRombel = (int)($_GET['rombel'] ?? 0);
$bulan    = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

$rows = [];
$namaRombel = '';
if ($idRombel > 0) {
    foreach ($rombelList as $r) {
        if ((int)$r['id_rombel'] === $idRombel) { $namaRombel = $r['nama_rombel']; break; }
    }

    $stmt = $conn->prepare(
        "SELECT s.nis, s.nama,
                SUM(CASE WHEN a.status='H' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN a.status='I' THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN a.status='S' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN a.status='A' THEN 1 ELSE 0 END) AS alpa,
                COUNT(a.id_absen) AS total_absen
         FROM siswa s
         LEFT JOIN absensi a ON a.nis = s.nis AND DATE_FORMAT(a.tgl_absen, '%Y-%m') = ?
         WHERE s.id_rombel = ? AND s.aktif = 1
         GROUP BY s.nis, s.nama
         ORDER BY s.nama"
    );
    $stmt->bind_param('si', $bulan, $idRombel);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$bulanNamaMap = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$by = (int)substr($bulan, 5, 2);
$bm = (int)substr($bulan, 0, 4);
$bulanNama = ($bulanNamaMap[$by] ?? '-') . ' ' . $bm;
?>
<div class="card no-print">
  <h2>Filter Rekap</h2>
  <form method="get" class="filter-bar">
    <div class="form-group">
      <label>Rombel</label>
      <select name="rombel" required>
        <option value="">-- pilih --</option>
        <?php foreach ($rombelList as $r): ?>
          <option value="<?= (int)$r['id_rombel'] ?>" <?= $idRombel === (int)$r['id_rombel'] ? 'selected' : '' ?>>
            <?= e($r['nama_rombel']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Bulan</label>
      <input type="month" name="bulan" value="<?= e($bulan) ?>" required>
    </div>
    <button class="btn">Tampilkan</button>
    <?php if ($idRombel > 0): ?>
      <button type="button" class="btn btn-warning" onclick="cetak()">🖨 Cetak Laporan</button>
    <?php endif; ?>
  </form>
</div>

<?php if ($idRombel > 0): ?>
<div class="card">
  <div class="print-head">
    <h2 style="margin-bottom:2px">LAPORAN REKAP KEHADIRAN SISWA</h2>
    <p><?= e($setting['nama_sekolah'] ?? 'SDN Tulungrejo 01') ?> &middot; <?= e($setting['alamat'] ?? '') ?><br>
       Rombel <?= e($namaRombel) ?> &middot; Bulan <?= e($bulanNama) ?> &middot; TP <?= e($setting['tahun_pelajaran'] ?? '') ?> (<?= e($setting['semester'] ?? '') ?>)</p>
  </div>

  <h2>Rekap Kehadiran <?= e($namaRombel) ?> - <?= e($bulanNama) ?></h2>

  <?php if (!$rows): ?>
    <div class="alert alert-warning">Tidak ada data siswa/absensi untuk filter ini.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th class="center">No</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th class="center">Hadir</th>
            <th class="center">Izin</th>
            <th class="center">Sakit</th>
            <th class="center">Alpa</th>
            <th class="center">%</th>
          </tr>
        </thead>
        <tbody>
        <?php $no = 1; foreach ($rows as $r):
            $total = (int)$r['total_absen'];
            $p = $total > 0 ? round($r['hadir'] / $total * 100, 1) : 0;
        ?>
          <tr>
            <td class="center"><?= $no++ ?></td>
            <td><?= e($r['nis']) ?></td>
            <td><?= e($r['nama']) ?></td>
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

    <div style="margin-top:26px;display:none" class="print-sign">
      <table style="width:100%;border:none">
        <tr>
          <td style="border:none;white-space:normal;width:50%">
            Mengetahui,<br>Kepala Sekolah<br><br><br><br>
            <strong>( <?= e($setting['kepala_sekolah'] ?? '........................') ?> )</strong>
          </td>
          <td style="border:none;white-space:normal">
            Blitar, <?= tanggal_indo(date('Y-m-d')) ?><br>Petugas Piket / Wali Kelas<br><br><br><br>
            <strong>( ........................................ )</strong>
          </td>
        </tr>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/library/footer.php'; ?>
