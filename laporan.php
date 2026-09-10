<?php
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Filter Parameter
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01'); // Default awal bulan
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d'); // Default hari ini
$status      = $_GET['status'] ?? 'Semua';

// Query dasar
$where_clauses = ["reservasi.tanggal_sewa BETWEEN '$tgl_mulai' AND '$tgl_selesai'"];

if ($status != 'Semua') {
    $where_clauses[] = "reservasi.status = '$status'";
}

$where_sql = implode(' AND ', $where_clauses);

// Query Data Laporan
$query_laporan = "
    SELECT reservasi.*, pelanggan.nama, lapangan.nama_lapangan 
    FROM reservasi 
    JOIN pelanggan ON reservasi.id_pelanggan = pelanggan.id_pelanggan
    JOIN lapangan ON reservasi.id_lapangan = lapangan.id_lapangan
    WHERE $where_sql
    ORDER BY reservasi.tanggal_sewa ASC, reservasi.jam_mulai ASC
";

$data_laporan = mysqli_query($conn, $query_laporan);

// Hitung total pendapatan dari data yang difilter (khusus yang Lunas)
$query_total = "
    SELECT SUM(total_harga) AS grand_total, COUNT(*) AS total_transaksi 
    FROM reservasi 
    WHERE $where_sql AND status = 'Lunas'
";
$res_total = mysqli_fetch_assoc(mysqli_query($conn, $query_total));
$grand_total = $res_total['grand_total'] ?? 0;
$total_transaksi_lunas = $res_total['total_transaksi'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Pendapatan | SM Sport Center</title>

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

        /* Mode Cetak/Print */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #fff !important;
            }
            .card-custom {
                border: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- Header Navbar (Diabaikan saat print) -->
    <nav class="navbar navbar-expand-lg py-3 sticky-top no-print">
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

    <main class="container my-5">
        
        <!-- Header Page -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 no-print">
            <div>
                <h4 class="fw-bold mb-1">Laporan Transaksi</h4>
                <p class="text-secondary small mb-0">Filter dan cetak rekapitulasi persewaan lapangan.</p>
            </div>
            
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-outline-secondary px-3 rounded-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <button onclick="window.print()" class="btn btn-success px-3 rounded-3">
                    <i class="bi bi-printer me-1"></i> Cetak Laporan
                </button>
            </div>
        </div>

        <!-- Form Filter (Diabaikan saat print) -->
        <div class="card card-custom p-4 mb-4 no-print">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-medium">Dari Tanggal</label>
                    <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium">Sampai Tanggal</label>
                    <input type="date" name="tgl_selesai" class="form-control" value="<?= $tgl_selesai ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium">Status Pembayaran</label>
                    <select name="status" class="form-select">
                        <option value="Semua" <?= $status == 'Semua' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="Lunas" <?= $status == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                        <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Batal" <?= $status == 'Batal' ? 'selected' : '' ?>>Batal</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="laporan.php" class="btn btn-light border w-100">Reset</a>
                </div>
            </form>
        </div>

        <!-- Tampilan Header Saat Dicetak -->
        <div class="d-none d-print-block text-center mb-4">
            <h2 class="fw-bold mb-1">SM SPORT CENTER</h2>
            <p class="mb-0">Laporan Transaksi Persewaan Lapangan</p>
            <small class="text-secondary">Periode: <?= date('d/m/Y', strtotime($tgl_mulai)) ?> s/d <?= date('d/m/Y', strtotime($tgl_selesai)) ?></small>
            <hr class="mt-3">
        </div>

        <!-- Summary Card -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card card-custom p-3 border-start border-primary border-4">
                    <div class="text-secondary small mb-1">Total Pendapatan (Status Lunas)</div>
                    <div class="fs-4 fw-bold text-primary">Rp <?= number_format($grand_total, 0, ",", ".") ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-custom p-3 border-start border-success border-4">
                    <div class="text-secondary small mb-1">Total Transaksi Lunas</div>
                    <div class="fs-4 fw-bold text-success"><?= number_format($total_transaksi_lunas) ?> Transaksi</div>
                </div>
            </div>
        </div>

        <!-- Tabel Laporan -->
        <div class="card card-custom p-4">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Pelanggan</th>
                            <th scope="col">Lapangan</th>
                            <th scope="col">Tanggal Sewa</th>
                            <th scope="col">Jam</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($data_laporan) > 0): 
                            while ($row = mysqli_fetch_assoc($data_laporan)): 
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="fw-medium"><?= htmlspecialchars($row['nama']); ?></td>
                                <td><?= htmlspecialchars($row['nama_lapangan']); ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal_sewa'])); ?></td>
                                <td><?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?></td>
                                <td>
                                    <?php if ($row['status'] == 'Lunas'): ?>
                                        <span class="badge bg-success">Lunas</span>
                                    <?php elseif ($row['status'] == 'Pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Batal</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold">
                                    Rp <?= number_format($row['total_harga'], 0, ",", "."); ?>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">
                                    Tidak ada data transaksi pada periode yang dipilih.
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