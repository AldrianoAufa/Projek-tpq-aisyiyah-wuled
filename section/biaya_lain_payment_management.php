<?php
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $siswa_id = (int)$_POST['siswa_id'];
        $biaya_lain_id = (int)$_POST['biaya_lain_id'];
        $tanggal = $conn->real_escape_string($_POST['tanggal']);
        $jumlah = (float)$_POST['jumlah'];
        $user_id = $_SESSION['user_id'];
        
        $query = "INSERT INTO pembayaran_biaya_lain (siswa_id, biaya_lain_id, tanggal, jumlah, user_id) VALUES ($siswa_id, $biaya_lain_id, '$tanggal', $jumlah, $user_id)";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Pembayaran berhasil dicatat.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal mencatat pembayaran: " . $conn->error . "</p>
                  </div>";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $query = "DELETE FROM pembayaran_biaya_lain WHERE id=$id";
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

// Fetch Students
$siswa_result = $conn->query("SELECT id, nama, login_code FROM siswa ORDER BY nama");
$siswa_options = [];
while ($row = $siswa_result->fetch_assoc()) {
    $siswa_options[] = $row;
}

// Fetch Other Fees Types
$biaya_result = $conn->query("SELECT * FROM biaya_lain_master ORDER BY nama");
$biaya_options = [];
while ($row = $biaya_result->fetch_assoc()) {
    $biaya_options[] = $row;
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

$total_result = $conn->query("SELECT COUNT(*) as total FROM pembayaran_biaya_lain p JOIN siswa s ON p.siswa_id = s.id $where");
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$query = "SELECT p.*, s.nama as nama_siswa, s.login_code, b.nama as nama_biaya, u.name as nama_petugas 
          FROM pembayaran_biaya_lain p 
          JOIN siswa s ON p.siswa_id = s.id 
          JOIN biaya_lain_master b ON p.biaya_lain_id = b.id 
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
            <input type="hidden" name="section" value="biaya_lain_payment_management">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Biaya</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Petugas</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result->num_rows > 0): ?>
                    <?php $no = $offset + 1; while ($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama_siswa']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['nama_biaya']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['nama_petugas'] ?? '-'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick='confirmDelete(<?php echo $row["id"]; ?>, "<?php echo htmlspecialchars($row["nama_siswa"]); ?>")' class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Tidak ada data pembayaran ditemukan.</td>
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
                <a href="?section=biaya_lain_payment_management&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
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
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modalTitle">Catat Pembayaran Biaya Lain</h3>
            <form id="paymentForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="siswa_id">Siswa</label>
                    <select name="siswa_id" id="siswa_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Pilih Siswa</option>
                        <?php foreach ($siswa_options as $siswa): ?>
                            <option value="<?php echo $siswa['id']; ?>"><?php echo htmlspecialchars($siswa['nama']); ?> (<?php echo htmlspecialchars($siswa['login_code']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="biaya_lain_id">Jenis Biaya</label>
                    <select name="biaya_lain_id" id="biaya_lain_id" onchange="updateAmount()" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Pilih Biaya</option>
                        <?php foreach ($biaya_options as $biaya): ?>
                            <option value="<?php echo $biaya['id']; ?>" data-amount="<?php echo $biaya['jumlah']; ?>"><?php echo htmlspecialchars($biaya['nama']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="tanggal">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" value="<?php echo date('Y-m-d'); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="jumlah">Jumlah (Rp)</label>
                    <input type="number" name="jumlah" id="jumlah" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
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
    
    function updateAmount() {
        const select = document.getElementById('biaya_lain_id');
        const amountInput = document.getElementById('jumlah');
        const selectedOption = select.options[select.selectedIndex];
        const amount = selectedOption.getAttribute('data-amount');
        
        if (amount) {
            amountInput.value = amount;
        } else {
            amountInput.value = '';
        }
    }

    function confirmDelete(id, name) {
        if (confirm('Apakah Anda yakin ingin menghapus pembayaran dari ' + name + '?')) {
            document.getElementById('deleteId').value = id;
            const form = document.getElementById('deleteForm');
            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }
    }
    
    window.onclick = function(event) {
        const modal = document.getElementById('paymentModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
