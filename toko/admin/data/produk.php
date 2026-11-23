<?php
/**
 * GET admin/data/produk.php?q=&kategori=
 * Daftar produk sebagai JSON (untuk pencarian AJAX & pemilih produk).
 */
require __DIR__ . '/../../template/config.php';
require __DIR__ . '/../../template/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Belum masuk.']);
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
$kategori = trim((string) ($_GET['kategori'] ?? ''));
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR sku LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($kategori !== '') {
    $where[] = 'category = ?';
    $params[] = $kategori;
}
$sql = 'SELECT id, name, sku, category, price, cost, stock, min_stock, emoji, image FROM products'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY name LIMIT 200';
$stmt = $koneksi->prepare($sql);
$stmt->execute($params);

// Tambahkan URL foto siap pakai supaya halaman pemanggil tidak perlu menghitung sendiri.
$produk = $stmt->fetchAll();
foreach ($produk as &$p) {
    $p['image_url'] = fotoUrl((string) ($p['image'] ?? ''));
}
unset($p);

echo json_encode(['ok' => true, 'data' => $produk], JSON_UNESCAPED_UNICODE);
