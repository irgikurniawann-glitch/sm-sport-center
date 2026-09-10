<?php
session_start();

// Proteksi Session: Jika belum login, alihkan ke index.php
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

$cari = $_GET['cari'] ?? '';

// Menggunakan Prepared Statement untuk pencarian
if (!empty($cari)) {
    $stmt = $conn->prepare("
        SELECT * FROM pelanggan 
        WHERE nama LIKE ? OR no_hp LIKE ? OR email LIKE ? 
        ORDER BY nama ASC
    ");
    $param_cari = "%" . $cari . "%";
    $stmt->bind_param("sss", $param_cari, $param_cari, $param_cari);
    $stmt->execute();
    $data = $stmt->get_result();
} else {
    $data = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Pelanggan | SM Sport Center</title>

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

        .table > :not(caption) > * > * {
            padding: 0.75rem 1rem;
        }
    </style>
</head>

<body>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="dashboard.php">
               <i class="bi bi-dribbble"></i> SM Sport Center
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-light text-danger btn-sm px-3 rounded-pill border" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5">
        
        <!-- Header Page -->
        <div class="mb-4">
            <h4 class="fw-bold mb-1">Data Pelanggan</h4>
            <p class="text-secondary small mb-0">Kelola informasi kontak dan profil pelanggan</p>
        </div>

        <!-- Alert Notifikasi Hapus -->
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'sukses_hapus'): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Data pelanggan berhasil dihapus beserta riwayat reservasinya.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['status'] == 'gagal_hapus'): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Gagal menghapus data pelanggan. Silakan coba lagi.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Filter & Table Card -->
        <div class="card card-custom p-4">
            
            <!-- Form Pencarian -->
            <form method="GET" class="mb-4">
                <div class="row g-2">
                    <div class="col-md-5 col-lg-4 ms-auto">
                        <div class="input-group">
                            <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama, no. HP, atau email..." value="<?= htmlspecialchars($cari) ?>">
                            <button class="btn btn-primary btn-sm px-3" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($cari)): ?>
                                <a href="pelanggan.php" class="btn btn-outline-secondary btn-sm" title="Reset">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Table Data -->
            <div class="table-responsive">
                <table class="table align-middle table-hover border-top">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th scope="col" width="60">ID</th>
                            <th scope="col">Nama Pelanggan</th>
                            <th scope="col">No. Handphone</th>
                            <th scope="col">Email</th>
                            <th scope="col">Alamat</th>
                            <th scope="col" class="text-end" width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($data) > 0): ?>
                            <?php while ($r = mysqli_fetch_assoc($data)): ?>
                                <tr>
                                    <td class="text-secondary small">#<?= $r['id_pelanggan']; ?></td>
                                    <td class="fw-medium"><?= htmlspecialchars($r['nama']); ?></td>
                                    <td><?= htmlspecialchars($r['no_hp'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($r['email'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($r['alamat'] ?? '-'); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit_pelanggan.php?id=<?= $r['id_pelanggan']; ?>" class="btn btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="hapus_pelanggan.php?id=<?= $r['id_pelanggan']; ?>" 
                                               class="btn btn-outline-danger" 
                                               title="Hapus"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini? Semua data reservasi pelanggan ini juga akan terhapus!');">
                                                <i class="bi bi-trash"></i> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-secondary">
                                    <i class="bi bi-people fs-2 d-block mb-2 text-muted"></i>
                                    Data pelanggan tidak ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>