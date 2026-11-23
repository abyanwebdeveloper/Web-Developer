<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "akademik";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Tidak bisa terkoneksi ke database: " . mysqli_connect_error());
}

$nim        = "";
$nama       = "";
$alamat     = "";
$fakultas   = "";
$sukses     = "";
$error      = "";

$op = isset($_GET['op']) ? $_GET['op'] : "";
$id = isset($_GET['id']) ? $_GET['id'] : "";

if ($op == 'delete' && $id) {
    $sql1   = "DELETE FROM mahasiswa WHERE id = '$id'";
    $q1     = mysqli_query($koneksi, $sql1);
    if ($q1) {
        $sukses = "Berhasil hapus data";
    } else {
        $error  = "Gagal menghapus data";
    }
}

if (isset($_POST['simpan'])) {
    $nim        = trim($_POST['nim']);
    $nama       = trim($_POST['nama']);
    $alamat     = trim($_POST['alamat']);
    $fakultas   = trim($_POST['fakultas']);

    if ($nim && $nama && $alamat && $fakultas) {
        if ($op == 'edit' && $id) {
            $sql1 = "UPDATE mahasiswa SET nim = '$nim', nama = '$nama', alamat = '$alamat', fakultas = '$fakultas' WHERE id = '$id'";
            $q1   = mysqli_query($koneksi, $sql1);
            if ($q1) {
                $sukses = "Data berhasil diupdate";
            } else {
                $error  = "Data gagal diupdate (NIM mungkin sudah digunakan mahasiswa lain)";
            }
        } else {
            $sql1 = "INSERT INTO mahasiswa (nim, nama, alamat, fakultas) VALUES ('$nim', '$nama', '$alamat', '$fakultas')";
            $q1   = mysqli_query($koneksi, $sql1);
            if ($q1) {
                $sukses   = "Data berhasil diupdate";
                $nim      = "";
                $nama     = "";
                $alamat   = "";
                $fakultas = "";
            } else {
                $error    = "Gagal memasukkan data (NIM sudah terdaftar)";
            }
        }
    } else {
        $error = "Silakan masukkan semua data";
    }
}

if ($op == 'edit' && $id && !isset($_POST['simpan'])) {
    $sql1   = "SELECT * FROM mahasiswa WHERE id = '$id'";
    $q1     = mysqli_query($koneksi, $sql1);
    $r1     = mysqli_fetch_array($q1);
    if ($r1) {
        $nim        = $r1['nim'];
        $nama       = $r1['nama'];
        $alamat     = $r1['alamat'];
        $fakultas   = $r1['fakultas'];
    } else {
        $error  = "Data tidak ditemukan";
    }
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
            <div class="card-header">
                Create / Edit Data
            </div>
            <div class="card-body">
                <?php if ($error) { ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
                <?php } ?>
                <?php if ($sukses) { ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $sukses; ?>
                </div>
                <?php } ?>
                <form action="<?php echo ($op == 'edit') ? "index.php?op=edit&id=$id" : "index.php"; ?>" method="POST">
                    <div class="mb-3 row">
                        <label for="nim" class="col-sm-2 col-form-label">NIM</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="nim" name="nim"
                                value="<?php echo htmlspecialchars($nim); ?>">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="nama" class="col-sm-2 col-form-label">Nama</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="nama" name="nama"
                                value="<?php echo htmlspecialchars($nama); ?>">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="alamat" class="col-sm-2 col-form-label">Alamat</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="alamat" name="alamat"
                                value="<?php echo htmlspecialchars($alamat); ?>">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="fakultas" class="col-sm-2 col-form-label">Fakultas</label>
                        <div class="col-sm-10">
                            <select class="form-select" name="fakultas" id="fakultas">
                                <option value="">- Pilih Fakultas -</option>
                                <option value="tif" <?php if ($fakultas == "teknik informatika") echo "selected"; ?>>
                                    Teknik Informatika
                                </option>
                                <option value="tif" <?php if ($fakultas == "teknik informatika") echo "selected"; ?>>
                                    Teknik Informatika
                                </option>
                                <option value="tif" <?php if ($fakultas == "teknik informatika") echo "selected"; ?>>
                                    Teknik Informatika
                                </option>
                                <option value="tif" <?php if ($fakultas == "teknik informatika") echo "selected"; ?>>
                                    Teknik Informatika
                                </option>
                                <option value="tif" <?php if ($fakultas == "teknik informatika") echo "selected"; ?>>
                                    Teknik Informatika
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <input type="submit" name="simpan" value="Simpan Data" class="btn btn-primary">
                        <?php if ($op == 'edit') { ?>
                        <a href="index.php" class="btn btn-secondary ms-2">Batal Edit</a>
                        <?php } ?>
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
                        <?php
                        $sql2   = "SELECT * FROM mahasiswa ORDER BY id DESC";
                        $q2     = mysqli_query($koneksi, $sql2);
                        $urut   = 1;
                        while ($r2 = mysqli_fetch_array($q2)) {
                            $id_mhs     = $r2['id'];
                            $nim_mhs    = $r2['nim'];
                            $nama_mhs   = $r2['nama'];
                            $alamat_mhs = $r2['alamat'];
                            $fak_mhs    = $r2['fakultas'];
                        ?>
                        <tr>
                            <th scope="row"><?php echo $urut++; ?></th>
                            <td><?php echo htmlspecialchars($nim_mhs); ?></td>
                            <td><?php echo htmlspecialchars($nama_mhs); ?></td>
                            <td><?php echo htmlspecialchars($alamat_mhs); ?></td>
                            <td><?php echo htmlspecialchars($fak_mhs); ?></td>
                            <td>
                                <a href="index.php?op=edit&id=<?php echo $id_mhs; ?>" class="btn btn-warning">Edit</a>
                                <a href="index.php?op=delete&id=<?php echo $id_mhs; ?>"
                                    onclick="return confirm('Yakin mau delete data?')" class="btn btn-danger">Delete</a>
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>