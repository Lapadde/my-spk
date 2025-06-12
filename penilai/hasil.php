<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role penilai
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penilai') {
    header("Location: ../login.html");
    exit();
}

// Inisialisasi variabel filter
$tahun_akademik = isset($_GET['tahun_akademik']) ? $_GET['tahun_akademik'] : '2023/2024';
$semester = isset($_GET['semester']) ? $_GET['semester'] : 'Ganjil';
$fakultas = isset($_GET['fakultas']) ? $_GET['fakultas'] : '';
$prodi = isset($_GET['prodi']) ? $_GET['prodi'] : '';

// Query untuk mendapatkan hasil penilaian
$query = "SELECT d.*, 
          COUNT(DISTINCT p.id) as jumlah_penilaian,
          AVG(p.nilai) as rata_rata_nilai,
          GROUP_CONCAT(DISTINCT k.nama_kriteria) as kriteria_dinilai
          FROM dosen d
          LEFT JOIN penilaian p ON d.id = p.dosen_id 
          AND p.tahun_akademik = ? 
          AND p.semester = ?
          AND p.penilai_id = ?
          LEFT JOIN kriteria k ON p.kriteria_id = k.id
          WHERE 1=1";

$params = [$tahun_akademik, $semester, $_SESSION['user_id']];
$types = "ssi";

if (!empty($fakultas)) {
    $query .= " AND d.fakultas = ?";
    $params[] = $fakultas;
    $types .= "s";
}

if (!empty($prodi)) {
    $query .= " AND d.prodi = ?";
    $params[] = $prodi;
    $types .= "s";
}

$query .= " GROUP BY d.id ORDER BY rata_rata_nilai DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Query untuk mendapatkan daftar fakultas
$fakultas_query = "SELECT DISTINCT fakultas FROM dosen ORDER BY fakultas";
$fakultas_result = $conn->query($fakultas_query);

// Query untuk mendapatkan daftar prodi
$prodi_query = "SELECT DISTINCT prodi FROM dosen ORDER BY prodi";
$prodi_result = $conn->query($prodi_query);

// Query untuk mendapatkan statistik penilaian
$stats_query = "SELECT 
                COUNT(DISTINCT dosen_id) as total_dosen,
                COUNT(DISTINCT kriteria_id) as total_kriteria,
                AVG(nilai) as rata_rata_keseluruhan,
                MIN(nilai) as nilai_terendah,
                MAX(nilai) as nilai_tertinggi
                FROM penilaian 
                WHERE tahun_akademik = ? 
                AND semester = ? 
                AND penilai_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("ssi", $tahun_akademik, $semester, $_SESSION['user_id']);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Query untuk mendapatkan distribusi nilai
$dist_query = "SELECT 
                CASE 
                    WHEN nilai >= 90 THEN 'Sangat Baik (90-100)'
                    WHEN nilai >= 80 THEN 'Baik (80-89)'
                    WHEN nilai >= 70 THEN 'Cukup (70-79)'
                    WHEN nilai >= 60 THEN 'Kurang (60-69)'
                    ELSE 'Sangat Kurang (<60)'
                END as kategori,
                COUNT(*) as jumlah
                FROM penilaian 
                WHERE tahun_akademik = ? 
                AND semester = ? 
                AND penilai_id = ?
                GROUP BY kategori 
                ORDER BY MIN(nilai) DESC";
$dist_stmt = $conn->prepare($dist_query);
$dist_stmt->bind_param("ssi", $tahun_akademik, $semester, $_SESSION['user_id']);
$dist_stmt->execute();
$dist_result = $dist_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Penilaian - SPK Dosen Terbaik</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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

        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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

        .badge-nilai {
            font-size: 0.9rem;
            padding: 0.5em 0.8em;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
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
                            <a class="nav-link" href="penilaian.php">
                                <i class="fas fa-star me-2"></i>Penilaian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="hasil.php">
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
                    <h2>Hasil Penilaian</h2>
                    <div class="user-info">
                        <span class="me-2">Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <img src="https://via.placeholder.com/40" class="rounded-circle" alt="Profile">
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tahun Akademik</label>
                            <select name="tahun_akademik" class="form-select">
                                <option value="2023/2024" <?php echo $tahun_akademik === '2023/2024' ? 'selected' : ''; ?>>2023/2024</option>
                                <option value="2022/2023" <?php echo $tahun_akademik === '2022/2023' ? 'selected' : ''; ?>>2022/2023</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select">
                                <option value="Ganjil" <?php echo $semester === 'Ganjil' ? 'selected' : ''; ?>>Ganjil</option>
                                <option value="Genap" <?php echo $semester === 'Genap' ? 'selected' : ''; ?>>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fakultas</label>
                            <select name="fakultas" class="form-select">
                                <option value="">Semua Fakultas</option>
                                <?php while ($row = $fakultas_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($row['fakultas']); ?>" 
                                            <?php echo $fakultas === $row['fakultas'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['fakultas']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Program Studi</label>
                            <select name="prodi" class="form-select">
                                <option value="">Semua Prodi</option>
                                <?php while ($row = $prodi_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($row['prodi']); ?>"
                                            <?php echo $prodi === $row['prodi'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['prodi']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
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
                        <div class="card stat-card bg-success text-white">
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
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Rata-rata Nilai</h6>
                                        <h2 class="mb-0"><?php echo round($stats['rata_rata_keseluruhan'], 2); ?></h2>
                                    </div>
                                    <i class="fas fa-chart-line stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Rentang Nilai</h6>
                                        <h2 class="mb-0"><?php echo $stats['nilai_terendah']; ?> - <?php echo $stats['nilai_tertinggi']; ?></h2>
                                    </div>
                                    <i class="fas fa-chart-bar stat-icon"></i>
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
                                <div class="chart-container">
                                    <canvas id="nilaiChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hasil Penilaian Table -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Hasil Penilaian</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Dosen</th>
                                                <th>Rata-rata</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            while ($row = $result->fetch_assoc()): 
                                                $status = '';
                                                $status_class = '';
                                                if ($row['rata_rata_nilai'] >= 90) {
                                                    $status = 'Sangat Baik';
                                                    $status_class = 'success';
                                                } elseif ($row['rata_rata_nilai'] >= 80) {
                                                    $status = 'Baik';
                                                    $status_class = 'primary';
                                                } elseif ($row['rata_rata_nilai'] >= 70) {
                                                    $status = 'Cukup';
                                                    $status_class = 'warning';
                                                } elseif ($row['rata_rata_nilai'] >= 60) {
                                                    $status = 'Kurang';
                                                    $status_class = 'danger';
                                                } else {
                                                    $status = 'Sangat Kurang';
                                                    $status_class = 'dark';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['nama']); ?><br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($row['fakultas']); ?> - 
                                                        <?php echo htmlspecialchars($row['prodi']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $status_class; ?> badge-nilai">
                                                        <?php echo round($row['rata_rata_nilai'], 2); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo $status; ?>
                                                    </span>
                                                </td>
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
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Chart.js Configuration
        const ctx = document.getElementById('nilaiChart').getContext('2d');
        const chartData = {
            labels: [
                <?php 
                $dist_result->data_seek(0);
                while ($row = $dist_result->fetch_assoc()) {
                    echo "'" . $row['kategori'] . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Jumlah Penilaian',
                data: [
                    <?php 
                    $dist_result->data_seek(0);
                    while ($row = $dist_result->fetch_assoc()) {
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
                maintainAspectRatio: false,
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