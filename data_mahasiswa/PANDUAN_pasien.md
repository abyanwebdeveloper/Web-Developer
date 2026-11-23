# Panduan Aplikasi Data Pasien (CRUD)

## Lokasi
`E:\xampp\htdocs\data_mahasiswa\pasien\`

## File
| File       | Fungsi                     |
|------------|----------------------------|
| main.php   | Halaman utama CRUD pasien  |
| index.php  | Redirect ke main.php       |

## Database
- Database: `rumah_sakit_soedono`
- Tabel: `pasien` (`id_pasien`, `Nama`, `Jenis_kelamin`, `Tanggal_lahir`, `No.telepon`, `Alamat`, `Keluhan_pasien`)

## Cara Menjalankan
1. Nyalakan Apache & MySQL di XAMPP Control Panel.
2. Buka **http://localhost/data_mahasiswa/pasien/** (atau `.../pasien/main.php`).

## Alur
1. **Create** — isi form → Simpan Data
2. **Read** — data tampil di tabel
3. **Update** — klik Edit → form terisi → ubah → Simpan
4. **Delete** — klik Delete → konfirmasi → terhapus
