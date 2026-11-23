<?php
/**
 * Data Rombel / Kelas (khusus admin)
 */
$page = 'rombel';
require_once __DIR__ . '/library/header.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id_rombel'] ?? 0);

    if ($aksi === 'tambah' || $aksi === 'ubah') {
        $kode  = trim($_POST['kode_rombel'] ?? '');
        $nama  = trim($_POST['nama_rombel'] ?? '');
        $ting  = (int)($_POST['tingkat'] ?? 0);
        $wali  = trim($_POST['wali_kelas'] ?? '');

        if ($kode === '' || $nama === '' || $ting < 1) {
            set_flash('danger', 'Kode, nama, dan tingkat rombel wajib diisi.');
        } elseif ($aksi === 'tambah') {
            $stmt = $conn->prepare("INSERT INTO rombel (kode_rombel, nama_rombel, tingkat, wali_kelas) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssis', $kode, $nama, $ting, $wali);
            $stmt->execute() ? set_flash('success', 'Rombel ditambahkan.') : set_flash('danger', 'Gagal: kode rombel sudah ada.');
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE rombel SET kode_rombel=?, nama_rombel=?, tingkat=?, wali_kelas=? WHERE id_rombel=?");
            $stmt->bind_param('ssisi', $kode, $nama, $ting, $wali, $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Rombel diperbarui.');
        }
    } elseif ($aksi === 'hapus') {
        $cek = $conn->prepare("SELECT COUNT(*) j FROM siswa WHERE id_rombel = ?");
        $cek->bind_param('i', $id);
        $cek->execute();
        $jml = (int)$cek->get_result()->fetch_assoc()['j'];
        $cek->close();

        if ($jml > 0) {
            set_flash('danger', "Tidak bisa menghapus: masih ada $jml siswa di rombel ini.");
        } else {
            $stmt = $conn->prepare("DELETE FROM rombel WHERE id_rombel = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Rombel dihapus.');
        }
    }
    redirect('rombel.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM rombel WHERE id_rombel = ?");
    $stmt->bind_param('i', $_GET['edit']);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$rombel = $conn->query(
    "SELECT r.*, (SELECT COUNT(*) FROM siswa s WHERE s.id_rombel = r.id_rombel AND s.aktif = 1) AS jml_siswa
     FROM rombel r ORDER BY r.tingkat, r.kode_rombel"
)->fetch_all(MYSQLI_ASSOC);
?>
<div class="card">
  <h2><?= $edit ? 'Ubah Rombel' : 'Tambah Rombel' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>">
    <input type="hidden" name="id_rombel" value="<?= (int)($edit['id_rombel'] ?? 0) ?>">
    <div class="row-form">
      <div class="form-group">
        <label>Kode Rombel * (mis. 1A)</label>
        <input type="text" name="kode_rombel" required value="<?= e($edit['kode_rombel'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Nama Rombel * (mis. Kelas 1A)</label>
        <input type="text" name="nama_rombel" required value="<?= e($edit['nama_rombel'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Tingkat *</label>
        <select name="tingkat" required>
          <?php for ($i = 1; $i <= 6; $i++): ?>
            <option value="<?= $i ?>" <?= (int)($edit['tingkat'] ?? 0) === $i ? 'selected' : '' ?>>Kelas <?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Wali Kelas</label>
        <input type="text" name="wali_kelas" value="<?= e($edit['wali_kelas'] ?? '') ?>">
      </div>
    </div>
    <button class="btn btn-success"><?= $edit ? '💾 Simpan' : '➕ Tambah' ?></button>
    <?php if ($edit): ?><a class="btn btn-secondary" href="rombel.php">Batal</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <h2>Daftar Rombel</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Kode</th><th>Nama</th><th>Tingkat</th><th>Wali Kelas</th><th class="center">Jml Siswa</th><th class="no-print">Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rombel as $r): ?>
        <tr>
          <td><?= e($r['kode_rombel']) ?></td>
          <td><?= e($r['nama_rombel']) ?></td>
          <td>Kelas <?= (int)$r['tingkat'] ?></td>
          <td><?= e($r['wali_kelas'] ?: '-') ?></td>
          <td class="center"><?= (int)$r['jml_siswa'] ?></td>
          <td class="no-print">
            <div class="actions">
              <a class="btn btn-sm btn-outline" href="rombel.php?edit=<?= (int)$r['id_rombel'] ?>">Ubah</a>
              <form method="post" class="confirm-submit" data-confirm="Hapus rombel <?= e($r['nama_rombel']) ?>?">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id_rombel" value="<?= (int)$r['id_rombel'] ?>">
                <button class="btn btn-sm btn-danger">Hapus</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/library/footer.php'; ?>
