<?php
session_start();

// Proteksi Session Login
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Ambil seluruh data lapangan
$query = "SELECT * FROM lapangan ORDER BY id_lapangan DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Lapangan | SM Sport Center</title>

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
    <nav class="navbar navbar-expand-lg py-3 sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="dashboard.php">
                <i class="bi bi-dribbble fs-5"></i> SM Sport Center
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-light text-danger btn-sm px-3 rounded-pill border" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-1">Data Lapangan</h4>
                <p class="text-secondary small mb-0">Kelola daftar fasilitas lapangan yang tersedia untuk disewa.</p>
            </div>
            <div>
                <a href="tambah_lapangan.php" class="btn btn-primary px-3 rounded-3">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Lapangan
                </a>
            </div>
        </div>

        <!-- Tabel Data Lapangan -->
        <div class="card card-custom p-4">
            <div class="table-responsive">
                <table class="table align-middle border-top mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th scope="col" width="80">ID</th>
                            <th scope="col">Nama Lapangan</th>
                            <th scope="col">Jenis / Tipe</th>
                            <th scope="col">Harga per Jam</th>
                            <th scope="col" width="160" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="fw-medium text-secondary">#<?= $row['id_lapangan']; ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_lapangan']); ?></td>
                                    <td>
                                        <?php if (!empty($row['jenis'])): ?>
                                            <span class="badge bg-light text-dark border fw-normal">
                                                <?= htmlspecialchars($row['jenis']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small"><em>- Tidak ada jenis -</em></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold text-success">
                                        Rp <?= number_format($row['harga_per_jam'], 0, ',', '.'); ?> / Jam
                                    </td>
                                    <td class="text-center">
                                        <a href="edit_lapangan.php?id=<?= $row['id_lapangan']; ?>" class="btn btn-sm btn-outline-warning me-1" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="hapus_lapangan.php?id=<?= $row['id_lapangan']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus lapangan ini?')" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-secondary">
                                    Belum ada data lapangan yang terdaftar.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>

</html>