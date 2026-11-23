# Panduan Project Makanan (CRUD)

## Lokasi Folder
`E:\xampp\htdocs\data_mahasiswa\project_makanan\`

## Struktur File
| File        | Fungsi                          |
|-------------|---------------------------------|
| config.php  | Koneksi ke database `cart_db`   |
| index.php   | Halaman utama CRUD makanan      |

## Struktur Database
- Database: `cart_db`
- Tabel: `products`

| Field | Type        | Keterangan           |
|-------|-------------|----------------------|
| id    | int (PK,AI) | Primary key          |
| nama  | varchar(100)| Nama makanan         |
| harga | varchar(100)| Harga makanan        |
| foto  | varchar(100)| URL/alamat gambar    |

## Cara Menjalankan
1. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Buka browser: **http://localhost/data_mahasiswa/project_makanan/**

## Alur Aplikasi
1. **Create** — Isi form (Nama, Harga, Foto URL) → klik *Simpan Data*.
2. **Read** — Semua produk tampil di tabel beserta thumbnail foto dan harga.
3. **Update** — Klik *Edit* pada baris data → form terisi otomatis → ubah → *Simpan Data*.
4. **Delete** — Klik *Delete* → konfirmasi → data terhapus.
