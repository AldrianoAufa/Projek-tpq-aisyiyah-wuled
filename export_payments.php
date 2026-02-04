<?php
session_start();
require_once 'db_connect.php';

if ($_SESSION['role'] == 'guru') {
    die("Akses ditolak");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan_' . $type . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($type == 'harian') {
        fputcsv($output, ['Tanggal', 'Nama Siswa', 'Kelas', 'Jumlah', 'Petugas']);
        $sql = "SELECT p.tanggal, s.nama, k.nama_kelas, p.jumlah, u.name as petugas 
                FROM pembayaran_spp_harian p 
                JOIN siswa s ON p.siswa_id = s.id 
                LEFT JOIN kelas k ON s.kelas_id = k.id
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.tanggal BETWEEN '$start_date' AND '$end_date'";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) fputcsv($output, $row);
        
    } else if ($type == 'mingguan') {
        fputcsv($output, ['Minggu', 'Bulan', 'Tahun', 'Nama Siswa', 'Kelas', 'Jumlah', 'Petugas']);
        $sql = "SELECT p.minggu_ke, p.bulan, p.tahun, s.nama, k.nama_kelas, p.jumlah, u.name as petugas 
                FROM pembayaran_spp_mingguan p 
                JOIN siswa s ON p.siswa_id = s.id 
                LEFT JOIN kelas k ON s.kelas_id = k.id
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'"; // Approximate by created_at
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) fputcsv($output, $row);
        
    } else if ($type == 'biaya_lain') {
        fputcsv($output, ['Tanggal', 'Nama Siswa', 'Jenis Biaya', 'Jumlah', 'Petugas']);
        $sql = "SELECT p.tanggal, s.nama, b.nama as jenis, p.jumlah, u.name as petugas 
                FROM pembayaran_biaya_lain p 
                JOIN siswa s ON p.siswa_id = s.id 
                JOIN biaya_lain_master b ON p.biaya_lain_id = b.id
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.tanggal BETWEEN '$start_date' AND '$end_date'";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>
