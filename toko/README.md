# TokoKita — aplikasi toko lokal

Aplikasi pengelolaan toko berbasis PHP 8.2 dan MySQL untuk XAMPP. Buka **http://localhost/toko/** setelah Apache dan MySQL dinyalakan di XAMPP.

## Fitur

- **Akses** — aplikasi langsung terbuka tanpa login (tanpa username, tanpa password).
- **Dashboard** — ringkasan omzet, transaksi, produk, stok menipis, dan penjualan 7 hari.
- **Produk** — tambah, edit, hapus, cari, filter kategori, harga modal/jual, batas minimum stok, dan foto produk.
- **Kasir** — keranjang belanja, pembayaran tunai/QRIS/transfer, hitung kembalian, dan pengurangan stok otomatis.
- **Riwayat** — pencarian transaksi dan struk yang dapat dicetak.
- **Panel Admin** — dasbor, kelola produk, transaksi, pengguna, dan backup data.
- **Barcode** — generator Code 128 (SVG) + cetak label produk.
- **Laporan** — omzet, jumlah transaksi/unit, laba kotor, rekap harian, dan produk terlaris berdasarkan rentang tanggal (ada mode cetak).

## Menjalankan

1. Simpan folder ini di `E:\xampp\htdocs\toko` (sudah dilakukan).
2. Nyalakan **Apache** dan **MySQL** pada XAMPP.
3. Kunjungi **http://localhost/toko/** di browser — aplikasi langsung terbuka, **tanpa halaman login**.

   Semua halaman (kasir, laporan, barcode, panel admin) bisa diakses tanpa username maupun password.

4. Database `toko` beserta tabel, akun pendukung, dan 8 produk contoh dibuat otomatis pada akses pertama. Data yang sudah ada tidak ditimpa pada akses berikutnya.

Konfigurasi bawaan MySQL: host `127.0.0.1`, port `3306`, user `root`, tanpa kata sandi. Jika berbeda, ubah `template/config.php`.

## Struktur folder

```
toko/
├── index.php          Aplikasi kasir & dashboard (wajib login)
├── login/             Sisa lama: redirect langsung ke aplikasi (login sudah dihapus)
├── admin/             Panel admin (dasbor, produk, transaksi, pengguna)
│   ├── assets/        CSS & JS panel admin
│   └── data/          API JSON (statistik, produk)
├── barcode/           Generator Code 128 + cetak label (code128.php)
├── bootstrap/         Bootstrap 5.3.3 lokal (CSS + JS)
├── data/              Backup & ekspor database (SQL/JSON/CSV)
├── laporan/           Laporan penjualan + mode cetak
├── assets/            CSS & JS aplikasi kasir
│   └── produk/        Foto barang (jpg) + CREDIT.txt (sumber & lisensi)
└── template/          config.php, auth.php, toko.sql (skema)
```

## Foto produk

- Foto barang disimpan di **`assets/produk/`** (misalnya `beras.jpg`, `telur.jpg`). Delapan produk contoh sudah berfoto.
- Path foto disimpan di kolom `image` tabel `products` (contoh: `assets/produk/beras.jpg`). Kolom ini ditambahkan otomatis oleh `template/config.php` untuk database lama.
- Menambah/mengganti foto: salin berkas gambar ke `assets/produk/`, lalu isi kolom **Foto produk** saat menambah/mengedit produk di halaman Produk atau Admin → Produk. Isi juga bisa berupa URL gambar (`https://...`).
- Kalau file foto tidak ada atau path salah, aplikasi otomatis menampilkan emoji produk sebagai pengganti.
- Sumber & lisensi foto contoh: lihat `assets/produk/CREDIT.txt` (Wikimedia Commons).

## Database & phpMyAdmin

- Skema + contoh data untuk impor manual: **`template/toko.sql`** (juga disalin ke `data/toko.sql`).
- Impor lewat phpMyAdmin: pilih database `toko` → tab **Import** → pilih berkas → **Go**. Perintah `DROP TABLE IF EXISTS` di awal berkas membuat impor ulang aman (menimpa tabel lama).
- Backup terjadwal/manual bisa dibuat dari menu **Admin → Data & Backup** (format SQL, JSON, atau CSV).
- Akun pada tabel `users` hanya data pendukung — login sudah dihapus, jadi username/password tidak dipakai lagi. Pengelolaan daftar akun tetap ada di **Admin → Pengguna**.

> Dipakai untuk admin **di localhost**. Jangan buka ke internet tanpa HTTPS dan konfigurasi keamanan server.
