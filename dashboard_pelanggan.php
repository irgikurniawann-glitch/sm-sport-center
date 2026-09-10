<?php
session_start();

// Proteksi Session: Hanya pelanggan yang boleh mengakses
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

$id_pelanggan = $_SESSION['id_pelanggan'];

// Ambil data detail pelanggan dari DB
$stmt_p = $conn->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ? LIMIT 1");
$stmt_p->bind_param("i", $id_pelanggan);
$stmt_p->execute();
$data_pelanggan = $stmt_p->get_result()->fetch_assoc();

$nama_pelanggan  = $data_pelanggan['nama'] ?? $_SESSION['nama'];
$email_pelanggan = $data_pelanggan['email'] ?? '';
$nohp_pelanggan  = $data_pelanggan['no_hp'] ?? '';

$pesan_sukses = "";
$pesan_error = "";
$last_booking_id = null;

// Tangani Form Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking'])) {
    $id_lapangan    = $_POST['id_lapangan'];
    $tanggal_sewa   = $_POST['tanggal_sewa'];
    $jam_mulai      = $_POST['jam_mulai'];
    $durasi_jam     = floatval($_POST['durasi_jam']);
    $jenis_payment  = $_POST['jenis_payment'];

    if (empty($id_lapangan) || empty($tanggal_sewa) || empty($jam_mulai) || $durasi_jam <= 0 || empty($jenis_payment)) {
        $pesan_error = "Harap isi semua kolom pemesanan dengan benar.";
    } else {
        // Hitung Jam Selesai secara Otomatis
        $jam_selesai = date('H:i:s', strtotime($jam_mulai . " +{$durasi_jam} hours"));

        // Cek Bentrok Jadwal di Tanggal & Lapangan yang sama
        $stmt_cek = $conn->prepare("
            SELECT * FROM reservasi 
            WHERE id_lapangan = ? AND tanggal_sewa = ? 
            AND ((jam_mulai < ? AND jam_selesai > ?) OR (jam_mulai < ? AND jam_selesai > ?))
        ");
        $stmt_cek->bind_param("isssss", $id_lapangan, $tanggal_sewa, $jam_selesai, $jam_mulai, $jam_selesai, $jam_selesai);
        $stmt_cek->execute();
        $res_cek = $stmt_cek->get_result();

        if ($res_cek->num_rows > 0) {
            $pesan_error = "Jadwal pada jam tersebut sudah terisi. Silakan pilih waktu lain.";
        } else {
            // Hitung total harga berdasarkan harga per jam
            $stmt_lap = $conn->prepare("SELECT harga_per_jam FROM lapangan WHERE id_lapangan = ?");
            $stmt_lap->bind_param("i", $id_lapangan);
            $stmt_lap->execute();
            $data_lap = $stmt_lap->get_result()->fetch_assoc();

            $harga_per_jam = $data_lap['harga_per_jam'] ?? 50000;
            $total_harga = $durasi_jam * $harga_per_jam;

            // Simpan ke database
            $stmt_ins = $conn->prepare("
                INSERT INTO reservasi (id_pelanggan, id_lapangan, tanggal_sewa, jam_mulai, jam_selesai, total_harga, jenis_payment) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_ins->bind_param("iisssds", $id_pelanggan, $id_lapangan, $tanggal_sewa, $jam_mulai, $jam_selesai, $total_harga, $jenis_payment);

            if ($stmt_ins->execute()) {
                $last_booking_id = $conn->insert_id;
                $pesan_sukses = "Booking berhasil! Silakan cetak bukti reservasi Anda di bawah.";
            } else {
                $pesan_error = "Gagal memproses booking. Silakan coba lagi.";
            }
        }
    }
}

// Filter tanggal untuk cek jadwal
$tgl_filter = $_GET['tgl_filter'] ?? date('Y-m-d');

// Ambil daftar lapangan
$lapangan_res = mysqli_query($conn, "SELECT * FROM lapangan ORDER BY nama_lapangan ASC");

// Ambil riwayat booking pelanggan aktif
$stmt_riwayat = $conn->prepare("
    SELECT r.*, l.nama_lapangan 
    FROM reservasi r 
    JOIN lapangan l ON r.id_lapangan = l.id_lapangan 
    WHERE r.id_pelanggan = ? 
    ORDER BY r.tanggal_sewa DESC, r.jam_mulai DESC
");
$stmt_riwayat->bind_param("i", $id_pelanggan);
$stmt_riwayat->execute();
$riwayat_res = $stmt_riwayat->get_result();

// Ambil jadwal terisi sesuai tanggal filter
$stmt_jadwal = $conn->prepare("
    SELECT r.*, l.nama_lapangan 
    FROM reservasi r 
    JOIN lapangan l ON r.id_lapangan = l.id_lapangan 
    WHERE r.tanggal_sewa = ? 
    ORDER BY r.jam_mulai ASC
");
$stmt_jadwal->bind_param("s", $tgl_filter);
$stmt_jadwal->execute();
$jadwal_res = $stmt_jadwal->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Area Pelanggan | SM Sport Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; color: #0f172a; }
        .card-custom { border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff; }
        
        /* CSS Khusus untuk Cetak Bukti */
        @media print {
            body * { visibility: hidden; }
            #printableArea, #printableArea * { visibility: visible; }
            #printableArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 20px;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom py-3 no-print">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="#">
                <i class="bi bi-dribbble"></i> SM Sport Center
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small">Selamat Datang, <strong class="text-dark"><?= htmlspecialchars($nama_pelanggan) ?></strong></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
            </div>
        </div>
    </nav>

    <main class="container my-5 no-print">

        <!-- Notifikasi -->
        <?php if (!empty($pesan_sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($pesan_sukses) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($pesan_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- Form Booking -->
            <div class="col-lg-6">
                <div class="card card-custom p-4 shadow-sm">
                    <h5 class="fw-bold mb-3"><i class="bi bi-calendar-plus text-primary me-2"></i>Booking Lapangan</h5>
                    <form method="POST" action="">

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium text-secondary">Nama Pemesan</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($nama_pelanggan) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium text-secondary">No. Handphone</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($nohp_pelanggan) ?>" readonly>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Pilih Lapangan</label>
                                <select name="id_lapangan" class="form-select" required>
                                    <option value="">-- Pilih Lapangan --</option>
                                    <?php 
                                    mysqli_data_seek($lapangan_res, 0);
                                    while ($l = mysqli_fetch_assoc($lapangan_res)): 
                                    ?>
                                        <option value="<?= $l['id_lapangan'] ?>">
                                            <?= htmlspecialchars($l['nama_lapangan']) ?> (Rp <?= number_format($l['harga_per_jam'] ?? 0, 0, ',', '.') ?>/jam)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Tanggal Sewa</label>
                                <input type="date" name="tanggal_sewa" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Waktu & Durasi Main -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Jam Mulai</label>
                                <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" required onchange="hitungJamSelesai()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Main Berapa Jam?</label>
                                <select name="durasi_jam" id="durasi_jam" class="form-select" required onchange="hitungJamSelesai()">
                                    <option value="1">1 Jam</option>
                                    <option value="2">2 Jam</option>
                                    <option value="3">3 Jam</option>
                                    <option value="4">4 Jam</option>
                                    <option value="5">5 Jam</option>
                                </select>
                            </div>
                        </div>

                        <!-- Estimasi Jam Selesai -->
                        <div class="alert alert-info py-2 small border-0 mb-3" id="box_estimasi" style="display: none;">
                            <i class="bi bi-clock me-1"></i> Estimasi Jam Selesai: <strong id="txt_jam_selesai">-</strong>
                        </div>

                        <!-- Metode Pembayaran Ringkas -->
                        <div class="mb-4">
                            <label class="form-label small fw-medium">Metode Pembayaran</label>
                            <select name="jenis_payment" class="form-select" required>
                                <option value="QRIS">QRIS</option>
                                <option value="Cash / Tunai">Cash / Tunai</option>
                            </select>
                        </div>

                        <button type="submit" name="booking" class="btn btn-primary w-100 fw-medium py-2">
                            <i class="bi bi-check-circle me-1"></i> Kirim Pemesanan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Cek Jadwal & Riwayat -->
            <div class="col-lg-6">
                
                <div class="card card-custom p-4 shadow-sm mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Jadwal Terisi</h5>
                        <form method="GET" class="d-flex align-items-center gap-2">
                            <input type="date" name="tgl_filter" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_filter) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Cek</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Lapangan</th>
                                    <th>Jam</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($jadwal_res->num_rows > 0): ?>
                                    <?php while ($j = $jadwal_res->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-medium"><?= htmlspecialchars($j['nama_lapangan']) ?></td>
                                            <td><?= substr($j['jam_mulai'], 0, 5) ?> - <?= substr($j['jam_selesai'], 0, 5) ?></td>
                                            <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle">Terisi</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-3 text-secondary small">
                                            Tidak ada booking pada tanggal <?= date('d/m/Y', strtotime($tgl_filter)) ?>.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card card-custom p-4 shadow-sm">
                    <h5 class="fw-bold mb-3"><i class="bi bi-journal-check text-primary me-2"></i>Riwayat Booking Saya</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Lapangan</th>
                                    <th>Jam</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($riwayat_res->num_rows > 0): ?>
                                    <?php while ($r = $riwayat_res->fetch_assoc()): 
                                        $id_res = $r['id_reservasi'] ?? $r['id'];
                                    ?>
                                        <tr>
                                            <td class="small"><?= date('d/m/Y', strtotime($r['tanggal_sewa'])) ?></td>
                                            <td class="fw-medium small"><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                                            <td class="small"><?= substr($r['jam_mulai'], 0, 5) ?> - <?= substr($r['jam_selesai'], 0, 5) ?></td>
                                            <td class="small fw-semibold text-success">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 12px;" 
                                                        onclick='bukaModalBukti(<?= json_encode([
                                                            "id" => $id_res,
                                                            "nama" => $nama_pelanggan,
                                                            "hp" => $nohp_pelanggan,
                                                            "lapangan" => $r["nama_lapangan"],
                                                            "tanggal" => date("d/m/Y", strtotime($r["tanggal_sewa"])),
                                                            "jam" => substr($r["jam_mulai"], 0, 5) . " - " . substr($r["jam_selesai"], 0, 5),
                                                            "total" => "Rp " . number_format($r["total_harga"], 0, ",", "."),
                                                            "payment" => $r["jenis_payment"]
                                                        ]) ?>)'>
                                                    <i class="bi bi-receipt"></i> Bukti
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-secondary small">Belum ada riwayat booking.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- Modal Bukti Reservasi -->
    <div class="modal fade no-print" id="modalBukti" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold text-primary"><i class="bi bi-receipt me-1"></i> Bukti Reservasi Lapangan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="printableArea">
                    <div class="text-center mb-3 pb-3 border-bottom">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-dribbble text-primary"></i> SM SPORT CENTER</h5>
                        <small class="text-muted">Bukti Pemesanan Lapangan (Booking Mandiri)</small>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">No. Transaksi:</span>
                        <strong id="m_id">#000</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Nama Pemesan:</span>
                        <strong id="m_nama">-</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">No. Handphone:</span>
                        <span id="m_hp">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Lapangan:</span>
                        <strong id="m_lapangan" class="text-primary">-</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Tanggal Main:</span>
                        <span id="m_tanggal">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Waktu / Jam:</span>
                        <span id="m_jam">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Metode Pembayaran:</span>
                        <span id="m_payment" class="badge bg-light text-dark border">-</span>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Total Biaya:</span>
                        <h5 class="fw-bold text-success mb-0" id="m_total">Rp 0</h5>
                    </div>
                    <div class="alert alert-light border border-dashed p-2 mt-3 text-center small text-muted mb-0">
                        Harap tunjukkan bukti reservasi ini kepada petugas di lokasi saat tiba.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Cetak Bukti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function hitungJamSelesai() {
            const jamMulaiInput = document.getElementById('jam_mulai').value;
            const durasi = parseFloat(document.getElementById('durasi_jam').value);
            const boxEstimasi = document.getElementById('box_estimasi');
            const txtJamSelesai = document.getElementById('txt_jam_selesai');

            if (jamMulaiInput) {
                const [jam, menit] = jamMulaiInput.split(':').map(Number);
                let totalJam = jam + durasi;
                let jamSelesaiFix = totalJam % 24;

                let jamStr = String(jamSelesaiFix).padStart(2, '0');
                let menitStr = String(menit).padStart(2, '0');

                txtJamSelesai.innerText = jamStr + ':' + menitStr;
                boxEstimasi.style.display = 'block';
            } else {
                boxEstimasi.style.display = 'none';
            }
        }

        function bukaModalBukti(data) {
            document.getElementById('m_id').innerText = '#' + data.id;
            document.getElementById('m_nama').innerText = data.nama;
            document.getElementById('m_hp').innerText = data.hp;
            document.getElementById('m_lapangan').innerText = data.lapangan;
            document.getElementById('m_tanggal').innerText = data.tanggal;
            document.getElementById('m_jam').innerText = data.jam;
            document.getElementById('m_payment').innerText = data.payment;
            document.getElementById('m_total').innerText = data.total;

            var modal = new bootstrap.Modal(document.getElementById('modalBukti'));
            modal.show();
        }
    </script>
</body>
</html>