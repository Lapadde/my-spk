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
    $nip = clean_input($_POST['nip']);
    $nama = clean_input($_POST['nama']);
    $jabatan = clean_input($_POST['jabatan']);
    $fakultas = clean_input($_POST['fakultas']);
    $prodi = clean_input($_POST['prodi']);

    // Validasi input
    $errors = [];
    
    if (empty($nip)) {
        $errors[] = "NIP wajib diisi!";
    }
    
    if (empty($nama)) {
        $errors[] = "Nama wajib diisi!";
    }
    
    if (empty($jabatan)) {
        $errors[] = "Jabatan wajib diisi!";
    }
    
    if (empty($fakultas)) {
        $errors[] = "Fakultas wajib diisi!";
    }
    
    if (empty($prodi)) {
        $errors[] = "Program Studi wajib diisi!";
    }

    // Cek duplikasi NIP
    $stmt = $conn->prepare("SELECT id FROM dosen WHERE nip = ? AND id != ?");
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $stmt->bind_param("si", $nip, $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $errors[] = "NIP sudah terdaftar!";
    }
    $stmt->close();

    // Jika ada error, kembali ke halaman dosen dengan pesan error
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: dosen.php");
        exit();
    }

    // Proses insert atau update
    if (isset($_POST['id'])) {
        // Update data
        $stmt = $conn->prepare("UPDATE dosen SET nip = ?, nama = ?, jabatan = ?, fakultas = ?, prodi = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $nip, $nama, $jabatan, $fakultas, $prodi, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Data dosen berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui data dosen!";
        }
    } else {
        // Insert data baru
        $stmt = $conn->prepare("INSERT INTO dosen (nip, nama, jabatan, fakultas, prodi) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nip, $nama, $jabatan, $fakultas, $prodi);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Data dosen berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan data dosen!";
        }
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: dosen.php");
    exit();
} else {
    header("Location: dosen.php");
    exit();
}
?> 