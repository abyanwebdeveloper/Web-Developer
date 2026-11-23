# Aplikasi Absensi SDN Tulungrejo 01

Aplikasi absensi siswa & jadwal petugas piket guru berbasis PHP (mysqli) + MySQL
untuk XAMPP.

## Struktur Folder

```
app/
├── index.php          Dashboard (statistik kehadiran, piket hari ini)
├── login.php          Halaman login
├── logout.php         Keluar
├── absensi.php        Input absensi harian per rombel
├── rekap.php          Rekap kehadiran + cetak laporan
├── siswa.php          Data siswa (CRUD)
├── rombel.php         Data rombel/kelas (admin)
├── users.php          Manajemen pengguna (admin)
├── pengaturan.php     Profil sekolah & tahun pelajaran (admin)
├── assets/            CSS & JS
├── config/            koneksi database
├── database/          petugas_piket.sql (+ backups/)
├── library/           fungsi bantu, auth, layout header/footer
├── piket/             Modul jadwal piket guru
└── ss_program/        Skrip otomasi (generate absensi, backup DB)
```

## Instalasi

1. Jalankan **Apache** & **MySQL** dari XAMPP Control Panel.
2. Buka phpMyAdmin → Import → pilih `database/petugas_piket.sql`
   (atau lewat terminal: `E:\xampp\mysql\bin\mysql.exe -u root < database\petugas_piket.sql`).
3. Buka `http://localhost/app/` di browser.

## Akun Default

| Peran  | Username | Password  |
|--------|----------|-----------|
| Admin  | `admin`  | `admin123`|
| Guru   | `guru1` … `guru6` | `guru123` |

> Segera ganti password setelah login (menu Pengguna, khusus admin).

## Hak Akses

- **Admin**: semua menu, termasuk data rombel, pengguna, dan pengaturan.
- **Guru/Petugas**: dashboard, input absensi, rekap, data siswa (lihat), jadwal piket.

## Alur Kerja Harian

1. Guru/petugas piket login.
2. Menu **Input Absensi** → pilih tanggal & rombel → isi status (H/I/S/A) → Simpan.
3. Menu **Rekap Kehadiran** → pilih rombel & bulan → **Cetak Laporan** (ada tanda tangan).
4. Absensi "Hadir" massal bisa dibuat otomatis lewat `ss_program/generate_absensi.php`.
5. Backup rutin: `ss_program/backup_db.php`.

## Keamanan

- Password di-hash dengan `password_hash()` (bcrypt).
- Prepared statements pada semua query (anti SQL injection).
- Escape output `htmlspecialchars` (anti XSS).
- Token CSRF pada semua form.
