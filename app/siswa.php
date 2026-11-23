<?php
/**
 * Data Siswa (CRUD)
 */
$page = 'siswa';
require_once __DIR__ . '/library/header.php';

$user = current_user();
$isAdmin = $user['hak_akses'] === 'admin';

$rombelList = $conn->query("SELECT * FROM rombel ORDER BY tingkat, kode_rombel")->fetch_all(MYSQLI_ASSOC);

// Simpan (tambah / ubah)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid() && $isAdmin) {
    $aksi = $_POST['aksi'] ?? '';
    $nis    = trim($_POST['nis'] ?? '');
    $nisn   = trim($_POST['nisn'] ?? '');
    $nama   = trim($_POST['nama'] ?? '');
    $jk     = ($_POST['jk'] ?? 'L') === 'P' ? 'P' : 'L';
    $tlahir = trim($_POST['tempat_lahir'] ?? '');
    $tgl    = $_POST['tgl_lahir'] ?: null;
    $alamat = trim($_POST['alamat'] ?? '');
    $hp     = trim($_POST['no_hp_ortu'] ?? '');
    $rombel = (int)($_POST['id_rombel'] ?? 0);

    if ($aksi === 'tambah') {
        if ($nis === '' || $nama === '' || $rombel === 0) {
            set_flash('danger', 'NIS, nama, dan rombel wajib diisi.');
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO siswa (nis, nisn, nama, jk, tempat_lahir, tgl_lahir, alamat, no_hp_ortu, id_rombel)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ssssssssi', $nis, $nisn, $nama, $jk, $tlahir, $tgl, $alamat, $hp, $rombel);
            if ($stmt->execute()) {
                set_flash('success', 'Siswa berhasil ditambahkan.');
            } else {
                set_flash('danger', 'Gagal: NIS mungkin sudah terpakai.');
            }
            $stmt->close();
        }
    } elseif ($aksi === 'ubah') {
        $stmt = $conn->prepare(
            "UPDATE siswa SET nisn=?, nama=?, jk=?, tempat_lahir=?, tgl_lahir=?, alamat=?, no_hp_ortu=?, id_rombel=? WHERE nis=?"
        );
        $stmt->bind_param('ssssssssi', $nisn, $nama, $jk, $tlahir, $tgl, $alamat, $hp, $rombel, $nis);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Data siswa diperbarui.');
    } elseif ($aksi === 'nonaktif') {
        $stmt = $conn->prepare("UPDATE siswa SET aktif = 1 - aktif WHERE nis = ?");
        $stmt->bind_param('s', $nis);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Status siswa diubah.');
    }
    redirect('siswa.php');
}

// Data untuk form edit
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM siswa WHERE nis = ?");
    $stmt->bind_param('s', $_GET['edit']);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Pencarian
$q = trim($_GET['q'] ?? '');
$sql = "SELECT s.*, r.nama_rombel FROM siswa s JOIN rombel r ON r.id_rombel = s.id_rombel";
if ($q !== '') {
    $sql .= " WHERE s.nama LIKE '%$q%' OR s.nis LIKE '$q%' OR r.nama_rombel LIKE '%$q%'";
}
$sql .= " ORDER BY r.tingkat, s.nama";
$siswa = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<?php if ($isAdmin): ?>
<div class="card">
  <h2><?= $edit ? 'Ubah Data Siswa' : 'Tambah Siswa' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>">
    <input type="hidden" name="nis" value="<?= e($edit['nis'] ?? '') ?>">

    <div class="row-form">
      <?php if ($edit): ?>
        <div class="form-group">
          <label>NIS (tidak bisa diubah)</label>
          <input type="text" value="<?= e($edit['nis']) ?>" disabled>
        </div>
      <?php else: ?>
        <div class="form-group">
          <label>NIS *</label>
          <input type="text" name="nis" required placeholder="2410006">
        </div>
      <?php endif; ?>
      <div class="form-group">
        <label>NISN</label>
        <input type="text" name="nisn" value="<?= e($edit['nisn'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Nama Lengkap *</label>
        <input type="text" name="nama" required value="<?= e($edit['nama'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Jenis Kelamin</label>
        <select name="jk">
          <option value="L" <?= ($edit['jk'] ?? 'L') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
          <option value="P" <?= ($edit['jk'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
        </select>
      </div>
      <div class="form-group">
        <label>Tempat Lahir</label>
        <input type="text" name="tempat_lahir" value="<?= e($edit['tempat_lahir'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Tanggal Lahir</label>
        <input type="date" name="tgl_lahir" value="<?= e($edit['tgl_lahir'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>No. HP Orang Tua</label>
        <input type="text" name="no_hp_ortu" value="<?= e($edit['no_hp_ortu'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Rombel *</label>
        <select name="id_rombel" required>
          <option value="">-- pilih --</option>
          <?php foreach ($rombelList as $r): ?>
            <option value="<?= (int)$r['id_rombel'] ?>" <?= (int)($edit['id_rombel'] ?? 0) === (int)$r['id_rombel'] ? 'selected' : '' ?>>
              <?= e($r['nama_rombel']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>Alamat</label>
        <input type="text" name="alamat" value="<?= e($edit['alamat'] ?? '') ?>">
      </div>
    </div>

    <button class="btn btn-success"><?= $edit ? '💾 Simpan Perubahan' : '➕ Tambah Siswa' ?></button>
    <?php if ($edit): ?><a class="btn btn-secondary" href="siswa.php">Batal</a><?php endif; ?>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <h2>Daftar Siswa (<?= count($siswa) ?>)</h2>
  <form method="get" class="filter-bar no-print" style="margin-bottom:12px">
    <div class="form-group" style="min-width:260px">
      <label>Cari</label>
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Nama / NIS / Rombel">
    </div>
    <button class="btn">Cari</button>
    <a class="btn btn-secondary" href="siswa.php">Reset</a>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>NIS</th><th>Nama</th><th>JK</th><th>Rombel</th><th>HP Orang Tua</th><th>Status</th>
          <?php if ($isAdmin): ?><th class="no-print">Aksi</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php if (!$siswa): ?>
        <tr><td colspan="7" class="center" style="color:var(--muted)">Tidak ada data siswa.</td></tr>
      <?php endif; ?>
      <?php foreach ($siswa as $s): ?>
        <tr>
          <td><?= e($s['nis']) ?></td>
          <td><?= e($s['nama']) ?></td>
          <td><?= e($s['jk']) ?></td>
          <td><?= e($s['nama_rombel']) ?></td>
          <td><?= e($s['no_hp_ortu'] ?: '-') ?></td>
          <td><span class="badge <?= $s['aktif'] ? 'badge-success' : 'badge-secondary' ?>"><?= $s['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
          <?php if ($isAdmin): ?>
          <td class="no-print">
            <div class="actions">
              <a class="btn btn-sm btn-outline" href="siswa.php?edit=<?= e($s['nis']) ?>">Ubah</a>
              <form method="post" class="confirm-submit" data-confirm="Ubah status siswa ini?">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="nonaktif">
                <input type="hidden" name="nis" value="<?= e($s['nis']) ?>">
                <button class="btn btn-sm btn-warning"><?= $s['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
              </form>
            </div>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/library/footer.php'; ?>
