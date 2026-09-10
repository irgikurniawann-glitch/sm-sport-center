<?php
session_start();

// 1. Proteksi Session: Pengguna harus login terlebih dahulu
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// 2. Validasi keberadaan ID pada parameter URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    // 3. Menggunakan Prepared Statement agar aman dari SQL Injection
    $stmt = $conn->prepare("DELETE FROM reservasi WHERE id_reservasi = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Redirect kembali ke halaman utama reservasi
header("Location: reservasi.php");
exit;
?>