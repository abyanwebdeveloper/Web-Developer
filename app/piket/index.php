<?php
/**
 * Jadwal Petugas Piket Guru
 */
$page = 'piket';
require_once __DIR__ . '/../library/header.php';

$user = current_user();
$isAdmin = $user['hak_akses'] === 'admin';

$hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid() && $isAdmin) {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $hari = in_array($_POST['hari'] ?? '', $hariList, true) ? $_POST['hari'] : '';
        $idUser = (int)($_POST['id_user'] ?? 0);
        $pos = trim($_POST['pos'] ?? 'Gerbang Utama');
        $jm = $_POST['jam_masuk'] ?? '06:30';
        $jp = $_POST['jam_pulang'] ?? '14:00';
        $ket = trim($_POST['keterangan'] ?? '');

        if ($hari === '' || $idUser === 0) {
            set_flash('danger', 'Hari dan petugas wajib dipilih.');
        } else {
            $stmt = $conn->prepare("INSERT INTO piket (hari, id_user, pos, jam_masuk, jam_pulang, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sissss', $hari, $idUser, $pos, $jm, $jp, $ket);
            $stmt->execute() ? set_flash('success', 'Jadwal piket ditambahkan.') : set_flash('danger', 'Gagal: petugas sudah terjadwal pada hari/pos tersebut.');
            $stmt->close();
        }
    } elseif ($aksi === 'hapus') {
        $stmt = $conn->prepare("DELETE FROM piket WHERE id_piket = ?");
        $stmt->bind_param('i', $_POST['id_piket']);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Jadwal piket dihapus.');
    }
    redirect('../piket/index.php');
}

$guruList = $conn->query("SELECT id_user, nama, jabatan FROM users WHERE aktif = 1 ORDER BY nama")->fetch_all(MYSQLI_ASSOC);

$piket = $conn->query(
    "SELECT p.*, u.nama, u.jabatan FROM piket p JOIN users u ON u.id_user = p.id_user
     ORDER BY FIELD(p.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), p.jam_masuk"
)->fetch_all(MYSQLI_ASSOC);

$terjadwal = [];
foreach ($piket as $p) { $terjadwal[$p['hari']][] = $p; }
?>
<?php if ($isAdmin): ?>
<div class="card">
  <h2>Tambah Jadwal Piket</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="tambah">
    <div class="row-form">
      <div class="form-group">
        <label>Hari *</label>
        <select name="hari" required>
          <option value="">-- pilih --</option>
          <?php foreach ($hariList as $h): ?><option value="<?= $h ?>"><?= $h ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Petugas * (guru)</label>
        <select name="id_user" required>
          <option value="">-- pilih --</option>
          <?php foreach ($guruList as $g): ?>
            <option value="<?= (int)$g['id_user'] ?>"><?= e($g['nama']) ?><?= $g['jabatan'] ? ' - ' . e($g['jabatan']) : '' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Pos / Lokasi</label>
        <input type="text" name="pos" value="Gerbang Utama">
      </div>
      <div class="form-group">
        <label>Jam Masuk - Pulang</label>
        <div style="display:flex;gap:6px">
          <input type="time" name="jam_masuk" value="06:30">
          <input type="time" name="jam_pulang" value="14:00">
        </div>
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>Keterangan</label>
        <input type="text" name="keterangan" placeholder="opsional">
      </div>
    </div>
    <button class="btn btn-success">➕ Tambah Jadwal</button>
  </form>
</div>
<?php endif; ?>

<?php
$hariIniId = ['Sunday' => 'Senin', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
              'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'][date('l')] ?? '';
?>
<div class="grid grid-2">
  <?php foreach ($hariList as $h): ?>
    <div class="card">
      <h2><?= $h ?> <?= $h === $hariIniId ? '<span class="badge badge-success">Hari ini</span>' : '' ?></h2>
      <?php if (empty($terjadwal[$h])): ?>
        <p style="color:var(--muted)">Belum ada petugas.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Petugas</th><th>Pos</th><th>Jam</th><th class="no-print"></th></tr></thead>
            <tbody>
            <?php foreach ($terjadwal[$h] as $p): ?>
              <tr>
                <td>
                  <strong><?= e($p['nama']) ?></strong><br>
                  <small style="color:var(--muted)"><?= e($p['jabatan'] ?: '') ?><?= $p['keterangan'] ? ' · ' . e($p['keterangan']) : '' ?></small>
                </td>
                <td><?= e($p['pos']) ?></td>
                <td><?= e(substr($p['jam_masuk'], 0, 5)) ?> - <?= e(substr($p['jam_pulang'], 0, 5)) ?></td>
                <td class="no-print">
                  <?php if ($isAdmin): ?>
                    <form method="post" class="confirm-submit" data-confirm="Hapus jadwal ini?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="aksi" value="hapus">
                      <input type="hidden" name="id_piket" value="<?= (int)$p['id_piket'] ?>">
                      <button class="btn btn-sm btn-danger">✕</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../library/footer.php'; ?>
