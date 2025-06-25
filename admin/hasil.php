<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Ambil tahun akademik dan semester dari parameter URL, default ke tahun/semester terbaru
$tahun_akademik = isset($_GET['tahun']) ? $_GET['tahun'] : '2023/2024';
$semester = isset($_GET['semester']) ? $_GET['semester'] : 'Ganjil';

// Inisialisasi variabel untuk menyimpan hasil query
$result = null;
$result_kriteria = null;
$result_detail = null;
$total_bobot = 0;

// Cek apakah tabel yang diperlukan sudah ada
$tables_exist = true;
$required_tables = ['hasil_akhir', 'dosen', 'kriteria', 'penilaian'];
foreach ($required_tables as $table) {
    $check_table = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check_table->num_rows == 0) {
        $tables_exist = false;
        break;
    }
}

if ($tables_exist) {
    // Query untuk mendapatkan hasil penilaian
    $query = "SELECT d.*, d.nama as nama_dosen, d.prodi as program_studi,
              (SELECT SUM(p.nilai * k.bobot / (SELECT SUM(bobot) FROM kriteria)) 
               FROM penilaian p 
               JOIN kriteria k ON p.kriteria_id = k.id 
               WHERE p.dosen_id = d.id AND p.tahun_akademik = ? AND p.semester = ?) as total_score,
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

    $query .= " ORDER BY total_score DESC";

    // Prepare statement
    $stmt = $conn->prepare($query);

    // Bind parameters
    $params = [$tahun_akademik, $semester, $tahun_akademik, $semester];
    if (!empty($fakultas)) {
        $params[] = $fakultas;
    }
    if (!empty($prodi)) {
        $params[] = $prodi;
    }

    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Query untuk mendapatkan statistik
    $stats_query = "SELECT 
                    COUNT(DISTINCT dosen_id) as total_dosen,
                    COUNT(*) as total_penilaian,
                    AVG(nilai) as rata_rata_nilai
                    FROM penilaian 
                    WHERE tahun_akademik = ? AND semester = ?";
    
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->bind_param('ss', $tahun_akademik, $semester);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();

    // Query untuk mendapatkan distribusi nilai
    $dist_query = "SELECT 
                   CASE 
                       WHEN nilai >= 90 THEN 'Sangat Baik'
                       WHEN nilai >= 80 THEN 'Baik'
                       WHEN nilai >= 70 THEN 'Cukup'
                       WHEN nilai >= 60 THEN 'Kurang'
                       ELSE 'Sangat Kurang'
                   END as kategori,
                   COUNT(*) as jumlah
                   FROM penilaian 
                   WHERE tahun_akademik = ? AND semester = ?
                   GROUP BY kategori
                   ORDER BY MIN(nilai) DESC";
    
    $dist_stmt = $conn->prepare($dist_query);
    $dist_stmt->bind_param('ss', $tahun_akademik, $semester);
    $dist_stmt->execute();
    $dist_result = $dist_stmt->get_result();

    // Query untuk mengambil data kriteria dan bobot
    $query_kriteria = "SELECT * FROM kriteria ORDER BY id ASC";
    $result_kriteria = $conn->query($query_kriteria);
    if ($result_kriteria) {
        while ($kriteria = $result_kriteria->fetch_assoc()) {
            $total_bobot += $kriteria['bobot'];
        }
        $result_kriteria->data_seek(0); // Reset pointer ke awal
    }

    // Query untuk mengambil data penilaian detail
    $query_detail = "SELECT p.*, d.nama as nama_dosen, k.nama_kriteria, k.bobot
                    FROM penilaian p
                    JOIN dosen d ON p.dosen_id = d.id
                    JOIN kriteria k ON p.kriteria_id = k.id
                    WHERE p.tahun_akademik = ? AND p.semester = ?
                    ORDER BY d.nama ASC, k.id ASC";
    $stmt_detail = $conn->prepare($query_detail);
    if ($stmt_detail) {
        $stmt_detail->bind_param("ss", $tahun_akademik, $semester);
        $stmt_detail->execute();
        $result_detail = $stmt_detail->get_result();
    }
}
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
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .table th {
            background-color: #f8f9fa;
        }

        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .ranking-badge {
            font-size: 1.2em;
            padding: 5px 10px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .ranking-1 { background-color: #FFD700; }
        .ranking-2 { background-color: #C0C0C0; }
        .ranking-3 { background-color: #CD7F32; }
        .ranking-other { background-color: var(--primary-color); }

        .no-data-message {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .no-data-message i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        @media print {
            .sidebar, .btn-print, .filter-section {
                display: none !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            .container-fluid {
                width: 100% !important;
                padding: 0 !important;
            }
            .col-md-9 {
                width: 100% !important;
                padding: 0 !important;
            }
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
                            <a class="nav-link active" href="hasil.php">
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
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-bar me-2"></i>Hasil Penilaian Dosen</h2>
                    <div>
                        <a href="ahp_calculation.php" class="btn btn-info me-2">
                            <i class="fas fa-calculator me-2"></i>Perhitungan AHP
                        </a>
                        <button class="btn btn-primary" onclick="exportToExcel()">
                            <i class="fas fa-download me-2"></i>Export Excel
                        </button>
                    </div>
                </div>
                
                <!-- Info AHP -->
                <div class="alert alert-info mb-4">
                    <h6><i class="fas fa-info-circle me-2"></i>Metode AHP (Analytic Hierarchy Process)</h6>
                    <p class="mb-0">
                        Hasil penilaian ini menggunakan metode AHP yang mempertimbangkan bobot kriteria berdasarkan perbandingan berpasangan. 
                        Untuk melihat detail perhitungan bobot dan uji konsistensi, silakan klik tombol "Perhitungan AHP" di atas.
                    </p>
                </div>

                <?php if (!$tables_exist): ?>
                <div class="alert alert-warning" role="alert">
                    <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Database Belum Siap</h4>
                    <p>Beberapa tabel yang diperlukan belum tersedia dalam database. Silakan pastikan Anda telah:</p>
                    <ol>
                        <li>Membuat database dengan nama <code>my-spk</code></li>
                        <li>Mengimpor struktur tabel dari file <code>database.sql</code></li>
                        <li>Memastikan semua tabel yang diperlukan sudah dibuat:
                            <ul>
                                <li>hasil_akhir</li>
                                <li>dosen</li>
                                <li>kriteria</li>
                                <li>penilaian</li>
                            </ul>
                        </li>
                    </ol>
                    <hr>
                    <p class="mb-0">Setelah database siap, silakan refresh halaman ini.</p>
                </div>
                <?php else: ?>
                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row">
                        <div class="col-md-3">
                            <label class="form-label">Tahun Akademik</label>
                            <select class="form-select" name="tahun">
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
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Hasil Akhir Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Hasil Akhir Perangkingan</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="hasilTable">
                                <thead>
                                    <tr>
                                        <th>Ranking</th>
                                        <th>NIP</th>
                                        <th>Nama Dosen</th>
                                        <th>Jabatan</th>
                                        <th>Fakultas</th>
                                        <th>Program Studi</th>
                                        <th>Total Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $ranking = 1;
                                    while ($row = $result->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="ranking-badge <?php 
                                                echo $ranking == 1 ? 'ranking-1' : 
                                                    ($ranking == 2 ? 'ranking-2' : 
                                                    ($ranking == 3 ? 'ranking-3' : 'ranking-other')); 
                                            ?>">
                                                <?php echo $ranking; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['nip']); ?></td>
                                        <td><?php echo htmlspecialchars(isset($row['nama_dosen']) ? $row['nama_dosen'] : $row['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                                        <td><?php echo htmlspecialchars($row['fakultas']); ?></td>
                                        <td><?php echo htmlspecialchars(isset($row['program_studi']) ? $row['program_studi'] : (isset($row['prodi']) ? $row['prodi'] : '')); ?></td>
                                        <td><?php echo number_format(isset($row['total_score']) ? $row['total_score'] : 0, 4); ?></td>
                                    </tr>
                                    <?php 
                                    $ranking++;
                                    endwhile; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="no-data-message">
                            <i class="fas fa-chart-bar"></i>
                            <h5>Belum ada data hasil</h5>
                            <p>Silakan lakukan penilaian terlebih dahulu</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Detail Penilaian Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Detail Penilaian</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result_detail && $result_detail->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="detailTable">
                                <thead>
                                    <tr>
                                        <th>Nama Dosen</th>
                                        <?php 
                                        $result_kriteria->data_seek(0);
                                        while ($kriteria = $result_kriteria->fetch_assoc()): 
                                        ?>
                                        <th><?php echo htmlspecialchars($kriteria['nama_kriteria']); ?></th>
                                        <?php endwhile; ?>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $current_dosen = '';
                                    $dosen_scores = [];
                                    while ($row = $result_detail->fetch_assoc()):
                                        if ($current_dosen != $row['nama_dosen']):
                                            if ($current_dosen != ''):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($current_dosen); ?></td>
                                        <?php
                                        $total = 0;
                                        $result_kriteria->data_seek(0);
                                        while ($kriteria = $result_kriteria->fetch_assoc()):
                                            $score = isset($dosen_scores[$kriteria['id']]) ? $dosen_scores[$kriteria['id']] : 0;
                                            $weighted_score = $score * ($kriteria['bobot'] / $total_bobot);
                                            $total += $weighted_score;
                                        ?>
                                        <td><?php echo number_format($weighted_score, 4); ?></td>
                                        <?php endwhile; ?>
                                        <td><?php echo number_format($total, 4); ?></td>
                                    </tr>
                                    <?php
                                            endif;
                                            $current_dosen = $row['nama_dosen'];
                                            $dosen_scores = [];
                                        endif;
                                        $dosen_scores[$row['kriteria_id']] = $row['nilai'];
                                    endwhile;
                                    
                                    // Tampilkan data dosen terakhir
                                    if ($current_dosen != ''):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($current_dosen); ?></td>
                                        <?php
                                        $total = 0;
                                        $result_kriteria->data_seek(0);
                                        while ($kriteria = $result_kriteria->fetch_assoc()):
                                            $score = isset($dosen_scores[$kriteria['id']]) ? $dosen_scores[$kriteria['id']] : 0;
                                            $weighted_score = $score * ($kriteria['bobot'] / $total_bobot);
                                            $total += $weighted_score;
                                        ?>
                                        <td><?php echo number_format($weighted_score, 4); ?></td>
                                        <?php endwhile; ?>
                                        <td><?php echo number_format($total, 4); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="no-data-message">
                            <i class="fas fa-table"></i>
                            <h5>Belum ada data penilaian</h5>
                            <p>Silakan tambahkan data penilaian terlebih dahulu</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#hasilTable').DataTable({
                "pageLength": 25,
                "order": [[ 6, "desc" ]]
            });
        });
        
        function exportToExcel() {
            // Ambil data dari tabel
            const table = document.getElementById('hasilTable');
            const rows = table.querySelectorAll('tbody tr');
            
            const data = [];
            
            // Header
            data.push(['Ranking', 'NIP', 'Nama Dosen', 'Jabatan', 'Fakultas', 'Program Studi', 'Total Score']);
            
            // Data rows
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const rowData = [];
                
                cells.forEach((cell, index) => {
                    if (index === 0) {
                        // Ranking
                        const badge = cell.querySelector('.ranking-badge');
                        rowData.push(badge ? badge.textContent.trim() : cell.textContent.trim());
                    } else {
                        rowData.push(cell.textContent.trim());
                    }
                });
                
                if (rowData.length > 0) {
                    data.push(rowData);
                }
            });
            
            // Buat workbook
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            
            // Set column widths
            ws['!cols'] = [
                { width: 10 }, // Ranking
                { width: 20 }, // NIP
                { width: 30 }, // Nama Dosen
                { width: 20 }, // Jabatan
                { width: 25 }, // Fakultas
                { width: 25 }, // Program Studi
                { width: 15 }  // Total Score
            ];
            
            XLSX.utils.book_append_sheet(wb, ws, 'Hasil Penilaian Dosen');
            
            // Export file
            const fileName = `Hasil_Penilaian_Dosen_${new Date().toISOString().split('T')[0]}.xlsx`;
            XLSX.writeFile(wb, fileName);
        }
    </script>
</body>
</html> 