<?php
session_start();
require_once '../db/config.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Akses ditolak');
}

// Cek apakah ada ID yang dikirim
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('ID tidak valid');
}

$id = (int)$_POST['id'];

// Ambil data kriteria
$stmt = $conn->prepare("SELECT * FROM kriteria WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.1 404 Not Found');
    exit('Data tidak ditemukan');
}

$kriteria = $result->fetch_assoc();

// Kirim response dalam format JSON
header('Content-Type: application/json');
echo json_encode($kriteria);

$stmt->close();
$conn->close();
?> 