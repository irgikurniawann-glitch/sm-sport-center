<?php
session_start();

// Proteksi Session
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

$pesan_error = "";

if (isset($_POST['simpan'])) {
    $nama   = trim($_POST['nama'] ?? '');
    $no_hp  = trim($_POST['no_hp'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    // Validasi input minimal nama
    if (empty($nama)) {
        $pesan_error = "Nama pelanggan wajib diisi.";
    } else {
        // Insert Data Pelanggan
        $stmt_insert = $conn->prepare("INSERT INTO pelanggan (nama, no_hp, email, alamat) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("ssss", $nama, $no_hp, $email, $alamat);

        if ($stmt_insert->execute()) {
            header("Location: pelanggan.php");
            exit;
        } else {
            $pesan_error = "Gagal menyimpan data pelanggan.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Pelanggan | SM Sport Center</title>

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f8fafc;
            --card-border: #e2e8f0;
            --text-muted: #64748b;
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
            <h4 class="fw-bold mb-1">Tambah Pelanggan Baru</h4>
            <p class="text-secondary small mb-0">Isi data identitas pelanggan di bawah ini</p>
        </div>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($pesan_error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-4">
            <form method="POST" action="">
                
                <div class="mb-3">
                    <label class="form-label small fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">No. Handphone / WA</label>
                        <input type="text" name="no_hp" class="form-control" placeholder="08123456789" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-medium">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat lengkap..."><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="pelanggan.php" class="btn btn-light px-4 border">Batal</a>
                    <button type="submit" name="simpan" class="btn btn-primary px-4">
                        <i class="bi bi-person-check me-1"></i> Simpan Pelanggan
                    </button>
                </div>

            </form>
        </div>

    </main>

</body>
</html>