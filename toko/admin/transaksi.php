<?php
require __DIR__ . '/../template/config.php';
require __DIR__ . '/../template/auth.php';
requireRole('admin');

$q = trim((string) ($_GET['q'] ?? ''));
$filterDate = (string) ($_GET['date'] ?? '');
if (!isValidDate($filterDate)) {
    $filterDate = '';
}
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(s.invoice LIKE ? OR s.customer LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($filterDate !== '') {
    $where[] = 'DATE(s.created_at) = ?';
    $params[] = $filterDate;
}
$sql = 'SELECT s.*, (SELECT SUM(quantity) FROM sale_items WHERE sale_id = s.id) AS item_count FROM sales s'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY s.id DESC LIMIT 200';
$stmt = $koneksi->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

$detailId = filter_var($_GET['detail'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$detailSale = null;
$detailItems = [];
if ($detailId) {
    $stmt = $koneksi->prepare('SELECT * FROM sales WHERE id = ?');
    $stmt->execute([$detailId]);
    $detailSale = $stmt->fetch();
    if ($detailSale) {
        $stmt = $koneksi->prepare('SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id');
        $stmt->execute([$detailId]);
        $detailItems = $stmt->fetchAll();
    }
}

$pageTitle = 'Transaksi';
$pageKey = 'transaksi';
require __DIR__ . '/_layout_top.php';
?>
    <div class="page-head">
        <div>
            <div class="eyebrow">RIWAYAT PENJUALAN</div>
            <h1>Transaksi</h1>
            <p>Menampilkan maksimal 200 transaksi terbaru beserta rinciannya.</p>
        </div>
        <a class="btn btn-brand" href="../index.php?page=pos">＋ Transaksi baru via kasir</a>
    </div>

    <div class="card-soft p-4 mb-4">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Cari</label>
                <input class="form-control" type="search" name="q" value="<?= h($q) ?>" placeholder="No. transaksi / pelanggan…">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Tanggal</label>
                <input class="form-control" type="date" name="date" value="<?= h($filterDate) ?>">
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-ghost-soft" type="submit">🔍 Filter</button>
            </div>
        </form>
    </div>

    <?php if ($detailSale): ?>
        <div class="card-soft p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div class="eyebrow small fw-bold text-success">RINCIAN TRANSAKSI</div>
                    <h5 class="fw-bold mb-1"><?= h($detailSale['invoice']) ?></h5>
                    <div class="text-secondary small"><?= h($detailSale['customer']) ?> · <?= h(indoDate($detailSale['created_at'], true)) ?> · <?= h($detailSale['payment_method']) ?></div>
                </div>
                <a class="btn btn-sm btn-ghost-soft" href="transaksi.php">✕ Tutup rincian</a>
            </div>
            <div class="table-responsive mt-3">
                <table class="table align-middle mb-0">
                    <thead><tr><th>PRODUK</th><th class="text-end">HARGA</th><th class="text-center">JUMLAH</th><th class="text-end">SUBTOTAL</th></tr></thead>
                    <tbody>
                    <?php foreach ($detailItems as $item): ?>
                        <tr>
                            <td><strong><?= h($item['product_name']) ?></strong></td>
                            <td class="text-end"><?= money($item['price']) ?></td>
                            <td class="text-center"><?= (int) $item['quantity'] ?></td>
                            <td class="text-end fw-bold"><?= money($item['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr><td colspan="3" class="text-end text-secondary">Total</td><td class="text-end fw-bold"><?= money($detailSale['total']) ?></td></tr>
                    <tr><td colspan="3" class="text-end text-secondary">Uang diterima</td><td class="text-end"><?= money($detailSale['paid']) ?></td></tr>
                    <tr><td colspan="3" class="text-end text-secondary">Kembalian</td><td class="text-end"><?= money($detailSale['change_amount']) ?></td></tr>
                    </tfoot>
                </table>
            </div>
            <a class="btn btn-sm btn-brand mt-3" href="../index.php?page=receipt&id=<?= (int) $detailSale['id'] ?>" target="_blank">🧾 Lihat / cetak struk</a>
        </div>
    <?php endif; ?>

    <div class="card-soft p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>NO. TRANSAKSI</th>
                    <th>PELANGGAN</th>
                    <th>TANGGAL</th>
                    <th>ITEM</th>
                    <th>PEMBAYARAN</th>
                    <th class="text-end">TOTAL</th>
                    <th class="text-end">AKSI</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sales as $row): ?>
                    <tr>
                        <td><strong><?= h($row['invoice']) ?></strong></td>
                        <td><?= h($row['customer']) ?></td>
                        <td class="text-secondary"><?= h(indoDate($row['created_at'], true)) ?></td>
                        <td><?= (int) $row['item_count'] ?> item</td>
                        <td><span class="pill"><?= h($row['payment_method']) ?></span></td>
                        <td class="text-end fw-bold"><?= money($row['total']) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-ghost-soft" href="transaksi.php?detail=<?= (int) $row['id'] ?>">Rincian</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!$sales): ?>
            <div class="p-5 text-center text-secondary">Tidak ada transaksi yang cocok dengan filter.</div>
        <?php endif; ?>
    </div>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
