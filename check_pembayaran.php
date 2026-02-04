<?php
require_once 'db_connect.php';

// Dapatkan data siswa untuk contoh
$siswa_id = 1; // Ganti dengan ID siswa yang sesuai
$tahun_ajaran = "2024/2025"; // Contoh tahun ajaran
$ta_parts = explode('/', $tahun_ajaran);
$ta_start = (int)$ta_parts[0];
$ta_end = (int)$ta_parts[1];

// Query untuk mengecek data pembayaran SPP mingguan
$query = "SELECT * FROM pembayaran_spp_mingguan 
          WHERE siswa_id = $siswa_id 
          AND ((tahun = $ta_start AND bulan >= 7) OR (tahun = $ta_end AND bulan <= 6))";

$result = $conn->query($query);

echo "<h2>Data Pembayaran SPP Mingguan</h2>";
echo "<p>Query: $query</p>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Siswa ID</th><th>Tahun</th><th>Bulan</th><th>Minggu Ke</th><th>Jumlah</th><th>Tanggal Bayar</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$row['id']."</td>";
        echo "<td>".$row['siswa_id']."</td>";
        echo "<td>".$row['tahun']."</td>";
        echo "<td>".$row['bulan']."</td>";
        echo "<td>".$row['minggu_ke']."</td>";
        echo "<td>".$row['jumlah']."</td>";
        echo "<td>".$row['tanggal_bayar']."</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data pembayaran SPP mingguan yang ditemukan untuk siswa ID: $siswa_id";
}

// Tampilkan total yang sudah dibayarkan
$total_query = "SELECT COALESCE(SUM(jumlah), 0) as total 
                FROM pembayaran_spp_mingguan 
                WHERE siswa_id = $siswa_id 
                AND ((tahun = $ta_start AND bulan >= 7) OR (tahun = $ta_end AND bulan <= 6))";
$total_result = $conn->query($total_query);
$total = $total_result->fetch_assoc()['total'];

echo "<h3>Total yang sudah dibayarkan: Rp " . number_format($total, 0, ',', '.') . "</h3>";

// Cek semua data di tabel pembayaran_spp_mingguan
echo "<h2>Semua Data di Tabel pembayaran_spp_mingguan</h2>";
$all_data = $conn->query("SELECT * FROM pembayaran_spp_mingguan");

if ($all_data->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Siswa ID</th><th>Tahun</th><th>Bulan</th><th>Minggu Ke</th><th>Jumlah</th><th>Tanggal Bayar</th></tr>";
    while($row = $all_data->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$row['id']."</td>";
        echo "<td>".$row['siswa_id']."</td>";
        echo "<td>".$row['tahun']."</td>";
        echo "<td>".$row['bulan']."</td>";
        echo "<td>".$row['minggu_ke']."</td>";
        echo "<td>".$row['jumlah']."</td>";
        echo "<td>".$row['tanggal_bayar']."</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tabel pembayaran_spp_mingguan masih kosong.";
}

$conn->close();
?>