<?php
session_start();

// 1. Proteksi Session: Pengguna harus login terlebih dahulu
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Validasi keberadaan ID pada parameter URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: reservasi.php");
    exit;
}

$id = $_GET['id'];

// Ambil detail data reservasi dengan Prepared Statement
$stmt_get = $conn->prepare("
    SELECT reservasi.*, pelanggan.nama, lapangan.nama_lapangan 
    FROM reservasi 
    JOIN pelanggan ON reservasi.id_pelanggan = pelanggan.id_pelanggan
    JOIN lapangan ON reservasi.id_lapangan = lapangan.id_lapangan
    WHERE id_reservasi = ?
");
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$res_get = $stmt_get->get_result();

if ($res_get->num_rows === 0) {
    header("Location: reservasi.php");
    exit;
}

$r = $res_get->fetch_assoc();

$pesan_error = "";

// Proses Update Status
if (isset($_POST['update'])) {
    $status = $_POST['status'] ?? '';

    if (!empty($status)) {
        $stmt_update = $conn->prepare("UPDATE reservasi SET status = ? WHERE id_reservasi = ?");
        $stmt_update->bind_param("si", $status, $id);

        if ($stmt_update->execute()) {
            header("Location: reservasi.php");
            exit;
        } else {
            $pesan_error = "Gagal memperbarui status reservasi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Reservasi | SM Sport Center</title>

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
                <a href="reservasi.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
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
            <h4 class="fw-bold mb-1">Edit Status Reservasi</h4>
            <p class="text-secondary small mb-0">Ubah status pembayaran transaksi #<?= $r['id_reservasi'] ?></p>
        </div>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($pesan_error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Ringkasan Info Transaksi -->
        <div class="card card-custom p-4 mb-3">
            <h6 class="fw-bold text-secondary mb-3 pb-2 border-bottom">Detail Pemesanan</h6>
            <div class="row g-2 small">
                <div class="col-4 text-secondary">Pelanggan</div>
                <div class="col-8 fw-semibold"><?= htmlspecialchars($r['nama']) ?></div>

                <div class="col-4 text-secondary">Lapangan</div>
                <div class="col-8 fw-semibold"><?= htmlspecialchars($r['nama_lapangan']) ?></div>

                <div class="col-4 text-secondary">Tanggal Sewa</div>
                <div class="col-8"><?= date('d M Y', strtotime($r['tanggal_sewa'])) ?></div>

                <div class="col-4 text-secondary">Waktu</div>
                <div class="col-8"><?= substr($r['jam_mulai'], 0, 5) ?> - <?= substr($r['jam_selesai'], 0, 5) ?> WIB</div>

                <div class="col-4 text-secondary">Total Biaya</div>
                <div class="col-8 fw-bold text-primary">Rp <?= number_format($r['total_harga'], 0, ",", ".") ?></div>
            </div>
        </div>

        <!-- Form Update Status -->
        <div class="card card-custom p-4">
            <form method="POST" action="">
                
                <div class="mb-4">
                    <label class="form-label small fw-medium">Status Pembayaran</label>
                    <select name="status" class="form-select" required>
                        <option value="Pending" <?= ($r['status'] == "Pending") ? "selected" : "" ?>>Pending</option>
                        <option value="Lunas" <?= ($r['status'] == "Lunas") ? "selected" : "" ?>>Lunas</option>
                        <option value="Batal" <?= ($r['status'] == "Batal") ? "selected" : "" ?>>Batal</option>
                    </select>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="reservasi.php" class="btn btn-light px-4 border">Batal</a>
                    <button type="submit" name="update" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>

    </main>

</body>
</html>