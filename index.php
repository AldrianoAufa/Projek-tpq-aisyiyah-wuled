<?php
session_start();
require_once 'db_connect.php';

// Definisi jumlah minggu per bulan
$minggu_per_bulan = [
    1 => 5,   // Januari 5 minggu
    2 => 4,   // Februari 4 minggu
    3 => 4,   // Maret 4 minggu
    4 => 5,   // April 5 minggu
    5 => 4,   // Mei 4 minggu
    6 => 4,   // Juni 4 minggu
    7 => 5,   // Juli 5 minggu
    8 => 4,   // Agustus 4 minggu
    9 => 4,   // September 4 minggu
    10 => 5,  // Oktober 5 minggu
    11 => 4,  // November 4 minggu
    12 => 4   // Desember 4 minggu
];

if (!isset($_SESSION['siswa_id'])) {
    header("Location: login.php");
    exit;
}

$siswa_id = $_SESSION['siswa_id'];
$nama_siswa = $_SESSION['nama'];

// Get active tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// --- FILTER LOGIC (Academic Year & Semester) ---
$current_year = date('Y');
$current_month = date('n');

// Determine default Academic Year
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

// Define Date Ranges & Months to Display
$months_to_show = [];
if ($semester === 'ganjil') {
    // July - Dec of Start Year
    $start_date = "$ta_start-07-01";
    $end_date = "$ta_start-12-31";
    for ($m = 7; $m <= 12; $m++) $months_to_show[] = ['m' => $m, 'y' => $ta_start];
} elseif ($semester === 'genap') {
    // Jan - Jun of End Year
    $start_date = "$ta_end-01-01";
    $end_date = "$ta_end-06-30";
    for ($m = 1; $m <= 6; $m++) $months_to_show[] = ['m' => $m, 'y' => $ta_end];
} else {
    // Full Year (July Start - June End)
    $start_date = "$ta_start-07-01";
    $end_date = "$ta_end-06-30";
    for ($m = 7; $m <= 12; $m++) $months_to_show[] = ['m' => $m, 'y' => $ta_start];
    for ($m = 1; $m <= 6; $m++) $months_to_show[] = ['m' => $m, 'y' => $ta_end];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - TPQ Aisyiyah Wuled</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3,
        .font-serif {
            font-family: 'Merriweather', serif;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-teal-900 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i class="fas fa-graduation-cap text-teal-100 text-2xl mr-3"></i>
                    <span class="font-bold text-xl text-white font-serif">TPQ Aisyiyah Wuled</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-teal-100 hidden sm:block">Halo, <b class="text-white"><?php echo $nama_siswa; ?></b></span>
                    <a href="logout.php" class="text-red-300 hover:text-red-100 font-medium text-sm transition-colors">
                        <i class="fas fa-sign-out-alt"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Global Filter -->
        <div class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
            <h2 class="text-lg font-bold text-gray-800 font-serif">Periode Akademik</h2>
            
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                <a href="?tab=dashboard&tahun_ajaran=<?php echo urlencode($tahun_ajaran); ?>&semester=<?php echo $semester; ?>" class="<?php echo $tab == 'dashboard' ? 'border-teal-500 text-teal-700' : 'border-transparent text-gray-500 hover:text-teal-600 hover:border-teal-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-calendar-check mr-2"></i> Status Pembayaran
                </a>
                <a href="?tab=history&tahun_ajaran=<?php echo urlencode($tahun_ajaran); ?>&semester=<?php echo $semester; ?>" class="<?php echo $tab == 'history' ? 'border-teal-500 text-teal-700' : 'border-transparent text-gray-500 hover:text-teal-600 hover:border-teal-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-history mr-2"></i> Riwayat Pembayaran
                </a>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-8">
            <?php
            if ($tab == 'dashboard') {
                // Fetch Settings for Target Calculation
                $settings = [];
                $q_set = $conn->query("SELECT * FROM settings");
                while ($row = $q_set->fetch_assoc()) {
                    $settings[$row['key']] = $row['value'];
                }
                
                // Set default values if not found in settings
                $target_infaq_annual = 200000; // Selalu gunakan nilai default 200.000
                
                // Update database dengan nilai default jika belum ada atau nilainya 0
                if (!isset($settings['target_infaq_tahunan']) || (int)$settings['target_infaq_tahunan'] === 0) {
                    $conn->query("REPLACE INTO settings (`key`, `value`) VALUES ('target_infaq_tahunan', '200000')");
                    $settings['target_infaq_tahunan'] = '200000';
                    $target_infaq_annual = 200000;
                } else {
                    $target_infaq_annual = (int)$settings['target_infaq_tahunan'];
                }
                
                // Set default untuk ikhsan
                $target_ikhsan_annual = isset($settings['target_ikhsan_tahunan']) ? (int)$settings['target_ikhsan_tahunan'] : 146000;
                if (!isset($settings['target_ikhsan_tahunan'])) {
                    $conn->query("INSERT INTO settings (`key`, `value`) VALUES ('target_ikhsan_tahunan', '146000') ");
                    $settings['target_ikhsan_tahunan'] = '146000'; // Update local settings
                }
                
                // Calculate Semester Targets (Annual / 2 for semester view, full for annual view)
                if ($semester === 'semua') {
                    $target_ikhsan_semester = $target_ikhsan_annual;
                    $target_infaq_semester = $target_infaq_annual;
                } else {
                    $target_ikhsan_semester = $target_ikhsan_annual / 2;
                    
                    // Hitung target infaq berdasarkan jumlah minggu di semester
                    if ($semester === 'genap') {
                        $bulan_awal = 1;  // Januari
                        $bulan_akhir = 6;  // Juni
                        $tahun = $ta_end;  // Tahun ajaran berakhir
                        
                        // Hitung total minggu untuk semester yang dipilih
                        $minggu_per_semester = 26; // Fixed 26 weeks per semester
                        $harga_per_minggu = 5000;
                        $target_infaq_semester = $minggu_per_semester * $harga_per_minggu; // 26 x 5.000 = 130.000
                    } else {
                        // Untuk semester ganjil, gunakan 26 minggu x 5.000
                        $minggu_per_semester = 26; // Fixed 26 weeks per semester
                        $harga_per_minggu = 5000;
                        $target_infaq_semester = $minggu_per_semester * $harga_per_minggu; // 26 x 5.000 = 130.000
                    }
                }
                
                // Hitung total hari efektif (tidak termasuk Jumat dan hari libur)
                $total_hari_efektif = 0;
                $current_date = new DateTime($start_date);
                $end_date_obj = new DateTime($end_date);
                
                while ($current_date <= $end_date_obj) {
                    // Lewati hari Jumat (5) dan hari libur
                    if ($current_date->format('N') != 5) { // 5 = Jumat
                        $date_str = $current_date->format('Y-m-d');
                        $is_holiday = $conn->query("SELECT id FROM libur WHERE tanggal = '$date_str'")->num_rows > 0;
                        if (!$is_holiday) {
                            $total_hari_efektif++;
                        }
                    }
                    $current_date->modify('+1 day');
                }
                
                // Hitung total yang seharusnya dibayar (hanya untuk hari efektif)
                $q_daily_rate = "SELECT value FROM settings WHERE `key` = 'default_spp_harian' LIMIT 1";
                $daily_rate = (float)($conn->query($q_daily_rate)->fetch_assoc()['value'] ?? 2000);
                $target_ikhsan_semester = $total_hari_efektif * $daily_rate;
                
                // Calculate Total Paid in Current Semester
                $q_paid_ikhsan = "SELECT COALESCE(SUM(jumlah), 0) as total FROM pembayaran_spp_harian WHERE siswa_id = '$siswa_id' AND tanggal BETWEEN '$start_date' AND '$end_date'";
                $paid_ikhsan = $conn->query($q_paid_ikhsan)->fetch_assoc()['total'];
                
                if ($semester === 'ganjil') {
                    $infaq_cond = "(tahun = $ta_start AND bulan >= 7)";
                } elseif ($semester === 'genap') {
                    $infaq_cond = "(tahun = $ta_end AND bulan <= 6)";
                } else {
                    $infaq_cond = "((tahun = $ta_start AND bulan >= 7) OR (tahun = $ta_end AND bulan <= 6))";
                }
                
                $q_paid_infaq = "SELECT COALESCE(SUM(jumlah), 0) as total FROM pembayaran_spp_mingguan WHERE siswa_id = '$siswa_id' AND $infaq_cond";
                $paid_infaq = $conn->query($q_paid_infaq)->fetch_assoc()['total'];
                
                // Calculate Deficits
                $deficit_ikhsan = max(0, $target_ikhsan_semester - $paid_ikhsan);
                $deficit_infaq = max(0, $target_infaq_semester - $paid_infaq);
            ?>

                <!-- Filter Form - Tahun Ajaran dan Semester -->
                <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center bg-gray-50 p-4 rounded-xl border border-gray-100 flex-wrap">
                    <input type="hidden" name="tab" value="dashboard">

                    <!-- Filter Tahun Ajaran -->
                    <div class="flex items-center gap-2">
                        <label for="tahun_ajaran" class="text-sm font-medium text-gray-700 whitespace-nowrap">Thn Ajaran:</label>
                        <select name="tahun_ajaran" id="tahun_ajaran" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                            <?php
                            $base_year = 2024; // Earliest year in system
                            $end_year = $current_year + 2; // Allow planning 2 years ahead
                            for ($y1 = $base_year; $y1 <= $end_year; $y1++) {
                                $y2 = $y1 + 1;
                                $val = "$y1/$y2";
                                $sel = ($tahun_ajaran == $val) ? 'selected' : '';
                                echo "<option value='$val' $sel>$val</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Filter Semester -->
                    <div class="flex items-center gap-2">
                        <label for="semester" class="text-sm font-medium text-gray-700 whitespace-nowrap">Semester:</label>
                        <select name="semester" id="semester" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                            <option value="ganjil" <?php echo $semester == 'ganjil' ? 'selected' : ''; ?>>Ganjil (Jul-Des)</option>
                            <option value="genap" <?php echo $semester == 'genap' ? 'selected' : ''; ?>>Genap (Jan-Jun)</option>
                            <option value="semua" <?php echo $semester == 'semua' ? 'selected' : ''; ?>>Satu Tahun</option>
                        </select>
                    </div>
                </form>

                <!-- Payment Deficit Information -->
                <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- SPP Harian (Ikhsan) Deficit -->
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 <?php echo $deficit_ikhsan > 0 ? 'border-red-500' : 'border-green-500'; ?>">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide">SPP Harian (Ikhsan)</h4>
                            <i class="fas fa-calendar-day text-2xl <?php echo $deficit_ikhsan > 0 ? 'text-red-500' : 'text-green-500'; ?>"></i>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total Hari semester <?php echo ucfirst($semester); ?>:</span>
                                <span class="font-semibold text-gray-800"><?php echo $total_hari_efektif; ?> hari x Rp <?php echo number_format($daily_rate, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Jumlah:</span>
                                <span class="font-semibold text-blue-600">Rp <?php echo number_format($target_ikhsan_semester, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Sudah Dibayar:</span>
                                <span class="font-semibold text-green-600">Rp <?php echo number_format($paid_ikhsan, 0, ',', '.'); ?></span>
                            </div>
                            <div class="border-t pt-2 flex justify-between">
                                <span class="font-bold text-gray-700">Kekurangan:</span>
                                <span class="font-bold text-lg <?php echo $deficit_ikhsan > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                    <?php if ($deficit_ikhsan > 0): ?>
                                        Rp <?php echo number_format($deficit_ikhsan, 0, ',', '.'); ?>
                                    <?php else: ?>
                                        <span class="flex items-center gap-1"><i class="fas fa-check-circle"></i> Lunas</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- SPP Mingguan (Infaq) Deficit -->
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 <?php echo $deficit_infaq > 0 ? 'border-red-500' : 'border-green-500'; ?>">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide">SPP Mingguan (Infaq)</h4>
                            <i class="fas fa-calendar-week text-2xl <?php echo $deficit_infaq > 0 ? 'text-red-500' : 'text-green-500'; ?>"></i>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600"><?php echo ucfirst($semester) == 'Semua' ? 'Tahunan' : 'Semester ' . ucfirst($semester); ?>:</span>
                                <span class="font-semibold text-gray-800">Rp <?php echo number_format($target_infaq_semester, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Sudah Dibayar:</span>
                                <span class="font-semibold text-green-600">Rp <?php echo number_format($paid_infaq, 0, ',', '.'); ?></span>
                            </div>
                            <div class="border-t pt-2 flex justify-between">
                                <span class="font-bold text-gray-700">Kekurangan:</span>
                                <span class="font-bold text-lg <?php echo $deficit_infaq > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                    <?php if ($deficit_infaq > 0): ?>
                                        Rp <?php echo number_format($deficit_infaq, 0, ',', '.'); ?>
                                    <?php else: ?>
                                        <span class="flex items-center gap-1"><i class="fas fa-check-circle"></i> Lunas</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendars Grid -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold mb-6 text-teal-900 border-b pb-2 font-serif">Kalender SPP Harian (<?php echo ucfirst($semester) . ' ' . $tahun_ajaran; ?>)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        // Display all months in the semester
                        foreach ($months_to_show as $period) {
                            $m = $period['m'];
                            $y = $period['y'];
                            $days_in_month = cal_days_in_month(CAL_GREGORIAN, $m, $y);
                            $month_name = date('F', mktime(0, 0, 0, $m, 10));
                            
                            // Get paid dates for this month
                            $paid_dates = [];
                            $result = $conn->query("SELECT DAY(tanggal) as day FROM pembayaran_spp_harian WHERE siswa_id = '$siswa_id' AND MONTH(tanggal) = '$m' AND YEAR(tanggal) = '$y'");
                            while ($row = $result->fetch_assoc()) {
                                $paid_dates[] = $row['day'];
                            }
                            
                            // Get holidays for this month
                            $holidays = [];
                            $result = $conn->query("SELECT DAY(tanggal) as day, deskripsi FROM libur WHERE MONTH(tanggal) = '$m' AND YEAR(tanggal) = '$y'");
                            while ($row = $result->fetch_assoc()) {
                                $holidays[$row['day']] = $row['deskripsi'];
                            }
                        ?>
                            <!-- Single Month Calendar -->
                            <div class="bg-white rounded-xl shadow-md p-4 border border-gray-100">
                                <h4 class="text-center font-bold text-teal-800 mb-3 text-sm"><?php echo "$month_name $y"; ?></h4>
                                <div class="grid grid-cols-7 gap-1 text-center text-xs">
                                    <div class="font-bold text-gray-400 uppercase text-[9px] mb-1">Min</div>
                                    <div class="font-bold text-gray-400 uppercase text-[9px] mb-1">Sen</div>
                                    <div class="font-bold text-gray-400 uppercase text-[9px] mb-1">Sel</div>
                                    <div class="font-bold text-gray-400 uppercase text-[9px] mb-1">Rab</div>
                                    <div class="font-bold text-gray-400 uppercase text-[9px] mb-1">Kam</div>
                                    <div class="font-bold text-gray-400 uppercase text-[9px] mb-1">Jum</div>
                                    <div class="font-bold text-gray-400 uppercase text-[9px] mb-1">Sab</div>

                                    <?php
                                    $first_day = date('w', strtotime("$y-$m-01"));
                                    for ($i = 0; $i < $first_day; $i++) {
                                        echo "<div></div>";
                                    }

                                    for ($day = 1; $day <= $days_in_month; $day++) {
                                        $date_str = "$y-$m-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                                        $day_of_week = date('w', strtotime($date_str));
                                        $is_friday = ($day_of_week == 5);
                                        $is_paid = in_array($day, $paid_dates);
                                        $is_holiday = isset($holidays[$day]);

                                        $bg_class = "bg-white border border-gray-200";
                                        $text_class = "text-gray-700";
                                        $status = "";

                                        if ($is_paid) {
                                            $bg_class = "bg-teal-500 border-teal-600 shadow-sm";
                                            $text_class = "text-white";
                                            $status = "Lunas";
                                        } elseif ($is_holiday) {
                                            $bg_class = "bg-red-50 border-red-200";
                                            $text_class = "text-red-500";
                                            $status = "Libur: " . $holidays[$day];
                                        } elseif ($is_friday) {
                                            $bg_class = "bg-gray-100 border-transparent";
                                            $text_class = "text-gray-400";
                                            $status = "Jumat (Libur)";
                                        }

                                        echo "<div class='p-1 rounded-lg $bg_class $text_class flex flex-col items-center justify-center h-10 relative group transition-all hover:scale-105'>";
                                        echo "<span class='font-bold text-[10px]'>$day</span>";
                                        if ($is_paid) echo "<i class='fas fa-check-circle text-[8px] mt-0.5'></i>";
                                        if ($status) echo "<div class='absolute bottom-full mb-2 hidden group-hover:block bg-gray-800 text-white text-xs p-2 rounded shadow-lg z-10 whitespace-nowrap'>$status</div>";
                                        echo "</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- SPP Mingguan Status -->
                <div>
                    <h3 class="text-xl font-bold mb-6 text-teal-900 border-b pb-2 font-serif">Status SPP Mingguan (<?php echo ucfirst($semester) . " " . $tahun_ajaran; ?>)</h3>
                    <div class="space-y-6">
                        <?php
                        // Daftar nama bulan dalam bahasa Indonesia
                        $nama_bulan = [
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember'
                        ];
                        
                        // Ambil data pembayaran SPP mingguan
                        $paid_weeks = [];
                        $res = $conn->query("SELECT bulan, minggu_ke, tahun, jumlah 
                                           FROM pembayaran_spp_mingguan 
                                           WHERE siswa_id = '$siswa_id' AND $infaq_cond");
                        
                        while ($row = $res->fetch_assoc()) {
                            $paid_weeks[$row['tahun']][$row['bulan']][$row['minggu_ke']] = [
                                'jumlah' => $row['jumlah']
                            ];
                        }
                        
                        // Tampilkan status per bulan
                        foreach ($months_to_show as $month_data) {
                            $bulan = $month_data['m'];
                            $tahun = $month_data['y'];
                            $total_minggu = $minggu_per_bulan[$bulan];
                            
                            echo "<div class='bg-white rounded-xl shadow-sm p-4 border border-gray-100'>";
                            echo "<h4 class='font-bold text-gray-800 mb-3'>{$nama_bulan[$bulan]} $tahun</h4>";
                            echo "<div class='grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3'>";
                            
                            for ($minggu = 1; $minggu <= $total_minggu; $minggu++) {
                                $is_paid = isset($paid_weeks[$tahun][$bulan][$minggu]);
                                $bg_color = $is_paid ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200';
                                $text_color = $is_paid ? 'text-green-700' : 'text-gray-600';
                                
                                echo "<div class='border rounded-lg p-3 $bg_color $text_color'>";
                                echo "<div class='flex justify-between items-center'>";
                                echo "<span class='font-medium'>Minggu ke-$minggu</span>";
                                echo $is_paid 
                                    ? "<span class='text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full'>Lunas</span>" 
                                    : "<span class='text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full'>Belum Lunas</span>";
                                echo "</div>";
                                
                                if ($is_paid) {
                                    echo "<div class='mt-1 text-xs text-gray-500'>";
                                    echo "Dibayar: Rp " . number_format($paid_weeks[$tahun][$bulan][$minggu]['jumlah'], 0, ',', '.');
                                    echo "</div>";
                                } else {
                                    echo "<div class='mt-1 text-xs text-gray-500'>Rp 5.000</div>";
                                }
                                
                                echo "</div>";
                            }
                            
                            echo "</div>"; // Close grid
                            echo "</div>"; // Close card
                        }
                        while ($row = $res->fetch_assoc()) {
                            $weekly_payments[$row['tahun']][$row['bulan']][] = $row['minggu_ke'];
                        }

                        foreach ($months_to_show as $period) {
                            $m = $period['m'];
                            $y = $period['y'];
                            $m_name = date('F', mktime(0, 0, 0, $m, 10));
                            $paid_weeks = isset($weekly_payments[$y][$m]) ? $weekly_payments[$y][$m] : [];
                        ?>
                            
                        <?php
                        }
                        ?>
                    </div>
                </div>
            <?php
            

            } else if ($tab == 'history') {
                // Fetch ALL payments and merge
                $history = [];

                // Daily SPP - Grouped by Transaction Time (approx)
                // Filter by DATE RANGE ($start_date to $end_date)
                $query_harian = "
                    SELECT 
                        MAX(created_at) as tanggal_transaksi, 
                        SUM(jumlah) as total_jumlah, 
                        'SPP Harian' as tipe, 
                        CONCAT(DATE_FORMAT(MIN(tanggal), '%d/%m/%Y'), ' - ', DATE_FORMAT(MAX(tanggal), '%d/%m/%Y'), ' (', COUNT(*), ' Hari)') as detail 
                    FROM pembayaran_spp_harian 
                    WHERE siswa_id = '$siswa_id' AND tanggal BETWEEN '$start_date' AND '$end_date'
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')
                ";
                $res = $conn->query($query_harian);
                while ($row = $res->fetch_assoc()) $history[] = $row;

                // Weekly SPP - Grouped by Transaction Time
                // Filter by Infaq Condition (Month/Year)
                // Note: $infaq_condition variable is not defined in this file yet.
                // We need to define it or use the logic manually.

                // Re-define infaq condition logic locally if not present, or use the one from dashboard logic if we copied it.
                // Wait, I didn't copy $infaq_condition definition in the previous step. Let's define it now.

                if ($semester === 'ganjil') {
                    $infaq_cond = "(tahun = $ta_start AND bulan >= 7)";
                } elseif ($semester === 'genap') {
                    $infaq_cond = "(tahun = $ta_end AND bulan <= 6)";
                } else {
                    $infaq_cond = "((tahun = $ta_start AND bulan >= 7) OR (tahun = $ta_end AND bulan <= 6))";
                }

                $query_mingguan = "
                    SELECT 
                        created_at, 
                        jumlah, 
                        minggu_ke, 
                        bulan, 
                        tahun 
                    FROM pembayaran_spp_mingguan 
                    WHERE siswa_id = '$siswa_id' AND $infaq_cond
                    ORDER BY created_at DESC, tahun DESC, bulan DESC, minggu_ke DESC
                ";
                $res = $conn->query($query_mingguan);
                $grouped_mingguan = [];

                while ($row = $res->fetch_assoc()) {
                    // Group by minute
                    $key = date('Y-m-d H:i', strtotime($row['created_at']));
                    if (!isset($grouped_mingguan[$key])) {
                        $grouped_mingguan[$key] = [
                            'tanggal_transaksi' => $row['created_at'],
                            'total_jumlah' => 0,
                            'tipe' => 'SPP Mingguan',
                            'items' => []
                        ];
                    }
                    $grouped_mingguan[$key]['total_jumlah'] += $row['jumlah'];
                    $grouped_mingguan[$key]['items'][] = $row;
                }

                foreach ($grouped_mingguan as $group) {
                    $details = [];
                    // Group items by Month-Year internally
                    $by_month = [];
                    foreach ($group['items'] as $item) {
                        $m_y = $item['bulan'] . '-' . $item['tahun'];
                        $by_month[$m_y][] = $item['minggu_ke'];
                    }

                    // Sort months chronologically ascending (Oldest -> Newest) for better readability
                    uksort($by_month, function ($a, $b) {
                        list($ma, $ya) = explode('-', $a);
                        list($mb, $yb) = explode('-', $b);
                        $ta = mktime(0, 0, 0, $ma, 1, $ya);
                        $tb = mktime(0, 0, 0, $mb, 1, $yb);
                        return $ta - $tb;
                    });

                    foreach ($by_month as $m_y => $weeks) {
                        list($bulan, $tahun) = explode('-', $m_y);
                        $min_w = min($weeks);
                        $max_w = max($weeks);
                        $month_name = date('M', mktime(0, 0, 0, $bulan, 10));
                        $details[] = "Minggu $min_w-$max_w ($month_name $tahun)";
                    }

                    $history[] = [
                        'tanggal_transaksi' => $group['tanggal_transaksi'],
                        'total_jumlah' => $group['total_jumlah'],
                        'tipe' => 'SPP Mingguan',
                        'detail' => implode(', ', $details)
                    ];
                }

                // Other Fees - Filter by Date
                $query_lain = "
                    SELECT 
                        p.created_at as tanggal_transaksi, 
                        p.jumlah as total_jumlah, 
                        'Biaya Lain' as tipe, 
                        b.nama as detail 
                    FROM pembayaran_biaya_lain p 
                    JOIN biaya_lain_master b ON p.biaya_lain_id = b.id 
                    WHERE p.siswa_id = '$siswa_id' AND p.tanggal BETWEEN '$start_date' AND '$end_date'
                ";
                $res = $conn->query($query_lain);
                while ($row = $res->fetch_assoc()) $history[] = $row;

                // Sort by date desc
                usort($history, function ($a, $b) {
                    return strtotime($b['tanggal_transaksi']) - strtotime($a['tanggal_transaksi']);
                });
            ?>
                <h3 class="text-xl font-bold mb-6 text-teal-900 font-serif">Riwayat Pembayaran Lengkap</h3>
                <div class="bg-white shadow-sm overflow-hidden rounded-xl border border-gray-100">
                    <ul class="divide-y divide-gray-100">
                        <?php if (empty($history)): ?>
                            <li class="px-6 py-8 text-sm text-gray-500 text-center">Belum ada riwayat pembayaran.</li>
                        <?php else: ?>
                            <?php foreach ($history as $h): ?>
                                <li class="px-6 py-4 hover:bg-teal-50 transition-colors flex flex-col md:flex-row md:items-center md:justify-between text-sm gap-2 md:gap-0">
                                    <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-4 flex-1">
                                        <div class="flex items-center justify-between md:justify-start gap-4 w-full md:w-auto">
                                            <span class="text-gray-500 w-auto md:w-32 flex-shrink-0 text-xs"><?php echo date('d/m/Y H:i', strtotime($h['tanggal_transaksi'])); ?></span>
                                            <span class="px-3 py-1 rounded-full text-xs font-bold w-auto md:w-28 text-center flex-shrink-0
                                            <?php echo $h['tipe'] == 'SPP Harian' ? 'bg-teal-100 text-teal-800' : ($h['tipe'] == 'SPP Mingguan' ? 'bg-cyan-100 text-cyan-800' : 'bg-purple-100 text-purple-800'); ?>">
                                                <?php echo $h['tipe']; ?>
                                            </span>
                                        </div>
                                        <span class="text-gray-700 w-full md:w-auto font-medium"><?php echo $h['detail']; ?></span>
                                    </div>
                                    <div class="font-bold text-teal-900 md:ml-4 mt-1 md:mt-0 text-right md:text-left text-base">Rp <?php echo number_format($h['total_jumlah'], 0, ',', '.'); ?></div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</body>

</html>