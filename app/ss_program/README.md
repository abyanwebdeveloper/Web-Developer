# ss_program — Skrip Program Tambahan

Folder berisi skrip bantu (otomasi) untuk aplikasi **Absensi SDN Tulungrejo 01**.

## Isi folder

| File | Fungsi |
|------|--------|
| `generate_absensi.php` | Membuat absensi "Hadir" otomatis untuk semua siswa aktif pada hari sekolah (Senin–Jumat) yang belum punya data absensi. |
| `backup_db.php` | Backup database `petugas_piket` ke folder `database/backups/`. |

## Cara menjalankan (CLI)

```bat
cd E:\xampp\htdocs\app
E:\xampp\php\php.exe ss_program\generate_absensi.php
E:\xampp\php\php.exe ss_program\generate_absensi.php 2026-10-13
E:\xampp\php\php.exe ss_program\backup_db.php
```

## Cara menjalankan (Browser)

Buka (setelah login **admin**):

- `http://localhost/app/ss_program/generate_absensi.php`
- `http://localhost/app/ss_program/backup_db.php`

> script ini menolak akses (403) jika belum login atau bukan admin.

> Catatan: baris absensi yang sudah ada **tidak ditimpa**, jadi skrip ini aman
> dijalankan berulang (idempoten).
