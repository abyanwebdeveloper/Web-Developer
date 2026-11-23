<?php
/**
 * GET admin/data/statistik.php?hari=7
 * Mengembalikan deret penjualan harian sebagai JSON (untuk grafik dasbor admin).
 */
require __DIR__ . '/../../template/config.php';
require __DIR__ . '/../../template/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Belum masuk.']);
    exit;
}

$days = filter_var($_GET['hari'] ?? 7, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 31]]) ?: 7;
$stmt = $koneksi->prepare('SELECT DATE(created_at) AS day, COUNT(*) AS orders_count, SUM(total) AS amount FROM sales WHERE created_at >= CURDATE() - INTERVAL ? DAY GROUP BY DATE(created_at)');
$stmt->execute([$days]);
$rows = $stmt->fetchAll();
$byDay = [];
foreach ($rows as $row) {
    $byDay[$row['day']] = $row;
}

$out = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $out[] = [
        'tanggal' => $day,
        'label' => date('d/m', strtotime($day)),
        'transaksi' => (int) ($byDay[$day]['orders_count'] ?? 0),
        'omzet' => (float) ($byDay[$day]['amount'] ?? 0),
    ];
}

echo json_encode(['ok' => true, 'hari' => $days, 'data' => $out], JSON_UNESCAPED_UNICODE);
