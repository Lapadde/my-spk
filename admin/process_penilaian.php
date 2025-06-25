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
    $tahun_akademik = clean_input($_POST['tahun_akademik']);
    $semester = clean_input($_POST['semester']);
    $keterangan = clean_input($_POST['keterangan'] ?? '');
    $penilai_id = $_SESSION['user_id']; // Menggunakan ID user yang sedang login
    $nilai_array = $_POST['nilai'] ?? [];

    // Validasi input
    $errors = [];
    if ($dosen_id <= 0) {
        $errors[] = "Dosen harus dipilih";
    }
    if (empty($tahun_akademik)) {
        $errors[] = "Tahun akademik harus diisi";
    }
    if (empty($semester)) {
        $errors[] = "Semester harus diisi";
    }
    if (empty($nilai_array)) {
        $errors[] = "Nilai kriteria harus diisi";
    }

    // Jika ada error, redirect kembali dengan pesan error
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: penilaian.php");
        exit();
    }

    // Mulai transaksi
    $conn->begin_transaction();
    
    try {
        // Cek apakah field keterangan ada di tabel penilaian
        $check_keterangan = $conn->query("SHOW COLUMNS FROM penilaian LIKE 'keterangan'");
        $has_keterangan = $check_keterangan->num_rows > 0;
        
        // Cek apakah field penilai_id ada di tabel penilaian
        $check_penilai = $conn->query("SHOW COLUMNS FROM penilaian LIKE 'penilai_id'");
        $has_penilai = $check_penilai->num_rows > 0;
        
        // Hapus penilaian yang sudah ada untuk dosen ini di tahun/semester yang sama
        $delete_stmt = $conn->prepare("DELETE FROM penilaian WHERE dosen_id = ? AND tahun_akademik = ? AND semester = ?");
        $delete_stmt->bind_param("iss", $dosen_id, $tahun_akademik, $semester);
        $delete_stmt->execute();
        
        // Insert penilaian baru untuk setiap kriteria
        if ($has_keterangan && $has_penilai) {
            $insert_stmt = $conn->prepare("INSERT INTO penilaian (dosen_id, kriteria_id, penilai_id, nilai, tahun_akademik, semester, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)");
        } elseif ($has_penilai) {
            $insert_stmt = $conn->prepare("INSERT INTO penilaian (dosen_id, kriteria_id, penilai_id, nilai, tahun_akademik, semester) VALUES (?, ?, ?, ?, ?, ?)");
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO penilaian (dosen_id, kriteria_id, nilai, tahun_akademik, semester) VALUES (?, ?, ?, ?, ?)");
        }
        
        $success_count = 0;
        foreach ($nilai_array as $kriteria_id => $nilai) {
            $kriteria_id = (int)$kriteria_id;
            $nilai = (float)$nilai;
            
            // Validasi nilai
            if ($nilai < 0 || $nilai > 100) {
                throw new Exception("Nilai untuk kriteria ID $kriteria_id harus antara 0 dan 100");
            }
            
            if ($has_keterangan && $has_penilai) {
                $insert_stmt->bind_param("iiidsss", $dosen_id, $kriteria_id, $penilai_id, $nilai, $tahun_akademik, $semester, $keterangan);
            } elseif ($has_penilai) {
                $insert_stmt->bind_param("iiidss", $dosen_id, $kriteria_id, $penilai_id, $nilai, $tahun_akademik, $semester);
            } else {
                $insert_stmt->bind_param("iidss", $dosen_id, $kriteria_id, $nilai, $tahun_akademik, $semester);
            }
            
            if ($insert_stmt->execute()) {
                $success_count++;
            } else {
                throw new Exception("Gagal menyimpan penilaian untuk kriteria ID $kriteria_id: " . $insert_stmt->error);
            }
        }
        
        // Commit transaksi
        $conn->commit();
        
        if ($success_count > 0) {
            $_SESSION['success'] = "Penilaian berhasil disimpan untuk $success_count kriteria!";
        } else {
            $_SESSION['error'] = "Tidak ada penilaian yang berhasil disimpan!";
        }
        
    } catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    $conn->close();
    header("Location: penilaian.php");
    exit();
}

// Jika bukan POST request, redirect ke halaman penilaian
header("Location: penilaian.php");
exit();
?> 