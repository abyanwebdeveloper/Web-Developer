<?php
/**
 * Pengaturan Sekolah (khusus admin)
 */
$page = 'pengaturan';
require_once __DIR__ . '/library/header.php';
require_admin();

$setting = get_pengaturan($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    $nama     = trim($_POST['nama_sekolah'] ?? '');
    $npsn     = trim($_POST['npsn'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');
    $kepala   = trim($_POST['kepala_sekolah'] ?? '');
    $tp       = trim($_POST['tahun_pelajaran'] ?? '');
    $semester = ($_POST['semester'] ?? 'Ganjil') === 'Genap' ? 'Genap' : 'Ganjil';

    if ($nama === '' || !preg_match('/^\d{4}\/\d{4}$/', $tp)) {
        set_flash('danger', 'Nama sekolah wajib diisi & tahun pelajaran formatnya 2025/2026.');
    } else {
        $stmt = $conn->prepare(
            "UPDATE pengaturan SET nama_sekolah=?, npsn=?, alamat=?, kepala_sekolah=?, tahun_pelajaran=?, semester=? WHERE id=?"
        );
        $stmt->bind_param('ssssssi', $nama, $npsn, $alamat, $kepala, $tp, $semester, $setting['id']);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Pengaturan disimpan.');
        redirect('pengaturan.php');
    }
}
?>
<div class="card">
  <h2>Profil Sekolah &amp; Tahun Pelajaran</h2>
  <form method="post">
    <?= csrf_field() ?>
    <div class="row-form">
      <div class="form-group">
        <label>Nama Sekolah *</label>
        <input type="text" name="nama_sekolah" required value="<?= e($setting['nama_sekolah'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>NPSN</label>
        <input type="text" name="npsn" value="<?= e($setting['npsn'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Alamat</label>
        <input type="text" name="alamat" value="<?= e($setting['alamat'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Kepala Sekolah</label>
        <input type="text" name="kepala_sekolah" value="<?= e($setting['kepala_sekolah'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Tahun Pelajaran * (2025/2026)</label>
        <input type="text" name="tahun_pelajaran" required pattern="\d{4}/\d{4}" value="<?= e($setting['tahun_pelajaran'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Semester</label>
        <select name="semester">
          <option value="Ganjil" <?= ($setting['semester'] ?? '') === 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
          <option value="Genap" <?= ($setting['semester'] ?? '') === 'Genap' ? 'selected' : '' ?>>Genap</option>
        </select>
      </div>
    </div>
    <button class="btn btn-success">💾 Simpan Pengaturan</button>
  </form>
</div>
<?php require_once __DIR__ . '/library/footer.php'; ?>
