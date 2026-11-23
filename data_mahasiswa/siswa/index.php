<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "databasesiswa";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Tidak bisa terkoneksi ke database: " . mysqli_connect_error());
}

$nama   = "";
$kelas  = "";
$nohp   = "";
$sukses = "";
$error  = "";

$op = isset($_GET['op']) ? $_GET['op'] : "";
$id = isset($_GET['id']) ? $_GET['id'] : "";

if ($op == 'delete' && $id) {
    $sql1 = "DELETE FROM tabelsiswa WHERE id = '$id'";
    $q1   = mysqli_query($koneksi, $sql1);
    if ($q1) {
        $sukses = "Berhasil hapus data";
    } else {
        $error = "Gagal menghapus data";
    }
}

if (isset($_POST['simpan'])) {
    $nama  = trim($_POST['nama']);
    $kelas = trim($_POST['kelas']);
    $nohp  = trim($_POST['nohp']);

    if ($nama && $kelas && $nohp) {
        if ($op == 'edit' && $id) {
            $sql1 = "UPDATE tabelsiswa SET nama = '$nama', kelas = '$kelas', nohp = '$nohp' WHERE id = '$id'";
            $q1   = mysqli_query($koneksi, $sql1);
            if ($q1) {
                $sukses = "Data berhasil diupdate";
                $nama  = "";
                $kelas = "";
                $nohp  = "";
                $op    = "";
            } else {
                $error = "Data gagal diupdate";
            }
        } else {
            $sql1 = "INSERT INTO tabelsiswa (nama, kelas, nohp) VALUES ('$nama', '$kelas', '$nohp')";
            $q1   = mysqli_query($koneksi, $sql1);
            if ($q1) {
                $sukses = "Data berhasil ditambahkan";
                $nama  = "";
                $kelas = "";
                $nohp  = "";
            } else {
                $error = "Gagal memasukkan data";
            }
        }
    } else {
        $error = "Silakan masukkan semua data";
    }
}

if ($op == 'edit' && $id && !isset($_POST['simpan'])) {
    $sql1 = "SELECT * FROM tabelsiswa WHERE id = '$id'";
    $q1   = mysqli_query($koneksi, $sql1);
    $r1   = mysqli_fetch_array($q1);
    if ($r1) {
        $nama  = $r1['nama'];
        $kelas = $r1['kelas'];
        $nohp  = $r1['nohp'];
    } else {
        $error = "Data tidak ditemukan";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa</title>
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
                Create / Edit Data Siswa
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
                        <label for="nama" class="col-sm-2 col-form-label">Nama</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="nama" name="nama"
                                value="<?php echo htmlspecialchars($nama); ?>">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="kelas" class="col-sm-2 col-form-label">Kelas</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="kelas" name="kelas"
                                value="<?php echo htmlspecialchars($kelas); ?>">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="nohp" class="col-sm-2 col-form-label">No HP</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="nohp" name="nohp"
                                value="<?php echo htmlspecialchars($nohp); ?>">
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
            <div class="card-header text-white bg-secondary d-flex justify-content-between align-items-center">
                Data Siswa
                <a href="export.php<?php echo (isset($_GET['cari']) && $_GET['cari'] != '') ? '?cari=' . urlencode($_GET['cari']) : ''; ?>" class="btn btn-sm btn-light">Export CSV</a>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-sm-8">
                        <input type="text" name="cari" class="form-control" placeholder="Cari nama / kelas / no HP..."
                            value="<?php echo isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : ''; ?>">
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Nama</th>
                            <th scope="col">Kelas</th>
                            <th scope="col">No HP</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql2 = "SELECT * FROM tabelsiswa ORDER BY id DESC";
                        if (isset($_GET['cari']) && trim($_GET['cari']) != '') {
                            $k = mysqli_real_escape_string($koneksi, trim($_GET['cari']));
                            $sql2 = "SELECT * FROM tabelsiswa WHERE nama LIKE '%$k%' OR kelas LIKE '%$k%' OR nohp LIKE '%$k%' ORDER BY id DESC";
                        }
                        $q2   = mysqli_query($koneksi, $sql2);
                        $urut = 1;
                        while ($r2 = mysqli_fetch_array($q2)) {
                            $id_siswa   = $r2['id'];
                            $nama_siswa = $r2['nama'];
                            $kelas_siswa= $r2['kelas'];
                            $nohp_siswa = $r2['nohp'];
                        ?>
                        <tr>
                            <th scope="row"><?php echo $urut++; ?></th>
                            <td><?php echo htmlspecialchars($nama_siswa); ?></td>
                            <td><?php echo htmlspecialchars($kelas_siswa); ?></td>
                            <td><?php echo htmlspecialchars($nohp_siswa); ?></td>
                            <td>
                                <a href="index.php?op=edit&id=<?php echo $id_siswa; ?>" class="btn btn-warning">Edit</a>
                                <a href="index.php?op=delete&id=<?php echo $id_siswa; ?>"
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
