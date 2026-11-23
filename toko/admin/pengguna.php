<?php
require __DIR__ . '/../template/config.php';
require __DIR__ . '/../template/auth.php';
requireRole('admin');

$me = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'Sesi berakhir. Muat ulang halaman lalu coba lagi.');
        header('Location: pengguna.php');
        exit;
    }
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'save') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
            $username = strtolower(trim((string) ($_POST['username'] ?? '')));
            $nama = trim((string) ($_POST['nama'] ?? ''));
            $role = (string) ($_POST['role'] ?? 'kasir');
            $aktif = isset($_POST['aktif']) ? 1 : 0;

            if (!preg_match('/^[a-z0-9_.]{3,30}$/', $username)) {
                throw new InvalidArgumentException('Username 3–30 karakter, huruf kecil/angka/titik/garis bawah saja.');
            }
            if ($nama === '' || mb_strlen($nama) > 80) {
                throw new InvalidArgumentException('Nama lengkap wajib diisi (maks 80 karakter).');
            }
            if (!in_array($role, ['admin', 'kasir'], true)) {
                throw new InvalidArgumentException('Role tidak dikenal.');
            }
            // Menonaktifkan akun terakhir yang aktif dengan role admin tidak diizinkan.
            $guard = $koneksi->prepare("SELECT COUNT(*) FROM users WHERE role='admin' AND aktif=1 AND id <> COALESCE(?, 0)");
            $guard->execute([$id]);
            if ($role !== 'admin' || !$aktif) {
                if ((int) $guard->fetchColumn() === 0) {
                    throw new InvalidArgumentException('Tidak dapat menonaktifkan / menurunkan role admin terakhir yang aktif.');
                }
            }

            if ($id) {
                $stmt = $koneksi->prepare('UPDATE users SET username=?, nama=?, role=?, aktif=? WHERE id=?');
                $stmt->execute([$username, $nama, $role, $aktif, $id]);
                setFlash('success', 'Akun "' . $username . '" berhasil diperbarui.');
            } else {
                $stmt = $koneksi->prepare("INSERT INTO users (username, password, nama, role, aktif) VALUES (?,'',?,?,?)");
                $stmt->execute([$username, $nama, $role, $aktif]);
                setFlash('success', 'Akun "' . $username . '" berhasil ditambahkan.');
            }
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === (int) $me['id']) {
                setFlash('error', 'Anda tidak dapat menghapus akun sendiri.');
                header('Location: pengguna.php');
                exit;
            }
            if ($id) {
                $check = $koneksi->prepare('SELECT role FROM users WHERE id = ?');
                $check->execute([$id]);
                $role = $check->fetchColumn();
                if ($role === 'admin') {
                    $guard = $koneksi->prepare("SELECT COUNT(*) FROM users WHERE role='admin' AND aktif=1");
                    $guard->execute();
                    if ((int) $guard->fetchColumn() <= 1) {
                        setFlash('error', 'Tidak dapat menghapus admin terakhir yang aktif.');
                        header('Location: pengguna.php');
                        exit;
                    }
                }
                $stmt = $koneksi->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$id]);
                setFlash('success', 'Akun pengguna berhasil dihapus.');
            }
        }
    } catch (Throwable $error) {
        error_log('Admin pengguna: ' . $error->getMessage());
        $message = $error instanceof InvalidArgumentException ? $error->getMessage()
            : (($error instanceof PDOException && $error->getCode() === '23000') ? 'Username sudah dipakai akun lain.' : 'Terjadi kesalahan saat menyimpan akun.');
        setFlash('error', $message);
    }
    header('Location: pengguna.php');
    exit;
}

$users = $koneksi->query('SELECT id, username, nama, role, aktif, created_at FROM users ORDER BY role DESC, username')->fetchAll();

$pageTitle = 'Pengguna';
$pageKey = 'pengguna';
require __DIR__ . '/_layout_top.php';
?>
    <div class="page-head">
        <div>
            <div class="eyebrow">KELOLA AKUN</div>
            <h1>Pengguna</h1>
            <p>Kelola daftar pengguna — aplikasi kini dibuka langsung tanpa login.</p>
        </div>
        <button class="btn btn-brand" type="button" data-bs-toggle="modal" data-bs-target="#userModal" data-mode="add">＋ Tambah pengguna</button>
    </div>

    <?= flashHtml(takeFlash()) ?>

    <div class="card-soft p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>PENGGUNA</th>
                    <th>ROLE</th>
                    <th>STATUS</th>
                    <th>TERDAFTAR</th>
                    <th class="text-end">AKSI</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-chip"><?= h(strtoupper(mb_substr($u['nama'], 0, 1))) ?></span>
                                <div><strong><?= h($u['nama']) ?></strong><br><small class="text-secondary">@<?= h($u['username']) ?></small></div>
                            </div>
                        </td>
                        <td><span class="pill <?= $u['role'] === 'admin' ? 'gold' : '' ?>"><?= h(ucfirst($u['role'])) ?></span></td>
                        <td><span class="pill <?= (int) $u['aktif'] ? '' : 'warn' ?>"><?= (int) $u['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td class="text-secondary"><?= h(indoDate($u['created_at'])) ?></td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-ghost-soft" type="button" data-bs-toggle="modal" data-bs-target="#userModal" data-mode="edit"
                                    data-id="<?= (int) $u['id'] ?>" data-username="<?= h($u['username']) ?>" data-nama="<?= h($u['nama']) ?>"
                                    data-role="<?= h($u['role']) ?>" data-aktif="<?= (int) $u['aktif'] ?>">✏️ Edit</button>
                            <?php if ((int) $u['id'] !== (int) $me['id']): ?>
                                <form method="post" class="d-inline" data-confirm="Hapus akun <?= h($u['username']) ?>?">
                                    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">🗑 Hapus</button>
                                </form>
                            <?php else: ?>
                                <span class="pill">Anda</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-soft p-4 mt-4">
        <h6 class="fw-bold">Catatan</h6>
        <ul class="mb-0 text-secondary small">
            <li>Aplikasi dibuka langsung — tidak ada login, username, maupun password.</li>
            <li>Daftar akun di atas hanya data pendukung, tidak dipakai untuk masuk.</li>
            <li>Kolom password dibiarkan kosong karena sudah tidak dipakai.</li>
        </ul>
    </div>

    <!-- Modal pengguna -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="post" id="userForm">
                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="eyebrow small fw-bold text-success">AKUN</div>
                        <h5 class="modal-title fw-bold" id="userModalTitle">Tambah pengguna baru</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <input class="form-control" name="username" maxlength="30" required pattern="[a-z0-9_.]{3,30}" placeholder="huruf kecil, angka, titik, garis bawah">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama lengkap</label>
                        <input class="form-control" name="nama" maxlength="80" required placeholder="Contoh: Siti Aminah">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Role</label>
                            <select class="form-select" name="role">
                                <option value="kasir">Kasir</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="aktif" value="1" id="aktifCheck" checked>
                                <label class="form-check-label small fw-bold" for="aktifCheck">Akun aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-ghost-soft" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan akun →</button>
                </div>
            </form>
        </div>
    </div>

    <script>
      (() => {
        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');
        modal.addEventListener('show.bs.modal', event => {
          const trigger = event.relatedTarget;
          const add = !trigger || trigger.dataset.mode === 'add';
          form.reset();
          form.elements.id.value = '';
          document.getElementById('userModalTitle').textContent = add ? 'Tambah pengguna baru' : 'Edit pengguna';
          if (!add && trigger) {
            ['id', 'username', 'nama', 'role'].forEach(key => { form.elements[key].value = trigger.dataset[key] ?? ''; });
            form.elements.aktif.checked = trigger.dataset.aktif === '1';
          }
        });
      })();
    </script>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
