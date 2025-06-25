<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Data dosen contoh
$sample_dosen = [
    [
        'nip' => '198501012010012001',
        'nidn' => '0001018501',
        'nama' => 'Prof. Dr. Taufiq Hidayat, S.Kom., M.Kom.',
        'jabatan' => 'Lektor',
        'fakultas' => 'Teknik',
        'program_studi' => 'Teknik Informatika'
    ],
    [
        'nip' => '198603152010012002',
        'nidn' => '0003158601',
        'nama' => 'Prof. Dr. Ahmad Rizki, S.Si., M.Si.',
        'jabatan' => 'Guru Besar',
        'fakultas' => 'MIPA',
        'program_studi' => 'Matematika'
    ],
    [
        'nip' => '198709202010012003',
        'nidn' => '0009208701',
        'nama' => 'Dr. Maria Christina, S.E., M.M.',
        'jabatan' => 'Lektor Kepala',
        'fakultas' => 'Ekonomi',
        'program_studi' => 'Manajemen'
    ],
    [
        'nip' => '198812102010012004',
        'nidn' => '0012108801',
        'nama' => 'Dr. Budi Santoso, S.Pd., M.Pd.',
        'jabatan' => 'Lektor',
        'fakultas' => 'Keguruan dan Ilmu Pendidikan',
        'program_studi' => 'Pendidikan Matematika'
    ],
    [
        'nip' => '199001152010012005',
        'nidn' => '0001159001',
        'nama' => 'Dr. Rina Dewi, S.Kom., M.Kom.',
        'jabatan' => 'Asisten Ahli',
        'fakultas' => 'Teknik',
        'program_studi' => 'Sistem Informasi'
    ]
];

// Data penilaian contoh (nilai 0-100)
$sample_penilaian = [
    // Dosen 1 - Sarah Amelia
    [
        'dosen_id' => 1,
        'kriteria_id' => 1, // Pendidikan
        'nilai' => 85
    ],
    [
        'dosen_id' => 1,
        'kriteria_id' => 2, // Penelitian
        'nilai' => 90
    ],
    [
        'dosen_id' => 1,
        'kriteria_id' => 3, // Pengabdian
        'nilai' => 80
    ],
    [
        'dosen_id' => 1,
        'kriteria_id' => 4, // Penunjang
        'nilai' => 75
    ],
    
    // Dosen 2 - Ahmad Rizki
    [
        'dosen_id' => 2,
        'kriteria_id' => 1, // Pendidikan
        'nilai' => 95
    ],
    [
        'dosen_id' => 2,
        'kriteria_id' => 2, // Penelitian
        'nilai' => 95
    ],
    [
        'dosen_id' => 2,
        'kriteria_id' => 3, // Pengabdian
        'nilai' => 85
    ],
    [
        'dosen_id' => 2,
        'kriteria_id' => 4, // Penunjang
        'nilai' => 80
    ],
    
    // Dosen 3 - Maria Christina
    [
        'dosen_id' => 3,
        'kriteria_id' => 1, // Pendidikan
        'nilai' => 80
    ],
    [
        'dosen_id' => 3,
        'kriteria_id' => 2, // Penelitian
        'nilai' => 85
    ],
    [
        'dosen_id' => 3,
        'kriteria_id' => 3, // Pengabdian
        'nilai' => 90
    ],
    [
        'dosen_id' => 3,
        'kriteria_id' => 4, // Penunjang
        'nilai' => 85
    ],
    
    // Dosen 4 - Budi Santoso
    [
        'dosen_id' => 4,
        'kriteria_id' => 1, // Pendidikan
        'nilai' => 75
    ],
    [
        'dosen_id' => 4,
        'kriteria_id' => 2, // Penelitian
        'nilai' => 80
    ],
    [
        'dosen_id' => 4,
        'kriteria_id' => 3, // Pengabdian
        'nilai' => 85
    ],
    [
        'dosen_id' => 4,
        'kriteria_id' => 4, // Penunjang
        'nilai' => 70
    ],
    
    // Dosen 5 - Rina Dewi
    [
        'dosen_id' => 5,
        'kriteria_id' => 1, // Pendidikan
        'nilai' => 70
    ],
    [
        'dosen_id' => 5,
        'kriteria_id' => 2, // Penelitian
        'nilai' => 75
    ],
    [
        'dosen_id' => 5,
        'kriteria_id' => 3, // Pengabdian
        'nilai' => 80
    ],
    [
        'dosen_id' => 5,
        'kriteria_id' => 4, // Penunjang
        'nilai' => 85
    ]
];

// Proses setup data contoh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_sample'])) {
    // Hapus data yang ada
    $conn->query("DELETE FROM penilaian");
    $conn->query("DELETE FROM dosen");
    $conn->query("DELETE FROM hasil_akhir");
    
    // Reset auto increment
    $conn->query("ALTER TABLE dosen AUTO_INCREMENT = 1");
    $conn->query("ALTER TABLE penilaian AUTO_INCREMENT = 1");
    $conn->query("ALTER TABLE hasil_akhir AUTO_INCREMENT = 1");
    
    // Insert dosen contoh
    $stmt = $conn->prepare("INSERT INTO dosen (nip, nidn, nama, jabatan, fakultas, prodi) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($sample_dosen as $dosen) {
        $stmt->bind_param("ssssss", 
            $dosen['nip'], 
            $dosen['nidn'], 
            $dosen['nama'], 
            $dosen['jabatan'], 
            $dosen['fakultas'], 
            $dosen['program_studi']
        );
        $stmt->execute();
    }
    
    // Insert penilaian contoh
    $stmt = $conn->prepare("INSERT INTO penilaian (dosen_id, kriteria_id, evaluator_id, nilai, tahun_akademik, semester) VALUES (?, ?, ?, ?, ?, ?)");
    $evaluator_id = $_SESSION['user_id'];
    $tahun_akademik = '2023/2024';
    $semester = 'Ganjil';
    
    foreach ($sample_penilaian as $penilaian) {
        $stmt->bind_param("iiidss", 
            $penilaian['dosen_id'], 
            $penilaian['kriteria_id'], 
            $evaluator_id, 
            $penilaian['nilai'], 
            $tahun_akademik, 
            $semester
        );
        $stmt->execute();
    }
    
    $success_msg = "Data contoh berhasil ditambahkan!";
}

// Cek apakah sudah ada data
$existing_dosen = $conn->query("SELECT COUNT(*) as total FROM dosen")->fetch_assoc()['total'];
$existing_penilaian = $conn->query("SELECT COUNT(*) as total FROM penilaian")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Data Contoh - SPK Dosen Terbaik</title>
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
        
        .dosen-card {
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
                        <h2><i class="fas fa-database me-2"></i>Setup Data Contoh</h2>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
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
                                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Data Dosen Contoh</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">
                                        Berikut adalah data dosen contoh yang akan ditambahkan untuk testing sistem AHP:
                                    </p>
                                    
                                    <div class="row">
                                        <?php foreach ($sample_dosen as $index => $dosen): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card dosen-card">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="fas fa-user-tie text-primary me-2"></i>
                                                        <?php echo htmlspecialchars($dosen['nama']); ?>
                                                    </h6>
                                                    <p class="card-text text-muted small mb-1">
                                                        <strong>NIP:</strong> <?php echo htmlspecialchars($dosen['nip']); ?>
                                                    </p>
                                                    <p class="card-text text-muted small mb-1">
                                                        <strong>NIDN:</strong> <?php echo htmlspecialchars($dosen['nidn']); ?>
                                                    </p>
                                                    <p class="card-text text-muted small mb-1">
                                                        <strong>Jabatan:</strong> <?php echo htmlspecialchars($dosen['jabatan']); ?>
                                                    </p>
                                                    <p class="card-text text-muted small mb-1">
                                                        <strong>Fakultas:</strong> <?php echo htmlspecialchars($dosen['fakultas']); ?>
                                                    </p>
                                                    <p class="card-text text-muted small">
                                                        <strong>Program Studi:</strong> <?php echo htmlspecialchars($dosen['program_studi']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h6>Data Penilaian:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>5 dosen dengan 4 kriteria masing-masing</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Total 20 data penilaian</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Nilai bervariasi 70-95 untuk testing</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Tahun akademik 2023/2024 semester Ganjil</li>
                                        </ul>
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
                                        <h6><i class="fas fa-lightbulb me-2"></i>Untuk Testing</h6>
                                        <p class="mb-0">
                                            Data contoh ini berguna untuk testing sistem AHP sebelum menggunakan data real.
                                        </p>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Perhatian!</h6>
                                        <p class="mb-0">
                                            Setup ini akan menghapus semua data dosen dan penilaian yang sudah ada.
                                        </p>
                                    </div>
                                    
                                    <?php if ($existing_dosen > 0 || $existing_penilaian > 0): ?>
                                    <div class="alert alert-danger">
                                        <h6><i class="fas fa-exclamation-circle me-2"></i>Peringatan!</h6>
                                        <p class="mb-0">
                                            Saat ini ada <strong><?php echo $existing_dosen; ?></strong> dosen dan 
                                            <strong><?php echo $existing_penilaian; ?></strong> penilaian yang sudah ada. 
                                            Setup ini akan menghapus semuanya.
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menambahkan data contoh? Data yang ada akan dihapus.');">
                                        <button type="submit" name="setup_sample" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Tambah Data Contoh
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