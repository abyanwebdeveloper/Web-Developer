# Panduan Aplikasi Data Siswa (CRUD)

## Folder
`E:\xampp\htdocs\data_mahasiswa\siswa\`

## Struktur Database
- Database: `databasesiswa`
- Tabel: `tabelsiswa`

| Field | Type         | Keterangan              |
|-------|--------------|-------------------------|
| id    | int (PK, AI) | Primary key, auto inc   |
| nama  | varchar(10)  | Nama siswa              |
| kelas | varchar(3)   | Kelas siswa             |
| nohp  | varchar(10)  | Nomor HP siswa          |

> Catatan: kolom `id` saya tambahkan otomatis agar bisa dipakai sebagai acuan update/delete.

## Cara Menjalankan
1. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Buka browser: **http://localhost/data_mahasiswa/siswa/**

## Alur Aplikasi
1. **Create** — Isi form (Nama, Kelas, No HP) lalu klik *Simpan Data*. Data disimpan ke tabel `tabelsiswa`.
2. **Read** — Semua data ditampilkan pada tabel di bawah form, diurutkan dari yang terbaru.
3. **Update** — Klik tombol *Edit* pada baris data. Form terisi otomatis, ubah nilainya, lalu klik *Simpan Data*.
4. **Delete** — Klik tombol *Delete*, konfirmasi pada popup, data dihapus dari tabel.

## Fitur Baru
- **Pencarian** — Ketik kata kunci pada kotak pencarian untuk memfilter berdasarkan nama, kelas, atau no HP. Klik *Reset* untuk menampilkan semua data kembali.
- **Export CSV** — Unduh data (hasil filter jika sedang mencari) ke file `data_siswa.csv` yang bisa dibuka di Excel.
