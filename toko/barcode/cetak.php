<?php
require __DIR__ . '/../template/config.php';
require __DIR__ . '/../template/auth.php';
requireLogin('../login/');
require __DIR__ . '/code128.php';

$plan = $_SESSION['barcode_plan'] ?? [];
if (!$plan) {
    setFlash('error', 'Belum ada label yang dipilih. Pilih produk dulu di halaman barcode.');
    header('Location: index.php');
    exit;
}

// Gabungkan rencana cetak: sku => [id, jumlah]
$grouped = [];
foreach ($plan as $entry) {
    $id = (int) $entry['name'];
    $grouped[$id]['sku'] = $entry['sku'];
    $grouped[$id]['qty'] = ($grouped[$id]['qty'] ?? 0) + 1;
}
$ids = array_keys($grouped);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $koneksi->prepare("SELECT id, name, sku, price, emoji FROM products WHERE id IN ($placeholders) ORDER BY sku");
$stmt->execute($ids);
$products = $stmt->fetchAll();
$totalLabels = array_sum(array_column($grouped, 'qty'));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Label Barcode — TokoKita</title>
    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="barcode.css">
</head>
<body class="print-body">
<div class="no-print toolbar">
    <div>
        <strong>Pratinjau label</strong>
        <span class="text-secondary"><?= count($products) ?> produk · <?= $totalLabels ?> label</span>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-brand" type="button" onclick="window.print()">🖨️ Cetak sekarang</button>
        <a class="btn btn-ghost-soft" href="index.php">← Kembali pilih produk</a>
    </div>
</div>

<div class="label-sheet">
    <?php foreach ($products as $p): ?>
        <?php for ($i = 0; $i < $grouped[(int) $p['id']]['qty']; $i++): ?>
            <div class="price-label">
                <div class="label-name"><?= h($p['emoji']) ?> <?= h($p['name']) ?></div>
                <div class="label-barcode"><?= code128_svg($p['sku'], 2, 42) ?></div>
                <div class="label-price"><?= money($p['price']) ?></div>
            </div>
        <?php endfor; ?>
    <?php endforeach; ?>
</div>

<script>
  // Cetak otomatis dimatikan; pengguna menekan tombol sendiri agar bisa atur printer.
</script>
</body>
</html>
