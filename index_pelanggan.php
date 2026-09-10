<?php
session_start();
if (!isset($_SESSION['login_pelanggan'])) {
    header("Location: login_pelanggan.php");
    exit;
}

include 'koneksi.php';

$tanggal_pilih = $_GET['tanggal'] ?? date('Y-m-d');
$id_lapangan_pilih = $_GET['id_lapangan'] ?? '';

// Ambil daftar lapangan
$lapangan_list = mysqli_query($conn, "SELECT * FROM lapangan ORDER BY nama_lapangan ASC");

// Query jadwal yang sudah dibooking pada tanggal terpilih
$query_jadwal = "
    SELECT reservasi.*, lapangan.nama_lapangan 
    FROM reservasi 
    JOIN lapangan ON reservasi.id_lapangan = lapangan.id_lapangan
    WHERE reservasi.tanggal_sewa = '$tanggal_pilih' AND reservasi.status <> 'Batal'
";

if (!empty($id_lapangan_pilih)) {
    $query_jadwal .= " AND reservasi.id_lapangan = '$id_lapangan_pilih'";
}

$query_jadwal .= " ORDER BY jam_mulai ASC";
$jadwal_res = mysqli_query($conn, $query_jadwal);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Lapangan | SM Sport Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, sans-serif; }
        .card-custom { border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; }
    </style>
</head>
<body>

    <!-- Navigasi Pelanggan -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="#">
                <i class="bi bi-dribbble fs-5"></i> SM Sport Center
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="small text-muted">Halo, <strong><?= htmlspecialchars($_SESSION['nama_pelanggan']) ?></strong></span>
                <a href="booking_mandiri.php" class="btn btn-primary btn-sm rounded-pill px-3"><i class="bi bi-calendar-plus me-1"></i> Booking Mandiri</a>
                <a href="logout_pelanggan.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Keluar</a>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Cek Jadwal Lapangan</h4>
                <p class="text-secondary small mb-0">Lihat ketersediaan jam sebelum membuat pesanan baru</p>
            </div>
        </div>

        <!-- Filter Tanggal & Lapangan -->
        <div class="card card-custom p-4 mb-4">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Pilih Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal_pilih) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Pilih Lapangan</label>
                    <select name="id_lapangan" class="form-select">
                        <option value="">-- Semua Lapangan --</option>
                        <?php mysqli_data_seek($lapangan_list, 0); while ($l = mysqli_fetch_assoc($lapangan_list)): ?>
                            <option value="<?= $l['id_lapangan'] ?>" <?= ($id_lapangan_pilih == $l['id_lapangan']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['nama_lapangan']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i> Tampilkan Jadwal</button>
                </div>
            </form>
        </div>

        <!-- Tabel Jadwal Terisi -->
        <div class="card card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-calendar-check me-2 text-primary"></i>Jadwal Terisi Tanggal <?= date('d M Y', strtotime($tanggal_pilih)) ?></h6>
            <div class="table-responsive">
                <table class="table align-middle border-top mb-0">
                    <thead class="table-light small text-secondary">
                        <tr>
                            <th>Lapangan</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <th>Status Slot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($jadwal_res) > 0): ?>
                            <?php while ($j = mysqli_fetch_assoc($jadwal_res)): ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($j['nama_lapangan']) ?></td>
                                    <td><?= substr($j['jam_mulai'], 0, 5) ?> WIB</td>
                                    <td><?= substr($j['jam_selesai'], 0, 5) ?> WIB</td>
                                    <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle">Terisi / Booked</span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Belum ada booking pada tanggal dan lapangan ini. Semua jam tersedia!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>