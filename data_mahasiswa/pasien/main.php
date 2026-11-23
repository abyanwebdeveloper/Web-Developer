<?php
mysqli_report(MYSQLI_REPORT_OFF);

$koneksi = mysqli_connect("localhost", "root", "", "rumah_sakit_soedono");
if (!$koneksi) {
    die("Tidak bisa terkoneksi ke database: " . mysqli_connect_error());
}

$Nama = ""; $Jenis_kelamin = ""; $Tanggal_lahir = ""; $No_telepon = ""; $Alamat = ""; $Keluhan_pasien = "";
$sukses = ""; $error = "";

$op = isset($_GET['op']) ? $_GET['op'] : "";
$id = isset($_GET['id']) ? $_GET['id'] : "";

if ($op == 'delete' && $id) {
    $q1 = mysqli_query($koneksi, "DELETE FROM pasien WHERE id_pasien = '$id'");
    if ($q1) { $sukses = "Berhasil hapus data"; } else { $error = "Gagal menghapus data"; }
}

if (isset($_POST['simpan'])) {
    $Nama = trim($_POST['Nama']);
    $Jenis_kelamin = trim($_POST['Jenis_kelamin']);
    $Tanggal_lahir = trim($_POST['Tanggal_lahir']);
    $No_telepon = trim($_POST['No_telepon']);
    $Alamat = trim($_POST['Alamat']);
    $Keluhan_pasien = trim($_POST['Keluhan_pasien']);

    if ($Nama && $Jenis_kelamin && $Tanggal_lahir && $No_telepon && $Alamat && $Keluhan_pasien !== "") {
        if ($op == 'edit' && $id) {
            $q1 = mysqli_query($koneksi, "UPDATE pasien SET Nama='$Nama', Jenis_kelamin='$Jenis_kelamin', Tanggal_lahir='$Tanggal_lahir', `No.telepon`='$No_telepon', Alamat='$Alamat', Keluhan_pasien='$Keluhan_pasien' WHERE id_pasien='$id'");
            if ($q1) {
                $sukses = "Data berhasil diupdate";
                $Nama=""; $Jenis_kelamin=""; $Tanggal_lahir=""; $No_telepon=""; $Alamat=""; $Keluhan_pasien=""; $op="";
            } else { $error = "Data gagal diupdate"; }
        } else {
            $q1 = mysqli_query($koneksi, "INSERT INTO pasien (Nama, Jenis_kelamin, Tanggal_lahir, `No.telepon`, Alamat, Keluhan_pasien) VALUES ('$Nama', '$Jenis_kelamin', '$Tanggal_lahir', '$No_telepon', '$Alamat', '$Keluhan_pasien')");
            if ($q1) {
                $sukses = "Data berhasil ditambahkan";
                $Nama=""; $Jenis_kelamin=""; $Tanggal_lahir=""; $No_telepon=""; $Alamat=""; $Keluhan_pasien="";
            } else { $error = "Gagal memasukkan data"; }
        }
    } else {
        $error = "Silakan masukkan semua data";
    }
}

if ($op == 'edit' && $id && !isset($_POST['simpan'])) {
    $q1 = mysqli_query($koneksi, "SELECT * FROM pasien WHERE id_pasien = '$id'");
    $r1 = mysqli_fetch_array($q1);
    if ($r1) {
        $Nama = $r1['Nama'];
        $Jenis_kelamin = $r1['Jenis_kelamin'];
        $Tanggal_lahir = $r1['Tanggal_lahir'];
        $No_telepon = $r1['No.telepon'];
        $Alamat = $r1['Alamat'];
        $Keluhan_pasien = $r1['Keluhan_pasien'];
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
    <title>Data Pasien</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
    .mx-auto { width: 900px; margin: 50px auto; }
    .card { margin-bottom: 20px; }
    </style>
</head>

<body>
    <div class="mx-auto">
        <div class="card">
            <div class="card-header">Create / Edit Data Pasien</div>
            <div class="card-body">
                <?php if ($error) { ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php } ?>
                <?php if ($sukses) { ?>
                <div class="alert alert-success"><?php echo $sukses; ?></div>
                <?php } ?>
                <form action="<?php echo ($op == 'edit') ? "main.php?op=edit&id=$id" : "main.php"; ?>" method="POST">
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Nama</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="Nama" value="<?php echo htmlspecialchars($Nama); ?>">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Jenis Kelamin</label>
                        <div class="col-sm-9">
                            <select class="form-select" name="Jenis_kelamin">
                                <option value="">- Pilih -</option>
                                <option value="Laki-laki" <?php if ($Jenis_kelamin == "Laki-laki") echo "selected"; ?>>Laki-laki</option>
                                <option value="Perempuan" <?php if ($Jenis_kelamin == "Perempuan") echo "selected"; ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Tanggal Lahir</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="Tanggal_lahir" value="<?php echo htmlspecialchars($Tanggal_lahir); ?>">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">No. Telepon</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="No_telepon" value="<?php echo htmlspecialchars($No_telepon); ?>">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Alamat</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="Alamat" value="<?php echo htmlspecialchars($Alamat); ?>">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Keluhan Pasien</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" name="Keluhan_pasien" value="<?php echo htmlspecialchars($Keluhan_pasien); ?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <input type="submit" name="simpan" value="Simpan Data" class="btn btn-primary">
                        <?php if ($op == 'edit') { ?>
                        <a href="main.php" class="btn btn-secondary ms-2">Batal Edit</a>
                        <?php } ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header text-white bg-secondary">Data Pasien</div>
            <div class="card-body">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Jenis Kelamin</th>
                            <th>Tanggal Lahir</th>
                            <th>No. Telepon</th>
                            <th>Alamat</th>
                            <th>Keluhan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q2 = mysqli_query($koneksi, "SELECT * FROM pasien ORDER BY id_pasien DESC");
                        $urut = 1;
                        while ($r2 = mysqli_fetch_array($q2)) {
                        ?>
                        <tr>
                            <th scope="row"><?php echo $urut++; ?></th>
                            <td><?php echo htmlspecialchars($r2['Nama']); ?></td>
                            <td><?php echo htmlspecialchars($r2['Jenis_kelamin']); ?></td>
                            <td><?php echo htmlspecialchars($r2['Tanggal_lahir']); ?></td>
                            <td><?php echo htmlspecialchars($r2['No.telepon']); ?></td>
                            <td><?php echo htmlspecialchars($r2['Alamat']); ?></td>
                            <td><?php echo htmlspecialchars($r2['Keluhan_pasien']); ?></td>
                            <td>
                                <a href="main.php?op=edit&id=<?php echo $r2['id_pasien']; ?>" class="btn btn-warning">Edit</a>
                                <a href="main.php?op=delete&id=<?php echo $r2['id_pasien']; ?>"
                                    onclick="return confirm('Yakin mau delete data?')" class="btn btn-danger">Delete</a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
