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

// Query untuk mendapatkan daftar dosen yang perlu dinilai
$query = "SELECT d.*, 
          GROUP_CONCAT(DISTINCT k.nama_kriteria) as kriteria_penilaian,
          COUNT(DISTINCT p.id) as jumlah_penilaian
          FROM dosen d
          LEFT JOIN penilaian p ON d.id = p.dosen_id 
          AND p.tahun_akademik = ? 
          AND p.semester = ?
          AND p.penilai_id = ?
          LEFT JOIN kriteria k ON k.id NOT IN (
              SELECT kriteria_id 
              FROM penilaian 
              WHERE dosen_id = d.id 
              AND tahun_akademik = ? 
              AND semester = ? 
              AND penilai_id = ?
          )
          WHERE 1=1";

$params = [$tahun_akademik, $semester, $_SESSION['user_id'], $tahun_akademik, $semester, $_SESSION['user_id']];
$types = "ssisss";

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

$query .= " GROUP BY d.id ORDER BY d.nama";

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

// Query untuk mendapatkan daftar kriteria
$kriteria_query = "SELECT * FROM kriteria ORDER BY nama_kriteria";
$kriteria_result = $conn->query($kriteria_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Dosen - SPK Dosen Terbaik</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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

        .dosen-card {
            transition: transform 0.3s;
        }

        .dosen-card:hover {
            transform: translateY(-5px);
        }

        .badge-kriteria {
            font-size: 0.8rem;
            margin: 0.2rem;
            padding: 0.4rem 0.6rem;
        }

        .progress {
            height: 0.5rem;
        }

        .btn-nilai {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
                    <h2>Penilaian Dosen</h2>
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

                <!-- Dosen List -->
                <div class="row">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card dosen-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($row['nama']); ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                NIP: <?php echo htmlspecialchars($row['nip']); ?><br>
                                                <?php echo htmlspecialchars($row['fakultas']); ?> - 
                                                <?php echo htmlspecialchars($row['prodi']); ?>
                                            </small>
                                        </p>
                                        
                                        <?php if (!empty($row['kriteria_penilaian'])): ?>
                                            <div class="mb-3">
                                                <h6 class="mb-2">Kriteria yang Perlu Dinilai:</h6>
                                                <?php 
                                                $kriteria = explode(',', $row['kriteria_penilaian']);
                                                foreach ($kriteria as $k): 
                                                ?>
                                                    <span class="badge bg-warning text-dark badge-kriteria">
                                                        <?php echo htmlspecialchars($k); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-success mb-3">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Semua kriteria telah dinilai
                                            </div>
                                        <?php endif; ?>

                                        <div class="progress mb-3">
                                            <?php 
                                            $total_kriteria = $kriteria_result->num_rows;
                                            $progress = ($row['jumlah_penilaian'] / $total_kriteria) * 100;
                                            ?>
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $progress; ?>%"
                                                 aria-valuenow="<?php echo $progress; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo round($progress); ?>%
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary btn-nilai" 
                                                onclick="showPenilaianModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama']); ?>')">
                                            <i class="fas fa-star me-2"></i>Beri Penilaian
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Tidak ada dosen yang perlu dinilai untuk filter yang dipilih.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Penilaian Modal -->
    <div class="modal fade" id="penilaianModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Beri Penilaian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_penilaian.php" method="POST">
                    <input type="hidden" name="dosen_id" id="dosen_id">
                    <input type="hidden" name="tahun_akademik" value="<?php echo $tahun_akademik; ?>">
                    <input type="hidden" name="semester" value="<?php echo $semester; ?>">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Dosen</label>
                            <input type="text" class="form-control" id="dosen_nama" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kriteria</label>
                            <select name="kriteria_id" class="form-select" required>
                                <option value="">Pilih Kriteria</option>
                                <?php 
                                $kriteria_result->data_seek(0);
                                while ($k = $kriteria_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $k['id']; ?>">
                                        <?php echo htmlspecialchars($k['nama_kriteria']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nilai (0-100)</label>
                            <input type="number" name="nilai" class="form-control" min="0" max="100" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Penilaian</button>
                    </div>
                </form>
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
        function showPenilaianModal(dosenId, dosenNama) {
            document.getElementById('dosen_id').value = dosenId;
            document.getElementById('dosen_nama').value = dosenNama;
            new bootstrap.Modal(document.getElementById('penilaianModal')).show();
        }
    </script>
</body>
</html> 