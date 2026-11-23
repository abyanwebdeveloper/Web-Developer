<?php
require __DIR__ . '/../template/config.php';
require __DIR__ . '/../template/auth.php';
requireRole('admin');

$today = $koneksi->query("SELECT COUNT(*) AS orders_count, COALESCE(SUM(total),0) AS revenue FROM sales WHERE created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY")->fetch();
$monthRevenue = $koneksi->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
$totals = $koneksi->query('SELECT (SELECT COUNT(*) FROM products) AS products, (SELECT COUNT(*) FROM sales) AS sales, (SELECT COUNT(*) FROM users) AS users')->fetch();
$lowStock = $koneksi->query('SELECT COUNT(*) FROM products WHERE stock <= min_stock')->fetchColumn();
$recentSales = $koneksi->query('SELECT * FROM sales ORDER BY id DESC LIMIT 6')->fetchAll();
$bestSellers = $koneksi->query('SELECT product_name, SUM(quantity) AS units, SUM(subtotal) AS amount FROM sale_items GROUP BY product_name ORDER BY units DESC LIMIT 5')->fetchAll();

$pageTitle = 'Dasbor';
$pageKey = 'dashboard';
require __DIR__ . '/_layout_top.php';
?>
    <div class="page-head">
        <div>
            <div class="eyebrow">RINGKASAN ADMIN</div>
            <h1>Halo, <?= h(currentUser()['nama']) ?>! 👋</h1>
            <p>Ini gambaran tokomu hari ini, <?= h(indoDate(date('Y-m-d'))) ?>.</p>
        </div>
        <a class="btn btn-brand" href="produk.php">＋ Tambah / kelola produk</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="stat-tile"><span class="lbl">Penjualan hari ini</span><strong><?= money($today['revenue']) ?></strong><span class="foot">Total pemasukan hari ini</span></div></div>
        <div class="col-6 col-lg-3"><div class="stat-tile"><span class="lbl">Transaksi hari ini</span><strong><?= number_format((int) $today['orders_count'], 0, ',', '.') ?></strong><span class="foot">Transaksi berhasil dicatat</span></div></div>
        <div class="col-6 col-lg-3"><div class="stat-tile"><span class="lbl">Omzet bulan ini</span><strong><?= money($monthRevenue) ?></strong><span class="foot">Sejak awal bulan berjalan</span></div></div>
        <div class="col-6 col-lg-3"><div class="stat-tile"><span class="lbl">Stok menipis</span><strong><?= number_format((int) $lowStock, 0, ',', '.') ?></strong><span class="foot">Produk perlu direstok</span></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card-soft p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div><h5 class="mb-0 fw-bold">Penjualan 7 hari terakhir</h5><small class="text-secondary">Kunjungi <a href="../laporan/">Laporan</a> untuk rincian per periode.</small></div>
                    <span class="pill">▦ Grafik harian</span>
                </div>
                <div class="bar-chart" data-chart>
                    <div class="text-secondary small w-100 text-center py-5">Memuat grafik…</div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card-soft p-4 h-100">
                <h5 class="fw-bold mb-3">Produk terlaris</h5>
                <?php if (!$bestSellers): ?>
                    <p class="text-secondary mb-0">Belum ada penjualan tercatat.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($bestSellers as $i => $row): ?>
                            <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
                                <div><strong><?= h($row['product_name']) ?></strong><br><small class="text-secondary"><?= (int) $row['units'] ?> unit terjual</small></div>
                                <span class="pill gold"><?= money($row['amount']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card-soft p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Transaksi terbaru</h5>
                    <a class="text-success fw-semibold small" href="transaksi.php">Lihat semua →</a>
                </div>
                <?php if (!$recentSales): ?>
                    <p class="text-secondary mb-0">Belum ada transaksi.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>INVOIS</th><th>PELANGGAN</th><th>PEMBAYARAN</th><th class="text-end">TOTAL</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentSales as $row): ?>
                                <tr>
                                    <td><strong><?= h($row['invoice']) ?></strong><br><small class="text-secondary"><?= h(indoDate($row['created_at'], true)) ?></small></td>
                                    <td><?= h($row['customer']) ?></td>
                                    <td><span class="pill"><?= h($row['payment_method']) ?></span></td>
                                    <td class="text-end fw-bold"><?= money($row['total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card-soft p-4 h-100">
                <h5 class="fw-bold mb-3">Status sistem</h5>
                <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Total produk</span><strong><?= number_format((int) $totals['products'], 0, ',', '.') ?></strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-secondary">Total transaksi</span><strong><?= number_format((int) $totals['sales'], 0, ',', '.') ?></strong></div>
                <div class="d-flex justify-content-between py-2"><span class="text-secondary">Pengguna terdaftar</span><strong><?= number_format((int) $totals['users'], 0, ',', '.') ?></strong></div>
                <hr>
                <div class="d-grid gap-2">
                    <a class="btn btn-ghost-soft" href="../barcode/">Cetak barcode produk</a>
                    <a class="btn btn-ghost-soft" href="../laporan/">Buka laporan penjualan</a>
                </div>
            </div>
        </div>
    </div>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
