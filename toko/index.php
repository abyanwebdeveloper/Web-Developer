<?php
session_start();
require __DIR__ . '/template/config.php';
require __DIR__ . '/template/auth.php';
requireLogin('login/');

function icon(string $name, int $size = 20): string {
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'box' => '<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="M3 7v10l9 5 9-5V7M12 12v10"/>',
        'cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 2h2l2.4 12.4a2 2 0 0 0 2 1.6H20a2 2 0 0 0 2-1.6L23 6H4"/>',
        'history' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'chart' => '<path d="M3 3v18h18M7 16l4-5 4 3 5-7"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'edit' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L9 17l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2m3 0-1 15H6L5 6m5 4v7m4-7v7"/>',
        'receipt' => '<path d="M4 3h16v18l-4-2-4 2-4-2-4 2V3Z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18"/>',
        'wallet' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18m-5 5h5"/>',
        'warning' => '<path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v4m0 4h.01"/>',
        'check' => '<path d="m4 12 5 5L20 6"/>',
        'close' => '<path d="M5 5 19 19M19 5 5 19"/>',
        'print' => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/>',
        'bag' => '<path d="M4 8h16l-1 13H5L4 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
        'trend' => '<path d="m3 17 7-7 4 4 7-7M15 7h6v6"/>',
        'filter' => '<path d="M4 7h16M7 12h10m-7 5h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 4 5 5-5 5m5-5H9"/>',
    ];
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['box']) . '</svg>';
}
function redirectWith(string $page, string $type, string $message): never {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: index.php?page=' . rawurlencode($page));
    exit;
}

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $isSale = $action === 'sale_create';
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        if ($isSale) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'message'=>'Sesi berakhir. Muat ulang halaman dan coba lagi.']); exit; }
        redirectWith('products', 'error', 'Sesi berakhir. Muat ulang halaman dan coba lagi.');
    }
    try {
        if ($action === 'product_save') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: null;
            $name = trim((string) ($_POST['name'] ?? ''));
            $sku = strtoupper(trim((string) ($_POST['sku'] ?? '')));
            $category = trim((string) ($_POST['category'] ?? ''));
            $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
            $cost = filter_var($_POST['cost'] ?? null, FILTER_VALIDATE_FLOAT);
            $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
            $minStock = filter_var($_POST['min_stock'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
            $emoji = trim((string) ($_POST['emoji'] ?? '📦'));
            $image = trim((string) ($_POST['image'] ?? ''));
            if ($name === '' || mb_strlen($name) > 160 || $sku === '' || mb_strlen($sku) > 50 || $category === '' || mb_strlen($category) > 80 ||
                $price === false || $price < 0 || $price > 9999999999 || $cost === false || $cost < 0 || $cost > 9999999999 ||
                $stock === false || $minStock === false || $stock > 1000000 || $minStock > 1000000 || mb_strlen($emoji) > 20 || mb_strlen($image) > 255) {
                redirectWith('products', 'error', 'Data produk belum lengkap atau tidak valid.');
            }
            $emoji = $emoji ?: '📦';
            $params = [$name, $sku, $category, $price, $cost, $stock, $minStock, $emoji, $image];
            if ($id) {
                $stmt = $koneksi->prepare('UPDATE products SET name=?, sku=?, category=?, price=?, cost=?, stock=?, min_stock=?, emoji=?, image=? WHERE id=?');
                $stmt->execute([...$params, $id]);
                redirectWith('products', 'success', 'Produk berhasil diperbarui.');
            }
            $stmt = $koneksi->prepare('INSERT INTO products (name, sku, category, price, cost, stock, min_stock, emoji, image) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute($params);
            redirectWith('products', 'success', 'Produk baru berhasil ditambahkan.');
        }
        if ($action === 'product_delete') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if (!$id) redirectWith('products', 'error', 'Produk tidak ditemukan.');
            $stmt = $koneksi->prepare('DELETE FROM products WHERE id=?');
            $stmt->execute([$id]);
            redirectWith('products', 'success', 'Produk berhasil dihapus. Riwayat penjualan tetap tersimpan.');
        }
        if ($isSale) {
            header('Content-Type: application/json; charset=utf-8');
            $items = json_decode((string) ($_POST['items'] ?? ''), true);
            $customer = trim((string) ($_POST['customer'] ?? ''));
            $customer = $customer === '' ? 'Pelanggan umum' : $customer;
            $method = (string) ($_POST['payment_method'] ?? '');
            $paidInput = filter_var($_POST['paid'] ?? null, FILTER_VALIDATE_FLOAT);
            if (!is_array($items) || count($items) < 1 || count($items) > 100 || mb_strlen($customer) > 120 || !in_array($method, ['Tunai','QRIS','Transfer'], true)) {
                throw new InvalidArgumentException('Periksa kembali keranjang dan informasi pembayaran.');
            }
            $normalized = [];
            foreach ($items as $item) {
                if (!is_array($item)) throw new InvalidArgumentException('Keranjang tidak valid.');
                $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                $qty = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1, 'max_range'=>10000]]);
                if (!$id || !$qty || isset($normalized[$id])) throw new InvalidArgumentException('Jumlah produk tidak valid.');
                $normalized[$id] = $qty;
            }
            $koneksi->beginTransaction();
            $lines = []; $total = 0;
            $productStmt = $koneksi->prepare('SELECT id, name, price, cost, stock FROM products WHERE id=? FOR UPDATE');
            foreach ($normalized as $id => $qty) {
                $productStmt->execute([$id]);
                $product = $productStmt->fetch();
                if (!$product) throw new InvalidArgumentException('Ada produk yang sudah tidak tersedia. Muat ulang halaman.');
                if ((int) $product['stock'] < $qty) throw new InvalidArgumentException('Stok ' . $product['name'] . ' tidak mencukupi. Tersisa ' . $product['stock'] . '.');
                $subtotal = round((float) $product['price'] * $qty, 2);
                $total += $subtotal;
                $lines[] = ['product'=>$product, 'qty'=>$qty, 'subtotal'=>$subtotal];
            }
            $total = round($total, 2);
            if ($method === 'Tunai' && ($paidInput === false || $paidInput < $total || $paidInput > 9999999999.99)) {
                throw new InvalidArgumentException('Jumlah uang diterima harus cukup untuk membayar total belanja.');
            }
            $paid = $method === 'Tunai' ? round($paidInput, 2) : $total;
            $invoice = 'TRX-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $koneksi->prepare('INSERT INTO sales (invoice, customer, payment_method, total, paid, change_amount) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$invoice, $customer, $method, $total, $paid, round($paid - $total, 2)]);
            $saleId = (int) $koneksi->lastInsertId();
            $itemStmt = $koneksi->prepare('INSERT INTO sale_items (sale_id, product_id, product_name, price, cost, quantity, subtotal) VALUES (?,?,?,?,?,?,?)');
            $stockStmt = $koneksi->prepare('UPDATE products SET stock=stock-? WHERE id=?');
            foreach ($lines as $line) {
                $p = $line['product'];
                $itemStmt->execute([$saleId, $p['id'], $p['name'], $p['price'], $p['cost'], $line['qty'], $line['subtotal']]);
                $stockStmt->execute([$line['qty'], $p['id']]);
            }
            $koneksi->commit();
            echo json_encode(['ok'=>true, 'url'=>'index.php?page=receipt&id=' . $saleId]);
            exit;
        }
        redirectWith('dashboard', 'error', 'Permintaan tidak dikenali.');
    } catch (Throwable $error) {
        if ($koneksi->inTransaction()) $koneksi->rollBack();
        error_log('Toko action: ' . $error->getMessage());
        $message = $error instanceof InvalidArgumentException ? $error->getMessage() :
            (($error instanceof PDOException && $error->getCode() === '23000') ? 'Kode SKU sudah dipakai. Gunakan kode lain.' : 'Terjadi kesalahan. Silakan coba lagi.');
        if ($isSale) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>$message]); exit; }
        redirectWith('products', 'error', $message);
    }
}

$page = (string) ($_GET['page'] ?? 'dashboard');
if (!in_array($page, ['dashboard','products','pos','history','reports','receipt'], true)) $page = 'dashboard';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$navItems = [
    'dashboard' => ['Dashboard','dashboard'], 'products' => ['Produk','box'], 'pos' => ['Kasir','cart'],
    'history' => ['Riwayat Transaksi','history'], 'reports' => ['Laporan','chart'],
];
$products = [];
if (in_array($page, ['dashboard','products','pos'], true)) $products = $koneksi->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
if ($page === 'dashboard') {
    $today = $koneksi->query("SELECT COUNT(*) AS orders_count, COALESCE(SUM(total),0) AS revenue FROM sales WHERE created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY")->fetch();
    $monthRevenue = $koneksi->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
    $recentSales = $koneksi->query('SELECT * FROM sales ORDER BY id DESC LIMIT 5')->fetchAll();
    $days = []; $maxChart = 0;
    $dailyRows = $koneksi->query('SELECT DATE(created_at) AS day, SUM(total) AS amount FROM sales WHERE created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(created_at)')->fetchAll(PDO::FETCH_KEY_PAIR);
    for ($i=6; $i>=0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days")); $amount = (float) ($dailyRows[$day] ?? 0);
        $days[] = ['label'=>date('D', strtotime($day)), 'date'=>$day, 'amount'=>$amount];
        $maxChart = max($maxChart, $amount);
    }
    $lowProducts = array_values(array_filter($products, fn($p) => (int) $p['stock'] <= (int) $p['min_stock']));
    usort($lowProducts, fn($a,$b) => (int) $a['stock'] <=> (int) $b['stock']);
}
if ($page === 'products' || $page === 'pos') {
    $categories = array_values(array_unique(array_column($products, 'category')));
    sort($categories, SORT_NATURAL | SORT_FLAG_CASE);
}
if ($page === 'history') {
    $q = trim((string) ($_GET['q'] ?? ''));
    $filterDate = (string) ($_GET['date'] ?? '');
    if (!isValidDate($filterDate)) $filterDate = '';
    $where = []; $params = [];
    if ($q !== '') { $where[] = '(s.invoice LIKE ? OR s.customer LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
    if ($filterDate !== '') { $where[] = 'DATE(s.created_at)=?'; $params[] = $filterDate; }
    $sql = 'SELECT s.*, (SELECT SUM(quantity) FROM sale_items WHERE sale_id=s.id) AS item_count FROM sales s' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY s.id DESC LIMIT 100';
    $stmt = $koneksi->prepare($sql); $stmt->execute($params); $sales = $stmt->fetchAll();
}
if ($page === 'reports') {
    $from = (string) ($_GET['from'] ?? date('Y-m-01'));
    $to = (string) ($_GET['to'] ?? date('Y-m-d'));
    if (!isValidDate($from)) $from = date('Y-m-01');
    if (!isValidDate($to)) $to = date('Y-m-d');
    if ($from > $to) [$from, $to] = [$to, $from];
    $range = [$from, $to];
    $stmt = $koneksi->prepare('SELECT COUNT(*) AS transactions, COALESCE(SUM(total),0) AS revenue FROM sales WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)');
    $stmt->execute($range); $report = $stmt->fetch();
    $stmt = $koneksi->prepare('SELECT COALESCE(SUM(i.quantity),0) AS units, COALESCE(SUM((i.price-i.cost)*i.quantity),0) AS profit FROM sale_items i JOIN sales s ON s.id=i.sale_id WHERE s.created_at >= ? AND s.created_at < DATE_ADD(?, INTERVAL 1 DAY)');
    $stmt->execute($range); $report += $stmt->fetch();
    $stmt = $koneksi->prepare('SELECT DATE(created_at) AS day, COUNT(*) AS orders_count, SUM(total) AS amount FROM sales WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY) GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 31');
    $stmt->execute($range); $dailyReport = $stmt->fetchAll();
    $stmt = $koneksi->prepare('SELECT i.product_name, SUM(i.quantity) AS units, SUM(i.subtotal) AS amount FROM sale_items i JOIN sales s ON s.id=i.sale_id WHERE s.created_at >= ? AND s.created_at < DATE_ADD(?, INTERVAL 1 DAY) GROUP BY i.product_name ORDER BY units DESC LIMIT 5');
    $stmt->execute($range); $topProducts = $stmt->fetchAll();
}
if ($page === 'receipt') {
    $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    $stmt = $koneksi->prepare('SELECT * FROM sales WHERE id=?'); $stmt->execute([$id ?: 0]);
    $sale = $stmt->fetch();
    if (!$sale) { http_response_code(404); $page = 'notfound'; }
    else { $stmt = $koneksi->prepare('SELECT * FROM sale_items WHERE sale_id=? ORDER BY id'); $stmt->execute([$id]); $saleItems = $stmt->fetchAll(); }
}
$pageTitle = $navItems[$page][0] ?? ($page === 'receipt' ? 'Detail Struk' : 'Tidak ditemukan');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#103b34">
    <meta name="csrf-token" content="<?= h($csrf) ?>">
    <meta name="app-base" content="<?= h(appBase()) ?>">
    <title><?= h($pageTitle) ?> — TokoKita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=2">
    <script src="assets/app.js?v=2" defer></script>
</head>
<body data-page="<?= h($page) ?>">
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php"><span class="brand-mark"><?= icon('bag', 23) ?></span><span>Toko<span class="brand-accent">Kita</span><small>STORE MANAGEMENT</small></span></a>
        <div class="nav-caption">MENU UTAMA</div>
        <nav class="side-nav" aria-label="Menu utama">
            <?php foreach ($navItems as $key => [$label,$glyph]): ?>
                <a class="nav-link <?= $page === $key ? 'active' : '' ?>" href="index.php?page=<?= h($key) ?>"><?= icon($glyph, 19) ?><span><?= h($label) ?></span><?php if ($key === 'pos'): ?><span class="nav-dot"></span><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom"><div class="help-card"><div class="help-icon">✦</div><strong>Semua jadi lebih mudah.</strong><p>Kelola tokomu dalam satu tempat, kapan saja.</p><a href="index.php?page=pos">Mulai transaksi <?= icon('arrow', 15) ?></a><div class="help-links"><a href="barcode/">Cetak barcode</a><a href="laporan/">Laporan penjualan</a><?php if (isAdmin()): ?><a href="admin/">Panel admin</a><?php endif; ?></div></div><div class="sidebar-profile"><span class="avatar"><?= h(strtoupper(mb_substr(currentUser()['nama'], 0, 2))) ?></span><span><strong><?= h(currentUser()['nama']) ?></strong><small><?= h(ucfirst(currentUser()['role'])) ?> · Pengelola toko</small></span></div></div>
    </aside>
    <div class="main-area">
        <header class="topbar"><div class="topbar-left"><span class="breadcrumb">Workspace <span>/</span> <strong><?= h($pageTitle) ?></strong></span></div><div class="topbar-right"><span class="today-label"><?= icon('calendar', 17) ?> <?= h(indoDate(date('Y-m-d'))) ?></span><span class="avatar top-avatar"><?= h(strtoupper(mb_substr(currentUser()['nama'], 0, 2))) ?></span></div></header>
        <main class="content">
        <?php if ($flash): ?><div class="flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>" role="alert"><?= icon($flash['type'] === 'success' ? 'check' : 'warning', 19) ?> <?= h($flash['message']) ?><button type="button" aria-label="Tutup" onclick="this.parentElement.remove()"><?= icon('close', 16) ?></button></div><?php endif; ?>

        <?php if ($page === 'dashboard'): ?>
            <div class="page-heading"><div><div class="eyebrow"><span class="live-dot"></span> RINGKASAN TOKO</div><h1>Selamat datang kembali! <span class="wave">👋</span></h1><p>Ini yang terjadi di tokomu hari ini, <?= h(indoDate(date('Y-m-d'))) ?>.</p></div><a class="btn btn-primary" href="index.php?page=pos"><?= icon('plus', 18) ?> Transaksi baru</a></div>
            <section class="welcome-banner"><div class="banner-content"><span class="banner-kicker">✦ BISNIS LEBIH PRAKTIS</span><h2>Kelola toko lebih mudah,<br>jualan jadi lebih lancar.</h2><p>Catat penjualan, pantau stok, dan lihat perkembangan toko — semua di satu tempat.</p><a href="index.php?page=pos">Buka kasir <?= icon('arrow', 17) ?></a></div><div class="banner-art" aria-hidden="true"><div class="art-circle art-circle-one"></div><div class="art-circle art-circle-two"></div><div class="art-bag">🛍️</div><div class="art-card art-card-top">↗ <span>Penjualan naik</span></div><div class="art-card art-card-bottom">✨ <span>Semua terpantau</span></div></div></section>
            <section class="stats-grid" aria-label="Statistik toko">
                <div class="stat-card"><div class="stat-top"><span class="stat-icon green"><?= icon('wallet', 21) ?></span><span class="stat-tag">Hari ini</span></div><span class="stat-label">Penjualan hari ini</span><strong><?= money($today['revenue']) ?></strong><span class="stat-foot">Total pemasukan hari ini</span></div>
                <div class="stat-card"><div class="stat-top"><span class="stat-icon blue"><?= icon('receipt', 21) ?></span><span class="stat-tag">Hari ini</span></div><span class="stat-label">Transaksi hari ini</span><strong><?= number_format((int)$today['orders_count'],0,',','.') ?></strong><span class="stat-foot">Transaksi berhasil dicatat</span></div>
                <div class="stat-card"><div class="stat-top"><span class="stat-icon purple"><?= icon('box', 21) ?></span><span class="stat-tag">Aktif</span></div><span class="stat-label">Total produk</span><strong><?= count($products) ?></strong><span class="stat-foot">Produk di katalog toko</span></div>
                <div class="stat-card"><div class="stat-top"><span class="stat-icon orange"><?= icon('warning', 21) ?></span><span class="stat-tag <?= count($lowProducts) ? 'tag-alert' : '' ?>">Perlu dicek</span></div><span class="stat-label">Stok menipis</span><strong><?= count($lowProducts) ?></strong><span class="stat-foot">Produk perlu ditambah stok</span></div>
            </section>
            <div class="dashboard-grid"><section class="panel chart-panel"><div class="panel-head"><div><h2>Ringkasan penjualan</h2><p>Performa tokomu selama 7 hari terakhir</p></div><span class="panel-badge"><?= icon('chart', 15) ?> 7 hari</span></div><div class="chart-summary"><strong><?= money($monthRevenue) ?></strong><span>Total penjualan bulan ini</span></div><div class="bar-chart"><?php foreach ($days as $day): ?><div class="bar-col" title="<?= h(indoDate($day['date']) . ': ' . money($day['amount'])) ?>"><div class="bar-track"><div class="bar-fill <?= $day['date'] === date('Y-m-d') ? 'current' : '' ?>" style="height:<?= $maxChart > 0 ? max(5, round($day['amount'] / $maxChart * 100)) : 5 ?>%"></div></div><span><?= h(['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'][$day['label']]) ?></span></div><?php endforeach; ?></div></section>
            <section class="panel quick-panel"><div class="panel-head"><div><h2>Akses cepat</h2><p>Yang sering kamu butuhkan</p></div></div><a class="quick-link" href="index.php?page=pos"><span class="quick-icon green"><?= icon('cart', 21) ?></span><span><strong>Buat transaksi</strong><small>Catat penjualan baru</small></span><?= icon('arrow', 18) ?></a><a class="quick-link" href="index.php?page=products"><span class="quick-icon blue"><?= icon('plus', 21) ?></span><span><strong>Kelola produk</strong><small>Tambah atau ubah stok</small></span><?= icon('arrow', 18) ?></a><a class="quick-link" href="index.php?page=reports"><span class="quick-icon purple"><?= icon('chart', 21) ?></span><span><strong>Lihat laporan</strong><small>Pantau hasil penjualan</small></span><?= icon('arrow', 18) ?></a></section></div>
            <div class="dashboard-grid lower-grid"><section class="panel"><div class="panel-head"><div><h2>Transaksi terbaru</h2><p>Aktivitas penjualan terkini</p></div><a class="text-link" href="index.php?page=history">Lihat semua <?= icon('arrow', 15) ?></a></div><?php if (!$recentSales): ?><div class="empty-small">Belum ada transaksi. <a href="index.php?page=pos">Mulai jualan sekarang</a></div><?php else: ?><div class="simple-list"><?php foreach ($recentSales as $row): ?><a class="list-row" href="index.php?page=receipt&id=<?= (int)$row['id'] ?>"><span class="list-icon"><?= icon('receipt', 19) ?></span><span class="list-main"><strong><?= h($row['invoice']) ?></strong><small><?= h($row['customer']) ?> · <?= h(indoDate($row['created_at'],true)) ?></small></span><strong class="list-amount"><?= money($row['total']) ?></strong></a><?php endforeach; ?></div><?php endif; ?></section>
            <section class="panel"><div class="panel-head"><div><h2>Perlu restok</h2><p>Produk dengan stok rendah</p></div><a class="text-link" href="index.php?page=products">Kelola <?= icon('arrow', 15) ?></a></div><?php if (!$lowProducts): ?><div class="empty-small">Mantap! Semua stok produk masih aman. ✨</div><?php else: ?><div class="simple-list"><?php foreach (array_slice($lowProducts,0,5) as $row): ?><div class="list-row"><span class="product-emoji mini-thumb"><?= h($row['emoji']) ?><?= fotoImg($row) ?></span><span class="list-main"><strong><?= h($row['name']) ?></strong><small><?= h($row['category']) ?></small></span><span class="stock-pill stock-low"><?= (int)$row['stock'] ?> tersisa</span></div><?php endforeach; ?></div><?php endif; ?></section></div>

        <?php elseif ($page === 'products'): ?>
            <div class="page-heading"><div><div class="eyebrow">KATALOG TOKO</div><h1>Kelola Produk</h1><p>Atur produk, harga, dan stok dalam satu tempat.</p></div><button class="btn btn-primary" type="button" id="addProduct"><?= icon('plus',18) ?> Tambah produk</button></div>
            <div class="summary-strip"><span class="summary-icon"><?= icon('box',20) ?></span><div><strong><?= count($products) ?> produk aktif</strong><span>Katalog yang siap dijual di tokomu</span></div><span class="summary-extra">Manajemen inventaris <?= icon('arrow',15) ?></span></div>
            <section class="panel table-panel"><div class="panel-head product-panel-head"><div><h2>Daftar produk</h2><p>Semua barang di tokomu</p></div><div class="search-field"><?= icon('search',18) ?><input id="productSearch" type="search" placeholder="Cari nama atau SKU..." aria-label="Cari produk"></div></div><div class="category-tabs" id="productCategories"><button type="button" class="category-tab active" data-category="all">Semua <span><?= count($products) ?></span></button><?php foreach ($categories as $cat): ?><button type="button" class="category-tab" data-category="<?= h($cat) ?>"><?= h($cat) ?></button><?php endforeach; ?></div><div class="table-wrap"><table class="data-table"><thead><tr><th>PRODUK</th><th>KATEGORI</th><th>HARGA JUAL</th><th>STOK</th><th class="align-right">AKSI</th></tr></thead><tbody id="productRows"><?php foreach ($products as $p): ?><tr data-search="<?= h(mb_strtolower($p['name'].' '.$p['sku'].' '.$p['category'])) ?>" data-category="<?= h($p['category']) ?>"><td><div class="table-product"><span class="product-emoji"><?= h($p['emoji']) ?><?= fotoImg($p) ?></span><span><strong><?= h($p['name']) ?></strong><small><?= h($p['sku']) ?></small></span></div></td><td><span class="category-label"><?= h($p['category']) ?></span></td><td><strong class="price-cell"><?= money($p['price']) ?></strong></td><td><span class="stock-pill <?= (int)$p['stock'] <= (int)$p['min_stock'] ? 'stock-low' : 'stock-ok' ?>"><?= (int)$p['stock'] <= (int)$p['min_stock'] ? '● ' : '' ?><?= (int)$p['stock'] ?> stok</span></td><td><div class="row-actions"><button type="button" class="icon-button edit-product" data-product="<?= h(json_encode($p, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>" title="Edit produk" aria-label="Edit <?= h($p['name']) ?>"><?= icon('edit',17) ?></button><form method="post" class="delete-form"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="product_delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="icon-button danger" type="submit" title="Hapus produk" aria-label="Hapus <?= h($p['name']) ?>"><?= icon('trash',17) ?></button></form></div></td></tr><?php endforeach; ?></tbody></table></div><div id="productEmpty" class="empty-small" <?= $products ? 'hidden' : '' ?>>Tidak ada produk yang cocok. Tambahkan produk atau coba kata kunci lain.</div><div class="table-footer">Menampilkan <span id="visibleCount"><?= count($products) ?></span> dari <?= count($products) ?> produk</div></section>
            <dialog id="productDialog" class="modal"><div class="modal-head"><div><span class="eyebrow">INVENTARIS</span><h2 id="modalTitle">Tambah produk baru</h2><p>Isi informasi produk untuk katalog tokomu.</p></div><button type="button" class="icon-button modal-close" aria-label="Tutup"><?= icon('close',20) ?></button></div><form method="post" id="productForm"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="product_save"><input type="hidden" name="id" value=""><div class="form-grid"><label class="field full">Nama produk <input name="name" maxlength="160" required placeholder="Contoh: Beras Premium 5 kg"></label><label class="field">Kode SKU <input name="sku" maxlength="50" required placeholder="Contoh: SMB-001"></label><label class="field">Kategori <input name="category" list="categoryOptions" maxlength="80" required placeholder="Contoh: Sembako"><datalist id="categoryOptions"><?php foreach ($categories as $cat): ?><option value="<?= h($cat) ?>"><?php endforeach; ?></datalist></label><label class="field">Harga jual (Rp) <input name="price" type="number" min="0" max="9999999999" step="0.01" required placeholder="0"></label><label class="field">Harga modal (Rp) <input name="cost" type="number" min="0" max="9999999999" step="0.01" required placeholder="0"></label><label class="field">Jumlah stok <input name="stock" type="number" min="0" max="1000000" step="1" required placeholder="0"></label><label class="field">Batas stok minimum <input name="min_stock" type="number" min="0" max="1000000" step="1" value="5" required></label><label class="field full">Emoji produk <input name="emoji" maxlength="20" value="📦" placeholder="📦"><small>Emoji cadangan bila produk belum punya foto.</small></label><label class="field full">Foto produk <input name="image" maxlength="255" placeholder="assets/produk/beras.jpg"><small>Alamat file foto di dalam folder <strong>assets/produk/</strong> (atau URL gambar). Kosongkan untuk memakai emoji.</small></label></div><div class="modal-actions"><button type="button" class="btn btn-light modal-close">Batal</button><button type="submit" class="btn btn-primary">Simpan produk <?= icon('arrow',17) ?></button></div></form></dialog>

        <?php elseif ($page === 'pos'): ?>
            <div class="page-heading"><div><div class="eyebrow"><span class="live-dot"></span> POINT OF SALE</div><h1>Kasir</h1><p>Pilih produk, buat transaksi, dan lanjutkan jualan.</p></div><span class="date-chip"><?= icon('calendar',17) ?> <?= h(indoDate(date('Y-m-d'))) ?></span></div>
            <div class="pos-layout"><section class="pos-catalog"><div class="catalog-toolbar"><div class="search-field pos-search"><?= icon('search',19) ?><input id="posSearch" type="search" placeholder="Cari produk atau kode SKU..." aria-label="Cari produk"></div><span id="posCount"><?= count($products) ?> produk tersedia</span></div><div class="pos-categories" id="posCategories"><button type="button" class="category-tab active" data-category="all">Semua produk</button><?php foreach ($categories as $cat): ?><button type="button" class="category-tab" data-category="<?= h($cat) ?>"><?= h($cat) ?></button><?php endforeach; ?></div><div class="section-line"><h2>Pilih produk</h2><span>Klik produk untuk menambah ke keranjang</span></div><div class="pos-products" id="posProducts"><?php foreach ($products as $p): ?><button type="button" class="pos-product <?= (int)$p['stock'] === 0 ? 'sold-out' : '' ?>" data-search="<?= h(mb_strtolower($p['name'].' '.$p['sku'].' '.$p['category'])) ?>" data-category="<?= h($p['category']) ?>" data-product="<?= h(json_encode($p, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>" <?= (int)$p['stock'] === 0 ? 'disabled' : '' ?>><span class="pos-product-art art-<?= h(['Sembako'=>'amber','Minuman'=>'mint','Makanan'=>'peach'][$p['category']] ?? 'lavender') ?>"><span><?= h($p['emoji']) ?></span><?= fotoImg($p) ?></span><span class="pos-product-info"><span class="pos-category"><?= h($p['category']) ?></span><strong><?= h($p['name']) ?></strong><span class="pos-product-bottom"><b><?= money($p['price']) ?></b><small><?= (int)$p['stock'] === 0 ? 'Habis' : 'Stok: '.(int)$p['stock'] ?></small></span></span><span class="add-bubble"><?= icon('plus',17) ?></span></button><?php endforeach; ?></div><div class="empty-small" id="posEmpty" <?= $products ? 'hidden' : '' ?>>Produk tidak ditemukan. Coba cari nama lain atau tambahkan produk.</div></section><aside class="checkout-panel"><div class="checkout-header"><div><h2>Keranjang <span id="cartBadge">0</span></h2><p>Daftar belanja pelanggan</p></div><span class="checkout-icon"><?= icon('bag',21) ?></span></div><div id="cartItems" class="cart-items"><div class="empty-cart"><span>🛒</span><strong>Keranjang masih kosong</strong><p>Pilih produk di sebelah kiri untuk memulai transaksi.</p></div></div><div class="checkout-bottom"><label class="field">Nama pelanggan <input id="customerInput" maxlength="120" placeholder="Pelanggan umum (opsional)"></label><label class="field">Metode pembayaran <select id="paymentMethod"><option value="Tunai">Tunai</option><option value="QRIS">QRIS</option><option value="Transfer">Transfer</option></select></label><div class="totals-row"><span>Subtotal <small id="cartQty">(0 item)</small></span><strong id="subtotal">Rp 0</strong></div><div class="totals-row total-final"><span>Total bayar</span><strong id="cartTotal">Rp 0</strong></div><label class="field" id="paidField">Uang diterima (Rp) <input id="paidInput" type="number" min="0" step="0.01" placeholder="Masukkan jumlah uang"></label><div class="totals-row change-row"><span>Kembalian</span><strong id="changeAmount">Rp 0</strong></div><div id="checkoutError" class="inline-error" role="alert" hidden></div><button type="button" class="btn btn-primary checkout-button" id="checkoutBtn" disabled>Proses pembayaran <?= icon('arrow',18) ?></button><p class="checkout-note">🔒 Transaksi tersimpan otomatis dan stok langsung diperbarui.</p></div></aside></div>

        <?php elseif ($page === 'history'): ?>
            <div class="page-heading"><div><div class="eyebrow">CATATAN PENJUALAN</div><h1>Riwayat Transaksi</h1><p>Semua transaksi yang pernah terjadi di tokomu.</p></div><a class="btn btn-primary" href="index.php?page=pos"><?= icon('plus',18) ?> Transaksi baru</a></div><section class="panel table-panel"><div class="panel-head history-head"><div><h2>Daftar transaksi</h2><p>Menampilkan maksimal 100 transaksi terbaru</p></div><form method="get" class="history-filters"><input type="hidden" name="page" value="history"><div class="search-field"><?= icon('search',17) ?><input name="q" value="<?= h($q) ?>" placeholder="Cari no. transaksi / pelanggan" aria-label="Cari transaksi"></div><input type="date" name="date" value="<?= h($filterDate) ?>" aria-label="Filter tanggal"><button class="btn btn-light" type="submit"><?= icon('filter',16) ?> Filter</button></form></div><div class="table-wrap"><table class="data-table"><thead><tr><th>NO. TRANSAKSI</th><th>PELANGGAN</th><th>TANGGAL</th><th>ITEM</th><th>PEMBAYARAN</th><th>TOTAL</th><th class="align-right">STRUK</th></tr></thead><tbody><?php foreach ($sales as $row): ?><tr><td><strong class="invoice-text"><?= h($row['invoice']) ?></strong></td><td><?= h($row['customer']) ?></td><td class="muted-cell"><?= h(indoDate($row['created_at'],true)) ?></td><td><?= (int)$row['item_count'] ?> item</td><td><span class="payment-pill"><?= h($row['payment_method']) ?></span></td><td><strong class="price-cell"><?= money($row['total']) ?></strong></td><td class="align-right"><a class="view-link" href="index.php?page=receipt&id=<?= (int)$row['id'] ?>">Lihat <?= icon('arrow',15) ?></a></td></tr><?php endforeach; ?></tbody></table></div><?php if (!$sales): ?><div class="empty-state"><span>🧾</span><strong>Belum ada transaksi</strong><p><?= ($q || $filterDate) ? 'Tidak ada transaksi yang cocok dengan filter ini.' : 'Transaksi pertamamu akan muncul di sini.' ?></p><a class="btn btn-primary" href="index.php?page=pos">Buka kasir</a></div><?php endif; ?><div class="table-footer">Menampilkan <?= count($sales) ?> transaksi</div></section>

        <?php elseif ($page === 'reports'): ?>
            <div class="page-heading"><div><div class="eyebrow">ANALISIS TOKO</div><h1>Laporan Penjualan</h1><p>Pantau performa dan perkembangan bisnismu.</p></div></div><form method="get" class="report-filter"><input type="hidden" name="page" value="reports"><span><?= icon('calendar',19) ?> Pilih periode</span><label>Dari <input type="date" name="from" value="<?= h($from) ?>" required></label><label>Sampai <input type="date" name="to" value="<?= h($to) ?>" required></label><button type="submit" class="btn btn-primary">Tampilkan <?= icon('arrow',17) ?></button></form><div class="period-label">Laporan periode <strong><?= h(indoDate($from)) ?> – <?= h(indoDate($to)) ?></strong></div><section class="stats-grid report-stats"><div class="stat-card"><div class="stat-top"><span class="stat-icon green"><?= icon('wallet',21) ?></span></div><span class="stat-label">Total omzet</span><strong><?= money($report['revenue']) ?></strong><span class="stat-foot">Penjualan dalam periode ini</span></div><div class="stat-card"><div class="stat-top"><span class="stat-icon blue"><?= icon('receipt',21) ?></span></div><span class="stat-label">Total transaksi</span><strong><?= number_format((int)$report['transactions'],0,',','.') ?></strong><span class="stat-foot">Transaksi berhasil</span></div><div class="stat-card"><div class="stat-top"><span class="stat-icon purple"><?= icon('bag',21) ?></span></div><span class="stat-label">Produk terjual</span><strong><?= number_format((int)$report['units'],0,',','.') ?></strong><span class="stat-foot">Total unit terjual</span></div><div class="stat-card"><div class="stat-top"><span class="stat-icon orange"><?= icon('trend',21) ?></span></div><span class="stat-label">Laba kotor</span><strong><?= money($report['profit']) ?></strong><span class="stat-foot">Omzet dikurangi modal barang</span></div></section><div class="dashboard-grid report-grid"><section class="panel"><div class="panel-head"><div><h2>Penjualan per hari</h2><p>Rincian transaksi harian dalam periode ini</p></div></div><div class="table-wrap"><table class="data-table compact-table"><thead><tr><th>TANGGAL</th><th>TRANSAKSI</th><th class="align-right">TOTAL PENJUALAN</th></tr></thead><tbody><?php foreach ($dailyReport as $row): ?><tr><td><strong><?= h(indoDate($row['day'])) ?></strong></td><td><?= (int)$row['orders_count'] ?> transaksi</td><td class="align-right"><strong class="price-cell"><?= money($row['amount']) ?></strong></td></tr><?php endforeach; ?></tbody></table></div><?php if (!$dailyReport): ?><div class="empty-small">Belum ada penjualan di periode ini.</div><?php endif; ?></section><section class="panel"><div class="panel-head"><div><h2>Produk terlaris</h2><p>Berdasarkan jumlah unit terjual</p></div></div><?php if (!$topProducts): ?><div class="empty-small">Produk terlaris akan tampil setelah ada transaksi.</div><?php else: ?><div class="top-list"><?php foreach ($topProducts as $index => $p): ?><div class="top-row"><span class="rank-number"><?= $index+1 ?></span><span class="list-main"><strong><?= h($p['product_name']) ?></strong><small><?= (int)$p['units'] ?> unit terjual</small></span><strong class="list-amount"><?= money($p['amount']) ?></strong></div><?php endforeach; ?></div><?php endif; ?></section></div>

        <?php elseif ($page === 'receipt'): ?>
            <div class="page-heading receipt-heading"><div><div class="eyebrow">TRANSAKSI BERHASIL</div><h1>Detail Struk</h1><p>Transaksi <?= h($sale['invoice']) ?> berhasil dicatat.</p></div><div class="heading-actions"><a href="index.php?page=history" class="btn btn-light">Kembali ke riwayat</a><button type="button" class="btn btn-primary" onclick="window.print()"><?= icon('print',18) ?> Cetak struk</button></div></div><div class="receipt-wrap"><div class="receipt-paper"><div class="receipt-brand"><span class="receipt-logo"><?= icon('bag',25) ?></span><h2>TokoKita</h2><p>Terima kasih sudah berbelanja!</p></div><div class="receipt-divider"></div><div class="receipt-meta"><span>No. Transaksi <strong><?= h($sale['invoice']) ?></strong></span><span>Tanggal <strong><?= h(indoDate($sale['created_at'],true)) ?></strong></span><span>Pelanggan <strong><?= h($sale['customer']) ?></strong></span><span>Metode bayar <strong><?= h($sale['payment_method']) ?></strong></span></div><div class="receipt-divider"></div><div class="receipt-items"><div class="receipt-table-head"><span>PRODUK</span><span>JUMLAH</span></div><?php foreach ($saleItems as $item): ?><div class="receipt-item"><div><strong><?= h($item['product_name']) ?></strong><small><?= (int)$item['quantity'] ?> × <?= money($item['price']) ?></small></div><strong><?= money($item['subtotal']) ?></strong></div><?php endforeach; ?></div><div class="receipt-divider"></div><div class="receipt-total"><span>Total bayar</span><strong><?= money($sale['total']) ?></strong></div><div class="receipt-meta receipt-payment"><span>Uang diterima <strong><?= money($sale['paid']) ?></strong></span><span>Kembalian <strong><?= money($sale['change_amount']) ?></strong></span></div><div class="receipt-divider"></div><div class="receipt-thanks">✦<br><strong>Terima kasih!</strong><span>Sampai jumpa kembali di TokoKita.</span></div></div><div class="receipt-aside"><div class="success-icon"><?= icon('check',30) ?></div><h2>Pembayaran selesai!</h2><p>Transaksi sudah tersimpan dan stok produk otomatis diperbarui.</p><a href="index.php?page=pos" class="btn btn-primary">Buat transaksi lagi <?= icon('arrow',17) ?></a><a href="index.php?page=history" class="text-link">Lihat semua transaksi <?= icon('arrow',15) ?></a></div></div>

        <?php else: ?><div class="empty-state"><h1>Transaksi tidak ditemukan</h1><a class="btn btn-primary" href="index.php?page=history">Kembali ke riwayat</a></div><?php endif; ?>
        </main><footer class="site-footer"><span>© <?= date('Y') ?> TokoKita. Dibuat untuk memudahkan bisnismu.</span><span>Made with ♥ for local business</span></footer>
    </div>
</div>
</body>
</html>
