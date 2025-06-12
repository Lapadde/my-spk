<?php
session_start();
require_once 'db/config.php'; // File konfigurasi database

// Fungsi untuk membersihkan input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Tangkap data dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = clean_input($_POST["username"]);
    $password = clean_input($_POST["password"]);
    $remember = isset($_POST["remember"]) ? true : false;
    
    // Validasi
    if (empty($username) || empty($password)) {
        header("Location: login.html?error=emptyfields");
        exit();
    }
    
    // Query ke database
    $sql = "SELECT id, username, password, role, full_name FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Jika remember me dicentang, set cookie
            if ($remember) {
                $cookie_value = base64_encode($user['id'] . ':' . hash('sha256', $user['password']));
                setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/"); // 30 hari
            }
            
            // Redirect berdasarkan role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'penilai':
                    header("Location: penilai/dashboard.php");
                    break;
                default:
                    header("Location: index.html");
            }
            exit();
        } else {
            header("Location: login.html?error=wrongpassword");
            exit();
        }
    } else {
        header("Location: login.html?error=nouser");
        exit();
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: login.html");
    exit();
}
?>