<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role penilai
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penilai') {
    header("Location: ../login.html");
    exit();
}

// Mengambil data penilaian yang perlu dilakukan
$user_id = $_SESSION['user_id'];
$query = "SELECT p.*, d.nama as nama_dosen, d.nip, d.fakultas, d.prodi, k.nama_kriteria 
          FROM penilaian p 
          JOIN dosen d ON p.dosen_id = d.id 
          JOIN kriteria k ON p.kriteria_id = k.id 
          WHERE p.penilai_id = ? 
          AND p.tahun_akademik = '2023/2024' 
          AND p.semester = 'Ganjil'
          ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Mengambil statistik penilaian
$stats = [
    'total_penilaian' => 0,
    'total_dosen' => 0,
    'total_kriteria' => 0,
    'rata_rata_nilai' => 0
];

// Hitung total penilaian
$query = "SELECT COUNT(*) as total FROM penilaian WHERE penilai_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['total_penilaian'] = $stmt->get_result()->fetch_assoc()['total'];

// Hitung total dosen yang dinilai
$query = "SELECT COUNT(DISTINCT dosen_id) as total FROM penilaian WHERE penilai_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['total_dosen'] = $stmt->get_result()->fetch_assoc()['total'];

// Hitung total kriteria yang dinilai
$query = "SELECT COUNT(DISTINCT kriteria_id) as total FROM penilaian WHERE penilai_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['total_kriteria'] = $stmt->get_result()->fetch_assoc()['total'];

// Hitung rata-rata nilai
$query = "SELECT AVG(nilai) as rata_rata FROM penilaian WHERE penilai_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['rata_rata_nilai'] = round($stmt->get_result()->fetch_assoc()['rata_rata'], 2);

// Mengambil data untuk chart distribusi nilai
$query = "SELECT 
            CASE 
                WHEN nilai >= 90 THEN 'Sangat Baik (90-100)'
                WHEN nilai >= 80 THEN 'Baik (80-89)'
                WHEN nilai >= 70 THEN 'Cukup (70-79)'
                WHEN nilai >= 60 THEN 'Kurang (60-69)'
                ELSE 'Sangat Kurang (<60)'
            END as kategori,
            COUNT(*) as jumlah
          FROM penilaian 
          WHERE penilai_id = ? 
          GROUP BY kategori 
          ORDER BY MIN(nilai) DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$chart_data = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penilai - SPK Dosen Terbaik</title>
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

        .no-data-message {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .no-data-message i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .penilaian-card {
            transition: all 0.3s;
        }

        .penilaian-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .badge-nilai {
            font-size: 0.9rem;
            padding: 0.5em 0.8em;
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
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
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
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user-cog me-2"></i>Profil
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
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <div class="user-info">
                        <span class="me-2">Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <img src="https://via.placeholder.com/40" class="rounded-circle" alt="Profile">
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Penilaian</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_penilaian']; ?></h2>
                                    </div>
                                    <i class="fas fa-star stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Dosen</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_dosen']; ?></h2>
                                    </div>
                                    <i class="fas fa-user-tie stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Kriteria</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_kriteria']; ?></h2>
                                    </div>
                                    <i class="fas fa-list stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Rata-rata Nilai</h6>
                                        <h2 class="mb-0"><?php echo $stats['rata_rata_nilai']; ?></h2>
                                    </div>
                                    <i class="fas fa-chart-line stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Chart Section -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Distribusi Nilai</h5>
                                <canvas id="nilaiChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Penilaian -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Penilaian Terbaru</h5>
                                <?php if ($result->num_rows > 0): ?>
                                    <div class="list-group">
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <div class="list-group-item penilaian-card">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($row['nama_dosen']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($row['nama_kriteria']); ?> | 
                                                            <?php echo htmlspecialchars($row['fakultas']); ?> - 
                                                            <?php echo htmlspecialchars($row['prodi']); ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge bg-primary badge-nilai">
                                                        <?php echo $row['nilai']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data-message">
                                        <i class="fas fa-clipboard-list"></i>
                                        <p>Belum ada penilaian yang dilakukan</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart.js Configuration
        const ctx = document.getElementById('nilaiChart').getContext('2d');
        const chartData = {
            labels: [
                <?php 
                $chart_data->data_seek(0);
                while ($row = $chart_data->fetch_assoc()) {
                    echo "'" . $row['kategori'] . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Jumlah Penilaian',
                data: [
                    <?php 
                    $chart_data->data_seek(0);
                    while ($row = $chart_data->fetch_assoc()) {
                        echo $row['jumlah'] . ",";
                    }
                    ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',   // Sangat Baik
                    'rgba(23, 162, 184, 0.8)',  // Baik
                    'rgba(255, 193, 7, 0.8)',   // Cukup
                    'rgba(255, 127, 80, 0.8)',  // Kurang
                    'rgba(220, 53, 69, 0.8)'    // Sangat Kurang
                ],
                borderColor: [
                    'rgb(40, 167, 69)',
                    'rgb(23, 162, 184)',
                    'rgb(255, 193, 7)',
                    'rgb(255, 127, 80)',
                    'rgb(220, 53, 69)'
                ],
                borderWidth: 1
            }]
        };

        new Chart(ctx, {
            type: 'pie',
            data: chartData,
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