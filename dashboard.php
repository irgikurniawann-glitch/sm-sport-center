<?php
session_start();

// Proteksi Session: Pengguna wajib login
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// 1. Hitung Ringkasan Data
$q_total_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reservasi");
$total_reservasi = mysqli_fetch_assoc($q_total_res)['total'] ?? 0;

$q_total_pel = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pelanggan");
$total_pelanggan = mysqli_fetch_assoc($q_total_pel)['total'] ?? 0;

$q_total_lap = mysqli_query($conn, "SELECT COUNT(*) AS total FROM lapangan");
$total_lapangan = mysqli_fetch_assoc($q_total_lap)['total'] ?? 0;

$q_lunas = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reservasi WHERE status = 'Lunas'");
$total_lunas = mysqli_fetch_assoc($q_lunas)['total'] ?? 0;

$q_pending = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reservasi WHERE status = 'Pending'");
$total_pending = mysqli_fetch_assoc($q_pending)['total'] ?? 0;

// TAMBAHAN: Hitung Total Pendapatan dari Transaksi Lunas
$q_pendapatan = mysqli_query($conn, "SELECT SUM(total_harga) AS total FROM reservasi WHERE status = 'Lunas'");
$total_pendapatan = mysqli_fetch_assoc($q_pendapatan)['total'] ?? 0;

// 2. Ambil 5 Reservasi Terbaru
$query_terbaru = "
    SELECT reservasi.*, pelanggan.nama, lapangan.nama_lapangan 
    FROM reservasi 
    JOIN pelanggan ON reservasi.id_pelanggan = pelanggan.id_pelanggan
    JOIN lapangan ON reservasi.id_lapangan = lapangan.id_lapangan
    ORDER BY id_reservasi DESC 
    LIMIT 5
";
$data_terbaru = mysqli_query($conn, $query_terbaru);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | SM Sport Center</title>

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

        .badge-status {
            font-weight: 500;
            padding: 0.35em 0.75em;
            border-radius: 50rem;
        }
    </style>
</head>

<body>

    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg py-3 sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="dashboard.php">
                <i class="bi bi-dribbble fs-5"></i> SM Sport Center
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary small d-none d-md-inline me-2">
                    <i class="bi bi-person-circle me-1"></i> Admin (<?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>)
                </span>
                <a href="logout.php" class="btn btn-light text-danger btn-sm px-3 rounded-pill border" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5">
        
        <!-- Welcome Banner & Quick Action -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-1">Dashboard Pengelolaan</h4>
                <p class="text-secondary small mb-0">Selamat datang kembali! Ringkasan status persewaan lapangan Anda.</p>
            </div>
            
            <!-- Tombol Navigasi Menu Utama -->
            <div class="d-flex flex-wrap gap-2">
                <a href="lapangan.php" class="btn btn-outline-primary px-3 rounded-3">
                    <i class="bi bi-dribbble me-1"></i> Lapangan
                </a>
                <a href="pelanggan.php" class="btn btn-outline-primary px-3 rounded-3">
                    <i class="bi bi-people me-1"></i> Pelanggan
                </a>
                <a href="reservasi.php" class="btn btn-outline-primary px-3 rounded-3">
                    <i class="bi bi-calendar-check me-1"></i> Reservasi
                </a>
                <!-- TOMBOL LAPORAN -->
                <a href="laporan.php" class="btn btn-primary px-3 rounded-3">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan
                </a>
            </div>
        </div>

        <!-- Grid Ringkasan Statistik -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card card-custom p-3 bg-primary text-white">
                    <div class="small opacity-75 mb-1"><i class="bi bi-wallet2 me-1"></i> Total Pendapatan (Lunas)</div>
                    <div class="fs-3 fw-bold">Rp <?= number_format($total_pendapatan, 0, ",", ".") ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card card-custom p-3">
                    <div class="text-secondary small mb-1"><i class="bi bi-dribbble me-1"></i> Lapangan</div>
                    <div class="fs-4 fw-bold text-dark"><?= number_format($total_lapangan) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card card-custom p-3">
                    <div class="text-secondary small mb-1"><i class="bi bi-people me-1"></i> Pelanggan</div>
                    <div class="fs-4 fw-bold text-dark"><?= number_format($total_pelanggan) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card card-custom p-3">
                    <div class="text-secondary small mb-1"><i class="bi bi-check-circle me-1 text-success"></i> Lunas</div>
                    <div class="fs-4 fw-bold text-success"><?= number_format($total_lunas) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card card-custom p-3">
                    <div class="text-secondary small mb-1"><i class="bi bi-clock-history me-1 text-warning"></i> Pending</div>
                    <div class="fs-4 fw-bold text-warning"><?= number_format($total_pending) ?></div>
                </div>
            </div>
        </div>

        <!-- Tabel Transaksi Terbaru -->
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Reservasi Terbaru</h6>
                <a href="reservasi.php" class="text-primary text-decoration-none small fw-medium">Lihat Semua <i class="bi bi-chevron-right"></i></a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle border-top mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th scope="col">Pelanggan</th>
                            <th scope="col">Lapangan</th>
                            <th scope="col">Tanggal & Waktu</th>
                            <th scope="col">Total Biaya</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($data_terbaru) > 0): ?>
                            <?php while ($r = mysqli_fetch_assoc($data_terbaru)): ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($r['nama']); ?></td>
                                    <td><?= htmlspecialchars($r['nama_lapangan']); ?></td>
                                    <td class="small">
                                        <?= date('d M Y', strtotime($r['tanggal_sewa'])); ?><br>
                                        <span class="text-secondary"><?= substr($r['jam_mulai'], 0, 5) ?> - <?= substr($r['jam_selesai'], 0, 5) ?> WIB</span>
                                    </td>
                                    <td class="fw-semibold">Rp <?= number_format($r['total_harga'], 0, ",", "."); ?></td>
                                    <td>
                                        <?php if ($r['status'] == 'Lunas'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle badge-status">Lunas</span>
                                        <?php elseif ($r['status'] == 'Pending'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle badge-status">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle badge-status">Batal</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-secondary">
                                    Belum ada transaksi reservasi.
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