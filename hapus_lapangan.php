<?php
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM lapangan WHERE id_lapangan = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        // Menangani error jika lapangan masih terikat transaksi di tabel reservasi
        echo "<script>
                alert('Gagal menghapus lapangan! Lapangan ini memiliki riwayat transaksi di data reservasi.');
                window.location.href = 'lapangan.php';
              </script>";
        exit;
    }
}

header("Location: lapangan.php");
exit;