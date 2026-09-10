<?php
session_start();

// Proteksi Session: Pengguna wajib login
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Validasi parameter ID dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: pelanggan.php");
    exit;
}

$id = $_GET['id'];

// Ambil data pelanggan berdasarkan ID
$stmt_get = $conn->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ?");
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$res_get = $stmt_get->get_result();

if ($res_get->num_rows === 0) {
    header("Location: pelanggan.php");
    exit;
}

$p = $res_get->fetch_assoc();
$pesan_error = "";

// Proses Update saat Form Dikirikan (Pengecekan REQUEST_METHOD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim($_POST['nama'] ?? '');
    $no_hp  = trim($_POST['no_hp'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if (empty($nama)) {
        $pesan_error = "Nama pelanggan tidak boleh kosong.";
    } else {
        // Query UPDATE tanpa menyentuh id_pelanggan
        $stmt_update = $conn->prepare("UPDATE pelanggan SET nama = ?, no_hp = ?, email = ?, alamat = ? WHERE id_pelanggan = ?");
        $stmt_update->bind_param("ssssi", $nama, $no_hp, $email, $alamat, $id);

        if ($stmt_update->execute()) {
            header("Location: pelanggan.php");
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
    <title>Edit Pelanggan | SM Sport Center</title>

    <!-- Bootstrap 5 & Icons -->
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

        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid var(--card-border);
        }

        .card-custom {
            border: 1px solid var(--card-border);
            border-radius: 12px;
            background: #ffffff;
        }
    </style>
</head>

<body>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="dashboard.php">
                <i class="bi bi-dribbble fs-5"></i> SM Sport Center
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="pelanggan.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <a href="logout.php" class="btn btn-light text-danger btn-sm px-3 rounded-pill border" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5" style="max-width: 600px;">
        
        <div class="mb-4">
            <h4 class="fw-bold mb-1">Edit Data Pelanggan</h4>
            <p class="text-secondary small mb-0">Ubah informasi profil untuk pelanggan #<?= htmlspecialchars($p['id_pelanggan']) ?></p>
        </div>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($pesan_error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-4">
            <!-- Action diisi eksplisit ke URL beserta parameternya -->
            <form method="POST" action="edit_pelanggan.php?id=<?= $id ?>">
                
                <div class="mb-3">
                    <label class="form-label small fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($p['nama']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">No. Handphone / WA</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($p['no_hp'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($p['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-medium">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($p['alamat'] ?? '') ?></textarea>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="pelanggan.php" class="btn btn-light px-4 border">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>

    </main>

</body>
</html>