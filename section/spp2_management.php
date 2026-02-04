<?php
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $siswa_id = (int)$_POST['siswa_id'];
        $total_bayar = (float)$_POST['total_bayar'];
        $user_id = $_SESSION['user_id'];

        // Get Weekly Rate
        $settings_res = $conn->query("SELECT value FROM settings WHERE `key`='default_spp_mingguan'");
        $weekly_rate = ($settings_res && $settings_res->num_rows > 0) ? (float)$settings_res->fetch_assoc()['value'] : 2000;

        if ($weekly_rate <= 0) $weekly_rate = 2000; // Safety fallback

        $weeks_count = floor($total_bayar / $weekly_rate);
        $remainder = $total_bayar % $weekly_rate;

        if ($weeks_count <= 0) {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Nominal pembayaran tidak cukup untuk 1 minggu (Nominal per minggu: Rp " . number_format($weekly_rate, 0, ',', '.') . ").</p>
                  </div>";
        } else {
            $success_count = 0;
            $conn->begin_transaction();

            try {
                // Get last payment period for this student
                $last_payment_res = $conn->query("SELECT minggu_ke, bulan, tahun FROM pembayaran_spp_mingguan WHERE siswa_id=$siswa_id ORDER BY tahun DESC, bulan DESC, minggu_ke DESC LIMIT 1");
                if ($last_payment_res && $last_payment_res->num_rows > 0) {
                    $last_payment = $last_payment_res->fetch_assoc();
                    $curr_week = $last_payment['minggu_ke'];
                    $curr_month = $last_payment['bulan'];
                    $curr_year = $last_payment['tahun'];

                    // Increment to next week
                    $curr_week++;
                    if ($curr_week > 4) {
                        $curr_week = 1;
                        $curr_month++;
                        if ($curr_month > 12) {
                            $curr_month = 1;
                            $curr_year++;
                        }
                    }
                } else {
                    // If no previous payment, start from beginning of current academic year
                    $current_year = date('Y');
                    $current_month = date('n');
                    if ($current_month >= 7) {
                        $start_month = 7;
                        $start_year = $current_year;
                    } else {
                        $start_month = 7;
                        $start_year = $current_year - 1;
                    }
                    $curr_week = 1;
                    $curr_month = $start_month;
                    $curr_year = $start_year;
                }

                $added_weeks = 0;

                while ($added_weeks < $weeks_count) {
                    // Check duplicate
                    $check = $conn->query("SELECT id FROM pembayaran_spp_mingguan WHERE siswa_id=$siswa_id AND minggu_ke=$curr_week AND bulan=$curr_month AND tahun=$curr_year");

                    if ($check->num_rows == 0) {
                        $query = "INSERT INTO pembayaran_spp_mingguan (siswa_id, minggu_ke, bulan, tahun, jumlah, user_id) VALUES ($siswa_id, $curr_week, $curr_month, $curr_year, $weekly_rate, $user_id)";
                        if ($conn->query($query)) {
                            $success_count++;
                        }
                    }

                    // Increment Period
                    $curr_week++;
                    if ($curr_week > 4) {
                        $curr_week = 1;
                        $curr_month++;
                        if ($curr_month > 12) {
                            $curr_month = 1;
                            $curr_year++;
                        }
                    }
                    $added_weeks++;
                }

                $conn->commit();

                $msg = "$success_count minggu pembayaran berhasil dicatat.";
                if ($remainder > 0) {
                    $msg .= " (Sisa uang: Rp " . number_format($remainder, 0, ',', '.') . " disimpan sebagai saldo/kembalian)";
                }

                echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                        <p class='font-bold'>Sukses!</p>
                        <p>$msg</p>
                      </div>";
            } catch (Exception $e) {
                $conn->rollback();
                echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                        <p class='font-bold'>Error!</p>
                        <p>Gagal mencatat pembayaran: " . $e->getMessage() . "</p>
                      </div>";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $query = "DELETE FROM pembayaran_spp_mingguan WHERE id=$id";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Pembayaran berhasil dihapus.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal menghapus data: " . $conn->error . "</p>
                  </div>";
        }
    }
}

// Fetch Students for Dropdown
$siswa_result = $conn->query("SELECT id, nama, login_code FROM siswa ORDER BY nama");
$siswa_options = [];
while ($row = $siswa_result->fetch_assoc()) {
    $siswa_options[] = $row;
}

// Pagination & Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$where = "WHERE 1=1";
if ($search) {
    $where .= " AND (s.nama LIKE '%$search%' OR s.login_code LIKE '%$search%')";
}

$total_result = $conn->query("SELECT COUNT(*) as total FROM pembayaran_spp_mingguan p JOIN siswa s ON p.siswa_id = s.id $where");
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$query = "SELECT p.*, s.nama as nama_siswa, s.login_code, u.name as nama_petugas 
          FROM pembayaran_spp_mingguan p 
          JOIN siswa s ON p.siswa_id = s.id 
          LEFT JOIN users u ON p.user_id = u.id 
          $where 
          ORDER BY p.tahun DESC, p.bulan DESC, p.minggu_ke DESC, p.created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

$months = [
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
?>

<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <button onclick="openModal('add')" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors flex items-center">
            <i class="fas fa-plus mr-2"></i> Catat Pembayaran
        </button>

        <form method="GET" class="flex w-full md:w-auto no-ajax">
            <input type="hidden" name="section" value="spp2_management">
            <div class="relative w-full md:w-64">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari siswa..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Petugas</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result->num_rows > 0): ?>
                    <?php $no = $offset + 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Minggu ke-<?php echo $row['minggu_ke']; ?>, <?php echo $months[$row['bulan']]; ?> <?php echo $row['tahun']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama_siswa']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['nama_petugas'] ?? '-'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick='confirmDelete(<?php echo $row["id"]; ?>, "<?php echo htmlspecialchars($row["nama_siswa"]); ?>")' class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada data pembayaran ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-6">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?section=spp2_management&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                        class="nav-link relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i == $page ? 'text-teal-600 bg-teal-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="paymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modalTitle">Catat Pembayaran SPP Mingguan</h3>
            <form id="paymentForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="siswa_id">Siswa</label>
                    <select name="siswa_id" id="siswa_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline select2">
                        <option value="">Pilih Siswa</option>
                        <?php foreach ($siswa_options as $siswa): ?>
                            <option value="<?php echo $siswa['id']; ?>"><?php echo htmlspecialchars($siswa['nama']); ?> (<?php echo htmlspecialchars($siswa['login_code']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <p class="text-xs text-gray-500">
                        Sistem otomatis melanjutkan dari pembayaran terakhir atau mulai dari awal tahun ajaran jika belum ada data.
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="total_bayar">Total Bayar (Rp)</label>
                    <input type="number" name="total_bayar" id="total_bayar" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Contoh: 10000">
                    <?php
                    $default_val = $conn->query("SELECT value FROM settings WHERE `key`='default_spp_mingguan'")->fetch_assoc()['value'] ?? '2000';
                    ?>
                    <p class="text-xs text-gray-500 mt-1">
                        Nominal per minggu: <b>Rp <?php echo number_format($default_val, 0, ',', '.'); ?></b>.
                        Sistem akan otomatis menghitung jumlah minggu.
                    </p>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="submit" name="action" value="add" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors">Simpan</button>
                    <button type="button" onclick="closeModal()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form (Hidden) -->
<form id="deleteForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
    function openModal(mode) {
        const modal = document.getElementById('paymentModal');
        modal.classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('paymentModal').classList.add('hidden');
    }

    function confirmDelete(id, name) {
        if (confirm('Apakah Anda yakin ingin menghapus pembayaran dari ' + name + '?')) {
            document.getElementById('deleteId').value = id;
            const form = document.getElementById('deleteForm');
            form.dispatchEvent(new Event('submit', {
                bubbles: true,
                cancelable: true
            }));
        }
    }

    window.onclick = function(event) {
        const modal = document.getElementById('paymentModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>