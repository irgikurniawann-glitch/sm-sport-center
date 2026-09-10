<?php
session_start();
include 'koneksi.php';

// Ambil input dari form login
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!empty($username) && !empty($password)) {
    // Prepared Statement untuk mencegah SQL Injection
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();

        // Simpan data admin ke Session
        $_SESSION['login']      = true;
        $_SESSION['id_admin']   = $admin['id_admin'];
        $_SESSION['nama_admin'] = $admin['nama_admin'] ?? $admin['nama'] ?? $admin['username'];

        header("Location: dashboard.php");
        exit;
    } else {
        // Jika gagal login, alihkan kembali ke index.php dengan parameter error
        header("Location: index.php?pesan=gagal");
        exit;
    }
} else {
    // Jika input kosong, kembalikan ke halaman login
    header("Location: index.php");
    exit;
}
?>