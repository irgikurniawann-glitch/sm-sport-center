<?php
session_start();

// Proteksi Session: Pengguna wajib login
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Validasi ID Reservasi dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: reservasi.php");
    exit;
}

$id_reservasi = $_GET['id'];

// Ambil data reservasi beserta nama pelanggan saat ini
$stmt_get = $conn->prepare("
    SELECT r.*, p.nama AS nama_pelanggan 
    FROM reservasi r 
    LEFT JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan 
    WHERE r.id_reservasi = ?
");
$stmt_get->bind_param("i", $id_reservasi);
$stmt_get->execute();
$res_get = $stmt_get->get_result();

if ($res_get->num_rows === 0) {
    header("Location: reservasi.php");
    exit;
}

$r = $res_get->fetch_assoc();
$pesan_error = "";

// Ambil daftar Pelanggan untuk autocomplete (datalist) & Dropdown Lapangan
$pelanggan_list = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");
$lapangan_list  = mysqli_query($conn, "SELECT * FROM lapangan ORDER BY nama_lapangan ASC");

// Proses Update Data Saat Form Dikirimkan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pelanggan_input = trim($_POST['nama_pelanggan'] ?? '');
    $id_lapangan          = $_POST['id_lapangan'] ?? '';
    $tanggal_sewa         = $_POST['tanggal_sewa'] ?? '';
    $jam_mulai            = $_POST['jam_mulai'] ?? '';
    $jam_selesai          = $_POST['jam_selesai'] ?? '';
    $total_harga_input    = $_POST['total_harga'] ?? '';
    $status               = $_POST['status'] ?? 'Pending';

    if (empty($nama_pelanggan_input) || empty($id_lapangan) || empty($tanggal_sewa) || empty($jam_mulai) || empty($jam_selesai)) {
        $pesan_error = "Harap isi semua kolom formulir dengan lengkap.";
    } elseif ($jam_selesai <= $jam_mulai) {
        $pesan_error = "Jam selesai harus lebih besar dari jam mulai.";
    } else {
        // 1. Cek atau Buat Pelanggan Baru berdasarkan Nama yang Diketik
        $stmt_check_p = $conn->prepare("SELECT id_pelanggan FROM pelanggan WHERE LOWER(nama) = LOWER(?) LIMIT 1");
        $stmt_check_p->bind_param("s", $nama_pelanggan_input);
        $stmt_check_p->execute();
        $res_check_p = $stmt_check_p->get_result();

        if ($res_check_p->num_rows > 0) {
            // Pelanggan sudah ada
            $p_row = $res_check_p->fetch_assoc();
            $id_pelanggan = $p_row['id_pelanggan'];
        } else {
            // Pelanggan belum ada, buat baru otomatis
            $stmt_ins_p = $conn->prepare("INSERT INTO pelanggan (nama) VALUES (?)");
            $stmt_ins_p->bind_param("s", $nama_pelanggan_input);
            if ($stmt_ins_p->execute()) {
                $id_pelanggan = $stmt_ins_p->insert_id;
            } else {
                $pesan_error = "Gagal membuat data pelanggan baru: " . $conn->error;
            }
        }

        if (empty($pesan_error)) {
            // 2. Tentukan Total Harga (Manual atau Otomatis)
            if ($total_harga_input !== '' && is_numeric($total_harga_input)) {
                // Jika diedit manual oleh admin
                $total_harga = (float) $total_harga_input;
            } else {
                // Hitung otomatis berdasarkan tarif jam jika input manual kosong
                $stmt_lap = $conn->prepare("SELECT harga_per_jam FROM lapangan WHERE id_lapangan = ?");
                $stmt_lap->bind_param("i", $id_lapangan);
                $stmt_lap->execute();
                $lap = $stmt_lap->get_result()->fetch_assoc();
                $harga_per_jam = $lap['harga_per_jam'] ?? 0;

                $waktu_mulai   = strtotime($jam_mulai);
                $waktu_selesai = strtotime($jam_selesai);
                $durasi_jam    = round(($waktu_selesai - $waktu_mulai) / 3600, 2);
                $total_harga   = $durasi_jam * $harga_per_jam;
            }

            // 3. Update ke Database
            $stmt_update = $conn->prepare("
                UPDATE reservasi 
                SET id_pelanggan = ?, id_lapangan = ?, tanggal_sewa = ?, jam_mulai = ?, jam_selesai = ?, total_harga = ?, status = ? 
                WHERE id_reservasi = ?
            ");
            $stmt_update->bind_param("iisssdsi", $id_pelanggan, $id_lapangan, $tanggal_sewa, $jam_mulai, $jam_selesai, $total_harga, $status, $id_reservasi);

            if ($stmt_update->execute()) {
                header("Location: reservasi.php");
                exit;
            } else {
                $pesan_error = "Gagal memperbarui reservasi: " . $conn->error;
            }
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
            <a href="reservasi.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </nav>

    <main class="container my-5" style="max-width: 650px;">

        <div class="mb-4">
            <h4 class="fw-bold mb-1">Edit Data Reservasi</h4>
            <p class="text-secondary small mb-0">Ubah jadwal, pilihan lapangan, total harga, atau status untuk transaksi #<?= htmlspecialchars($r['id_reservasi']) ?></p>
        </div>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($pesan_error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-4">
            <form method="POST" action="edit_reservasi.php?id=<?= $id_reservasi ?>">

                <!-- Ketik Nama Pelanggan (Manual/Autocomplete) -->
                <div class="mb-3">
                    <label class="form-label small fw-medium">Pelanggan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pelanggan" class="form-control" list="list_pelanggan" placeholder="Ketik nama pelanggan..." value="<?= htmlspecialchars($r['nama_pelanggan'] ?? '') ?>" required autocomplete="off">
                    <datalist id="list_pelanggan">
                        <?php while ($p = mysqli_fetch_assoc($pelanggan_list)): ?>
                            <option value="<?= htmlspecialchars($p['nama']) ?>">
                        <?php endwhile; ?>
                    </datalist>
                    <div class="form-text text-muted small">Ketik nama baru atau pilih dari daftar pelanggan yang ada.</div>
                </div>

                <!-- Pilih Lapangan -->
                <div class="mb-3">
                    <label class="form-label small fw-medium">Lapangan <span class="text-danger">*</span></label>
                    <select name="id_lapangan" class="form-select" required>
                        <option value="">-- Pilih Lapangan --</option>
                        <?php while ($l = mysqli_fetch_assoc($lapangan_list)): ?>
                            <option value="<?= $l['id_lapangan'] ?>" <?= ($l['id_lapangan'] == $r['id_lapangan']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['nama_lapangan']) ?> - (Rp <?= number_format($l['harga_per_jam'], 0, ',', '.') ?>/jam)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Tanggal & Jam -->
                <div class="mb-3">
                    <label class="form-label small fw-medium">Tanggal Sewa <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_sewa" class="form-control" value="<?= htmlspecialchars($r['tanggal_sewa']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Jam Mulai <span class="text-danger">*</span></label>
                        <input type="time" name="jam_mulai" class="form-control" value="<?= htmlspecialchars(substr($r['jam_mulai'], 0, 5)) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Jam Selesai <span class="text-danger">*</span></label>
                        <input type="time" name="jam_selesai" class="form-control" value="<?= htmlspecialchars(substr($r['jam_selesai'], 0, 5)) ?>" required>
                    </div>
                </div>

                <!-- Total Harga & Status Pembayaran -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Total Harga (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary">Rp</span>
                            <input type="number" name="total_harga" class="form-control" placeholder="0" value="<?= htmlspecialchars((int) $r['total_harga']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Status Transaksi <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Pending" <?= ($r['status'] == 'Pending') ? 'selected' : '' ?>>Pending (Menunggu)</option>
                            <option value="Lunas" <?= ($r['status'] == 'Lunas') ? 'selected' : '' ?>>Lunas</option>
                            <option value="Batal" <?= ($r['status'] == 'Batal') ? 'selected' : '' ?>>Batal</option>
                        </select>
                    </div>
                </div>

                <!-- Tombol Simpan -->
                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="reservasi.php" class="btn btn-light px-4 border">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>

    </main>

</body>

</html>