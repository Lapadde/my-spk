<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role penilai
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penilai') {
    header("Location: ../login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan bersihkan data
    $dosen_id = clean_input($_POST['dosen_id']);
    $kriteria_id = clean_input($_POST['kriteria_id']);
    $nilai = clean_input($_POST['nilai']);
    $keterangan = clean_input($_POST['keterangan']);
    $tahun_akademik = clean_input($_POST['tahun_akademik']);
    $semester = clean_input($_POST['semester']);
    $penilai_id = $_SESSION['user_id'];

    // Validasi input
    if (empty($dosen_id) || empty($kriteria_id) || empty($nilai) || empty($tahun_akademik) || empty($semester)) {
        header("Location: penilaian.php?error=Semua+field+harus+diisi");
        exit();
    }

    // Validasi nilai
    if (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        header("Location: penilaian.php?error=Nilai+harus+antara+0-100");
        exit();
    }

    // Cek apakah penilaian sudah ada
    $check_query = "SELECT id FROM penilaian 
                   WHERE dosen_id = ? 
                   AND kriteria_id = ? 
                   AND tahun_akademik = ? 
                   AND semester = ? 
                   AND penilai_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("iisss", $dosen_id, $kriteria_id, $tahun_akademik, $semester, $penilai_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update penilaian yang sudah ada
        $query = "UPDATE penilaian 
                 SET nilai = ?, keterangan = ?, updated_at = CURRENT_TIMESTAMP 
                 WHERE dosen_id = ? 
                 AND kriteria_id = ? 
                 AND tahun_akademik = ? 
                 AND semester = ? 
                 AND penilai_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("dsiisss", $nilai, $keterangan, $dosen_id, $kriteria_id, $tahun_akademik, $semester, $penilai_id);
    } else {
        // Insert penilaian baru
        $query = "INSERT INTO penilaian (dosen_id, kriteria_id, penilai_id, nilai, keterangan, tahun_akademik, semester) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiidsss", $dosen_id, $kriteria_id, $penilai_id, $nilai, $keterangan, $tahun_akademik, $semester);
    }

    if ($stmt->execute()) {
        // Log aktivitas
        $activity = "Memberikan penilaian untuk dosen ID: " . $dosen_id . " pada kriteria ID: " . $kriteria_id;
        $log_query = "INSERT INTO log_activity (user_id, activity) VALUES (?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("is", $penilai_id, $activity);
        $log_stmt->execute();

        header("Location: penilaian.php?success=Penilaian+berhasil+disimpan");
    } else {
        header("Location: penilaian.php?error=Gagal+menyimpan+penilaian");
    }
    exit();
}

// Fungsi untuk membersihkan input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?> 