<?php
require_once 'db/config.php';

echo "<h2>Memperbaiki Struktur Database</h2>";

// Cek apakah field prodi ada di tabel dosen
$check_prodi = $conn->query("SHOW COLUMNS FROM dosen LIKE 'prodi'");
if ($check_prodi->num_rows == 0) {
    echo "<p>Field 'prodi' tidak ditemukan, menambahkan...</p>";
    $conn->query("ALTER TABLE dosen ADD COLUMN prodi varchar(100) DEFAULT NULL AFTER fakultas");
    echo "<p>✅ Field 'prodi' berhasil ditambahkan</p>";
} else {
    echo "<p>✅ Field 'prodi' sudah ada</p>";
}

// Cek apakah field program_studi ada di tabel dosen
$check_program_studi = $conn->query("SHOW COLUMNS FROM dosen LIKE 'program_studi'");
if ($check_program_studi->num_rows == 0) {
    echo "<p>Field 'program_studi' tidak ditemukan, menambahkan...</p>";
    $conn->query("ALTER TABLE dosen ADD COLUMN program_studi varchar(100) DEFAULT NULL AFTER prodi");
    echo "<p>✅ Field 'program_studi' berhasil ditambahkan</p>";
} else {
    echo "<p>✅ Field 'program_studi' sudah ada</p>";
}

// Cek apakah field evaluator_id ada di tabel penilaian
$check_evaluator = $conn->query("SHOW COLUMNS FROM penilaian LIKE 'evaluator_id'");
if ($check_evaluator->num_rows == 0) {
    echo "<p>Field 'evaluator_id' tidak ditemukan di tabel penilaian, menambahkan...</p>";
    $conn->query("ALTER TABLE penilaian ADD COLUMN evaluator_id int(11) NOT NULL AFTER kriteria_id");
    echo "<p>✅ Field 'evaluator_id' berhasil ditambahkan</p>";
} else {
    echo "<p>✅ Field 'evaluator_id' sudah ada</p>";
}

// Cek apakah field penilai_id ada di tabel penilaian
$check_penilai = $conn->query("SHOW COLUMNS FROM penilaian LIKE 'penilai_id'");
if ($check_penilai->num_rows == 0) {
    echo "<p>Field 'penilai_id' tidak ditemukan di tabel penilaian, menambahkan...</p>";
    $conn->query("ALTER TABLE penilaian ADD COLUMN penilai_id int(11) NOT NULL AFTER kriteria_id");
    echo "<p>✅ Field 'penilai_id' berhasil ditambahkan</p>";
} else {
    echo "<p>✅ Field 'penilai_id' sudah ada</p>";
}

// Cek apakah field ranking ada di tabel hasil_akhir
$check_ranking = $conn->query("SHOW COLUMNS FROM hasil_akhir LIKE 'ranking'");
if ($check_ranking->num_rows == 0) {
    echo "<p>Field 'ranking' tidak ditemukan, menambahkan...</p>";
    $conn->query("ALTER TABLE hasil_akhir ADD COLUMN ranking int(11) DEFAULT NULL AFTER total_nilai");
    echo "<p>✅ Field 'ranking' berhasil ditambahkan</p>";
} else {
    echo "<p>✅ Field 'ranking' sudah ada</p>";
}

// Cek apakah field peringkat ada di tabel hasil_akhir
$check_peringkat = $conn->query("SHOW COLUMNS FROM hasil_akhir LIKE 'peringkat'");
if ($check_peringkat->num_rows == 0) {
    echo "<p>Field 'peringkat' tidak ditemukan, menambahkan...</p>";
    $conn->query("ALTER TABLE hasil_akhir ADD COLUMN peringkat int(11) DEFAULT NULL AFTER total_nilai");
    echo "<p>✅ Field 'peringkat' berhasil ditambahkan</p>";
} else {
    echo "<p>✅ Field 'peringkat' sudah ada</p>";
}

// Cek apakah field nama_lengkap ada di tabel users
$check_nama_lengkap = $conn->query("SHOW COLUMNS FROM users LIKE 'nama_lengkap'");
if ($check_nama_lengkap->num_rows == 0) {
    echo "<p>Field 'nama_lengkap' tidak ditemukan, menambahkan...</p>";
    $conn->query("ALTER TABLE users ADD COLUMN nama_lengkap varchar(100) DEFAULT NULL AFTER password");
    echo "<p>✅ Field 'nama_lengkap' berhasil ditambahkan</p>";
} else {
    echo "<p>✅ Field 'nama_lengkap' sudah ada</p>";
}

// Cek apakah field full_name ada di tabel users
$check_full_name = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");
if ($check_full_name->num_rows == 0) {
    echo "<p>Field 'full_name' tidak ditemukan, menambahkan...</p>";
    $conn->query("ALTER TABLE users ADD COLUMN full_name varchar(100) DEFAULT NULL AFTER password");
    echo "<p>✅ Field 'full_name' berhasil ditambahkan</p>";
} else {
    echo "<p>✅ Field 'full_name' sudah ada</p>";
}

// Cek apakah field is_active ada di tabel users
$check_is_active = $conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
if ($check_is_active->num_rows == 0) {
    echo "<p>Field 'is_active' tidak ditemukan, menambahkan...</p>";
    $conn->query("ALTER TABLE users ADD COLUMN is_active tinyint(1) DEFAULT 1 AFTER role");
    echo "<p>✅ Field 'is_active' berhasil ditambahkan</p>";
} else {
    echo "<p>✅ Field 'is_active' sudah ada</p>";
}

// Cek apakah field nidn ada di tabel dosen
$check_nidn = $conn->query("SHOW COLUMNS FROM dosen LIKE 'nidn'");
if ($check_nidn->num_rows == 0) {
    echo "<p>Field 'nidn' tidak ditemukan, menambahkan...</p>";
    $conn->query("ALTER TABLE dosen ADD COLUMN nidn varchar(20) DEFAULT NULL AFTER nip");
    echo "<p>✅ Field 'nidn' berhasil ditambahkan</p>";
} else {
    echo "<p>✅ Field 'nidn' sudah ada</p>";
}

// Cek apakah tabel perbandingan_kriteria ada
$check_perbandingan = $conn->query("SHOW TABLES LIKE 'perbandingan_kriteria'");
if ($check_perbandingan->num_rows == 0) {
    echo "<p>Tabel 'perbandingan_kriteria' tidak ditemukan, membuat...</p>";
    $conn->query("CREATE TABLE IF NOT EXISTS `perbandingan_kriteria` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `kriteria1_id` int(11) NOT NULL,
        `kriteria2_id` int(11) NOT NULL,
        `nilai` decimal(5,4) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `kriteria1_id` (`kriteria1_id`),
        KEY `kriteria2_id` (`kriteria2_id`),
        CONSTRAINT `perbandingan_kriteria_ibfk_1` FOREIGN KEY (`kriteria1_id`) REFERENCES `kriteria` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `perbandingan_kriteria_ibfk_2` FOREIGN KEY (`kriteria2_id`) REFERENCES `kriteria` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "<p>✅ Tabel 'perbandingan_kriteria' berhasil dibuat</p>";
} else {
    echo "<p>✅ Tabel 'perbandingan_kriteria' sudah ada</p>";
}

// Cek apakah tabel hasil_akhir ada
$check_hasil_akhir = $conn->query("SHOW TABLES LIKE 'hasil_akhir'");
if ($check_hasil_akhir->num_rows == 0) {
    echo "<p>Tabel 'hasil_akhir' tidak ditemukan, membuat...</p>";
    $conn->query("CREATE TABLE IF NOT EXISTS `hasil_akhir` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `dosen_id` int(11) NOT NULL,
        `tahun_akademik` varchar(20) NOT NULL,
        `semester` varchar(10) NOT NULL,
        `total_score` decimal(10,4) NOT NULL,
        `ranking` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `dosen_id` (`dosen_id`),
        CONSTRAINT `hasil_akhir_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "<p>✅ Tabel 'hasil_akhir' berhasil dibuat</p>";
} else {
    echo "<p>✅ Tabel 'hasil_akhir' sudah ada</p>";
    
    // Cek apakah field total_score ada
    $check_total_score = $conn->query("SHOW COLUMNS FROM hasil_akhir LIKE 'total_score'");
    if ($check_total_score->num_rows == 0) {
        echo "<p>Field 'total_score' tidak ditemukan, menambahkan...</p>";
        $conn->query("ALTER TABLE hasil_akhir ADD COLUMN total_score decimal(10,4) NOT NULL AFTER semester");
        echo "<p>✅ Field 'total_score' berhasil ditambahkan</p>";
    } else {
        echo "<p>✅ Field 'total_score' sudah ada</p>";
    }

    // Cek apakah field keterangan ada di tabel penilaian
    $check_keterangan = $conn->query("SHOW COLUMNS FROM penilaian LIKE 'keterangan'");
    if ($check_keterangan->num_rows == 0) {
        echo "<p>Field 'keterangan' tidak ditemukan di tabel penilaian, menambahkan...</p>";
        $conn->query("ALTER TABLE penilaian ADD COLUMN keterangan text DEFAULT NULL AFTER semester");
        echo "<p>✅ Field 'keterangan' berhasil ditambahkan</p>";
    } else {
        echo "<p>✅ Field 'keterangan' sudah ada</p>";
    }
}

// Cek apakah ada user admin
$check_admin = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$admin_count = $check_admin->fetch_assoc()['count'];

if ($admin_count == 0) {
    echo "<p>User admin tidak ditemukan, menambahkan...</p>";
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, nama_lengkap, email, role) VALUES ('admin', '$hashed_password', 'Administrator', 'admin@example.com', 'admin')");
    echo "<p>✅ User admin berhasil ditambahkan (username: admin, password: admin123)</p>";
} else {
    echo "<p>✅ User admin sudah ada</p>";
}

// Update tahun_akademik yang salah format menjadi format tahun ajaran
$update_tahun = $conn->query("UPDATE penilaian SET tahun_akademik = '2024/2025' WHERE tahun_akademik = '2025'");
if ($update_tahun) {
    echo "<p>✅ Data penilaian dengan tahun_akademik '2025' berhasil diubah menjadi '2024/2025'</p>";
} else {
    echo "<p>❌ Gagal update tahun_akademik: " . $conn->error . "</p>";
}

echo "<h3>Struktur Database Berhasil Diperbaiki!</h3>";
echo "<p>Sekarang database sudah konsisten dan siap digunakan.</p>";
echo "<p><a href='admin/dashboard.php'>Kembali ke Dashboard</a></p>";
?> 