<?php
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: lapangan.php");
    exit;
}

$id = $_GET['id'];

// Ambil data lapangan spesifik
$stmt_get = $conn->prepare("SELECT * FROM lapangan WHERE id_lapangan = ?");
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$res_get = $stmt_get->get_result();

if ($res_get->num_rows === 0) {
    header("Location: lapangan.php");
    exit;
}

$l = $res_get->fetch_assoc();
$pesan_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lapangan = trim($_POST['nama_lapangan'] ?? '');
    $jenis         = trim($_POST['jenis'] ?? '');
    $harga_per_jam = trim($_POST['harga_per_jam'] ?? 0);

    if (empty($nama_lapangan) || empty($harga_per_jam)) {
        $pesan_error = "Nama lapangan dan harga sewa wajib diisi.";
    } else {
        // Update data lapangan
        $stmt_update = $conn->prepare("UPDATE lapangan SET nama_lapangan = ?, jenis = ?, harga_per_jam = ? WHERE id_lapangan = ?");
        $stmt_update->bind_param("ssii", $nama_lapangan, $jenis, $harga_per_jam, $id);

        if ($stmt_update->execute()) {
            header("Location: lapangan.php");
            exit;
        } else {
            $pesan_error = "Gagal memperbarui data: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Lapangan | SM Sport Center</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f8fafc;
            --card-border: #e2e8f0;
        }

        body {
            background-color: var(--bg-body);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        .card-custom {
            border: 1px solid var(--card-border);
            border-radius: 12px;
            background: #ffffff;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg py-3 border-bottom bg-white">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="dashboard.php">
                <i class="bi bi-dribbble fs-5"></i> SM Sport Center
            </a>
            <a href="lapangan.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </nav>

    <main class="container my-5" style="max-width: 600px;">

        <div class="mb-4">
            <h4 class="fw-bold mb-1">Edit Data Lapangan</h4>
            <p class="text-secondary small mb-0">Ubah detail rincian untuk lapangan #<?= htmlspecialchars($l['id_lapangan']) ?></p>
        </div>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($pesan_error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-4">
            <form method="POST" action="edit_lapangan.php?id=<?= $id ?>">

                <div class="mb-3">
                    <label class="form-label small fw-medium">Nama Lapangan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lapangan" class="form-control" value="<?= htmlspecialchars($l['nama_lapangan']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-medium">Jenis / Tipe Lapangan</label>
                    <input type="text" name="jenis" class="form-control" value="<?= htmlspecialchars($l['jenis'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-medium">Harga Sewa per Jam (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_per_jam" class="form-control" value="<?= htmlspecialchars($l['harga_per_jam']) ?>" required>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="lapangan.php" class="btn btn-light px-4 border">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>

    </main>

</body>

</html>