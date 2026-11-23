<?php
require __DIR__ . '/../template/config.php';
require __DIR__ . '/../template/auth.php';
requireLogin('../login/');
require __DIR__ . '/code128.php';

// Aksi cetak: kirim daftar id produk + jumlah label masing-masing ke halaman cetak.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'Sesi berakhir. Muat ulang halaman lalu coba lagi.');
        header('Location: index.php');
        exit;
    }
    $ids = array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), fn($v) => $v > 0);
    $qty = array_map('intval', (array) ($_POST['qty'] ?? []));
    if (!$ids) {
        setFlash('error', 'Pilih minimal satu produk untuk dicetak barcode-nya.');
        header('Location: index.php');
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $koneksi->prepare("SELECT id, sku FROM products WHERE id IN ($placeholders) ORDER BY sku");
    $stmt->execute($ids);
    $skus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $plan = [];
    foreach ($ids as $id) {
        if (!isset($skus[$id])) continue;
        $n = max(1, min(50, $qty[$id] ?? 1));
        for ($i = 0; $i < $n; $i++) {
            $plan[] = ['sku' => $skus[$id], 'name' => $id]; // sku & id
        }
    }
    if (!$plan) {
        setFlash('error', 'Produk tidak ditemukan.');
        header('Location: index.php');
        exit;
    }
    $_SESSION['barcode_plan'] = $plan;
    header('Location: cetak.php');
    exit;
}

$products = $koneksi->query('SELECT * FROM products ORDER BY sku')->fetchAll();
$mode = (string) ($_GET['mode'] ?? 'pilih'); // pilih | per-sku
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h(csrfToken()) ?>">
    <title>Barcode Produk — TokoKita</title>
    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
    <link rel="stylesheet" href="barcode.css">
</head>
<body>
<div class="d-flex flex-lg-row flex-column">
    <aside class="admin-sidebar">
        <a class="brand" href="../index.php"><span class="mark">🛍️</span><span>Toko<span>Kita</span></span></a>
        <div class="nav-caption">MENU</div>
        <a class="side-link" href="../index.php"><span class="ico">▦</span>Aplikasi Kasir</a>
        <?php if (isAdmin()): ?><a class="side-link" href="../admin/"><span class="ico">⚙</span>Panel Admin</a><?php endif; ?>
        <a class="side-link" href="../laporan/"><span class="ico">↗</span>Laporan</a>
        <a class="side-link active" href="index.php"><span class="ico">▮</span>Barcode Produk</a>
        <div class="sidebar-foot">
            <strong><?= h(currentUser()['nama']) ?></strong>
            <small><?= h(ucfirst(currentUser()['role'])) ?></small>
        </div>
    </aside>
    <main class="admin-main flex-grow-1">
        <div class="page-head">
            <div>
                <div class="eyebrow">LABEL PRODUK</div>
                <h1>Barcode Produk</h1>
                <p>Barcode Code 128 dari kode SKU — siap ditempel di rak atau kemasan.</p>
            </div>
            <div class="btn-group">
                <a class="btn <?= $mode === 'pilih' ? 'btn-brand' : 'btn-ghost-soft' ?>" href="index.php">Centang manual</a>
                <a class="btn <?= $mode === 'per-sku' ? 'btn-brand' : 'btn-ghost-soft' ?>" href="index.php?mode=per-sku">1 label tiap produk</a>
            </div>
        </div>

        <?= flashHtml(takeFlash()) ?>

        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="card-soft p-4">
                    <h6 class="fw-bold mb-3">Contoh tampilan barcode</h6>
                    <div class="sample-box">
                        <?php $first = $products[0] ?? null; ?>
                        <?php if ($first): ?>
                            <div class="sample-label">
                                <strong><?= h($first['name']) ?></strong>
                                <div class="sample-barcode"><?= code128_svg($first['sku'], 2, 44) ?></div>
                                <span class="sample-price"><?= money($first['price']) ?></span>
                            </div>
                        <?php else: ?>
                            <p class="text-secondary mb-0">Belum ada produk. Tambahkan produk dulu di panel admin.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card-soft p-4 h-100">
                    <h6 class="fw-bold mb-2">Cara pakai</h6>
                    <ol class="small text-secondary mb-0 ps-3">
                        <li>Pilih produk &amp; jumlah label yang ingin dicetak.</li>
                        <li>Tekan tombol <strong>Cetak label terpilih</strong>.</li>
                        <li>Pada halaman hasil, tekan tombol cetak printer (atau Ctrl+P).</li>
                        <li>Gunakan kertas label 50×30 mm untuk hasil terbaik.</li>
                    </ol>
                </div>
            </div>
        </div>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <div class="card-soft p-0 overflow-hidden mb-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th style="width:52px"><?= $mode === 'per-sku' ? '' : 'PILIH' ?></th>
                            <th>PRODUK</th>
                            <th>SKU</th>
                            <th>HARGA</th>
                            <th style="width:150px">JUMLAH LABEL</th>
                            <th>PRATINJAU</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <?php if ($mode !== 'per-sku'): ?>
                                        <input class="form-check-input" type="checkbox" name="ids[]" value="<?= (int) $p['id'] ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="ids[]" value="<?= (int) $p['id'] ?>">
                                        <span class="pill">✓</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="avatar-chip"><?= h($p['emoji']) ?><?= fotoImg($p) ?></span> <strong class="ms-1"><?= h($p['name']) ?></strong></td>
                                <td><code><?= h($p['sku']) ?></code></td>
                                <td class="fw-semibold"><?= money($p['price']) ?></td>
                                <td>
                                    <input class="form-control form-control-sm" type="number" name="qty[<?= (int) $p['id'] ?>]"
                                           value="<?= $mode === 'per-sku' ? 1 : 1 ?>" min="1" max="50">
                                </td>
                                <td><div class="mini-barcode"><?= code128_svg($p['sku'], 1, 30, false) ?></div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!$products): ?>
                    <div class="p-5 text-center text-secondary">Belum ada produk di database.</div>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-brand" type="submit" <?= $products ? '' : 'disabled' ?>>🖨️ Cetak label terpilih →</button>
                <a class="btn btn-ghost-soft" href="../index.php">Kembali ke aplikasi</a>
            </div>
        </form>
    </main>
</div>
<script src="../bootstrap/bootstrap.bundle.min.js"></script>
<script src="../admin/assets/admin.js"></script>
</body>
</html>
