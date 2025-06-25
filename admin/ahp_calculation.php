<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

class AHPCalculation {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Skala AHP (1-9)
    private $ahp_scale = [
        1 => 'Sama penting',
        2 => 'Sedikit lebih penting',
        3 => 'Lebih penting',
        4 => 'Jauh lebih penting',
        5 => 'Sangat lebih penting',
        6 => 'Sangat jauh lebih penting',
        7 => 'Ekstrim lebih penting',
        8 => 'Sangat ekstrim lebih penting',
        9 => 'Mutlak lebih penting'
    ];
    
    // Random Index (RI) untuk uji konsistensi
    private $random_index = [
        1 => 0, 2 => 0, 3 => 0.58, 4 => 0.90, 5 => 1.12,
        6 => 1.24, 7 => 1.32, 8 => 1.41, 9 => 1.45, 10 => 1.49
    ];
    
    /**
     * Membuat matriks perbandingan berpasangan untuk kriteria
     */
    public function createCriteriaComparisonMatrix($tahun_akademik, $semester) {
        $query = "SELECT * FROM kriteria ORDER BY id ASC";
        $result = $this->conn->query($query);
        $criteria = [];
        while ($row = $result->fetch_assoc()) {
            $criteria[] = $row;
        }
        
        $n = count($criteria);
        $matrix = [];
        
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($i == $j) {
                    $matrix[$i][$j] = 1;
                } else {
                    $matrix[$i][$j] = 0;
                }
            }
        }
        
        return [
            'criteria' => $criteria,
            'matrix' => $matrix,
            'size' => $n
        ];
    }
    
    /**
     * Menyimpan matriks perbandingan kriteria
     */
    public function saveCriteriaComparison($comparisons) {
        $this->conn->query("DELETE FROM perbandingan_kriteria");
        
        $stmt = $this->conn->prepare("INSERT INTO perbandingan_kriteria (kriteria1_id, kriteria2_id, nilai) VALUES (?, ?, ?)");
        
        foreach ($comparisons as $comp) {
            $stmt->bind_param("iid", $comp['kriteria1_id'], $comp['kriteria2_id'], $comp['nilai']);
            $stmt->execute();
        }
        
        return true;
    }
    
    /**
     * Menghitung bobot kriteria menggunakan AHP
     */
    public function calculateCriteriaWeights($tahun_akademik, $semester) {
        // Cek apakah ada kriteria
        $criteria_check = $this->conn->query("SELECT COUNT(*) as count FROM kriteria");
        $criteria_count = $criteria_check->fetch_assoc()['count'];
        
        if ($criteria_count == 0) {
            return ['error' => 'Belum ada kriteria yang ditambahkan. Silakan tambahkan kriteria terlebih dahulu.'];
        }
        
        $query = "SELECT pk.*, k1.nama_kriteria as kriteria1_nama, k2.nama_kriteria as kriteria2_nama 
                  FROM perbandingan_kriteria pk
                  JOIN kriteria k1 ON pk.kriteria1_id = k1.id
                  JOIN kriteria k2 ON pk.kriteria2_id = k2.id
                  ORDER BY pk.kriteria1_id, pk.kriteria2_id";
        $result = $this->conn->query($query);
        
        $comparisons = [];
        while ($row = $result->fetch_assoc()) {
            $comparisons[] = $row;
        }
        
        if (empty($comparisons)) {
            return ['error' => 'Matriks perbandingan belum dibuat. Silakan isi matriks perbandingan terlebih dahulu.'];
        }
        
        $criteria_query = "SELECT * FROM kriteria ORDER BY id ASC";
        $criteria_result = $this->conn->query($criteria_query);
        $criteria = [];
        while ($row = $criteria_result->fetch_assoc()) {
            $criteria[] = $row;
        }
        
        $n = count($criteria);
        $matrix = [];
        
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $matrix[$i][$j] = 1;
            }
        }
        
        foreach ($comparisons as $comp) {
            $i = array_search($comp['kriteria1_id'], array_column($criteria, 'id'));
            $j = array_search($comp['kriteria2_id'], array_column($criteria, 'id'));
            
            if ($i !== false && $j !== false) {
                $matrix[$i][$j] = $comp['nilai'];
                $matrix[$j][$i] = 1 / $comp['nilai'];
            }
        }
        
        $column_sums = [];
        for ($j = 0; $j < $n; $j++) {
            $sum = 0;
            for ($i = 0; $i < $n; $i++) {
                $sum += $matrix[$i][$j];
            }
            $column_sums[$j] = $sum;
        }
        
        $normalized_matrix = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $normalized_matrix[$i][$j] = $matrix[$i][$j] / $column_sums[$j];
            }
        }
        
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $sum = 0;
            for ($j = 0; $j < $n; $j++) {
                $sum += $normalized_matrix[$i][$j];
            }
            $weights[$i] = $sum / $n;
        }
        
        $lambda_max = 0;
        for ($j = 0; $j < $n; $j++) {
            $sum = 0;
            for ($i = 0; $i < $n; $i++) {
                $sum += $matrix[$i][$j] * $weights[$i];
            }
            $lambda_max += $sum / $weights[$j];
        }
        $lambda_max = $lambda_max / $n;
        
        $ci = ($lambda_max - $n) / ($n - 1);
        $ri = isset($this->random_index[$n]) ? $this->random_index[$n] : 1.49;
        $cr = $ci / $ri;
        
        for ($i = 0; $i < $n; $i++) {
            $stmt = $this->conn->prepare("UPDATE kriteria SET bobot = ? WHERE id = ?");
            $stmt->bind_param("di", $weights[$i], $criteria[$i]['id']);
            $stmt->execute();
        }
        
        return [
            'criteria' => $criteria,
            'weights' => $weights,
            'lambda_max' => $lambda_max,
            'ci' => $ci,
            'cr' => $cr,
            'is_consistent' => $cr < 0.1,
            'matrix' => $matrix,
            'normalized_matrix' => $normalized_matrix
        ];
    }
    
    /**
     * Menghitung nilai akhir dosen menggunakan bobot kriteria
     */
    public function calculateFinalScores($tahun_akademik, $semester) {
        $dosen_query = "SELECT * FROM dosen ORDER BY nama ASC";
        $dosen_result = $this->conn->query($dosen_query);
        $dosen = [];
        while ($row = $dosen_result->fetch_assoc()) {
            $dosen[] = $row;
        }
        
        $kriteria_query = "SELECT * FROM kriteria ORDER BY id ASC";
        $kriteria_result = $this->conn->query($kriteria_query);
        $kriteria = [];
        while ($row = $kriteria_result->fetch_assoc()) {
            $kriteria[] = $row;
        }
        
        $final_scores = [];
        
        foreach ($dosen as $d) {
            $total_score = 0;
            $criteria_scores = [];
            
            foreach ($kriteria as $k) {
                $nilai_query = "SELECT AVG(nilai) as rata_rata 
                               FROM penilaian 
                               WHERE dosen_id = ? AND kriteria_id = ? 
                               AND tahun_akademik = ? AND semester = ?";
                $stmt = $this->conn->prepare($nilai_query);
                $stmt->bind_param("iiss", $d['id'], $k['id'], $tahun_akademik, $semester);
                $stmt->execute();
                $result = $stmt->get_result();
                $nilai = $result->fetch_assoc()['rata_rata'];
                
                if ($nilai === null) {
                    $nilai = 0;
                }
                
                $weighted_score = $nilai * $k['bobot'];
                $total_score += $weighted_score;
                
                $criteria_scores[$k['nama_kriteria']] = [
                    'nilai' => $nilai,
                    'bobot' => $k['bobot'],
                    'weighted_score' => $weighted_score
                ];
            }
            
            $final_scores[] = [
                'dosen_id' => $d['id'],
                'nama' => $d['nama'],
                'nip' => $d['nip'],
                'fakultas' => $d['fakultas'],
                'program_studi' => isset($d['prodi']) ? $d['prodi'] : (isset($d['program_studi']) ? $d['program_studi'] : ''),
                'total_score' => $total_score,
                'criteria_scores' => $criteria_scores
            ];
        }
        
        usort($final_scores, function($a, $b) {
            return $b['total_score'] <=> $a['total_score'];
        });
        
        for ($i = 0; $i < count($final_scores); $i++) {
            $final_scores[$i]['ranking'] = $i + 1;
        }
        
        return $final_scores;
    }
    
    /**
     * Menyimpan hasil akhir ke database
     */
    public function saveFinalResults($final_scores, $tahun_akademik, $semester) {
        $delete_query = "DELETE FROM hasil_akhir WHERE tahun_akademik = ? AND semester = ?";
        $stmt = $this->conn->prepare($delete_query);
        $stmt->bind_param("ss", $tahun_akademik, $semester);
        $stmt->execute();
        
        $insert_query = "INSERT INTO hasil_akhir (dosen_id, tahun_akademik, semester, total_score, ranking) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($insert_query);
        
        foreach ($final_scores as $score) {
            $stmt->bind_param("issdi", 
                $score['dosen_id'], 
                $tahun_akademik, 
                $semester, 
                $score['total_score'], 
                $score['ranking']
            );
            $stmt->execute();
        }
        
        return true;
    }
    
    /**
     * Menjalankan seluruh proses AHP
     */
    public function runAHPCalculation($tahun_akademik, $semester) {
        // Cek apakah ada kriteria
        $criteria_check = $this->conn->query("SELECT COUNT(*) as count FROM kriteria");
        $criteria_count = $criteria_check->fetch_assoc()['count'];
        
        if ($criteria_count == 0) {
            return ['error' => 'Belum ada kriteria yang ditambahkan. Silakan tambahkan kriteria terlebih dahulu.'];
        }
        
        // Cek apakah ada dosen
        $dosen_check = $this->conn->query("SELECT COUNT(*) as count FROM dosen");
        $dosen_count = $dosen_check->fetch_assoc()['count'];
        
        if ($dosen_count == 0) {
            return ['error' => 'Belum ada dosen yang ditambahkan. Silakan tambahkan dosen terlebih dahulu.'];
        }
        
        // Cek apakah ada penilaian
        $penilaian_check = $this->conn->query("SELECT COUNT(*) as count FROM penilaian WHERE tahun_akademik = '$tahun_akademik' AND semester = '$semester'");
        $penilaian_count = $penilaian_check->fetch_assoc()['count'];
        
        if ($penilaian_count == 0) {
            return ['error' => "Belum ada penilaian untuk tahun akademik $tahun_akademik semester $semester. Silakan lakukan penilaian terlebih dahulu."];
        }
        
        $criteria_result = $this->calculateCriteriaWeightsFromDatabase($tahun_akademik, $semester);
        
        if (isset($criteria_result['error'])) {
            return $criteria_result;
        }
        
        $final_scores = $this->calculateFinalScores($tahun_akademik, $semester);
        $this->saveFinalResults($final_scores, $tahun_akademik, $semester);
        
        return [
            'criteria_weights' => $criteria_result,
            'final_scores' => $final_scores,
            'success' => true
        ];
    }
    
    /**
     * Menghitung bobot kriteria langsung dari database tanpa matriks perbandingan
     */
    public function calculateCriteriaWeightsFromDatabase($tahun_akademik, $semester) {
        // Cek apakah ada kriteria
        $criteria_check = $this->conn->query("SELECT COUNT(*) as count FROM kriteria");
        $criteria_count = $criteria_check->fetch_assoc()['count'];
        
        if ($criteria_count == 0) {
            return ['error' => 'Belum ada kriteria yang ditambahkan. Silakan tambahkan kriteria terlebih dahulu.'];
        }
        
        $criteria_query = "SELECT * FROM kriteria ORDER BY id ASC";
        $criteria_result = $this->conn->query($criteria_query);
        $criteria = [];
        $weights = [];
        $total_bobot = 0;
        
        while ($row = $criteria_result->fetch_assoc()) {
            $criteria[] = $row;
            $total_bobot += $row['bobot'];
        }
        
        // Normalisasi bobot jika total tidak sama dengan 1
        if ($total_bobot > 0) {
            for ($i = 0; $i < count($criteria); $i++) {
                $weights[$i] = $criteria[$i]['bobot'] / $total_bobot;
            }
        } else {
            return ['error' => 'Bobot kriteria belum diisi. Silakan isi bobot kriteria terlebih dahulu.'];
        }
        
        // Update bobot yang sudah dinormalisasi ke database
        for ($i = 0; $i < count($criteria); $i++) {
            $stmt = $this->conn->prepare("UPDATE kriteria SET bobot = ? WHERE id = ?");
            $stmt->bind_param("di", $weights[$i], $criteria[$i]['id']);
            $stmt->execute();
        }
        
        // Hitung uji konsistensi sederhana (karena tidak ada matriks perbandingan)
        $n = count($criteria);
        $lambda_max = $n; // Ideal case
        $ci = 0; // Perfect consistency
        $ri = isset($this->random_index[$n]) ? $this->random_index[$n] : 1.49;
        $cr = 0; // Perfect consistency
        
        return [
            'criteria' => $criteria,
            'weights' => $weights,
            'lambda_max' => $lambda_max,
            'ci' => $ci,
            'cr' => $cr,
            'is_consistent' => true, // Selalu konsisten karena menggunakan bobot langsung
            'source' => 'database_weights'
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ahp = new AHPCalculation($conn);
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_comparison':
                $comparisons = json_decode($_POST['comparisons'], true);
                $result = $ahp->saveCriteriaComparison($comparisons);
                echo json_encode(['success' => $result]);
                break;
                
            case 'calculate_weights':
                $tahun = $_POST['tahun_akademik'];
                $semester = $_POST['semester'];
                $result = $ahp->calculateCriteriaWeights($tahun, $semester);
                echo json_encode($result);
                break;
                
            case 'run_calculation':
                $tahun = $_POST['tahun_akademik'];
                $semester = $_POST['semester'];
                $result = $ahp->runAHPCalculation($tahun, $semester);
                echo json_encode($result);
                break;
                
            case 'get_criteria_weights':
                $query = "SELECT id, nama_kriteria, bobot FROM kriteria ORDER BY id ASC";
                $result = $conn->query($query);
                $criteria = [];
                while ($row = $result->fetch_assoc()) {
                    $criteria[] = $row;
                }
                echo json_encode(['success' => true, 'criteria' => $criteria]);
                break;
        }
        exit();
    }
}

// Ambil data untuk halaman
$ahp = new AHPCalculation($conn);
$tahun_akademik = isset($_GET['tahun']) ? $_GET['tahun'] : '2023/2024';
$semester = isset($_GET['semester']) ? $_GET['semester'] : 'Ganjil';

$criteria_matrix = $ahp->createCriteriaComparisonMatrix($tahun_akademik, $semester);
$criteria_weights = $ahp->calculateCriteriaWeightsFromDatabase($tahun_akademik, $semester);
$final_scores = [];
$res = $conn->query("SELECT h.*, d.nama, d.nip, d.fakultas, d.prodi FROM hasil_akhir h JOIN dosen d ON h.dosen_id = d.id WHERE h.tahun_akademik = '$tahun_akademik' AND h.semester = '$semester' ORDER BY h.ranking ASC");
while ($row = $res->fetch_assoc()) {
    $final_scores[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perhitungan AHP - SPK Dosen Terbaik</title>
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
        
        .comparison-matrix {
            font-size: 0.9rem;
        }
        
        .comparison-matrix input {
            width: 60px;
            text-align: center;
        }
        
        .weight-highlight {
            background-color: #d4edda;
            font-weight: bold;
        }
        
        .consistency-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .consistency-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
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
                            <a class="nav-link active" href="ahp_calculation.php">
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
                        <h2><i class="fas fa-calculator me-2"></i>Perhitungan AHP</h2>
                        <div>
                            <button class="btn btn-primary" onclick="runFullCalculation()">
                                <i class="fas fa-play me-2"></i>Jalankan Perhitungan AHP (Dengan Bobot Database)
                            </button>
                        </div>
                    </div>
                    
                    <!-- Informasi Sistem -->
                    <div class="alert alert-primary">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Sistem AHP yang Diperbarui:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Bobot kriteria diambil langsung dari database (tabel kriteria)</li>
                            <li>Tidak perlu mengisi matriks perbandingan manual</li>
                            <li>Sistem otomatis konsisten karena menggunakan bobot yang sudah ditentukan</li>
                            <li>Perhitungan lebih cepat dan akurat</li>
                        </ul>
                    </div>
                    
                    <!-- Filter Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="tahun_akademik" class="form-label">Tahun Akademik</label>
                                    <select class="form-select" id="tahun_akademik">
                                        <option value="2023/2024" <?php echo $tahun_akademik == '2023/2024' ? 'selected' : ''; ?>>2023/2024</option>
                                        <option value="2024/2025" <?php echo $tahun_akademik == '2024/2025' ? 'selected' : ''; ?>>2024/2025</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="semester" class="form-label">Semester</label>
                                    <select class="form-select" id="semester">
                                        <option value="Ganjil" <?php echo $semester == 'Ganjil' ? 'selected' : ''; ?>>Ganjil</option>
                                        <option value="Genap" <?php echo $semester == 'Genap' ? 'selected' : ''; ?>>Genap</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 1: Matriks Perbandingan Kriteria -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Step 1: Bobot Kriteria (Langsung dari Database)</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Sistem menggunakan bobot kriteria langsung dari database!</strong>
                                <br>
                                Tidak perlu mengisi matriks perbandingan manual. Bobot kriteria diambil langsung dari tabel kriteria.
                            </div>
                            
                            <?php if (!isset($criteria_weights['error'])): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kriteria</th>
                                            <th>Bobot</th>
                                            <th>Persentase</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($criteria_weights['criteria'] as $i => $criteria): ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td><strong><?php echo htmlspecialchars($criteria['nama_kriteria']); ?></strong></td>
                                            <td><?php echo number_format($criteria_weights['weights'][$i], 4); ?></td>
                                            <td><?php echo number_format($criteria_weights['weights'][$i] * 100, 2); ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $criteria_weights['error']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Step 2: Bobot Kriteria -->
                    <?php if (!isset($criteria_weights['error'])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-weight-hanging me-2"></i>Step 2: Uji Konsistensi (Otomatis Konsisten)</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Karena menggunakan bobot langsung dari database, sistem otomatis konsisten!</strong>
                                <br>
                                Tidak perlu uji konsistensi manual karena bobot sudah ditentukan langsung.
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Bobot Kriteria (Dari Database):</h6>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Kriteria</th>
                                                <th>Bobot</th>
                                                <th>Persentase</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($criteria_weights['criteria'] as $i => $criteria): ?>
                                            <tr class="weight-highlight">
                                                <td><?php echo htmlspecialchars($criteria['nama_kriteria']); ?></td>
                                                <td><?php echo number_format($criteria_weights['weights'][$i], 4); ?></td>
                                                <td><?php echo number_format($criteria_weights['weights'][$i] * 100, 2); ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Status Konsistensi:</h6>
                                    <div class="card consistency-success">
                                        <div class="card-body">
                                            <p><strong>λ maks:</strong> <?php echo number_format($criteria_weights['lambda_max'], 4); ?></p>
                                            <p><strong>CI (Consistency Index):</strong> <?php echo number_format($criteria_weights['ci'], 4); ?></p>
                                            <p><strong>CR (Consistency Ratio):</strong> <?php echo number_format($criteria_weights['cr'], 4); ?></p>
                                            <p><strong>Status:</strong> 
                                                <span class="badge bg-success">Konsisten (Bobot dari Database)</span>
                                            </p>
                                            <p><strong>Sumber:</strong> 
                                                <span class="badge bg-info">Database Kriteria</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Step 3: Hasil Akhir -->
                    <?php if (!empty($final_scores)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Step 3: Hasil Akhir & Perangkingan</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Ranking</th>
                                            <th>Nama Dosen</th>
                                            <th>NIP</th>
                                            <th>Fakultas</th>
                                            <th>Program Studi</th>
                                            <th>Total Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($final_scores as $score): ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $score['ranking'] == 1 ? 'bg-warning' : 
                                                        ($score['ranking'] == 2 ? 'bg-secondary' : 
                                                        ($score['ranking'] == 3 ? 'bg-danger' : 'bg-primary')); 
                                                ?>">
                                                    <?php echo $score['ranking']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($score['nama']); ?></td>
                                            <td><?php echo htmlspecialchars($score['nip']); ?></td>
                                            <td><?php echo htmlspecialchars($score['fakultas']); ?></td>
                                            <td><?php echo htmlspecialchars($score['program_studi']); ?></td>
                                            <td><strong><?php echo number_format($score['total_score'], 4); ?></strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function runFullCalculation() {
            const tahun = document.getElementById('tahun_akademik').value;
            const semester = document.getElementById('semester').value;
            
            // Tampilkan loading
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghitung...';
            button.disabled = true;
            
            fetch('ahp_calculation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=run_calculation&tahun_akademik=${tahun}&semester=${semester}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Perhitungan AHP berhasil diselesaikan menggunakan bobot dari database!');
                    location.reload();
                } else if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    alert('Gagal menjalankan perhitungan AHP! Silakan cek data kriteria dan penilaian.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan dalam menjalankan perhitungan AHP!');
            })
            .finally(() => {
                // Kembalikan button ke kondisi semula
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    </script>
</body>
</html> 