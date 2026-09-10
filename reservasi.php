<?php
session_start();

// 1. Proteksi Session: Jika belum login, tendang balik ke index.php
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');
$waktu_sekarang = date('Y-m-d H:i:s');

$cari = $_GET['cari'] ?? '';

// Menggunakan Prepared Statement agar aman dari SQL Injection
if (!empty($cari)) {
    $stmt = $conn->prepare("
        SELECT reservasi.*, pelanggan.nama, lapangan.nama_lapangan
        FROM reservasi
        JOIN pelanggan ON reservasi.id_pelanggan = pelanggan.id_pelanggan
        JOIN lapangan ON reservasi.id_lapangan = lapangan.id_lapangan
        WHERE pelanggan.nama LIKE ? OR lapangan.nama_lapangan LIKE ?
        ORDER BY tanggal_sewa DESC, jam_mulai DESC, id_reservasi DESC
    ");
    $param_cari = "%" . $cari . "%";
    $stmt->bind_param("ss", $param_cari, $param_cari);
    $stmt->execute();
    $data = $stmt->get_result();
} else {
    $data = mysqli_query($conn, "
        SELECT reservasi.*, pelanggan.nama, lapangan.nama_lapangan
        FROM reservasi
        JOIN pelanggan ON reservasi.id_pelanggan = pelanggan.id_pelanggan
        JOIN lapangan ON reservasi.id_lapangan = lapangan.id_lapangan
        ORDER BY tanggal_sewa DESC, jam_mulai DESC, id_reservasi DESC
    ");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Reservasi | SM Sport Center</title>

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

        .table > :not(caption) > * > * {
            padding: 0.75rem 1rem;
        }

        .badge {
            font-weight: 500;
            padding: 0.4em 0.75em;
        }

        /* Highlight baris yang sudah habis waktunya */
        .row-expired {
            background-color: #f8fafc !important;
            opacity: 0.75;
        }

        /* Animasi pulse untuk status Sedang Main */
        @keyframes pulse-play {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .badge-playing {
            animation: pulse-play 1.5s infinite;
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
        
        <!-- Header Page & Action -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-1">Data Reservasi</h4>
                <p class="text-secondary small mb-0">Kelola dan pantau seluruh pemesanan lapangan (Jam saat ini: <strong><?= date('H:i') ?> WIB</strong>)</p>
            </div>
            <!-- Koneksi ke file tambah_reservasi.php -->
            <a href="tambah_reservasi.php" class="btn btn-primary px-3 rounded-3">
                <i class="bi bi-plus-lg me-1"></i> Tambah Reservasi
            </a>
        </div>

        <!-- Filter & Table Card -->
        <div class="card card-custom p-4">
            
            <!-- Form Pencarian -->
            <form method="GET" action="reservasi.php" class="mb-4">
                <div class="row g-2">
                    <div class="col-md-5 col-lg-4 ms-auto">
                        <div class="input-group">
                            <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama pelanggan atau lapangan..." value="<?= htmlspecialchars($cari) ?>">
                            <button class="btn btn-primary btn-sm px-3" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($cari)): ?>
                                <a href="reservasi.php" class="btn btn-outline-secondary btn-sm" title="Reset">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Table Data -->
            <div class="table-responsive">
                <table class="table align-middle table-hover border-top mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th scope="col" width="60">ID</th>
                            <th scope="col">Pelanggan</th>
                            <th scope="col">Lapangan</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Jam Operasional</th>
                            <th scope="col">Total Biaya</th>
                            <th scope="col">Status Bayar</th>
                            <th scope="col">Status Billing</th>
                            <th scope="col" class="text-end" width="140">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($data && mysqli_num_rows($data) > 0): ?>
                            <?php while ($r = mysqli_fetch_assoc($data)): ?>
                                <?php
                                    // Logika perhitungan status billing waktu
                                    $waktu_mulai   = $r['tanggal_sewa'] . ' ' . $r['jam_mulai'];
                                    $waktu_selesai = $r['tanggal_sewa'] . ' ' . $r['jam_selesai'];

                                    $is_expired = ($waktu_sekarang >= $waktu_selesai);
                                    $is_playing = ($waktu_sekarang >= $waktu_mulai && $waktu_sekarang < $waktu_selesai);
                                ?>
                                <tr class="<?= ($is_expired && $r['status'] != 'Batal') ? 'row-expired' : '' ?>">
                                    <td class="text-secondary small">#<?= $r['id_reservasi']; ?></td>
                                    <td class="fw-medium"><?= htmlspecialchars($r['nama']); ?></td>
                                    <td><?= htmlspecialchars($r['nama_lapangan']); ?></td>
                                    <td><?= date('d M Y', strtotime($r['tanggal_sewa'])); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-normal">
                                            <?= substr($r['jam_mulai'], 0, 5); ?> - <?= substr($r['jam_selesai'], 0, 5); ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold text-success">Rp <?= number_format($r['total_harga'], 0, ",", "."); ?></td>
                                    <td>
                                        <?php if ($r['status'] == "Lunas"): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Lunas</span>
                                        <?php elseif ($r['status'] == "Pending"): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Batal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- Indikator Waktu Billing -->
                                        <?php if ($r['status'] == "Batal"): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Batal</span>
                                        <?php elseif ($is_expired): ?>
                                            <span class="badge bg-secondary text-white"><i class="bi bi-clock-history me-1"></i> Habis</span>
                                        <?php elseif ($is_playing): ?>
                                            <span class="badge bg-info text-dark border border-info badge-playing"><i class="bi bi-play-circle-fill me-1"></i> Sedang Main</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-hourglass-split me-1"></i> Belum Main</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <!-- Koneksi ke edit_reservasi.php & hapus_reservasi.php -->
                                        <a href="edit_reservasi.php?id=<?= $r['id_reservasi']; ?>" class="btn btn-outline-warning btn-sm me-1" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="hapus_reservasi.php?id=<?= $r['id_reservasi']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus reservasi ini?')" class="btn btn-outline-danger btn-sm" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-secondary">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                    Data reservasi tidak ditemukan.
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