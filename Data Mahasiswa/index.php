<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "akademik";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Tidak bisa terkoneksi ke database: " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
    .mx-auto {
        width: 800px;
        margin: 50px auto;
    }

    .card {
        margin-bottom: 20px;
    }
    </style>
</head>

<body>
    <div class="mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                Create / Edit Data
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="mb-3 row">
                        <label for="nim" class="col-sm-2 col-form-label">NIM</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="nim" name="nim">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="nama" class="col-sm-2 col-form-label">Nama</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="nama" name="nama">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="alamat" class="col-sm-2 col-form-label">Alamat</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="alamat" name="alamat">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="fakultas" class="col-sm-2 col-form-label">Fakultas</label>
                        <div class="col-sm-10">
                            <select class="form-select" name="fakultas" id="fakultas">
                                <option value="">- Pilih Fakultas -</option>
                                <option value="teknik informatika">Teknik Informatika</option>
                                <option value="teknik informatika">Teknik Informatika</option>
                                <option value="teknik informatika">Teknik Informatika</option>
                                <option value="teknik informatika">Teknik Informatika</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <input type="submit" name="simpan" value="Simpan Data" class="btn btn-primary">
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header text-white bg-secondary">
                Data Mahasiswa
            </div>
            <div class="card-body">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">NIM</th>
                            <th scope="col">Nama</th>
                            <th scope="col">Alamat</th>
                            <th scope="col">Fakultas</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>