# Dokumentasi Sistem SPK Dosen dengan Metode AHP

## 📋 Deskripsi Sistem

Sistem Penunjang Keputusan (SPK) untuk penilaian dosen terbaik menggunakan metode **Analytic Hierarchy Process (AHP)**. Sistem ini membantu menentukan dosen terbaik berdasarkan perbandingan berpasangan antar kriteria dan alternatif.

## 🎯 Kriteria Penilaian Default

Sistem menggunakan 4 kriteria utama dengan bobot sebagai berikut:

1. **Pendidikan** (40%) - Latar belakang pendidikan dosen
2. **Penelitian** (25%) - Produktivitas dan kualitas penelitian
3. **Pengabdian Masyarakat** (25%) - Kontribusi dalam pengabdian masyarakat
4. **Penunjang** (10%) - Kegiatan penunjang lainnya

## 🔧 Tahapan Implementasi AHP

### 1. Tentukan Hirarki Penilaian
- **Tujuan**: Menentukan dosen terbaik
- **Kriteria**: 4 kriteria utama dengan bobot yang telah ditentukan
- **Alternatif**: Dosen A, B, C, D, E (atau sesuai data yang ada)

### 2. Buat Matriks Perbandingan Berpasangan
- Bandingkan pentingnya antar kriteria menggunakan skala AHP (1-9)
- Input nilai perbandingan melalui interface web
- Sistem otomatis menghitung nilai reciprocal

### 3. Normalisasi & Hitung Prioritas
- Normalisasi matriks perbandingan
- Hitung bobot masing-masing kriteria
- Tampilkan hasil bobot dalam persentase

### 4. Uji Konsistensi
- Hitung λ maks, CI, dan CR
- Jika CR < 0.1 → hasil konsisten dan valid
- Jika CR ≥ 0.1 → perlu revisi matriks perbandingan

### 5. Hitung Nilai Akhir Dosen
- Kalikan bobot kriteria dengan nilai dosen di masing-masing kriteria
- Jumlahkan untuk mendapat nilai total
- Urutkan berdasarkan nilai tertinggi

### 6. Perangkingan
- Dosen dengan nilai tertinggi = dosen terbaik
- Tampilkan ranking 1, 2, 3, dst.

## 🚀 Cara Penggunaan

### Setup Awal

1. **Login sebagai Admin**
   ```
   Username: admin
   Password: password
   ```

2. **Setup Kriteria Default**
   - Buka menu "Kriteria"
   - Klik tombol "Setup Kriteria Default"
   - Konfirmasi untuk menghapus kriteria yang ada
   - Sistem akan menambahkan 4 kriteria default

3. **Tambah Data Dosen**
   - Buka menu "Dosen"
   - Klik "Tambah Dosen"
   - Isi data lengkap dosen

4. **Input Penilaian**
   - Buka menu "Penilaian"
   - Pilih dosen dan kriteria
   - Input nilai (0-100)
   - Simpan penilaian

### Perhitungan AHP

1. **Buka Halaman AHP**
   - Klik menu "Perhitungan AHP"

2. **Input Matriks Perbandingan**
   - Isi nilai perbandingan antar kriteria (1-9)
   - Klik "Simpan Matriks Perbandingan"

3. **Jalankan Perhitungan**
   - Klik "Jalankan Perhitungan"
   - Sistem akan menghitung bobot dan ranking

4. **Lihat Hasil**
   - Bobot kriteria akan ditampilkan
   - Status konsistensi (CR < 0.1 = konsisten)
   - Ranking dosen terbaik

## 📊 Skala Perbandingan AHP

| Nilai | Keterangan |
|-------|------------|
| 1 | Sama penting |
| 2 | Sedikit lebih penting |
| 3 | Lebih penting |
| 4 | Jauh lebih penting |
| 5 | Sangat lebih penting |
| 6 | Sangat jauh lebih penting |
| 7 | Ekstrim lebih penting |
| 8 | Sangat ekstrim lebih penting |
| 9 | Mutlak lebih penting |

## 🔍 Interpretasi Hasil

### Bobot Kriteria
- Menunjukkan tingkat kepentingan relatif setiap kriteria
- Total bobot = 1.0 (100%)
- Semakin tinggi bobot, semakin penting kriteria tersebut

### Consistency Ratio (CR)
- **CR < 0.1**: Matriks konsisten, hasil valid
- **CR ≥ 0.1**: Matriks tidak konsisten, perlu revisi

### Ranking Dosen
- **Ranking 1**: Dosen terbaik
- **Ranking 2**: Dosen kedua terbaik
- dst.

## ⚠️ Catatan Penting

1. **Data Penilaian**
   - Pastikan semua dosen telah dinilai untuk semua kriteria
   - Nilai yang kosong akan dianggap 0

2. **Konsistensi**
   - Jika CR ≥ 0.1, revisi matriks perbandingan
   - Gunakan skala yang lebih konsisten

3. **Bobot Kriteria**
   - Bobot dapat diubah manual di menu "Kriteria"
   - Pastikan total bobot = 1.0

## 🛠️ Fitur Sistem

### Admin
- ✅ Manajemen dosen
- ✅ Manajemen kriteria
- ✅ Manajemen penilaian
- ✅ Perhitungan AHP
- ✅ Lihat hasil ranking
- ✅ Manajemen user

### Penilai
- ✅ Input penilaian dosen
- ✅ Lihat hasil penilaian
- ✅ Dashboard statistik

## 📁 Struktur File

```
admin/
├── ahp_calculation.php      # Perhitungan AHP
├── setup_default_criteria.php # Setup kriteria default
├── kriteria.php             # Manajemen kriteria
├── dosen.php               # Manajemen dosen
├── penilaian.php           # Manajemen penilaian
├── hasil.php               # Hasil akhir
└── dashboard.php           # Dashboard admin

penilai/
├── dashboard.php           # Dashboard penilai
├── penilaian.php           # Input penilaian
└── hasil.php               # Lihat hasil

db/
└── config.php              # Konfigurasi database
```

## 🔧 Konfigurasi Database

Pastikan database `my-spk` sudah dibuat dan tabel-tabel berikut sudah ada:

- `users` - Data pengguna
- `dosen` - Data dosen
- `kriteria` - Data kriteria
- `penilaian` - Data penilaian
- `perbandingan_kriteria` - Matriks perbandingan AHP
- `hasil_akhir` - Hasil perhitungan akhir

## 📞 Support

Jika ada pertanyaan atau masalah, silakan:

- Hubungi administrator sistem
- Hubungi via Telegram: [@backupku](https://t.me/backupku)
- Follow Instagram: [@taaufiik25](https://instagram.com/taaufiik25)

---

**Sistem SPK Dosen dengan Metode AHP**  
*Membantu menentukan dosen terbaik secara objektif dan terukur* 
