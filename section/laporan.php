<?php
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// 1. Calculate Totals (Keep separate queries for accuracy on summary cards)
$query_spp1 = "SELECT SUM(jumlah) as total FROM pembayaran_spp_harian WHERE tanggal BETWEEN '$start_date' AND '$end_date'";
$total_spp1 = $conn->query($query_spp1)->fetch_assoc()['total'] ?? 0;

$query_spp2 = "SELECT SUM(jumlah) as total FROM pembayaran_spp_mingguan WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
$total_spp2 = $conn->query($query_spp2)->fetch_assoc()['total'] ?? 0;

$query_lain = "SELECT SUM(jumlah) as total FROM pembayaran_biaya_lain WHERE tanggal BETWEEN '$start_date' AND '$end_date'";
$total_lain = $conn->query($query_lain)->fetch_assoc()['total'] ?? 0;

$grand_total = $total_spp1 + $total_spp2 + $total_lain;

// 2. Fetch All Transactions (UNION ALL)
// Columns: tanggal, nama_siswa, nama_kelas, jenis, keterangan, jumlah, penerima
$query_all = "
    SELECT 
        p.tanggal as tgl, 
        s.nama as nama_siswa, 
        k.nama_kelas, 
        'Ikhsan (SPP Harian)' as jenis, 
        '-' as keterangan, 
        p.jumlah,
        u.name as penerima
    FROM pembayaran_spp_harian p
    JOIN siswa s ON p.siswa_id = s.id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.tanggal BETWEEN '$start_date' AND '$end_date'

    UNION ALL

    SELECT 
        DATE(p.created_at) as tgl, 
        s.nama as nama_siswa, 
        k.nama_kelas, 
        'Infaq (SPP Mingguan)' as jenis, 
        CONCAT('Mg ', p.minggu_ke, ' Bln ', p.bulan, ' ', p.tahun) as keterangan, 
        p.jumlah,
        u.name as penerima
    FROM pembayaran_spp_mingguan p
    JOIN siswa s ON p.siswa_id = s.id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE DATE(p.created_at) BETWEEN '$start_date' AND '$end_date'

    UNION ALL

    SELECT 
        p.tanggal as tgl, 
        s.nama as nama_siswa, 
        k.nama_kelas, 
        'Biaya Lain' as jenis, 
        b.nama as keterangan, 
        p.jumlah,
        u.name as penerima
    FROM pembayaran_biaya_lain p
    JOIN siswa s ON p.siswa_id = s.id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    JOIN biaya_lain_master b ON p.biaya_lain_id = b.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.tanggal BETWEEN '$start_date' AND '$end_date'

    ORDER BY tgl DESC, nama_siswa ASC
";

$result_all = $conn->query($query_all);
?>

<div class="bg-white rounded-xl shadow-md p-6 mb-8 no-print">
    <h3 class="text-lg font-bold text-gray-800 mb-4 font-serif">Filter Laporan</h3>
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end no-ajax">
        <input type="hidden" name="section" value="laporan">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Dari Tanggal</label>
            <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Sampai Tanggal</label>
            <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors">
            <i class="fas fa-filter mr-2"></i> Tampilkan
        </button>
        <button type="button" onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors ml-auto">
            <i class="fas fa-print mr-2"></i> Cetak
        </button>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-teal-50 rounded-xl p-6 border border-teal-100">
        <p class="text-teal-600 text-sm font-medium">Total Ikhsan</p>
        <h3 class="text-2xl font-bold text-teal-900">Rp <?php echo number_format($total_spp1, 0, ',', '.'); ?></h3>
    </div>
    <div class="bg-blue-50 rounded-xl p-6 border border-blue-100">
        <p class="text-blue-600 text-sm font-medium">Total Infaq</p>
        <h3 class="text-2xl font-bold text-blue-900">Rp <?php echo number_format($total_spp2, 0, ',', '.'); ?></h3>
    </div>
    <div class="bg-purple-50 rounded-xl p-6 border border-purple-100">
        <p class="text-purple-600 text-sm font-medium">Total Biaya Lain</p>
        <h3 class="text-2xl font-bold text-purple-900">Rp <?php echo number_format($total_lain, 0, ',', '.'); ?></h3>
    </div>
    <div class="bg-green-50 rounded-xl p-6 border border-green-100">
        <p class="text-green-600 text-sm font-medium">Total Pemasukan</p>
        <h3 class="text-2xl font-bold text-green-900">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></h3>
    </div>
</div>

<!-- Unified Transaction Table -->
<div class="bg-white rounded-xl shadow-md p-6 print-area">
    <h3 class="text-lg font-bold text-gray-800 mb-4 font-serif border-b pb-2">Rincian Transaksi</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penerima</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                if ($result_all && $result_all->num_rows > 0):
                    $no = 1;
                    while($row = $result_all->fetch_assoc()): 
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($row['tgl'])); ?></td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama_siswa']); ?></td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['nama_kelas'] ?? '-'); ?></td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                        <?php 
                        $badge_color = 'bg-gray-100 text-gray-800';
                        if (strpos($row['jenis'], 'Ikhsan') !== false) $badge_color = 'bg-teal-100 text-teal-800';
                        elseif (strpos($row['jenis'], 'Infaq') !== false) $badge_color = 'bg-blue-100 text-blue-800';
                        elseif (strpos($row['jenis'], 'Biaya Lain') !== false) $badge_color = 'bg-purple-100 text-purple-800';
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $badge_color; ?>">
                            <?php echo htmlspecialchars($row['jenis']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['keterangan']); ?></td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm font-bold text-gray-900 text-right">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['penerima'] ?? '-'); ?></td>
                </tr>
                <?php 
                    endwhile; 
                else:
                ?>
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">Tidak ada data transaksi pada periode ini.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        body { background-color: white; font-size: 12pt; }
        .shadow-md { box-shadow: none !important; border: none !important; }
        #sidebar, header { display: none !important; }
        main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .print-area { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        
        /* Fix for scrollbar issue */
        .overflow-x-auto {
            overflow: visible !important;
            display: block !important;
            width: 100% !important;
        }
        table {
            width: 100% !important;
            table-layout: fixed; /* Ensure columns don't expand too much */
        }
        td, th {
            white-space: normal !important; /* Allow wrapping */
            word-wrap: break-word;
        }
    }
</style>
