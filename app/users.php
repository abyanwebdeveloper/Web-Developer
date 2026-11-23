<?php
/**
 * Manajemen Pengguna (khusus admin)
 */
$page = 'users';
require_once __DIR__ . '/library/header.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id_user'] ?? 0);

    if ($aksi === 'tambah' || $aksi === 'ubah') {
        $username = trim($_POST['username'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $nip      = trim($_POST['nip'] ?? '');
        $jabatan  = trim($_POST['jabatan'] ?? 'Guru');
        $hak      = ($_POST['hak_akses'] ?? 'guru') === 'admin' ? 'admin' : 'guru';
        $password = $_POST['password'] ?? '';

        if ($username === '' || $nama === '') {
            set_flash('danger', 'Username dan nama wajib diisi.');
        } elseif ($aksi === 'tambah' && strlen($password) < 5) {
            set_flash('danger', 'Password minimal 5 karakter.');
        } elseif ($aksi === 'ubah' && $password !== '' && strlen($password) < 5) {
            set_flash('danger', 'Password minimal 5 karakter.');
        } elseif ($aksi === 'tambah') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, nama, nip, jabatan, hak_akses) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $username, $hash, $nama, $nip, $jabatan, $hak);
            $stmt->execute() ? set_flash('success', 'Pengguna ditambahkan.') : set_flash('danger', 'Username sudah dipakai.');
            $stmt->close();
        } else {
            if ($password === '') {
                $stmt = $conn->prepare("UPDATE users SET username=?, nama=?, nip=?, jabatan=?, hak_akses=? WHERE id_user=?");
                $stmt->bind_param('sssssi', $username, $nama, $nip, $jabatan, $hak, $id);
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, password=?, nama=?, nip=?, jabatan=?, hak_akses=? WHERE id_user=?");
                $stmt->bind_param('ssssssi', $username, $hash, $nama, $nip, $jabatan, $hak, $id);
            }
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Pengguna diperbarui.');
        }
    } elseif ($aksi === 'nonaktif') {
        if ($id === current_user()['id_user']) {
            set_flash('danger', 'Tidak bisa menonaktifkan akun sendiri.');
        } else {
            $stmt = $conn->prepare("UPDATE users SET aktif = 1 - aktif WHERE id_user = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Status pengguna diubah.');
        }
    }
    redirect('users.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->bind_param('i', $_GET['edit']);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$users = $conn->query("SELECT * FROM users ORDER BY hak_akses, nama")->fetch_all(MYSQLI_ASSOC);
?>
<div class="card">
  <h2><?= $edit ? 'Ubah Pengguna' : 'Tambah Pengguna' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>">
    <input type="hidden" name="id_user" value="<?= (int)($edit['id_user'] ?? 0) ?>">
    <div class="row-form">
      <div class="form-group">
        <label>Username *</label>
        <input type="text" name="username" required value="<?= e($edit['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Password <?= $edit ? '(kosongkan jika tetap)' : '*' ?></label>
        <input type="password" name="password" <?= $edit ? '' : 'required' ?> minlength="5" placeholder="minimal 5 karakter">
      </div>
      <div class="form-group">
        <label>Nama Lengkap *</label>
        <input type="text" name="nama" required value="<?= e($edit['nama'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>NIP</label>
        <input type="text" name="nip" value="<?= e($edit['nip'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Jabatan</label>
        <input type="text" name="jabatan" value="<?= e($edit['jabatan'] ?? 'Guru') ?>">
      </div>
      <div class="form-group">
        <label>Hak Akses</label>
        <select name="hak_akses">
          <option value="guru" <?= ($edit['hak_akses'] ?? '') === 'guru' ? 'selected' : '' ?>>Guru / Petugas</option>
          <option value="admin" <?= ($edit['hak_akses'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>
    </div>
    <button class="btn btn-success"><?= $edit ? '💾 Simpan' : '➕ Tambah' ?></button>
    <?php if ($edit): ?><a class="btn btn-secondary" href="users.php">Batal</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <h2>Daftar Pengguna</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Username</th><th>Nama</th><th>NIP</th><th>Jabatan</th><th>Hak Akses</th><th>Status</th><th class="no-print">Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['username']) ?></td>
          <td><?= e($u['nama']) ?></td>
          <td><?= e($u['nip'] ?: '-') ?></td>
          <td><?= e($u['jabatan'] ?: '-') ?></td>
          <td><span class="badge <?= $u['hak_akses'] === 'admin' ? 'badge-danger' : 'badge-info' ?>"><?= e(ucfirst($u['hak_akses'])) ?></span></td>
          <td><span class="badge <?= $u['aktif'] ? 'badge-success' : 'badge-secondary' ?>"><?= $u['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
          <td class="no-print">
            <div class="actions">
              <a class="btn btn-sm btn-outline" href="users.php?edit=<?= (int)$u['id_user'] ?>">Ubah</a>
              <?php if ((int)$u['id_user'] !== (int)current_user()['id_user']): ?>
                <form method="post" class="confirm-submit" data-confirm="Ubah status pengguna ini?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="nonaktif">
                  <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
                  <button class="btn btn-sm btn-warning"><?= $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/library/footer.php'; ?>
