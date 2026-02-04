<?php
include '../db_connect.php';

// Check role
session_start();
if (!isset($_SESSION['role'])) {
    die("Akses ditolak.");
}

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'] ?? 0;
$kelas_filter = isset($_GET['kelas_filter']) && !empty($_GET['kelas_filter']) ? (int)$_GET['kelas_filter'] : null;

// --- FILTER LOGIC (Academic Year & Semester) ---
$current_year = date('Y');
$current_month = date('n');

// Determine default Academic Year if not set
if ($current_month >= 7) {
    $default_tahun_ajaran = $current_year . '/' . ($current_year + 1);
    $default_semester = 'ganjil';
} else {
    $default_tahun_ajaran = ($current_year - 1) . '/' . $current_year;
    $default_semester = 'genap';
}

$tahun_ajaran = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : $default_tahun_ajaran;
$semester = isset($_GET['semester']) ? $_GET['semester'] : $default_semester;

// Parse Academic Year
$ta_parts = explode('/', $tahun_ajaran);
$ta_start = (int)$ta_parts[0];
$ta_end = (int)$ta_parts[1];

// Define Date Ranges for Queries
if ($semester === 'ganjil') {
    // July - Dec of Start Year
    $start_date = "$ta_start-07-01";
    $end_date = "$ta_start-12-31";
    $infaq_condition = "(p.tahun = $ta_start AND p.bulan >= 7)";
} elseif ($semester === 'genap') {
    // Jan - Jun of End Year
    $start_date = "$ta_end-01-01";
    $end_date = "$ta_end-06-30";
    $infaq_condition = "(p.tahun = $ta_end AND p.bulan <= 6)";
} else {
    // Full Year (July Start - June End)
    $start_date = "$ta_start-07-01";
    $end_date = "$ta_end-06-30";
    $infaq_condition = "((p.tahun = $ta_start AND p.bulan >= 7) OR (p.tahun = $ta_end AND p.bulan <= 6))";
}

// Set headers for Excel download
$filename = "laporan_pembayaran_" . str_replace('/', '-', $tahun_ajaran) . "_" . $semester . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");

// Styles for the Excel table
echo "
<style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 5px; }
    th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .bg-red { background-color: #ffcccc; color: red; }
    .bg-green { background-color: #ccffcc; color: green; }
</style>
";

// Configuration for Targets (Same as dashboard)
// Configuration for Targets (Fetched from Settings)
$settings = [];
$q_set = $conn->query("SELECT * FROM settings");
while ($row = $q_set->fetch_assoc()) {
    $settings[$row['key']] = $row['value'];
}

$target_ikhsan = (int)($settings['target_ikhsan_tahunan'] ?? 146000);
$target_infaq = (int)($settings['target_infaq_tahunan'] ?? 0);       

// Build WHERE clause
$where_clause = "";
if ($user_role === 'admin' && $kelas_filter) {
    $where_clause = " WHERE s.kelas_id = $kelas_filter ";
} elseif ($user_role === 'guru') {
    $guru_kelas_ids = [];
    $q_guru_kelas = $conn->query("SELECT kelas_id FROM guru_kelas WHERE user_id = $user_id");
    while ($row = $q_guru_kelas->fetch_assoc()) {
        $guru_kelas_ids[] = $row['kelas_id'];
    }
    
    if (!empty($guru_kelas_ids)) {
        $ids_str = implode(',', $guru_kelas_ids);
        $where_clause = " WHERE s.kelas_id IN ($ids_str) ";
    } else {
        $where_clause = " WHERE 1=0 ";
    }
}

echo "<h3>Laporan Pembayaran TPQ Aisyiyah Wuled</h3>";
echo "<p>Tahun Ajaran: $tahun_ajaran | Semester: " . ucfirst($semester) . "</p>";

if ($user_role === 'admin' && !$kelas_filter) {
    // --- ADMIN VIEW (Per Class Summary) ---
    echo "<table>";
    echo "<thead>
            <tr>
                <th>Kelas</th>
                <th>Ikhsan (Masuk)</th>
                <th>Infaq (Masuk)</th>
                <th>Biaya Lain</th>
                <th>Total</th>
            </tr>
          </thead>";
    echo "<tbody>";
    
    $class_query = "
        SELECT 
            k.id, k.nama_kelas,
            (SELECT COALESCE(SUM(p.jumlah), 0) FROM pembayaran_spp_harian p JOIN siswa s ON p.siswa_id = s.id WHERE s.kelas_id = k.id AND p.tanggal BETWEEN '$start_date' AND '$end_date') as total_spp1,
            (SELECT COALESCE(SUM(p.jumlah), 0) FROM pembayaran_spp_mingguan p JOIN siswa s ON p.siswa_id = s.id WHERE s.kelas_id = k.id AND $infaq_condition) as total_spp2,
            (SELECT COALESCE(SUM(p.jumlah), 0) FROM pembayaran_biaya_lain p JOIN siswa s ON p.siswa_id = s.id WHERE s.kelas_id = k.id AND p.tanggal BETWEEN '$start_date' AND '$end_date') as total_lain
        FROM kelas k
        ORDER BY k.nama_kelas
    ";
    
    $res = $conn->query($class_query);
    while($row = $res->fetch_assoc()) {
        $total_row = $row['total_spp1'] + $row['total_spp2'] + $row['total_lain'];
        echo "<tr>
                <td>" . htmlspecialchars($row['nama_kelas']) . "</td>
                <td class='text-right'>Rp " . number_format($row['total_spp1'], 0, ',', '.') . "</td>
                <td class='text-right'>Rp " . number_format($row['total_spp2'], 0, ',', '.') . "</td>
                <td class='text-right'>Rp " . number_format($row['total_lain'], 0, ',', '.') . "</td>
                <td class='text-right'>Rp " . number_format($total_row, 0, ',', '.') . "</td>
              </tr>";
    }
    echo "</tbody></table>";

} else {
    // --- GURU/FILTERED VIEW (Per Student Detail) ---
    echo "<table>";
    echo "<thead>
            <tr>
                <th rowspan='2'>Nama Siswa</th>
                <th colspan='2'>IKHSAN</th>
                <th colspan='2'>INFAQ</th>
                <th>BIAYA LAIN</th>
            </tr>
            <tr>
                <th>Sudah Bayar</th>
                <th>Kekurangan</th>
                <th>Sudah Bayar</th>
                <th>Kekurangan</th>
                <th>Rincian</th>
            </tr>
          </thead>";
    echo "<tbody>";
    
    $student_query = "
        SELECT 
            s.id, s.nama,
            (SELECT COALESCE(SUM(jumlah), 0) FROM pembayaran_spp_harian p WHERE p.siswa_id = s.id AND p.tanggal BETWEEN '$start_date' AND '$end_date') as bayar_ikhsan,
            (SELECT COALESCE(SUM(jumlah), 0) FROM pembayaran_spp_mingguan p WHERE p.siswa_id = s.id AND $infaq_condition) as bayar_infaq
        FROM siswa s
        $where_clause
        ORDER BY s.nama
    ";

    $res = $conn->query($student_query);
    while($row = $res->fetch_assoc()) {
        $kurang_ikhsan = max(0, $target_ikhsan - $row['bayar_ikhsan']);
        $kurang_infaq = max(0, $target_infaq - $row['bayar_infaq']);
        
        $bl_details = [];
        $q_bl = $conn->query("
            SELECT b.nama, b.jumlah as tagihan, COALESCE(SUM(p.jumlah), 0) as terbayar
            FROM biaya_lain_master b
            LEFT JOIN pembayaran_biaya_lain p ON b.id = p.biaya_lain_id AND p.siswa_id = " . $row['id'] . " AND p.tanggal BETWEEN '$start_date' AND '$end_date'
            GROUP BY b.id
        ");
        while($bl = $q_bl->fetch_assoc()) {
            $sisa = $bl['tagihan'] - $bl['terbayar'];
            if ($sisa > 0) {
                $bl_details[] = $bl['nama'] . " (-" . number_format($sisa, 0, ',', '.') . ")";
            }
        }
        $bl_text = empty($bl_details) ? "Lunas" : implode(", ", $bl_details);
        
        echo "<tr>
                <td>" . htmlspecialchars($row['nama']) . "</td>
                <td class='text-right'>Rp " . number_format($row['bayar_ikhsan'], 0, ',', '.') . "</td>
                <td class='text-right " . ($kurang_ikhsan > 0 ? 'bg-red' : '') . "'>Rp " . number_format($kurang_ikhsan, 0, ',', '.') . "</td>
                <td class='text-right'>Rp " . number_format($row['bayar_infaq'], 0, ',', '.') . "</td>
                <td class='text-right " . ($kurang_infaq > 0 ? 'bg-red' : '') . "'>Rp " . number_format($kurang_infaq, 0, ',', '.') . "</td>
                <td>" . htmlspecialchars($bl_text) . "</td>
              </tr>";
    }
    echo "</tbody></table>";
}
?>
