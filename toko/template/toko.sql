-- =====================================================================
--  Database TokoKita — impor manual lewat phpMyAdmin
--  Pilih database tujuan "toko" (atau biarkan, skema membuatnya sendiri),
--  lalu jalankan berkas ini. PERHATIAN: perintah DROP akan menghapus
--  tabel lama beserta isinya.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `toko` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `toko`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(80) NOT NULL,
    role ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    category VARCHAR(80) NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    min_stock INT UNSIGNED NOT NULL DEFAULT 5,
    emoji VARCHAR(20) NOT NULL DEFAULT '📦',
    image VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice VARCHAR(40) NOT NULL UNIQUE,
    customer VARCHAR(120) NOT NULL DEFAULT 'Pelanggan umum',
    payment_method VARCHAR(30) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    paid DECIMAL(12,2) NOT NULL,
    change_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sales_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(160) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    cost DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_items_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Akun pendukung (login sudah dihapus — tidak dipakai untuk masuk) -----
-- admin  (role admin)
-- kasir  (role kasir)
INSERT INTO users (username, password, nama, role, aktif) VALUES
('admin', '', 'Administrator Toko', 'admin', 1),
('kasir', '', 'Kasir Utama', 'kasir', 1);

-- Contoh produk -------------------------------------------------------
INSERT INTO products (name, sku, category, price, cost, stock, min_stock, emoji, image) VALUES
('Beras Premium 5 kg', 'SMB-001', 'Sembako', 78000, 67000, 32, 8, '🍚', 'assets/produk/beras.jpg'),
('Minyak Goreng 1 L', 'SMB-002', 'Sembako', 19000, 15500, 24, 8, '🫗', 'assets/produk/minyak.jpg'),
('Gula Pasir 1 kg', 'SMB-003', 'Sembako', 17500, 14000, 18, 6, '🧂', 'assets/produk/gula.jpg'),
('Telur Ayam 1 kg', 'SMB-004', 'Sembako', 29000, 25500, 16, 6, '🥚', 'assets/produk/telur.jpg'),
('Kopi Susu Botol', 'MNM-001', 'Minuman', 12000, 8000, 28, 8, '☕', 'assets/produk/kopi-susu.jpg'),
('Susu UHT Cokelat', 'MNM-002', 'Minuman', 8500, 6000, 36, 10, '🥛', 'assets/produk/susu-cokelat.jpg'),
('Roti Gandum', 'MKN-001', 'Makanan', 22000, 17000, 12, 5, '🍞', 'assets/produk/roti.jpg'),
('Mie Instan Goreng', 'MKN-002', 'Makanan', 3500, 2700, 48, 12, '🍜', 'assets/produk/mie-instan.jpg');

-- Contoh transaksi 7 hari terakhir (agar dashboard/laporan langsung terisi)
INSERT INTO sales (id, invoice, customer, payment_method, total, paid, change_amount, created_at) VALUES
(1, 'TRX-SEED-0001', 'Ibu Sari',    'Tunai',    175000, 200000, 25000, NOW() - INTERVAL 5 DAY),
(2, 'TRX-SEED-0002', 'Budi Santoso','QRIS',      58000,  58000,     0, NOW() - INTERVAL 3 DAY),
(3, 'TRX-SEED-0003', 'Pelanggan umum','Tunai',    46500,  50000,  3500, NOW() - INTERVAL 2 DAY),
(4, 'TRX-SEED-0004', 'Ani Wijaya',  'Transfer',   34500,  34500,     0, NOW() - INTERVAL 1 DAY),
(5, 'TRX-SEED-0005', 'Andi',        'Tunai',     102000, 110000,  8000, NOW() - INTERVAL 3 HOUR);

INSERT INTO sale_items (sale_id, product_id, product_name, price, cost, quantity, subtotal) VALUES
(1, 1, 'Beras Premium 5 kg', 78000, 67000, 2, 156000),
(1, 2, 'Minyak Goreng 1 L',  19000, 15500, 1,  19000),
(2, 5, 'Kopi Susu Botol',    12000,  8000, 3,  36000),
(2, 7, 'Roti Gandum',        22000, 17000, 1,  22000),
(3, 8, 'Mie Instan Goreng',   3500,  2700, 5,  17500),
(3, 4, 'Telur Ayam 1 kg',    29000, 25500, 1,  29000),
(4, 6, 'Susu UHT Cokelat',    8500,  6000, 2,  17000),
(4, 3, 'Gula Pasir 1 kg',    17500, 14000, 1,  17500),
(5, 1, 'Beras Premium 5 kg', 78000, 67000, 1,  78000),
(5, 5, 'Kopi Susu Botol',    12000,  8000, 2,  24000);
