<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Fungsi untuk backup database
function backupDatabase($conn) {
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $backup = "";
    
    // Dump struktur dan data untuk setiap tabel
    foreach ($tables as $table) {
        $result = $conn->query("SHOW CREATE TABLE $table");
        $row = $result->fetch_row();
        $backup .= "\n\n" . $row[1] . ";\n\n";
        
        $result = $conn->query("SELECT * FROM $table");
        while ($row = $result->fetch_assoc()) {
            $values = array_map(function($value) use ($conn) {
                if ($value === null) return 'NULL';
                return "'" . $conn->real_escape_string($value) . "'";
            }, $row);
            $backup .= "INSERT INTO $table VALUES (" . implode(", ", $values) . ");\n";
        }
    }

    return $backup;
}

// Proses backup
if (isset($_POST['backup'])) {
    $backup = backupDatabase($conn);
    $filename = "backup_" . date("Y-m-d_H-i-s") . ".sql";
    
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . $filename . "\"");
    echo $backup;
    exit();
}

// Proses restore
if (isset($_POST['restore']) && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file'];
    
    if ($file['error'] === 0) {
        $sql = file_get_contents($file['tmp_name']);
        
        // Eksekusi query SQL
        if ($conn->multi_query($sql)) {
            $_SESSION['success'] = "Database berhasil di-restore!";
        } else {
            $_SESSION['error'] = "Gagal melakukan restore database: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Gagal mengupload file!";
    }
    
    header("Location: backup.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Database - SPK Dosen Terbaik</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: var(--secondary-color);
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
            margin: 0.2rem 0;
            border-radius: 0.25rem;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,.1);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .backup-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-chalkboard-teacher me-2"></i>
                        SPK Dosen
                    </h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dosen.php">
                                <i class="fas fa-user-tie me-2"></i>Dosen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kriteria.php">
                                <i class="fas fa-list me-2"></i>Kriteria
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="penilaian.php">
                                <i class="fas fa-star me-2"></i>Penilaian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="hasil.php">
                                <i class="fas fa-chart-bar me-2"></i>Hasil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user-cog me-2"></i>Profil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="backup.php">
                                <i class="fas fa-database me-2"></i>Backup
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="log_activity.php">
                                <i class="fas fa-history me-2"></i>Log Aktivitas
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Backup Database</h2>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Backup Section -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-download backup-icon"></i>
                                <h4 class="card-title">Backup Database</h4>
                                <p class="card-text">Download salinan database dalam format SQL</p>
                                <form method="POST">
                                    <button type="submit" name="backup" class="btn btn-primary">
                                        <i class="fas fa-download me-2"></i>Download Backup
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Restore Section -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-upload backup-icon"></i>
                                <h4 class="card-title">Restore Database</h4>
                                <p class="card-text">Upload file SQL untuk mengembalikan database</p>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <input type="file" class="form-control" name="sql_file" accept=".sql" required>
                                    </div>
                                    <button type="submit" name="restore" class="btn btn-primary" 
                                            onclick="return confirm('Peringatan: Restore database akan menimpa data yang ada. Lanjutkan?')">
                                        <i class="fas fa-upload me-2"></i>Upload & Restore
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Information Card -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>Informasi</h5>
                        <ul class="mb-0">
                            <li>Backup database akan mengunduh file SQL yang berisi struktur dan data dari semua tabel.</li>
                            <li>Restore database akan menimpa data yang ada dengan data dari file SQL yang diupload.</li>
                            <li>Pastikan untuk selalu membuat backup sebelum melakukan restore.</li>
                            <li>File backup akan otomatis diberi nama dengan format: <code>backup_YYYY-MM-DD_HH-mm-ss.sql</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 