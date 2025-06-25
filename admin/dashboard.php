<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Mengambil statistik untuk dashboard
$stats = [
    'total_dosen' => 0,
    'total_penilai' => 0,
    'total_kriteria' => 0,
    'total_penilaian' => 0
];

// Hitung total dosen
$query = "SELECT COUNT(*) as total FROM dosen";
$result = $conn->query($query);
if ($result) {
    $stats['total_dosen'] = $result->fetch_assoc()['total'];
}

// Hitung total penilai
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'penilai' AND is_active = 1";
$result = $conn->query($query);
if ($result) {
    $stats['total_penilai'] = $result->fetch_assoc()['total'];
}

// Hitung total kriteria
$query = "SELECT COUNT(*) as total FROM kriteria";
$result = $conn->query($query);
if ($result) {
    $stats['total_kriteria'] = $result->fetch_assoc()['total'];
}

// Hitung total penilaian
$query = "SELECT COUNT(*) as total FROM penilaian";
$result = $conn->query($query);
if ($result) {
    $stats['total_penilaian'] = $result->fetch_assoc()['total'];
}

// Mengambil data dosen terbaik
$query = "SELECT d.nama, d.nip, h.total_nilai, h.peringkat 
          FROM hasil_akhir h 
          JOIN dosen d ON h.dosen_id = d.id 
          WHERE h.tahun_akademik = '2023/2024' 
          AND h.semester = 'Ganjil' 
          ORDER BY h.peringkat ASC 
          LIMIT 5";
$top_dosen = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SPK Dosen Terbaik</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Desktop Sidebar Styles */
        .desktop-sidebar {
            background: var(--secondary-color);
            min-height: 100vh;
            color: white;
        }
        
        .desktop-sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
            margin: 0.2rem 0;
            border-radius: 0.25rem;
        }
        
        .desktop-sidebar .nav-link:hover {
            background: rgba(255,255,255,.1);
            color: white;
        }
        
        .desktop-sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }
        
        /* Mobile Navigation Styles */
        .mobile-nav {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: var(--secondary-color);
            padding: 1rem;
        }
        
        .mobile-nav .dropdown-menu {
            width: 100%;
            background: var(--secondary-color);
            border: none;
            border-radius: 0;
            margin-top: 0.5rem;
        }
        
        .mobile-nav .dropdown-item {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
        }
        
        .mobile-nav .dropdown-item:hover {
            background: rgba(255,255,255,.1);
            color: white;
        }
        
        .mobile-nav .dropdown-item.active {
            background: var(--primary-color);
            color: white;
        }
        
        .mobile-nav .dropdown-toggle {
            width: 100%;
            text-align: left;
            background: var(--primary-color);
            border: none;
            padding: 0.75rem 1rem;
            color: white;
        }
        
        .mobile-nav .dropdown-toggle::after {
            float: right;
            margin-top: 0.5rem;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .desktop-sidebar {
                display: none !important;
            }
            
            .mobile-nav {
                display: block;
            }
            
            .main-content {
                margin-top: 4rem !important;
                width: 100% !important;
                margin-left: 0 !important;
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-nav {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Mobile Navigation -->
            <div class="mobile-nav">
                <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="mobileMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bars me-2"></i>Menu
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="mobileMenu">
                        <li>
                            <a class="dropdown-item active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="dosen.php">
                                <i class="fas fa-user-tie me-2"></i>Dosen
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="kriteria.php">
                                <i class="fas fa-list me-2"></i>Kriteria
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="penilaian.php">
                                <i class="fas fa-star me-2"></i>Penilaian
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="hasil.php">
                                <i class="fas fa-chart-bar me-2"></i>Hasil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user-cog me-2"></i>Profil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="backup.php">
                                <i class="fas fa-database me-2"></i>Backup
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="log_activity.php">
                                <i class="fas fa-history me-2"></i>Log Aktivitas
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Desktop Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 desktop-sidebar">
                <div class="p-3">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-chalkboard-teacher me-2"></i>
                        SPK Dosen
                    </h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
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
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user-cog me-2"></i>Profil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="backup.php">
                                <i class="fas fa-database me-2"></i>Backup
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="log_activity.php">
                                <i class="fas fa-history me-2"></i>Log Aktivitas
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
            <div class="col-md-9 col-lg-10 p-4 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <div class="user-info">
                        <span class="me-2">Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <img src="https://via.placeholder.com/40" class="rounded-circle" alt="Profile">
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-user-tie stat-icon text-primary"></i>
                                <h4 class="mt-2"><?php echo $stats['total_dosen']; ?></h4>
                                <p class="text-muted mb-0">Total Dosen</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users stat-icon text-success"></i>
                                <h4 class="mt-2"><?php echo $stats['total_penilai']; ?></h4>
                                <p class="text-muted mb-0">Total Penilai</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-list stat-icon text-warning"></i>
                                <h4 class="mt-2"><?php echo $stats['total_kriteria']; ?></h4>
                                <p class="text-muted mb-0">Total Kriteria</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-star stat-icon text-info"></i>
                                <h4 class="mt-2"><?php echo $stats['total_penilaian']; ?></h4>
                                <p class="text-muted mb-0">Total Penilaian</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Setup Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Setup Sistem</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="setup_default_criteria.php" class="btn btn-info">
                                        <i class="fas fa-download me-2"></i>Setup Kriteria Default AHP
                                    </a>
                                    <a href="setup_sample_data.php" class="btn btn-success">
                                        <i class="fas fa-database me-2"></i>Setup Data Contoh
                                    </a>
                                    <a href="../fix_database_structure.php" class="btn btn-warning">
                                        <i class="fas fa-tools me-2"></i>Perbaiki Struktur Database
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Perhitungan AHP</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    Jalankan perhitungan AHP untuk menentukan ranking dosen terbaik berdasarkan bobot kriteria.
                                </p>
                                <div class="d-grid">
                                    <a href="ahp_calculation.php" class="btn btn-primary">
                                        <i class="fas fa-play me-2"></i>Jalankan Perhitungan AHP
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Distribusi Nilai Dosen</h5>
                                <canvas id="nilaiChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Status Penilaian</h5>
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Dosen Table -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Top 5 Dosen Terbaik</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Peringkat</th>
                                        <th>NIP</th>
                                        <th>Nama</th>
                                        <th>Total Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $top_dosen->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if ($row['peringkat'] == 1): ?>
                                                <span class="badge bg-warning">1</span>
                                            <?php elseif ($row['peringkat'] == 2): ?>
                                                <span class="badge bg-secondary">2</span>
                                            <?php elseif ($row['peringkat'] == 3): ?>
                                                <span class="badge bg-danger">3</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary"><?php echo $row['peringkat']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['nip']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td><?php echo number_format($row['total_nilai'], 4); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js Scripts -->
    <script>
        // Data untuk grafik distribusi nilai
        const nilaiCtx = document.getElementById('nilaiChart').getContext('2d');
        new Chart(nilaiCtx, {
            type: 'bar',
            data: {
                labels: ['80-85', '85-90', '90-95', '95-100'],
                datasets: [{
                    label: 'Jumlah Dosen',
                    data: [5, 8, 12, 3],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Data untuk grafik status penilaian
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Sudah Dinilai', 'Belum Dinilai'],
                datasets: [{
                    data: [75, 25],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html> 