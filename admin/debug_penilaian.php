<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

echo "<h2>Debug Penilaian</h2>";

// Tampilkan informasi user yang sedang login
echo "<h3>User yang Sedang Login:</h3>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Username: " . $_SESSION['username'] . "</p>";
echo "<p>Role: " . $_SESSION['role'] . "</p>";

// Cek struktur tabel penilaian
echo "<h3>Struktur Tabel Penilaian:</h3>";
$result = $conn->query("DESCRIBE penilaian");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// Cek data kriteria
echo "<h3>Data Kriteria:</h3>";
$result = $conn->query("SELECT * FROM kriteria");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nama Kriteria</th><th>Bobot</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nama_kriteria'] . "</td>";
        echo "<td>" . $row['bobot'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data kriteria";
}

// Cek data dosen
echo "<h3>Data Dosen:</h3>";
$result = $conn->query("SELECT * FROM dosen LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>NIP</th><th>Nama</th><th>Fakultas</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nip'] . "</td>";
        echo "<td>" . $row['nama'] . "</td>";
        echo "<td>" . $row['fakultas'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data dosen";
}

// Cek data users
echo "<h3>Data Users:</h3>";
$result = $conn->query("SELECT id, username, role FROM users");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['id'] == $_SESSION['user_id']) ? ' style="background-color: yellow;"' : '';
        echo "<tr" . $highlight . ">";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data users";
}

// Cek data penilaian yang sudah ada
echo "<h3>Data Penilaian yang Sudah Ada:</h3>";
$result = $conn->query("SELECT p.*, d.nama as nama_dosen, k.nama_kriteria FROM penilaian p JOIN dosen d ON p.dosen_id = d.id JOIN kriteria k ON p.kriteria_id = k.id LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Dosen</th><th>Kriteria</th><th>Nilai</th><th>Tahun</th><th>Semester</th><th>Penilai ID</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nama_dosen'] . "</td>";
        echo "<td>" . $row['nama_kriteria'] . "</td>";
        echo "<td>" . $row['nilai'] . "</td>";
        echo "<td>" . $row['tahun_akademik'] . "</td>";
        echo "<td>" . $row['semester'] . "</td>";
        echo "<td>" . (isset($row['penilai_id']) ? $row['penilai_id'] : (isset($row['evaluator_id']) ? $row['evaluator_id'] : 'N/A')) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data penilaian";
}

echo "<br><a href='penilaian.php'>Kembali ke Halaman Penilaian</a>";
?> 