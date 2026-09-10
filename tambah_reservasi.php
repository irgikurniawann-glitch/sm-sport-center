<?php
session_start();

// 1. Proteksi Session: Pengguna harus login terlebih dahulu
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Ambil data pilihan untuk Autocomplete Pelanggan & Dropdown Lapangan
$pelanggan_list = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");
$lapangan_list  = mysqli_query($conn, "SELECT * FROM lapangan ORDER BY nama_lapangan ASC");

$pesan_error = "";

if (isset($_POST['simpan'])) {
    $nama_pelanggan_input = trim($_POST['nama_pelanggan'] ?? '');
    $no_hp_input          = trim($_POST['no_hp'] ?? '');
    $email_input          = trim($_POST['email'] ?? '');
    $alamat_input         = trim($_POST['alamat'] ?? '');
    $id_lapangan          = $_POST['id_lapangan'] ?? '';
    $tanggal              = $_POST['tanggal'] ?? '';
    $mulai                = $_POST['jam_mulai'] ?? '';
    $durasi               = (int)($_POST['durasi_jam'] ?? 1); // Durasi bermain (dalam jam)
    $total                = $_POST['total'] ?? '';
    $jenis_payment        = $_POST['jenis_payment'] ?? 'Cash';

    // Hitung jam selesai otomatis di sisi PHP backend
    if (!empty($mulai) && $durasi > 0) {
        $selesai = date('H:i', strtotime("+$durasi hour", strtotime($mulai)));
    } else {
        $selesai = '';
    }

    // Validasi dasar di sisi backend
    if (empty($nama_pelanggan_input) || empty($id_lapangan) || empty($tanggal) || empty($mulai) || empty($durasi) || empty($total) || empty($jenis_payment)) {
        $pesan_error = "Harap isi seluruh kolom pendaftaran dengan benar.";
    } elseif ($durasi <= 0) {
        $pesan_error = "Durasi bermain minimal 1 jam.";
    } else {
        // 1. Cek atau Buat Pelanggan Baru berdasarkan Nama yang Diketik
        $stmt_check_p = $conn->prepare("SELECT id_pelanggan FROM pelanggan WHERE LOWER(nama) = LOWER(?) LIMIT 1");
        $stmt_check_p->bind_param("s", $nama_pelanggan_input);
        $stmt_check_p->execute();
        $res_check_p = $stmt_check_p->get_result();

        if ($res_check_p->num_rows > 0) {
            // Pelanggan sudah ada di database, update no_hp, email, dan alamat
            $p_row = $res_check_p->fetch_assoc();
            $id_pelanggan = $p_row['id_pelanggan'];

            $stmt_upd_p = $conn->prepare("UPDATE pelanggan SET no_hp = ?, email = ?, alamat = ? WHERE id_pelanggan = ?");
            $stmt_upd_p->bind_param("sssi", $no_hp_input, $email_input, $alamat_input, $id_pelanggan);
            $stmt_upd_p->execute();
        } else {
            // Pelanggan belum ada, otomatis tambahkan nama, no_hp, email, dan alamat ke tabel pelanggan
            $stmt_ins_p = $conn->prepare("INSERT INTO pelanggan (nama, no_hp, email, alamat) VALUES (?, ?, ?, ?)");
            $stmt_ins_p->bind_param("ssss", $nama_pelanggan_input, $no_hp_input, $email_input, $alamat_input);
            if ($stmt_ins_p->execute()) {
                $id_pelanggan = $stmt_ins_p->insert_id;
            } else {
                $pesan_error = "Gagal membuat data pelanggan baru: " . $conn->error;
            }
        }

        if (empty($pesan_error)) {
            // 2. Cek Jadwal Bentrok menggunakan Prepared Statement
            $stmt_cek = $conn->prepare("
                SELECT id_reservasi 
                FROM reservasi 
                WHERE id_lapangan = ? 
                  AND tanggal_sewa = ? 
                  AND (? < jam_selesai AND ? > jam_mulai)
            ");
            $stmt_cek->bind_param("isss", $id_lapangan, $tanggal, $mulai, $selesai);
            $stmt_cek->execute();
            $res_cek = $stmt_cek->get_result();

            if ($res_cek->num_rows > 0) {
                $pesan_error = "Jadwal pada jam dan lapangan tersebut sudah terisi! Silakan pilih jam atau lapangan lain.";
            } else {
                // 3. Insert Data Reservasi dengan jenis_payment
                $stmt_insert = $conn->prepare("
                    INSERT INTO reservasi (id_pelanggan, id_lapangan, tanggal_sewa, jam_mulai, jam_selesai, total_harga, jenis_payment) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_insert->bind_param("iisssis", $id_pelanggan, $id_lapangan, $tanggal, $mulai, $selesai, $total, $jenis_payment);
                
                if ($stmt_insert->execute()) {
                    header("Location: reservasi.php");
                    exit;
                } else {
                    $pesan_error = "Gagal menyimpan data ke basis data: " . $conn->error;
                }
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
    <title>Tambah Reservasi | SM Sport Center</title>

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
    <main class="container my-5" style="max-width: 720px;">
        
        <div class="mb-4">
            <h4 class="fw-bold mb-1">Tambah Reservasi Baru (Billing)</h4>
            <p class="text-secondary small mb-0">Isi data reservasi dan informasi pelanggan secara lengkap</p>
        </div>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= htmlspecialchars($pesan_error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-4">
            <form method="POST" action="">
                
                <!-- Informasi Pelanggan -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_pelanggan" class="form-control" list="list_pelanggan" placeholder="Ketik nama pelanggan..." value="<?= htmlspecialchars($_POST['nama_pelanggan'] ?? '') ?>" required autocomplete="off">
                        <datalist id="list_pelanggan">
                            <?php while ($p = mysqli_fetch_assoc($pelanggan_list)): ?>
                                <option value="<?= htmlspecialchars($p['nama']) ?>">
                            <?php endwhile; ?>
                        </datalist>
                        <div class="form-text text-muted small">Ketik nama baru atau pilih dari daftar.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Nomor HP / WhatsApp</label>
                        <input type="tel" name="no_hp" class="form-control" placeholder="Contoh: 081234567890" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Alamat</label>
                        <input type="text" name="alamat" class="form-control" placeholder="Kota / Alamat singkat" value="<?= htmlspecialchars($_POST['alamat'] ?? '') ?>">
                    </div>
                </div>

                <hr class="my-4 text-muted">

                <!-- Pilihan Lapangan & Waktu Sewa -->
                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-medium">Lapangan <span class="text-danger">*</span></label>
                        <select id="id_lapangan" name="id_lapangan" class="form-select" required>
                            <option value="" disabled <?= !isset($_POST['id_lapangan']) ? 'selected' : '' ?>>-- Pilih Lapangan --</option>
                            <?php mysqli_data_seek($lapangan_list, 0); while ($l = mysqli_fetch_assoc($lapangan_list)): ?>
                                <option value="<?= $l['id_lapangan'] ?>" data-harga="<?= $l['harga_per_jam'] ?>" <?= (isset($_POST['id_lapangan']) && $_POST['id_lapangan'] == $l['id_lapangan']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l['nama_lapangan']) ?> - (Rp <?= number_format($l['harga_per_jam'], 0, ',', '.') ?>/jam)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-medium">Tanggal Sewa <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $_POST['tanggal'] ?? date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label small fw-medium">Jam Mulai <span class="text-danger">*</span></label>
                        <input type="time" id="jam_mulai" name="jam_mulai" class="form-control" value="<?= $_POST['jam_mulai'] ?? date('H:i') ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-medium">Durasi (Jam) <span class="text-danger">*</span></label>
                        <input type="number" id="durasi_jam" name="durasi_jam" class="form-control" min="1" max="24" value="<?= $_POST['durasi_jam'] ?? '1' ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-medium">Jam Selesai</label>
                        <input type="time" id="jam_selesai" class="form-control bg-light" readonly>
                        <div class="form-text text-muted small">Otomatis</div>
                    </div>
                </div>

                <!-- Total Harga & Jenis Payment -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Total Harga (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary">Rp</span>
                            <input type="number" id="total" name="total" class="form-control" placeholder="0" value="<?= $_POST['total'] ?? '' ?>" required>
                        </div>
                        <div class="form-text text-muted small">Otomatis terhitung dari durasi x tarif</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Jenis Payment <span class="text-danger">*</span></label>
                        <select name="jenis_payment" class="form-select" required>
                            <option value="Cash" <?= (isset($_POST['jenis_payment']) && $_POST['jenis_payment'] == 'Cash') ? 'selected' : '' ?>>Cash / Tunai</option>
                            <option value="QRIS" <?= (isset($_POST['jenis_payment']) && $_POST['jenis_payment'] == 'QRIS') ? 'selected' : '' ?>>QRIS</option>
                        </select>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="reservasi.php" class="btn btn-light px-4 border">Batal</a>
                    <button type="submit" name="simpan" class="btn btn-primary px-4">
                        <i class="bi bi-calendar-plus me-1"></i> Simpan Reservasi
                    </button>
                </div>

            </form>
        </div>

    </main>

    <!-- JavaScript untuk Menghitung Jam Selesai & Total Harga Secara Otomatis -->
    <script>
        function kalkulasiReservasi() {
            // 1. Hitung Jam Selesai
            const inputMulai = document.getElementById('jam_mulai').value;
            const durasi = parseInt(document.getElementById('durasi_jam').value) || 0;
            const inputSelesai = document.getElementById('jam_selesai');

            if (inputMulai && durasi > 0) {
                const [jam, menit] = inputMulai.split(':').map(Number);
                let totalJam = jam + durasi;
                let jamHasil = totalJam % 24;

                const jamFormatted = String(jamHasil).padStart(2, '0');
                const menitFormatted = String(menit).padStart(2, '0');

                inputSelesai.value = `${jamFormatted}:${menitFormatted}`;
            } else {
                inputSelesai.value = '';
            }

            // 2. Hitung Total Harga Otomatis
            const selectLapangan = document.getElementById('id_lapangan');
            const selectedOption = selectLapangan.options[selectLapangan.selectedIndex];
            const hargaPerJam = selectedOption ? parseFloat(selectedOption.getAttribute('data-harga')) || 0 : 0;
            const inputTotal = document.getElementById('total');

            if (hargaPerJam > 0 && durasi > 0) {
                inputTotal.value = hargaPerJam * durasi;
            } else {
                inputTotal.value = '';
            }
        }

        // Jalankan fungsi kalkulasi saat input Lapangan, Jam Mulai, atau Durasi diubah
        document.getElementById('id_lapangan').addEventListener('change', kalkulasiReservasi);
        document.getElementById('jam_mulai').addEventListener('input', kalkulasiReservasi);
        document.getElementById('durasi_jam').addEventListener('input', kalkulasiReservasi);

        // Jalankan sekali saat halaman pertama kali dimuat
        document.addEventListener('DOMContentLoaded', kalkulasiReservasi);
    </script>

</body>
</html>