<?php
// Function to check if a date is a holiday
function isHoliday($conn, $date) {
    $date_str = $date->format('Y-m-d');
    $result = $conn->query("SELECT id FROM libur WHERE tanggal = '$date_str'");
    return $result && $result->num_rows > 0;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $siswa_id = (int)$_POST['siswa_id'];
        $total_bayar = (float)$_POST['total_bayar'];
        $user_id = $_SESSION['user_id'];

        // Get Daily Rate
        $settings_res = $conn->query("SELECT value FROM settings WHERE `key`='default_spp_harian'");
        $daily_rate = ($settings_res && $settings_res->num_rows > 0) ? (float)$settings_res->fetch_assoc()['value'] : 2000;

        if ($daily_rate <= 0) $daily_rate = 2000; // Safety fallback

        $days_count = floor($total_bayar / $daily_rate);
        $remainder = $total_bayar % $daily_rate;

        if ($days_count <= 0) {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Nominal pembayaran tidak cukup untuk 1 hari (Nominal per hari: Rp " . number_format($daily_rate, 0, ',', '.') . ").</p>
                  </div>";
        } else {
            $success_count = 0;
            $conn->begin_transaction();

            try {
                // Get last payment date for this student
                $last_payment_res = $conn->query("SELECT tanggal FROM pembayaran_spp_harian WHERE siswa_id=$siswa_id ORDER BY tanggal DESC LIMIT 1");
                if ($last_payment_res && $last_payment_res->num_rows > 0) {
                    $last_date = $last_payment_res->fetch_assoc()['tanggal'];
                    $current_date = new DateTime($last_date);
                    $current_date->modify('+1 day'); // Start from next day
                } else {
                    // If no previous payment, start from beginning of current semester
                    $current_year = date('Y');
                    $current_month = date('n');
                    if ($current_month >= 7) {
                        $start_date = $current_year . '-07-01';
                    } else {
                        $start_date = ($current_year - 1) . '-07-01';
                    }
                    $current_date = new DateTime($start_date);
                }

                $added_days = 0;

                while ($added_days < $days_count) {
                    // Skip Friday (5) and holidays
                    $is_holiday = isHoliday($conn, $current_date);
                    if ($current_date->format('w') == 5 || $is_holiday) {
                        $current_date->modify('+1 day');
                        continue;
                    }

                    $date_str = $current_date->format('Y-m-d');

                    // Check duplicate
                    $check = $conn->query("SELECT id FROM pembayaran_spp_harian WHERE siswa_id=$siswa_id AND tanggal='$date_str'");
                    if ($check->num_rows == 0) {
                        $query = "INSERT INTO pembayaran_spp_harian (siswa_id, tanggal, jumlah, user_id) VALUES ($siswa_id, '$date_str', $daily_rate, $user_id)";
                        if ($conn->query($query)) {
                            $success_count++;
                        }
                    }

                    $current_date->modify('+1 day');
                    $added_days++;
                }

                $conn->commit();

                $msg = "$success_count data pembayaran berhasil dicatat.\n";
                // Count skipped holidays
                $holidays_count = 0;
                $check_date = clone $current_date;
                for ($i = 0; $i < $days_count * 2; $i++) { // Check up to double the days to account for weekends/holidays
                    if ($check_date->format('w') != 5 && isHoliday($conn, $check_date)) {
                        $holidays_count++;
                    }
                    $check_date->modify('+1 day');
                }
                if ($holidays_count > 0) {
                    $msg .= "\n*Catatan*: $holidays_count hari libur dilewati dalam perhitungan.";
                }
                if ($remainder > 0) {
                    $msg .= "\nSisa uang: Rp " . number_format($remainder, 0, ',', '.') . " (belum diproses)";
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
        $query = "DELETE FROM pembayaran_spp_harian WHERE id=$id";
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

$total_result = $conn->query("SELECT COUNT(*) as total FROM pembayaran_spp_harian p JOIN siswa s ON p.siswa_id = s.id $where");
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$query = "SELECT p.*, s.nama as nama_siswa, s.login_code, u.name as nama_petugas 
          FROM pembayaran_spp_harian p 
          JOIN siswa s ON p.siswa_id = s.id 
          LEFT JOIN users u ON p.user_id = u.id 
          $where 
          ORDER BY p.tanggal DESC, p.created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
?>

<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <button onclick="openModal('add')" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors flex items-center">
            <i class="fas fa-plus mr-2"></i> Catat Pembayaran
        </button>

        <form method="GET" class="flex w-full md:w-auto no-ajax">
            <input type="hidden" name="section" value="spp1_management">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
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
                    <a href="?section=spp1_management&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
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
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modalTitle">Catat Pembayaran SPP Harian</h3>
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
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="total_bayar">Total Bayar (Rp)</label>
                    <input type="number" name="total_bayar" id="total_bayar" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Contoh: 50000">
                    <?php
                    $default_val = $conn->query("SELECT value FROM settings WHERE `key`='default_spp_harian'")->fetch_assoc()['value'] ?? '2000';
                    ?>
                    <?php
                    // Count upcoming holidays in the next 30 days
                    $today = new DateTime();
                    $next_month = new DateTime('+30 days');
                    $holidays = [];
                    $holiday_result = $conn->query("SELECT tanggal, deskripsi FROM libur 
                                                  WHERE tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                                                  ORDER BY tanggal");
                    while ($row = $holiday_result->fetch_assoc()) {
                        $holidays[] = [
                            'date' => $row['tanggal'],
                            'desc' => $row['deskripsi']
                        ];
                    }
                    ?>
                    <p class="text-xs text-gray-500 mt-1">
                        Nominal per hari: <b>Rp <?php echo number_format($default_val, 0, ',', '.'); ?></b>.<br>
                        Hari Jumat dan hari libur tidak dihitung.
                        <?php if (!empty($holidays)): ?>
                            <br>Libur mendatang (30 hari ke depan):
                            <?php foreach ($holidays as $holiday): ?>
                                <br>- <?php echo date('d M Y', strtotime($holiday['date'])); ?>: <?php echo htmlspecialchars($holiday['desc']); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        Simpan
                    </button>
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