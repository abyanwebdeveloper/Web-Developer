<?php
/**
 * Input Absensi Harian per Rombel
 */
$page = 'absensi';
require_once __DIR__ . '/library/header.php';

$user = current_user();
$setting = get_pengaturan($conn);

$tgl = $_GET['tgl'] ?? date('Y-m-d');
$idRombel = (int)($_GET['rombel'] ?? 0);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) $tgl = date('Y-m-d');

$rombelList = $conn->query("SELECT * FROM rombel ORDER BY tingkat, kode_rombel")->fetch_all(MYSQLI_ASSOC);

// Simpan absensi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    $tgl = $_POST['tgl_absen'] ?? $tgl;
    $idRombel = (int)($_POST['id_rombel'] ?? 0);

    $nisArr    = $_POST['nis'] ?? [];
    $statusArr = $_POST['status'] ?? [];
    $ketArr    = $_POST['keterangan'] ?? [];

    $stmt = $conn->prepare(
        "INSERT INTO absensi (tgl_absen, nis, status, keterangan, id_user)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE status = VALUES(status), keterangan = VALUES(keterangan), id_user = VALUES(id_user)"
    );

    $saved = 0;
    foreach ($nisArr as $i => $nis) {
        $status = in_array($statusArr[$i] ?? '', ['H', 'I', 'S', 'A'], true) ? $statusArr[$i] : 'H';
        $ket = trim($ketArr[$i] ?? '');
        $stmt->bind_param('ssssi', $tgl, $nis, $status, $ket, $user['id_user']);
        if ($stmt->execute()) $saved++;
    }
    $stmt->close();

    set_flash('success', "Absensi berhasil disimpan ($saved siswa).");
    redirect('absensi.php?tgl=' . urlencode($tgl) . '&rombel=' . $idRombel);
}

// Ambil siswa + status tersimpan (jika rombel dipilih)
$siswaList = [];
$sudahTersimpan = false;
if ($idRombel > 0) {
    $stmt = $conn->prepare(
        "SELECT s.nis, s.nama, a.status, a.keterangan
         FROM siswa s
         LEFT JOIN absensi a ON a.nis = s.nis AND a.tgl_absen = ?
         WHERE s.id_rombel = ? AND s.aktif = 1
         ORDER BY s.nama"
    );
    $stmt->bind_param('si', $tgl, $idRombel);
    $stmt->execute();
    $siswaList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($siswaList as $s) {
        if ($s['status'] !== null) { $sudahTersimpan = true; break; }
    }
}

function status_options($selected)
{
    $opts = ['H' => 'Hadir', 'I' => 'Izin', 'S' => 'Sakit', 'A' => 'Alpa'];
    $html = '';
    foreach ($opts as $k => $v) {
        $html .= '<option value="' . $k . '"' . ($selected === $k ? ' selected' : '') . '>' . $v . '</option>';
    }
    return $html;
}
?>
<div class="card">
  <h2>Pilih Tanggal &amp; Rombel</h2>
  <form method="get" class="filter-bar">
    <div class="form-group">
      <label>Tanggal</label>
      <input type="date" name="tgl" value="<?= e($tgl) ?>" required>
    </div>
    <div class="form-group">
      <label>Rombel</label>
      <select name="rombel" required>
        <option value="">-- pilih --</option>
        <?php foreach ($rombelList as $r): ?>
          <option value="<?= (int)$r['id_rombel'] ?>" <?= $idRombel === (int)$r['id_rombel'] ? 'selected' : '' ?>>
            <?= e($r['nama_rombel']) ?><?= $r['wali_kelas'] ? ' - ' . e($r['wali_kelas']) : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn">Tampilkan</button>
  </form>
</div>

<?php if ($idRombel > 0): ?>
<div class="card">
  <h2>
    Absensi <?= tanggal_indo($tgl) ?>
    <?php if ($sudahTersimpan): ?>
      <span class="badge badge-warning">Sudah terisi (disimpan ulang akan diperbarui)</span>
    <?php else: ?>
      <span class="badge badge-info">Belum terisi</span>
    <?php endif; ?>
  </h2>

  <?php if (!$siswaList): ?>
    <div class="alert alert-warning">Tidak ada siswa aktif pada rombel ini.</div>
  <?php else: ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="tgl_absen" value="<?= e($tgl) ?>">
      <input type="hidden" name="id_rombel" value="<?= $idRombel ?>">

      <div class="filter-bar no-print" style="margin-bottom:12px">
        <button type="button" class="btn btn-sm btn-success" onclick="setAllStatus('H')">Tandai Semua Hadir</button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="setAllStatus('A')">Tandai Semua Alpa</button>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th class="center">No</th>
              <th>NIS</th>
              <th>Nama Siswa</th>
              <th style="width:140px">Status</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($siswaList as $i => $s): ?>
            <tr>
              <td class="center"><?= $i + 1 ?></td>
              <td><?= e($s['nis']) ?></td>
              <td><?= e($s['nama']) ?></td>
              <td>
                <input type="hidden" name="nis[]" value="<?= e($s['nis']) ?>">
                <select name="status[]"><?= status_options($s['status'] ?? 'H') ?></select>
              </td>
              <td>
                <input type="text" name="keterangan[]" value="<?= e($s['keterangan'] ?? '') ?>" placeholder="mis. izin acara keluarga">
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:14px">
        <button class="btn btn-success">💾 Simpan Absensi</button>
        <button type="button" class="btn btn-secondary" onclick="cetak()">🖨 Cetak Daftar Hadir</button>
      </div>
    </form>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/library/footer.php'; ?>
