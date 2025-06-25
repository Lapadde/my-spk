<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Cek apakah tabel yang diperlukan sudah ada
$tables = ['dosen', 'kriteria', 'penilaian'];
$tables_exist = true;

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $tables_exist = false;
        break;
    }
}

// Inisialisasi variabel filter
$tahun_akademik = isset($_GET['tahun_akademik']) ? $_GET['tahun_akademik'] : date('Y');
$semester = isset($_GET['semester']) ? $_GET['semester'] : (date('n') <= 6 ? 'Genap' : 'Ganjil');
$fakultas = isset($_GET['fakultas']) ? $_GET['fakultas'] : '';
$prodi = isset($_GET['prodi']) ? $_GET['prodi'] : '';

// Query untuk mendapatkan daftar dosen yang perlu dinilai
$query = "SELECT d.*, 
          (SELECT COUNT(*) FROM penilaian WHERE dosen_id = d.id AND tahun_akademik = ? AND semester = ?) as jumlah_penilaian,
          (SELECT COUNT(*) FROM kriteria) as total_kriteria
          FROM dosen d
          WHERE 1=1";

// Tambahkan filter jika ada
if (!empty($fakultas)) {
    $query .= " AND d.fakultas = ?";
}
if (!empty($prodi)) {
    $query .= " AND d.prodi = ?";
}

$query .= " ORDER BY d.nama ASC";

// Prepare statement
$stmt = $conn->prepare($query);

// Bind parameters
$params = [$tahun_akademik, $semester];
if (!empty($fakultas)) {
    $params[] = $fakultas;
}
if (!empty($prodi)) {
    $params[] = $prodi;
}

$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Query untuk mendapatkan daftar fakultas
$fakultas_query = "SELECT DISTINCT fakultas FROM dosen ORDER BY fakultas ASC";
$fakultas_result = $conn->query($fakultas_query);

// Query untuk mendapatkan daftar prodi
$prodi_query = "SELECT DISTINCT prodi FROM dosen ORDER BY prodi ASC";
$prodi_result = $conn->query($prodi_query);

// Query untuk mendapatkan daftar kriteria
$kriteria_query = "SELECT * FROM kriteria ORDER BY id ASC";
$kriteria_result = $conn->query($kriteria_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Dosen - SPK Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
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

        .progress {
            height: 10px;
            border-radius: 5px;
        }

        .progress-bar {
            background-color: var(--primary-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .dosen-card {
            transition: transform 0.2s;
        }

        .dosen-card:hover {
            transform: translateY(-5px);
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
                            <a class="dropdown-item" href="dashboard.php">
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
                            <a class="dropdown-item active" href="penilaian.php">
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
                            <a class="nav-link active" href="penilaian.php">
                                <i class="fas fa-star me-2"></i>Penilaian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="hasil.php">
                                <i class="fas fa-chart-bar me-2"></i>Hasil
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
                    <h2>Penilaian Dosen</h2>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tahun Akademik</label>
                            <select class="form-select" name="tahun_akademik">
                                <option value="2023/2024" <?php echo $tahun_akademik == '2023/2024' ? 'selected' : ''; ?>>2023/2024</option>
                                <option value="2024/2025" <?php echo $tahun_akademik == '2024/2025' ? 'selected' : ''; ?>>2024/2025</option>
                                <option value="2025/2026" <?php echo $tahun_akademik == '2025/2026' ? 'selected' : ''; ?>>2025/2026</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Semester</label>
                            <select class="form-select" name="semester">
                                <option value="Ganjil" <?php echo $semester == 'Ganjil' ? 'selected' : ''; ?>>Ganjil</option>
                                <option value="Genap" <?php echo $semester == 'Genap' ? 'selected' : ''; ?>>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fakultas</label>
                            <select class="form-select" name="fakultas">
                                <option value="">Semua Fakultas</option>
                                <?php while($row = $fakultas_result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['fakultas']; ?>" <?php echo $fakultas == $row['fakultas'] ? 'selected' : ''; ?>>
                                        <?php echo $row['fakultas']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Program Studi</label>
                            <select class="form-select" name="prodi">
                                <option value="">Semua Prodi</option>
                                <?php while($row = $prodi_result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['prodi']; ?>" <?php echo $prodi == $row['prodi'] ? 'selected' : ''; ?>>
                                        <?php echo $row['prodi']; ?>
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

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Dosen List -->
                <div class="row">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card dosen-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $row['nama']; ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <i class="fas fa-id-card me-2"></i><?php echo isset($row['nidn']) ? $row['nidn'] : (isset($row['nip']) ? $row['nip'] : ''); ?><br>
                                                <i class="fas fa-university me-2"></i><?php echo $row['fakultas']; ?><br>
                                                <i class="fas fa-graduation-cap me-2"></i><?php echo isset($row['prodi']) ? $row['prodi'] : (isset($row['program_studi']) ? $row['program_studi'] : ''); ?>
                                            </small>
                                        </p>
                                        <div class="progress mb-3">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo ($row['jumlah_penilaian'] / $row['total_kriteria']) * 100; ?>%">
                                            </div>
                                        </div>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <?php echo $row['jumlah_penilaian']; ?> dari <?php echo $row['total_kriteria']; ?> kriteria dinilai
                                            </small>
                                        </p>
                                        <button type="button" class="btn btn-primary w-100" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#penilaianModal<?php echo $row['id']; ?>">
                                            <i class="fas fa-star me-2"></i>Nilai Dosen
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Penilaian -->
                            <div class="modal fade" id="penilaianModal<?php echo $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Penilaian <?php echo $row['nama']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="process_penilaian.php" method="POST">
                                                <input type="hidden" name="dosen_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="tahun_akademik" value="<?php echo $tahun_akademik; ?>">
                                                <input type="hidden" name="semester" value="<?php echo $semester; ?>">
                                                
                                                <?php 
                                                $kriteria_result->data_seek(0);
                                                while($kriteria = $kriteria_result->fetch_assoc()): 
                                                ?>
                                                    <div class="mb-3">
                                                        <label class="form-label"><?php echo $kriteria['nama_kriteria']; ?></label>
                                                        <input type="number" class="form-control" 
                                                               name="nilai[<?php echo $kriteria['id']; ?>]" 
                                                               min="0" max="100" required>
                                                        <div class="form-text">
                                                            Nilai 0-100
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Keterangan</label>
                                                    <textarea class="form-control" name="keterangan" rows="3"></textarea>
                                                </div>
                                                
                                                <div class="text-end">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-2"></i>Simpan Penilaian
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Belum ada data dosen yang tersedia.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set active menu item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const menuItems = document.querySelectorAll('.nav-link, .dropdown-item');
            
            menuItems.forEach(item => {
                if (item.getAttribute('href') === currentPage) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>