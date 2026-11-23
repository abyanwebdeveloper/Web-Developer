<?php
require __DIR__ . '/../template/config.php';
require __DIR__ . '/../template/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'Sesi berakhir. Muat ulang halaman lalu coba lagi.');
        header('Location: produk.php');
        exit;
    }
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'save') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
            $name = trim((string) ($_POST['name'] ?? ''));
            $sku = strtoupper(trim((string) ($_POST['sku'] ?? '')));
            $category = trim((string) ($_POST['category'] ?? ''));
            $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
            $cost = filter_var($_POST['cost'] ?? null, FILTER_VALIDATE_FLOAT);
            $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            $minStock = filter_var($_POST['min_stock'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            $emoji = trim((string) ($_POST['emoji'] ?? '📦')) ?: '📦';
            $image = trim((string) ($_POST['image'] ?? ''));
            if ($name === '' || mb_strlen($name) > 160 || $sku === '' || mb_strlen($sku) > 50 || $category === ''
                || $price === false || $price < 0 || $cost === false || $cost < 0
                || $stock === false || $minStock === false || $stock > 1000000 || $minStock > 1000000
                || mb_strlen($image) > 255) {
                throw new InvalidArgumentException('Data produk belum lengkap atau tidak valid.');
            }
            if ($id) {
                $stmt = $koneksi->prepare('UPDATE products SET name=?, sku=?, category=?, price=?, cost=?, stock=?, min_stock=?, emoji=?, image=? WHERE id=?');
                $stmt->execute([$name, $sku, $category, $price, $cost, $stock, $minStock, $emoji, $image, $id]);
                setFlash('success', 'Produk "' . $name . '" berhasil diperbarui.');
            } else {
                $stmt = $koneksi->prepare('INSERT INTO products (name, sku, category, price, cost, stock, min_stock, emoji, image) VALUES (?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$name, $sku, $category, $price, $cost, $stock, $minStock, $emoji, $image]);
                setFlash('success', 'Produk "' . $name . '" berhasil ditambahkan.');
            }
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id) {
                $stmt = $koneksi->prepare('DELETE FROM products WHERE id=?');
                $stmt->execute([$id]);
                setFlash('success', 'Produk berhasil dihapus. Riwayat penjualan tetap tersimpan.');
            }
        }
    } catch (Throwable $error) {
        error_log('Admin produk: ' . $error->getMessage());
        $message = $error instanceof InvalidArgumentException ? $error->getMessage()
            : (($error instanceof PDOException && $error->getCode() === '23000') ? 'Kode SKU sudah dipakai produk lain.' : 'Terjadi kesalahan saat menyimpan produk.');
        setFlash('error', $message);
    }
    header('Location: produk.php');
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
$kategoriFilter = trim((string) ($_GET['kategori'] ?? ''));
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR sku LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($kategoriFilter !== '') {
    $where[] = 'category = ?';
    $params[] = $kategoriFilter;
}
$stmt = $koneksi->prepare('SELECT * FROM products' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC');
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $koneksi->query('SELECT DISTINCT category FROM products ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Kelola Produk';
$pageKey = 'produk';
require __DIR__ . '/_layout_top.php';
?>
    <div class="page-head">
        <div>
            <div class="eyebrow">INVENTARIS TOKO</div>
            <h1>Kelola Produk</h1>
            <p>Tambah, ubah, hapus, dan cari produk dalam satu tempat.</p>
        </div>
        <button class="btn btn-brand" type="button" data-bs-toggle="modal" data-bs-target="#productModal" data-mode="add">＋ Tambah produk</button>
    </div>

    <?= flashHtml(takeFlash()) ?>

    <div class="card-soft p-4 mb-4">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Cari produk</label>
                <input class="form-control" type="search" name="q" value="<?= h($q) ?>" placeholder="Nama atau SKU…">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Kategori</label>
                <select class="form-select" name="kategori">
                    <option value="">Semua kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= h($cat) ?>" <?= $cat === $kategoriFilter ? 'selected' : '' ?>><?= h($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-ghost-soft" type="submit">🔍 Terapkan filter</button>
            </div>
        </form>
        <div class="form-borderless mt-3">
            <input class="form-control" id="ajaxProductSearch" type="search" placeholder="…atau ketik di sini untuk pencarian langsung (AJAX)">
        </div>
    </div>

    <div class="card-soft p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th style="width:60px"></th>
                    <th>PRODUK</th>
                    <th>KATEGORI</th>
                    <th>HARGA JUAL</th>
                    <th>HARGA MODAL</th>
                    <th>STOK</th>
                    <th class="text-end">AKSI</th>
                </tr>
                </thead>
                <tbody id="ajaxProductRows">
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><span class="avatar-chip"><?= h($p['emoji']) ?><?= fotoImg($p) ?></span></td>
                        <td><strong><?= h($p['name']) ?></strong><br><small class="text-secondary"><?= h($p['sku']) ?></small></td>
                        <td><span class="pill"><?= h($p['category']) ?></span></td>
                        <td class="fw-bold"><?= money($p['price']) ?></td>
                        <td class="text-secondary"><?= money($p['cost']) ?></td>
                        <td><span class="pill <?= (int) $p['stock'] <= (int) $p['min_stock'] ? 'warn' : '' ?>"><?= (int) $p['stock'] ?> stok (min <?= (int) $p['min_stock'] ?>)</span></td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-ghost-soft" type="button" data-bs-toggle="modal" data-bs-target="#productModal" data-mode="edit"
                                    data-id="<?= (int) $p['id'] ?>" data-name="<?= h($p['name']) ?>" data-sku="<?= h($p['sku']) ?>"
                                    data-category="<?= h($p['category']) ?>" data-price="<?= h($p['price']) ?>" data-cost="<?= h($p['cost']) ?>"
                                    data-stock="<?= (int) $p['stock'] ?>" data-min_stock="<?= (int) $p['min_stock'] ?>" data-emoji="<?= h($p['emoji']) ?>"
                                    data-image="<?= h($p['image'] ?? '') ?>">✏️ Edit</button>
                            <form method="post" class="d-inline" data-confirm="Hapus produk <?= h($p['name']) ?>? Riwayat penjualannya tetap tersimpan.">
                                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">🗑 Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!$products): ?>
            <div id="ajaxProductEmpty" class="p-5 text-center text-secondary">Tidak ada produk yang cocok dengan pencarian.</div>
        <?php endif; ?>
    </div>

    <!-- Modal produk -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" method="post" id="productForm">
                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="eyebrow small fw-bold text-success">INVENTARIS</div>
                        <h5 class="modal-title fw-bold" id="productModalTitle">Tambah produk baru</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nama produk</label>
                            <input class="form-control" name="name" maxlength="160" required placeholder="Contoh: Beras Premium 5 kg">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kode SKU</label>
                            <input class="form-control" name="sku" maxlength="50" required placeholder="Contoh: SMB-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kategori</label>
                            <input class="form-control" name="category" list="categoryList" maxlength="80" required placeholder="Contoh: Sembako">
                            <datalist id="categoryList">
                                <?php foreach ($categories as $cat): ?><option value="<?= h($cat) ?>"><?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Harga jual (Rp)</label>
                            <input class="form-control" name="price" type="number" min="0" step="0.01" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Harga modal (Rp)</label>
                            <input class="form-control" name="cost" type="number" min="0" step="0.01" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Jumlah stok</label>
                            <input class="form-control" name="stock" type="number" min="0" step="1" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Batas stok minimum</label>
                            <input class="form-control" name="min_stock" type="number" min="0" step="1" value="5" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Emoji produk</label>
                            <input class="form-control" name="emoji" maxlength="20" value="📦" placeholder="📦">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Foto produk</label>
                            <input class="form-control" name="image" id="productImageInput" maxlength="255" placeholder="assets/produk/beras.jpg">
                            <div class="form-text">Isi dengan alamat file di folder <code>assets/produk/</code> atau URL gambar. Kosongkan untuk memakai emoji.</div>
                            <img id="productImagePreview" class="rounded mt-2 d-none" alt="" style="width:72px;height:72px;object-fit:cover">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-ghost-soft" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan produk →</button>
                </div>
            </form>
        </div>
    </div>

    <script>
      (() => {
        const modal = document.getElementById('productModal');
        const form = document.getElementById('productForm');
        const imgInput = document.getElementById('productImageInput');
        const imgPreview = document.getElementById('productImagePreview');
        const base = location.pathname.indexOf('/admin/') > 0 ? location.pathname.slice(0, location.pathname.indexOf('/admin/')) : '';
        function tampilkanFoto() {
          let v = (imgInput.value || '').trim();
          if (v && !/^(https?:)?\/\//i.test(v) && v.charAt(0) !== '/') v = base + '/' + v.replace(/^\.?\//, '');
          if (!v) { imgPreview.classList.add('d-none'); imgPreview.removeAttribute('src'); return; }
          imgPreview.onerror = () => imgPreview.classList.add('d-none');
          imgPreview.src = v;
          imgPreview.classList.toggle('d-none', !v);
        }
        imgInput.addEventListener('input', tampilkanFoto);
        modal.addEventListener('show.bs.modal', event => {
          const trigger = event.relatedTarget;
          const add = !trigger || trigger.dataset.mode === 'add';
          form.reset();
          form.elements.id.value = '';
          document.getElementById('productModalTitle').textContent = add ? 'Tambah produk baru' : 'Edit produk';
          if (!add && trigger) {
            ['id', 'name', 'sku', 'category', 'price', 'cost', 'stock', 'min_stock', 'emoji', 'image'].forEach(key => {
              if (form.elements[key]) form.elements[key].value = trigger.dataset[key] ?? '';
            });
          }
          tampilkanFoto();
        });
      })();
    </script>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
