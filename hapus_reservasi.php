<?php
session_start();

// 1. Proteksi Session: Pengguna harus login terlebih dahulu
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// 2. Tangkap parameter ID reservasi dari URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_reservasi = $_GET['id'];

    // 3. Gunakan Prepared Statement untuk menghapus reservasi
    $stmt = $conn->prepare("DELETE FROM reservasi WHERE id_reservasi = ?");
    $stmt->bind_param("i", $id_reservasi);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Berhasil dihapus
            header("Location: reservasi.php?status=sukses_hapus");
            exit;
        } else {
            // Data tidak ditemukan
            header("Location: reservasi.php?status=gagal_tidak_ditemukan");
            exit;
        }
    } else {
        // Gagal mengeksekusi query
        header("Location: reservasi.php?status=gagal_hapus");
        exit;
    }
} else {
    // Jika tidak ada ID di URL, kembalikan ke reservasi.php
    header("Location: reservasi.php");
    exit;
}
?>