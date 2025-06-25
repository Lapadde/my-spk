<?php
session_start();
require_once '../db/config.php';

// Hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Akses ditolak');
}

$tahun_akademik = '2024/2025'; // Ganti sesuai kebutuhan
$semester = 'Genap'; // Ganti sesuai kebutuhan

// Ambil semua penilaian untuk tahun & semester ini
$result = $conn->query("SELECT id FROM penilaian WHERE tahun_akademik = '$tahun_akademik' AND semester = '$semester'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nilai_baru = rand(60, 100); // Nilai acak antara 60-100
        $conn->query("UPDATE penilaian SET nilai = $nilai_baru WHERE id = " . $row['id']);
    }
    echo "<p>✅ Nilai penilaian dosen berhasil diacak untuk $tahun_akademik semester $semester.</p>";
} else {
    echo "<p>Tidak ada data penilaian untuk $tahun_akademik semester $semester.</p>";
}
echo "<a href='check_penilaian.php'>Cek Data Penilaian</a>";
?>