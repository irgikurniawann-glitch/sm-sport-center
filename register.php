<?php
session_start();

// Jika pengguna sudah login, langsung arahkan sesuai peran/role
if (isset($_SESSION['login'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_pelanggan.php");
    }
    exit;
}

include 'koneksi.php';

$pesan_sukses = "";
$pesan_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $no_hp    = trim($_POST['no_hp']);
    $alamat   = trim($_POST['alamat']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    // Validasi dasar
    if (empty($nama) || empty($email) || empty($no_hp) || empty($alamat) || empty($password) || empty($konfirmasi_password)) {
        $pesan_error = "Semua kolom wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan_error = "Format email tidak valid!";
    } elseif ($password !== $konfirmasi_password) {
        $pesan_error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek apakah email atau No HP sudah terdaftar
        $stmt_cek = $conn->prepare("SELECT id_pelanggan FROM pelanggan WHERE email = ? OR no_hp = ?");
        $stmt_cek->bind_param("ss", $email, $no_hp);
        $stmt_cek->execute();
        $res_cek = $stmt_cek->get_result();

        if ($res_cek->num_rows > 0) {
            $pesan_error = "Email atau Nomor HP sudah terdaftar. Silakan gunakan email/HP lain atau langsung Login.";
        } else {
            // Hash password untuk keamanan
            $password_hashed = password_hash($password, PASSWORD_BCRYPT);

            // Simpan data akun pelanggan baru beserta alamat
            $stmt_ins = $conn->prepare("INSERT INTO pelanggan (nama, email, no_hp, alamat, password) VALUES (?, ?, ?, ?, ?)");
            $stmt_ins->bind_param("sssss", $nama, $email, $no_hp, $alamat, $password_hashed);

            if ($stmt_ins->execute()) {
                $pesan_sukses = "Pendaftaran akun berhasil! Silakan login untuk melanjutkan.";
            } else {
                $pesan_error = "Gagal membuat akun. Silakan coba beberapa saat lagi.";
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
    <title>Daftar Akun Baru | SM Sport Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: system-ui, -apple-system, sans-serif;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-custom {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            width: 100%;
            max-width: 480px;
        }
    </style>
</head>
<body>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-12 d-flex justify-content-center">
                <div class="card card-custom p-4 shadow-sm">
                    
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="text-primary display-6 mb-2">
                            <i class="bi bi-dribbble"></i>
                        </div>
                        <h4 class="fw-bold">SM Sport Center</h4>
                        <p class="text-secondary small">Buat akun pelanggan baru untuk booking lapangan</p>
                    </div>

                    <!-- Alert Notifikasi -->
                    <?php if (!empty($pesan_sukses)): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3 small" role="alert">
                            <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($pesan_sukses) ?>
                            <a href="index.php" class="fw-bold text-success d-block mt-1">Klik di sini untuk Login &rarr;</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($pesan_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3 small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($pesan_error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Form Pendaftaran -->
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">No. Handphone (WA)</label>
                                <input type="tel" name="no_hp" class="form-control" placeholder="081234567890" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Input Alamat -->
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Jl. Merdeka No. 12, Bekasi" required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="******" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Konfirmasi Password</label>
                                <input type="password" name="konfirmasi_password" class="form-control" placeholder="******" required>
                            </div>
                        </div>

                        <button type="submit" name="register" class="btn btn-primary w-100 fw-medium py-2 mt-2">
                            <i class="bi bi-person-plus me-1"></i> Daftar Sekarang
                        </button>
                    </form>

                    <!-- Link Login -->
                    <div class="text-center mt-4 border-top pt-3">
                        <span class="small text-secondary">Sudah punya akun?</span>
                        <a href="index.php" class="small fw-bold text-decoration-none ms-1">Login di sini</a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>