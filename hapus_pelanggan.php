<?php
session_start();

// Proteksi Session: Pengguna wajib login
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Validasi ID Pelanggan dari URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_pelanggan = $_GET['id'];

    // Hapus data pelanggan dari database
    $stmt = $conn->prepare("DELETE FROM pelanggan WHERE id_pelanggan = ?");
    $stmt->bind_param("i", $id_pelanggan);

    try {
        if ($stmt->execute()) {
            // Berhasil dihapus
            header("Location: pelanggan.php?status=sukses_hapus");
            exit;
        } else {
            // Gagal
            header("Location: pelanggan.php?status=gagal_hapus");
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        // Jika pelanggan masih terikat dengan data reservasi (Foreign Key Error)
        header("Location: pelanggan.php?status=gagal_fk");
        exit;
    }
} else {
    header("Location: pelanggan.php");
    exit;
}
?>