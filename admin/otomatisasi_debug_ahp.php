<?php
session_start();
require_once '../db/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Akses ditolak');
}

echo "<h2>Debug & Otomatisasi AHP</h2>";

// 1. Tampilkan bobot kriteria
echo "<h3>Bobot Kriteria Saat Ini:</h3>";
$res = $conn->query("SELECT id, nama_kriteria, bobot FROM kriteria ORDER BY id");
echo "<table border='1'><tr><th>ID</th><th>Nama Kriteria</th><th>Bobot</th></tr>";
while ($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['nama_kriteria']}</td><td>{$row['bobot']}</td></tr>";
}
echo "</table>";

// 2. Tampilkan nilai penilaian dosen
$tahun_akademik = isset($_GET['tahun']) ? $_GET['tahun'] : '2024/2025';
$semester = isset($_GET['semester']) ? $_GET['semester'] : 'Genap';
echo "<h3>Nilai Penilaian Dosen ($tahun_akademik, $semester):</h3>";
$res = $conn->query("SELECT p.id, d.nama as dosen, k.nama_kriteria, p.nilai FROM penilaian p JOIN dosen d ON p.dosen_id = d.id JOIN kriteria k ON p.kriteria_id = k.id WHERE p.tahun_akademik = '$tahun_akademik' AND p.semester = '$semester' ORDER BY d.nama, k.nama_kriteria");
echo "<table border='1'><tr><th>ID</th><th>Dosen</th><th>Kriteria</th><th>Nilai</th></tr>";
while ($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['dosen']}</td><td>{$row['nama_kriteria']}</td><td>{$row['nilai']}</td></tr>";
}
echo "</table>";

// 3. Tombol untuk mengacak nilai penilaian dosen
if (isset($_GET['acak_nilai'])) {
    $res = $conn->query("SELECT id FROM penilaian WHERE tahun_akademik = '$tahun_akademik' AND semester = '$semester'");
    while ($row = $res->fetch_assoc()) {
        $nilai_baru = rand(60, 100);
        $conn->query("UPDATE penilaian SET nilai = $nilai_baru WHERE id = {$row['id']}");
    }
    echo "<p style='color:green'>Nilai penilaian dosen berhasil diacak!</p>";
}
echo "<form method='get'><input type='hidden' name='tahun' value='$tahun_akademik'><input type='hidden' name='semester' value='$semester'><button type='submit' name='acak_nilai' value='1'>Acak Nilai Penilaian Dosen</button></form>";

// 4. Tombol untuk mengacak bobot kriteria
if (isset($_GET['acak_bobot'])) {
    $res = $conn->query("SELECT id FROM kriteria");
    $total = 0;
    $bobot = [];
    while ($row = $res->fetch_assoc()) {
        $bobot[$row['id']] = rand(10, 50);
        $total += $bobot[$row['id']];
    }
    // Normalisasi bobot
    foreach ($bobot as $id => $b) {
        $bobot_n = $b / $total;
        $conn->query("UPDATE kriteria SET bobot = $bobot_n WHERE id = $id");
    }
    echo "<p style='color:green'>Bobot kriteria berhasil diacak dan dinormalisasi!</p>";
}
echo "<form method='get'><button type='submit' name='acak_bobot' value='1'>Acak Bobot Kriteria</button></form>";

echo "<br><a href='ahp_calculation.php'>Kembali ke Perhitungan AHP</a> | <a href='hasil.php'>Lihat Hasil</a>"; 