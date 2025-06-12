<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Cek apakah tabel yang diperlukan sudah ada
$tables = ['users', 'log_activity'];
$tables_exist = true;

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $tables_exist = false;
        break;
    }
}

// Buat tabel log_activity jika belum ada
if (!$tables_exist) {
    $create_log_table = "CREATE TABLE IF NOT EXISTS log_activity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        activity_type VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($create_log_table)) {
        $tables_exist = true;
    }
}

// Inisialisasi variabel filter
$filter_user = isset($_GET['user']) ? $_GET['user'] : '';
$filter_action = isset($_GET['action']) ? $_GET['action'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

if ($tables_exist) {
    // Query untuk mengambil log aktivitas
    $query = "SELECT l.*, u.username, u.full_name
              FROM log_activity l
              LEFT JOIN users u ON l.user_id = u.id
              WHERE 1=1";

    // Tambahkan filter jika ada
    if (!empty($filter_user)) {
        $query .= " AND u.username LIKE ?";
    }
    if (!empty($filter_action)) {
        $query .= " AND l.activity_type LIKE ?";
    }
    if (!empty($filter_date)) {
        $query .= " AND DATE(l.created_at) = ?";
    }

    $query .= " ORDER BY l.created_at DESC";

    // Prepare statement
    $stmt = $conn->prepare($query);

    // Bind parameters
    $params = [];
    $types = '';

    if (!empty($filter_user)) {
        $params[] = "%$filter_user%";
        $types .= 's';
    }
    if (!empty($filter_action)) {
        $params[] = "%$filter_action%";
        $types .= 's';
    }
    if (!empty($filter_date)) {
        $params[] = $filter_date;
        $types .= 's';
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Query untuk mendapatkan daftar user
    $users_query = "SELECT id, username, full_name FROM users ORDER BY username ASC";
    $users_result = $conn->query($users_query);

    // Query untuk mendapatkan daftar tipe aktivitas
    $activity_types_query = "SELECT DISTINCT activity_type FROM log_activity ORDER BY activity_type ASC";
    $activity_types_result = $conn->query($activity_types_query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - SPK Dosen Terbaik</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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

        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .log-icon {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }

        .log-success { color: #28a745; }
        .log-warning { color: #ffc107; }
        .log-danger { color: #dc3545; }
        .log-info { color: #17a2b8; }
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
                            <a class="nav-link" href="backup.php">
                                <i class="fas fa-database me-2"></i>Backup
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="log_activity.php">
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
                    <h2>Log Aktivitas</h2>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Username</label>
                            <select name="user" class="form-select">
                                <option value="">Semua User</option>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                <option value="<?php echo $user['username']; ?>" 
                                        <?php echo $filter_user === $user['username'] ? 'selected' : ''; ?>>
                                    <?php echo $user['username']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Aksi</label>
                            <select name="action" class="form-select">
                                <option value="">Semua Aksi</option>
                                <?php while ($action = $activity_types_result->fetch_assoc()): ?>
                                <option value="<?php echo $action['activity_type']; ?>"
                                        <?php echo $filter_action === $action['activity_type'] ? 'selected' : ''; ?>>
                                    <?php echo $action['activity_type']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control" value="<?php echo $filter_date; ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                            <a href="log_activity.php" class="btn btn-secondary">
                                <i class="fas fa-sync me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Log Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="logTable">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Username</th>
                                        <th>Aksi</th>
                                        <th>Detail</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i:s', strtotime($row['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td>
                                                    <?php
                                                    $icon_class = '';
                                                    switch (strtolower($row['activity_type'])) {
                                                        case 'login':
                                                            $icon_class = 'log-success';
                                                            break;
                                                        case 'logout':
                                                            $icon_class = 'log-warning';
                                                            break;
                                                        case 'delete':
                                                            $icon_class = 'log-danger';
                                                            break;
                                                        default:
                                                            $icon_class = 'log-info';
                                                    }
                                                    ?>
                                                    <i class="fas fa-circle <?php echo $icon_class; ?> log-icon"></i>
                                                    <?php echo htmlspecialchars($row['activity_type']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Tidak ada data log aktivitas</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#logTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                order: [[0, 'desc']],
                pageLength: 25
            });
        });
    </script>
</body>
</html> 