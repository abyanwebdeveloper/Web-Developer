<?php
require __DIR__ . '/../template/config.php';
require __DIR__ . '/../template/auth.php';
requireRole('admin');

/** Bangun dump SQL sederhana (schema + data) dari tabel yang dipilih. */
function buildSqlDump(PDO $koneksi, array $tables): string
{
    $sql = "-- Backup database toko — dibuat " . date('Y-m-d H:i:s') . "\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $table) {
        $create = $koneksi->query("SHOW CREATE TABLE `$table`")->fetch();
        $sql .= "DROP TABLE IF EXISTS `$table`;\n" . $create['Create Table'] . ";\n\n";

        $rows = $koneksi->query("SELECT * FROM `$table`")->fetchAll();
        if (!$rows) continue;
        $columns = array_keys($rows[0]);
        $colList = '`' . implode('`, `', $columns) . '`';
        $sql .= "INSERT INTO `$table` ($colList) VALUES\n";
        $lines = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($row as $value) {
                $values[] = $value === null ? 'NULL' : $koneksi->quote((string) $value);
            }
            $lines[] = '(' . implode(', ', $values) . ')';
        }
        $sql .= implode(",\n", $lines) . ";\n\n";
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

$tables = $koneksi->query("SHOW TABLES FROM `toko`")->fetchAll(PDO::FETCH_COLUMN);
$stats = [];
foreach ($tables as $table) {
    $stats[$table] = (int) $koneksi->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $message = 'Sesi berakhir. Muat ulang halaman lalu coba lagi.';
    } else {
        $format = (string) ($_POST['format'] ?? 'sql');
        $target = (string) ($_POST['tabel'] ?? 'toko');
        $allowed = array_merge(['toko'], $tables);
        if (!in_array($target, $allowed, true)) $target = 'toko';
        $pick = $target === 'toko' ? $tables : [$target];
        $stamp = date('Ymd-His');

        if ($format === 'sql') {
            $dump = buildSqlDump($koneksi, $pick);
            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="backup-toko-' . $stamp . '.sql"');
            echo $dump;
            exit;
        }
        if ($format === 'json') {
            $payload = [];
            foreach ($pick as $table) {
                $payload[$table] = $koneksi->query("SELECT * FROM `$table`")->fetchAll();
            }
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="backup-toko-' . $stamp . '.json"');
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
        if ($format === 'csv') {
            $table = $target === 'toko' ? 'products' : $target;
            $rows = $koneksi->query("SELECT * FROM `$table`")->fetchAll();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $table . '-' . $stamp . '.csv"');
            $out = fopen('php://output', 'w');
            if ($rows) {
                fputcsv($out, array_keys($rows[0]), ';');
                foreach ($rows as $row) fputcsv($out, $row, ';');
            }
            fclose($out);
            exit;
        }
        $message = 'Format tidak dikenal.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data &amp; Backup — Admin TokoKita</title>
    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
</head>
<body>
<div class="d-flex flex-lg-row flex-column">
    <aside class="admin-sidebar">
        <a class="brand" href="../admin/index.php"><span class="mark">🛍️</span><span>Toko<span>Kita</span></span></a>
        <div class="nav-caption">MENU ADMIN</div>
        <a class="side-link" href="../admin/index.php"><span class="ico">▦</span>Dasbor</a>
        <a class="side-link" href="../admin/produk.php"><span class="ico">◫</span>Kelola Produk</a>
        <a class="side-link" href="../admin/transaksi.php"><span class="ico">↺</span>Transaksi</a>
        <a class="side-link" href="../admin/pengguna.php"><span class="ico">☺</span>Pengguna</a>
        <a class="side-link active" href="index.php"><span class="ico">⤓</span>Data &amp; Backup</a>
        <a class="side-link" href="../laporan/"><span class="ico">↗</span>Laporan</a>
        <a class="side-link" href="../barcode/"><span class="ico">▮</span>Barcode Produk</a>
        <div class="sidebar-foot">
            <strong><?= h(currentUser()['nama']) ?></strong>
            <small><?= h(ucfirst(currentUser()['role'])) ?></small>
        </div>
    </aside>
    <main class="admin-main flex-grow-1">
        <div class="page-head">
            <div>
                <div class="eyebrow">CADANGAN DATABASE</div>
                <h1>Data &amp; Backup</h1>
                <p>Unduh cadangan database toko dalam format SQL, JSON, atau CSV.</p>
            </div>
        </div>

        <?php if ($message): ?><div class="alert alert-warning"><?= h($message) ?></div><?php endif; ?>

        <div class="row g-3 mb-4">
            <?php foreach ($stats as $table => $count): ?>
                <div class="col-6 col-lg-3">
                    <div class="stat-tile">
                        <span class="lbl">Tabel <?= h($table) ?></span>
                        <strong><?= number_format($count, 0, ',', '.') ?></strong>
                        <span class="foot">baris data tersimpan</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card-soft p-4 mb-4">
            <h6 class="fw-bold mb-3">Unduh cadangan</h6>
            <form method="post" class="row g-3 align-items-end">
                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Tabel</label>
                    <select class="form-select" name="tabel">
                        <option value="toko">Seluruh database (toko)</option>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?= h($table) ?>"><?= h($table) ?> (<?= number_format($stats[$table], 0, ',', '.') ?> baris)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Format</label>
                    <select class="form-select" name="format">
                        <option value="sql">SQL — bisa diimpor lagi lewat phpMyAdmin</option>
                        <option value="json">JSON — untuk keperluan program lain</option>
                        <option value="csv">CSV — buka di Excel (hanya tabel terpilih)</option>
                    </select>
                </div>
                <div class="col-md-4 d-grid">
                    <button class="btn btn-brand" type="submit">⤓ Unduh sekarang</button>
                </div>
            </form>
        </div>

        <div class="card-soft p-4">
            <h6 class="fw-bold mb-2">Mengimpor kembali (phpMyAdmin)</h6>
            <ol class="small text-secondary mb-0 ps-3">
                <li>Buka <code>http://localhost/phpmyadmin</code> di browser.</li>
                <li>Pilih database <strong>toko</strong> di panel kiri, lalu klik tab <strong>Import</strong>.</li>
                <li>Pilih berkas hasil unduhan tadi (format SQL) dan tekan <strong>Go</strong>.</li>
                <li>Berkas SQL backup sudah berisi perintah <code>DROP TABLE IF EXISTS</code> sehingga aman ditimpa.</li>
            </ol>
            <hr>
            <p class="small text-secondary mb-0">Skema kosong + contoh data juga tersedia di <code>template/toko.sql</code> dan <code>data/toko.sql</code>.</p>
        </div>

        <div class="admin-footer">© <?= date('Y') ?> TokoKita — Panel Admin</div>
    </main>
</div>
<script src="../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
