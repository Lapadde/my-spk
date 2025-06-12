<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Fungsi untuk membersihkan input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan bersihkan data
    $dosen_id = (int)$_POST['dosen_id'];
    $kriteria_id = (int)$_POST['kriteria_id'];
    $nilai = (float)$_POST['nilai'];
    $tahun_akademik = clean_input($_POST['tahun_akademik']);
    $semester = clean_input($_POST['semester']);
    $evaluator_id = $_SESSION['user_id']; // Menggunakan ID user yang sedang login

    // Validasi input
    $errors = [];
    if ($dosen_id <= 0) {
        $errors[] = "Dosen harus dipilih";
    }
    if ($kriteria_id <= 0) {
        $errors[] = "Kriteria harus dipilih";
    }
    if ($nilai < 0 || $nilai > 100) {
        $errors[] = "Nilai harus antara 0 dan 100";
    }
    if (empty($tahun_akademik)) {
        $errors[] = "Tahun akademik harus diisi";
    }
    if (empty($semester)) {
        $errors[] = "Semester harus diisi";
    }

    // Jika ada error, redirect kembali dengan pesan error
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: penilaian.php");
        exit();
    }

    // Cek apakah ini update atau insert baru
    if (isset($_POST['id'])) {
        // Update data yang ada
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE penilaian SET dosen_id = ?, kriteria_id = ?, nilai = ?, tahun_akademik = ?, semester = ? WHERE id = ?");
        $stmt->bind_param("iidssi", $dosen_id, $kriteria_id, $nilai, $tahun_akademik, $semester, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Penilaian berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui penilaian!";
        }
    } else {
        // Insert data baru
        $stmt = $conn->prepare("INSERT INTO penilaian (dosen_id, kriteria_id, evaluator_id, nilai, tahun_akademik, semester) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidss", $dosen_id, $kriteria_id, $evaluator_id, $nilai, $tahun_akademik, $semester);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Penilaian berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan penilaian!";
        }
    }

    $stmt->close();
    header("Location: penilaian.php");
    exit();
}

// Jika bukan POST request, redirect ke halaman penilaian
header("Location: penilaian.php");
exit();
?> 