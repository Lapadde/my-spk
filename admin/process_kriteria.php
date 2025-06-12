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
    $nama_kriteria = clean_input($_POST['nama_kriteria']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $bobot = (float)$_POST['bobot'];

    // Validasi input
    $errors = [];
    if (empty($nama_kriteria)) {
        $errors[] = "Nama kriteria harus diisi";
    }
    if (empty($deskripsi)) {
        $errors[] = "Deskripsi harus diisi";
    }
    if ($bobot < 0 || $bobot > 1) {
        $errors[] = "Bobot harus antara 0 dan 1";
    }

    // Jika ada error, redirect kembali dengan pesan error
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: kriteria.php");
        exit();
    }

    // Cek apakah ini update atau insert baru
    if (isset($_POST['id'])) {
        // Update data yang ada
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE kriteria SET nama_kriteria = ?, deskripsi = ?, bobot = ? WHERE id = ?");
        $stmt->bind_param("ssdi", $nama_kriteria, $deskripsi, $bobot, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Kriteria berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui kriteria!";
        }
    } else {
        // Insert data baru
        $stmt = $conn->prepare("INSERT INTO kriteria (nama_kriteria, deskripsi, bobot) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $nama_kriteria, $deskripsi, $bobot);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Kriteria berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan kriteria!";
        }
    }

    $stmt->close();
    header("Location: kriteria.php");
    exit();
}

// Jika bukan POST request, redirect ke halaman kriteria
header("Location: kriteria.php");
exit();
?> 