<?php
include 'config.php';

// Variabel awal
$nama = "";
$harga = "";
$foto = "";
$sukses = "";
$error = "";

$op = $_GET['op'] ?? '';
$id = $_GET['id'] ?? '';

// Fungsi untuk mengamankan output
function e($data) {
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

// PROSES DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id_hapus = (int)($_POST['id'] ?? 0);

    if ($id_hapus > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_hapus);

        if (mysqli_stmt_execute($stmt)) {
            $sukses = "Data berhasil dihapus";
        } else {
            $error = "Gagal menghapus data";
        }

        mysqli_stmt_close($stmt);
    }
}

// PROSES SIMPAN DAN EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $nama = trim($_POST['nama'] ?? '');
    $harga = trim($_POST['harga'] ?? '');
    $foto = trim($_POST['foto'] ?? '');
    $id_edit = (int)($_POST['id'] ?? 0);

    if ($nama !== '' && $harga !== '' && $foto !== '') {

        if (!is_numeric($harga) || (float)$harga < 0) {
            $error = "Harga harus berupa angka yang valid";
        } else {
            if ($id_edit > 0) {
                // UPDATE DATA
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE products SET nama = ?, harga = ?, foto = ? WHERE id = ?"
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    "sssi",
                    $nama,
                    $harga,
                    $foto,
                    $id_edit
                );

                if (mysqli_stmt_execute($stmt)) {
                    $sukses = "Data berhasil diupdate";
                    $nama = "";
                    $harga = "";
                    $foto = "";
                } else {
                    $error = "Data gagal diupdate";
                }

                mysqli_stmt_close($stmt);
            } else {
                // INSERT DATA
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO products (nama, harga, foto) VALUES (?, ?, ?)"
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    "sss",
                    $nama,
                    $harga,
                    $foto
                );

                if (mysqli_stmt_execute($stmt)) {
                    $sukses = "Data berhasil ditambahkan";
                    $nama = "";
                    $harga = "";
                    $foto = "";
                } else {
                    $error = "Gagal memasukkan data";
                }

                mysqli_stmt_close($stmt);
            }
        }
    } else {
        $error = "Silakan masukkan semua data";
    }
}

// PROSES EDIT (MENGAMBIL DATA)
if ($op === 'edit' && $id !== '' && !isset($_POST['simpan'])) {
    $id = (int)$id;

    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM products WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($hasil);

    if ($data) {
        $nama = $data['nama'];
        $harga = $data['harga'];
        $foto = $data['foto'];
    } else {
        $error = "Data tidak ditemukan";
        $op = '';
    }

    mysqli_stmt_close($stmt);
}

// AMBIL SEMUA DATA
$q2 = mysqli_query(
    $conn,
    "SELECT * FROM products ORDER BY id DESC"
);

if (!$q2) {
    $error = "Gagal mengambil data: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Project Makanan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    .mx-auto {
        width: 900px;
        max-width: 95%;
        margin: 50px auto;
    }

    .card {
        margin-bottom: 20px;
    }

    .foto-thumb {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
    }
    </style>
</head>

<body>

    <div class="mx-auto">

        <!-- FORM CREATE / EDIT -->
        <div class="card">

            <div class="card-header">
                Create / Edit Makanan
            </div>

            <div class="card-body">

                <?php if ($error !== '') { ?>
                <div class="alert alert-danger">
                    <?php echo e($error); ?>
                </div>
                <?php } ?>

                <?php if ($sukses !== '') { ?>
                <div class="alert alert-success">
                    <?php echo e($sukses); ?>
                </div>
                <?php } ?>

                <form action="index.php" method="POST">

                    <input type="hidden" name="id" value="<?php echo $op === 'edit' ? e($id) : ''; ?>">

                    <div class="mb-3 row">
                        <label class="col-sm-2 col-form-label">
                            Nama
                        </label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="nama" value="<?php echo e($nama); ?>"
                                required>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label class="col-sm-2 col-form-label">
                            Harga
                        </label>

                        <div class="col-sm-10">
                            <input type="number" class="form-control" name="harga" min="0" step="any"
                                value="<?php echo e($harga); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label class="col-sm-2 col-form-label">
                            Foto (URL)
                        </label>

                        <div class="col-sm-10">
                            <input type="url" class="form-control" name="foto" value="<?php echo e($foto); ?>"
                                placeholder="https://contoh.com/foto.jpg" required>
                        </div>
                    </div>

                    <div class="col-12">

                        <input type="submit" name="simpan" value="Simpan Data" class="btn btn-primary">

                        <?php if ($op === 'edit') { ?>
                        <a href="index.php" class="btn btn-secondary ms-2">
                            Batal Edit
                        </a>
                        <?php } ?>

                    </div>

                </form>
            </div>
        </div>


        <!-- TABEL DATA MAKANAN -->
        <div class="card">

            <div class="card-header text-white bg-secondary">
                Data Makanan
            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-striped align-middle">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Foto</th>
                                <th>Nama</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                    $urut = 1;

                    if ($q2) {
                        while ($r2 = mysqli_fetch_assoc($q2)) {
                    ?>

                            <tr>
                                <th scope="row">
                                    <?php echo $urut++; ?>
                                </th>

                                <td>
                                    <img src="<?php echo e($r2['foto']); ?>" class="foto-thumb"
                                        alt="<?php echo e($r2['nama']); ?>">
                                </td>

                                <td>
                                    <?php echo e($r2['nama']); ?>
                                </td>

                                <td>
                                    Rp <?php echo e($r2['harga']); ?>
                                </td>

                                <td>
                                    <a href="index.php?op=edit&id=<?php echo (int)$r2['id']; ?>"
                                        class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <form action="index.php" method="POST" class="d-inline"
                                        onsubmit="return confirm('Yakin mau menghapus data ini?')">

                                        <input type="hidden" name="id" value="<?php echo (int)$r2['id']; ?>">

                                        <button type="submit" name="hapus" class="btn btn-danger btn-sm">
                                            Delete
                                        </button>

                                    </form>
                                </td>
                            </tr>

                            <?php
                        }
                    }
                    ?>

                            <?php if ($q2 && mysqli_num_rows($q2) === 0) { ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    Belum ada data makanan
                                </td>
                            </tr>
                            <?php } ?>

                        </tbody>

                    </table>

                </div>
            </div>
        </div>

    </div>

</body>

</html>