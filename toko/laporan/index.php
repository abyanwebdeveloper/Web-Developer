<?php
require __DIR__ . '/../template/config.php';
require __DIR__ . '/../template/auth.php';
requireLogin('../login/');

$from = (string) ($_GET['from'] ?? date('Y-m-01'));
$to = (string) ($_GET['to'] ?? date('Y-m-d'));
if (!isValidDate($from)) $from = date('Y-m-01');
if (!isValidDate($to)) $to = date('Y-m-d');
if ($from > $to) [$from, $to] = [$to, $from];
$range = [$from, $to];

// Periode cepat: 7 hari, bulan ini, bulan lalu, tahun ini.
$quick = (string) ($_GET['periode'] ?? '');
if ($quick === '7hari') { $from = date('Y-m-d', strtotime('-6 days')); $to = date('Y-m-d'); }
elseif ($quick === 'bulan-ini') { $from = date('Y-m-01'); $to = date('Y-m-d'); }
elseif ($quick === 'bulan-lalu') { $from = date('Y-m-01', strtotime('first day of last month')); $to = date('Y-m-t', strtotime('first day of last month')); }
elseif ($quick === 'tahun-ini') { $from = date('Y-01-01'); $to = date('Y-m-d'); }
if ($from > $to) [$from, $to] = [$to, $from];
$range = [$from, $to];

$stmt = $koneksi->prepare('SELECT COUNT(*) AS transactions, COALESCE(SUM(total),0) AS revenue FROM sales WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)');
$stmt->execute($range);
$report = $stmt->fetch();

$stmt = $koneksi->prepare('SELECT COALESCE(SUM(i.quantity),0) AS units, COALESCE(SUM((i.price - i.cost) * i.quantity),0) AS profit, COALESCE(SUM(i.cost * i.quantity),0) AS modal FROM sale_items i JOIN sales s ON s.id = i.sale_id WHERE s.created_at >= ? AND s.created_at < DATE_ADD(?, INTERVAL 1 DAY)');
$stmt->execute($range);
$report += $stmt->fetch();

$stmt = $koneksi->prepare('SELECT DATE(created_at) AS day, COUNT(*) AS orders_count, SUM(total) AS amount FROM sales WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY) GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 62');
$stmt->execute($range);
$daily = $stmt->fetchAll();

$stmt = $koneksi->prepare('SELECT i.product_name, SUM(i.quantity) AS units, SUM(i.subtotal) AS amount, SUM((i.price - i.cost) * i.quantity) AS profit FROM sale_items i JOIN sales s ON s.id = i.sale_id WHERE s.created_at >= ? AND s.created_at < DATE_ADD(?, INTERVAL 1 DAY) GROUP BY i.product_name ORDER BY units DESC LIMIT 10');
$stmt->execute($range);
$topProducts = $stmt->fetchAll();

$avg = (int) $report['transactions'] > 0 ? $report['revenue'] / $report['transactions'] : 0;
$margin = $report['revenue'] > 0 ? ($report['profit'] / $report['revenue']) * 100 : 0;
$print = isset($_GET['cetak']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penjualan — TokoKita</title>
    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
    <link rel="stylesheet" href="laporan.css">
</head>
<body>
<?php if (!$print): ?>
<div class="d-flex flex-lg-row flex-column">
    <aside class="admin-sidebar no-print">
        <a class="brand" href="../index.php"><span class="mark">🛍️</span><span>Toko<span>Kita</span></span></a>
        <div class="nav-caption">MENU</div>
        <a class="side-link" href="../index.php"><span class="ico">▦</span>Aplikasi Kasir</a>
        <?php if (isAdmin()): ?><a class="side-link" href="../admin/"><span class="ico">⚙</span>Panel Admin</a><?php endif; ?>
        <a class="side-link active" href="index.php"><span class="ico">↗</span>Laporan</a>
        <a class="side-link" href="../barcode/"><span class="ico">▮</span>Barcode Produk</a>
        <div class="sidebar-foot">
            <strong><?= h(currentUser()['nama']) ?></strong>
            <small><?= h(ucfirst(currentUser()['role'])) ?></small>
        </div>
    </aside>
    <main class="admin-main flex-grow-1">
        <div class="page-head">
            <div>
                <div class="eyebrow">ANALISIS TOKO</div>
                <h1>Laporan Penjualan</h1>
                <p>Omzet, laba, dan produk terlaris berdasarkan periode yang dipilih.</p>
            </div>
            <a class="btn btn-brand" href="?from=<?= h($from) ?>&amp;to=<?= h($to) ?>&amp;cetak=1" target="_blank">🖨️ Cetak laporan</a>
        </div>

        <div class="card-soft p-4 mb-4">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Periode cepat</label>
                    <select class="form-select" name="periode" onchange="this.form.submit()">
                        <option value="">— pilih —</option>
                        <option value="7hari" <?= $quick === '7hari' ? 'selected' : '' ?>>7 hari terakhir</option>
                        <option value="bulan-ini" <?= $quick === 'bulan-ini' ? 'selected' : '' ?>>Bulan ini</option>
                        <option value="bulan-lalu" <?= $quick === 'bulan-lalu' ? 'selected' : '' ?>>Bulan lalu</option>
                        <option value="tahun-ini" <?= $quick === 'tahun-ini' ? 'selected' : '' ?>>Tahun ini</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Dari tanggal</label>
                    <input class="form-control" type="date" name="from" value="<?= h($from) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Sampai tanggal</label>
                    <input class="form-control" type="date" name="to" value="<?= h($to) ?>" required>
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-ghost-soft" type="submit">Tampilkan laporan</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="<?= $print ? 'print-sheet' : 'container-fluid px-lg-4 pb-4' ?>">
    <?php if (!$print): ?>
        <div class="period-label no-print mb-3">Laporan periode <strong><?= h(indoDate($from)) ?> – <?= h(indoDate($to)) ?></strong></div>
    <?php else: ?>
        <div class="print-head">
            <h2>TokoKita — Laporan Penjualan</h2>
            <p>Periode <?= h(indoDate($from)) ?> – <?= h(indoDate($to)) ?> · Dicetak <?= h(indoDate(date('Y-m-d'), true)) ?> oleh <?= h(currentUser()['nama']) ?></p>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="stat-tile"><span class="lbl">Total omzet</span><strong><?= money($report['revenue']) ?></strong><span class="foot">Penjualan kotor periode ini</span></div></div>
        <div class="col-6 col-lg-3"><div class="stat-tile"><span class="lbl">Jumlah transaksi</span><strong><?= number_format((int) $report['transactions'], 0, ',', '.') ?></strong><span class="foot">Rata-rata <?= money($avg) ?>/transaksi</span></div></div>
        <div class="col-6 col-lg-3"><div class="stat-tile"><span class="lbl">Produk terjual</span><strong><?= number_format((int) $report['units'], 0, ',', '.') ?></strong><span class="foot">Total unit pada periode ini</span></div></div>
        <div class="col-6 col-lg-3"><div class="stat-tile"><span class="lbl">Laba kotor</span><strong><?= money($report['profit']) ?></strong><span class="foot">Margin <?= number_format((float) $margin, 1, ',', '.') ?>% dari omzet</span></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card-soft p-4 h-100">
                <h6 class="fw-bold mb-3">Penjualan per hari</h6>
                <?php if (!$daily): ?>
                    <p class="text-secondary mb-0">Tidak ada penjualan pada periode ini.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 laporan-table">
                            <thead><tr><th>TANGGAL</th><th class="text-center">TRANSAKSI</th><th class="text-end">OMZET</th></tr></thead>
                            <tbody>
                            <?php foreach ($daily as $row): ?>
                                <tr>
                                    <td><?= h(indoDate($row['day'])) ?></td>
                                    <td class="text-center"><?= number_format((int) $row['orders_count'], 0, ',', '.') ?></td>
                                    <td class="text-end fw-bold"><?= money($row['amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                            <tr><td class="fw-bold">TOTAL</td><td class="text-center fw-bold"><?= number_format((int) $report['transactions'], 0, ',', '.') ?></td><td class="text-end fw-bold"><?= money($report['revenue']) ?></td></tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card-soft p-4 h-100">
                <h6 class="fw-bold mb-3">Produk terlaris</h6>
                <?php if (!$topProducts): ?>
                    <p class="text-secondary mb-0">Belum ada data produk terjual.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 laporan-table">
                            <thead><tr><th>PRODUK</th><th class="text-center">UNIT</th><th class="text-end">OMZET</th><th class="text-end">LABA</th></tr></thead>
                            <tbody>
                            <?php foreach ($topProducts as $row): ?>
                                <tr>
                                    <td><?= h($row['product_name']) ?></td>
                                    <td class="text-center"><?= number_format((int) $row['units'], 0, ',', '.') ?></td>
                                    <td class="text-end"><?= money($row['amount']) ?></td>
                                    <td class="text-end fw-bold"><?= money($row['profit']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between small text-secondary">
                    <span>Modal barang terjual</span><strong class="text-dark"><?= money($report['modal']) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <?php if ($print): ?>
        <div class="print-sign">
            <div>Dibuat oleh,</div>
            <div class="sign-name"><?= h(currentUser()['nama']) ?></div>
        </div>
    <?php endif; ?>
</div>

<?php if (!$print): ?>
    </main>
</div>
<script src="../bootstrap/bootstrap.bundle.min.js"></script>
<?php endif; ?>
</body>
</html>
