<?php
session_start();
require_once 'db/config.php';

// Cek apakah user yang mengakses adalah admin
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     header("Location: login.html");
//     exit();
// }

// Fungsi untuk membersihkan input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Tangkap data dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = clean_input($_POST["full_name"]);
    $username = clean_input($_POST["username"]);
    $email = clean_input($_POST["email"]);
    $password = clean_input($_POST["password"]);
    $role = clean_input($_POST["role"]);
    $is_active = isset($_POST["is_active"]) ? 1 : 0;
    
    // Validasi
    $errors = [];
    
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($role)) {
        $errors[] = "Semua field wajib diisi!";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password minimal 8 karakter!";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }
    
    // Cek jika username sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $errors[] = "Username sudah digunakan!";
    }
    $stmt->close();
    
    // Cek jika email sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $errors[] = "Email sudah terdaftar!";
    }
    $stmt->close();
    
    // Jika ada error, kembalikan ke form
    if (!empty($errors)) {
        $error_msg = urlencode(implode("<br>", $errors));
        header("Location: create_user.php?error=" . $error_msg);
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $username, $hashed_password, $email, $full_name, $role, $is_active);
    
    if ($stmt->execute()) {
        $success_msg = "User berhasil dibuat!";
        header("Location: user_management.php?success=" . urlencode($success_msg));
    } else {
        $errors[] = "Gagal membuat user: " . $conn->error;
        $error_msg = urlencode(implode("<br>", $errors));
        header("Location: create_user.php?error=" . $error_msg);
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: create_user.php");
    exit();
}
?>