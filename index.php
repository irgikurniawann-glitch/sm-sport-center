<?php
session_start();

// Jika sudah login, alihkan otomatis sesuai peran (role)
if (isset($_SESSION['login'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'pelanggan') {
        header("Location: /sm_sport_center/dashboard_pelanggan.php");
    } else {
        header("Location: /sm_sport_center/dashboard.php");
    }
    exit;
}

include 'koneksi.php';

$pesan_error = "";

if (isset($_POST['login'])) {
    $input_user = trim($_POST['username']); // Bisa berisi Username (Admin) atau Email (Pelanggan)
    $password   = $_POST['password'];

    if (!empty($input_user) && !empty($password)) {

        // -------------------------------------------------------------
        // 1. CEK AKTOR 1: ADMIN (Berdasarkan Username)
        // -------------------------------------------------------------
        $stmt_admin = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt_admin->bind_param("s", $input_user);
        $stmt_admin->execute();
        $res_admin = $stmt_admin->get_result();

        if ($res_admin->num_rows === 1) {
            $row_admin = $res_admin->fetch_assoc();

            // Verifikasi Password Admin (Mendukung Plain Text maupun Password Hash)
            if ($password === $row_admin['password'] || password_verify($password, $row_admin['password'])) {
                $_SESSION['login']    = true;
                $_SESSION['role']     = 'admin';
                $_SESSION['id_user']  = $row_admin['id_admin'] ?? $row_admin['id'];
                $_SESSION['username'] = $row_admin['username'];
                $_SESSION['nama']     = $row_admin['nama'] ?? $row_admin['username'];

                header("Location: dashboard.php");
                exit;
            } else {
                $pesan_error = "Password admin yang Anda masukkan salah.";
            }
        } else {
            // -------------------------------------------------------------
            // 2. CEK AKTOR 2: PELANGGAN (Murni Berdasarkan Email)
            // -------------------------------------------------------------
            $stmt_pelanggan = $conn->prepare("SELECT * FROM pelanggan WHERE email = ? LIMIT 1");
            $stmt_pelanggan->bind_param("s", $input_user);
            $stmt_pelanggan->execute();
            $res_pelanggan = $stmt_pelanggan->get_result();

            if ($res_pelanggan->num_rows === 1) {
                $row_pelanggan = $res_pelanggan->fetch_assoc();

                // Verifikasi Password Pelanggan (Cek Password Hash dari register.php / Plain Text)
                if (password_verify($password, $row_pelanggan['password']) || $password === $row_pelanggan['password']) {
                    $_SESSION['login']        = true;
                    $_SESSION['role']         = 'pelanggan';
                    $_SESSION['id_pelanggan'] = $row_pelanggan['id_pelanggan'];
                    $_SESSION['nama']         = $row_pelanggan['nama'];
                    $_SESSION['email']        = $row_pelanggan['email'];

                    // Mengarahkan Pelanggan ke dashboard_pelanggan.php
                    header("Location: dashboard_pelanggan.php");
                    exit;
                } else {
                    $pesan_error = "Password pelanggan yang Anda masukkan salah.";
                }
            } else {
                $pesan_error = "Username atau Email tidak terdaftar dalam sistem.";
            }
        }
    } else {
        $pesan_error = "Harap isi username/email dan password.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | SM Sport Center</title>
    
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

    <div class="card shadow-sm border-0 p-4" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-primary"><i class="bi bi-dribbble"></i> SM Sport Center</h4>
            <p class="text-secondary small mb-0">Masuk sebagai Admin atau Pelanggan</p>
        </div>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger border-0 small py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($pesan_error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label small fw-medium">Username / Email</label>
                <input type="text" name="username" class="form-control" placeholder="Admin: Username | Pelanggan: Email" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="off">
            </div>
            <div class="mb-4">
                <label class="form-label small fw-medium">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 fw-medium py-2">
                Masuk <i class="bi bi-box-arrow-in-right ms-1"></i>
            </button>
        </form>

        <!-- Link Daftar Akun Baru -->
        <div class="text-center mt-4 border-top pt-3">
            <span class="small text-secondary">Belum punya akun?</span>
            <a href="register.php" class="small fw-bold text-decoration-none ms-1">Daftar di sini</a>
        </div>
    </div>

</body>
</html>