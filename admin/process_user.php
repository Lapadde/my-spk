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
    $username = clean_input($_POST['username']);
    $full_name = clean_input($_POST['full_name']);
    $role = clean_input($_POST['role']);

    // Validasi input
    if (empty($username) || empty($full_name) || empty($role)) {
        $_SESSION['error'] = "Semua field harus diisi!";
        header("Location: ../user_management.php");
        exit();
    }

    // Validasi role
    if (!in_array($role, ['admin', 'evaluator'])) {
        $_SESSION['error'] = "Role tidak valid!";
        header("Location: ../user_management.php");
        exit();
    }

    // Cek apakah username sudah ada
    $check_query = "SELECT id FROM users WHERE username = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Username sudah digunakan!";
        header("Location: ../user_management.php");
        exit();
    }

    // Hash password
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Proses tambah/edit user
    if (isset($_POST['user_id'])) {
        // Edit user
        $user_id = $_POST['user_id'];
        
        if (!empty($password)) {
            // Update dengan password baru
            $query = "UPDATE users SET username = ?, password = ?, full_name = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ssssi", $username, $password, $full_name, $role, $user_id);
            }
        } else {
            // Update tanpa password
            $query = "UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("sssi", $username, $full_name, $role, $user_id);
            }
        }
    } else {
        // Tambah user baru
        $query = "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ssss", $username, $password, $full_name, $role);
        }
    }

    // Eksekusi query
    if ($stmt && $stmt->execute()) {
        // Log aktivitas
        $user_id = $_SESSION['user_id'];
        $activity = "Mengupdate user: " . $username;
        $log_query = "INSERT INTO log_activity (user_id, activity) VALUES (?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("is", $user_id, $activity);
        $log_stmt->execute();
        
        $_SESSION['success'] = isset($_POST['user_id']) ? "Data user berhasil diperbarui!" : "Data user berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Terjadi kesalahan! Silakan coba lagi.";
    }
}

// Proses hapus user
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Cek apakah user yang akan dihapus bukan user yang sedang login
    if ($user_id == $_SESSION['user_id']) {
        header("Location: ../user_management.php?error=Tidak+dapat+menghapus+user+yang+sedang+login");
        exit();
    }
    
    // Hapus user
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Log aktivitas
        $user_id = $_SESSION['user_id'];
        $activity = "Menghapus user dengan ID: " . $_GET['id'];
        $log_query = "INSERT INTO log_activity (user_id, activity) VALUES (?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("is", $user_id, $activity);
        $log_stmt->execute();
        
        header("Location: ../user_management.php?success=User+berhasil+dihapus");
        exit();
    } else {
        header("Location: ../user_management.php?error=Gagal+menghapus+user");
        exit();
    }
}

header("Location: ../user_management.php");
exit();
?> 