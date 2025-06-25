<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Kriteria default sesuai tahapan AHP
$default_criteria = [
    [
        'nama_kriteria' => 'Pendidikan',
        'deskripsi' => 'Kriteria penilaian berdasarkan latar belakang pendidikan dosen',
        'bobot' => 0.40
    ],
    [
        'nama_kriteria' => 'Penelitian',
        'deskripsi' => 'Kriteria penilaian berdasarkan produktivitas dan kualitas penelitian dosen',
        'bobot' => 0.25
    ],
    [
        'nama_kriteria' => 'Pengabdian Masyarakat',
        'deskripsi' => 'Kriteria penilaian berdasarkan kontribusi dosen dalam pengabdian masyarakat',
        'bobot' => 0.25
    ],
    [
        'nama_kriteria' => 'Penunjang',
        'deskripsi' => 'Kriteria penilaian berdasarkan kegiatan penunjang lainnya',
        'bobot' => 0.10
    ]
];

// Proses setup kriteria default
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_criteria'])) {
    // Hapus kriteria yang ada
    $conn->query("DELETE FROM kriteria");
    
    // Reset auto increment
    $conn->query("ALTER TABLE kriteria AUTO_INCREMENT = 1");
    
    // Insert kriteria default
    $stmt = $conn->prepare("INSERT INTO kriteria (nama_kriteria, deskripsi, bobot) VALUES (?, ?, ?)");
    
    foreach ($default_criteria as $criteria) {
        $stmt->bind_param("ssd", $criteria['nama_kriteria'], $criteria['deskripsi'], $criteria['bobot']);
        $stmt->execute();
    }
    
    $success_msg = "Kriteria default berhasil ditambahkan!";
}

// Cek apakah sudah ada kriteria
$existing_criteria = $conn->query("SELECT COUNT(*) as total FROM kriteria")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Kriteria Default - SPK Dosen Terbaik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: var(--secondary-color);
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
            margin: 0.2rem 0;
            border-radius: 0.25rem;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,.1);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .criteria-card {
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-chalkboard-teacher me-2"></i>
                        SPK Dosen
                    </h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dosen.php">
                                <i class="fas fa-user-tie me-2"></i>Dosen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kriteria.php">
                                <i class="fas fa-list me-2"></i>Kriteria
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="penilaian.php">
                                <i class="fas fa-star me-2"></i>Penilaian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="hasil.php">
                                <i class="fas fa-chart-bar me-2"></i>Hasil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="ahp_calculation.php">
                                <i class="fas fa-calculator me-2"></i>Perhitungan AHP
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-cogs me-2"></i>Setup Kriteria Default</h2>
                        <a href="kriteria.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Kriteria
                        </a>
                    </div>
                    
                    <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Kriteria Default AHP</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">
                                        Berikut adalah kriteria default yang akan digunakan dalam perhitungan AHP untuk penilaian dosen terbaik:
                                    </p>
                                    
                                    <div class="row">
                                        <?php foreach ($default_criteria as $index => $criteria): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card criteria-card">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="fas fa-check-circle text-primary me-2"></i>
                                                        <?php echo htmlspecialchars($criteria['nama_kriteria']); ?>
                                                    </h6>
                                                    <p class="card-text text-muted small">
                                                        <?php echo htmlspecialchars($criteria['deskripsi']); ?>
                                                    </p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="badge bg-primary">
                                                            Bobot: <?php echo number_format($criteria['bobot'] * 100, 0); ?>%
                                                        </span>
                                                        <span class="text-muted small">
                                                            <?php echo number_format($criteria['bobot'], 4); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h6>Total Bobot: 100%</h6>
                                        <div class="progress mb-3">
                                            <div class="progress-bar" role="progressbar" style="width: 100%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-lightbulb me-2"></i>Penting!</h6>
                                        <p class="mb-0">
                                            Setup ini akan menghapus semua kriteria yang sudah ada dan menggantinya dengan kriteria default AHP.
                                        </p>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Perhatian!</h6>
                                        <p class="mb-0">
                                            Pastikan Anda telah menyimpan data penting sebelum melakukan setup ini.
                                        </p>
                                    </div>
                                    
                                    <?php if ($existing_criteria > 0): ?>
                                    <div class="alert alert-danger">
                                        <h6><i class="fas fa-exclamation-circle me-2"></i>Peringatan!</h6>
                                        <p class="mb-0">
                                            Saat ini ada <strong><?php echo $existing_criteria; ?></strong> kriteria yang sudah ada. 
                                            Setup ini akan menghapus semuanya.
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin melakukan setup kriteria default? Data kriteria yang ada akan dihapus.');">
                                        <button type="submit" name="setup_criteria" class="btn btn-primary w-100">
                                            <i class="fas fa-download me-2"></i>Setup Kriteria Default
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 