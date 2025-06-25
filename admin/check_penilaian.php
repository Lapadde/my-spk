<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

echo "<h2>Cek Data Penilaian</h2>";

// Cek semua data penilaian
echo "<h3>Semua Data Penilaian:</h3>";
$result = $conn->query("SELECT p.*, d.nama as nama_dosen, k.nama_kriteria FROM penilaian p JOIN dosen d ON p.dosen_id = d.id JOIN kriteria k ON p.kriteria_id = k.id ORDER BY p.tahun_akademik, p.semester, d.nama");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Dosen</th><th>Kriteria</th><th>Nilai</th><th>Tahun Akademik</th><th>Semester</th><th>Penilai ID</th><th>Keterangan</th>";
    echo "</tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nama_dosen'] . "</td>";
        echo "<td>" . $row['nama_kriteria'] . "</td>";
        echo "<td>" . $row['nilai'] . "</td>";
        echo "<td>" . $row['tahun_akademik'] . "</td>";
        echo "<td>" . $row['semester'] . "</td>";
        echo "<td>" . (isset($row['penilai_id']) ? $row['penilai_id'] : (isset($row['evaluator_id']) ? $row['evaluator_id'] : 'N/A')) . "</td>";
        echo "<td>" . (isset($row['keterangan']) ? $row['keterangan'] : '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Tidak ada data penilaian sama sekali!</p>";
}

// Cek jumlah penilaian per tahun/semester
echo "<h3>Jumlah Penilaian per Tahun/Semester:</h3>";
$result = $conn->query("SELECT tahun_akademik, semester, COUNT(*) as jumlah FROM penilaian GROUP BY tahun_akademik, semester ORDER BY tahun_akademik, semester");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Tahun Akademik</th><th>Semester</th><th>Jumlah Penilaian</th>";
    echo "</tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['tahun_akademik'] . "</td>";
        echo "<td>" . $row['semester'] . "</td>";
        echo "<td>" . $row['jumlah'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Tidak ada data penilaian!</p>";
}

// Cek penilaian untuk tahun 2024/2025 semester Genap
echo "<h3>Penilaian untuk 2024/2025 Semester Genap:</h3>";
$result = $conn->query("SELECT p.*, d.nama as nama_dosen, k.nama_kriteria FROM penilaian p JOIN dosen d ON p.dosen_id = d.id JOIN kriteria k ON p.kriteria_id = k.id WHERE p.tahun_akademik = '2024/2025' AND p.semester = 'Genap'");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Dosen</th><th>Kriteria</th><th>Nilai</th>";
    echo "</tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['nama_dosen'] . "</td>";
        echo "<td>" . $row['nama_kriteria'] . "</td>";
        echo "<td>" . $row['nilai'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'>Ditemukan " . $result->num_rows . " data penilaian untuk 2024/2025 semester Genap</p>";
} else {
    echo "<p style='color: red;'>Tidak ada penilaian untuk 2024/2025 semester Genap!</p>";
}

// Cek penilaian untuk tahun 2023/2024 semester Ganjil
echo "<h3>Penilaian untuk 2023/2024 Semester Ganjil:</h3>";
$result = $conn->query("SELECT p.*, d.nama as nama_dosen, k.nama_kriteria FROM penilaian p JOIN dosen d ON p.dosen_id = d.id JOIN kriteria k ON p.kriteria_id = k.id WHERE p.tahun_akademik = '2023/2024' AND p.semester = 'Ganjil'");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Dosen</th><th>Kriteria</th><th>Nilai</th>";
    echo "</tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['nama_dosen'] . "</td>";
        echo "<td>" . $row['nama_kriteria'] . "</td>";
        echo "<td>" . $row['nilai'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'>Ditemukan " . $result->num_rows . " data penilaian untuk 2023/2024 semester Ganjil</p>";
} else {
    echo "<p style='color: red;'>Tidak ada penilaian untuk 2023/2024 semester Ganjil!</p>";
}

echo "<br><a href='penilaian.php'>Kembali ke Halaman Penilaian</a> | ";
echo "<a href='ahp_calculation.php'>Kembali ke Halaman AHP</a>";
?> 