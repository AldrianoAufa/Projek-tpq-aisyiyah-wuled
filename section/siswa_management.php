<?php
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama = $conn->real_escape_string($_POST['nama']);
        $login_code = $conn->real_escape_string($_POST['login_code']);
        
        $kelas_id = !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : "NULL";
        
        $query = "INSERT INTO siswa (nama, login_code, kelas_id) VALUES ('$nama', '$login_code', $kelas_id)";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Data siswa berhasil ditambahkan.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal menambahkan data: " . $conn->error . "</p>
                  </div>";
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $nama = $conn->real_escape_string($_POST['nama']);
        $login_code = $conn->real_escape_string($_POST['login_code']);
        
        $kelas_id = !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : "NULL";
        
        $query = "UPDATE siswa SET nama='$nama', login_code='$login_code', kelas_id=$kelas_id WHERE id=$id";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Data siswa berhasil diperbarui.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal memperbarui data: " . $conn->error . "</p>
                  </div>";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $query = "DELETE FROM siswa WHERE id=$id";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Data siswa berhasil dihapus.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal menghapus data: " . $conn->error . "</p>
                  </div>";
        }
    }
}

// Fetch Classes for Dropdown
// Fetch Classes for Dropdown
if ($_SESSION['role'] == 'guru') {
    $user_id = $_SESSION['user_id'];
    $kelas_result = $conn->query("SELECT k.* FROM kelas k 
                                  JOIN guru_kelas gk ON k.id = gk.kelas_id 
                                  WHERE gk.user_id = $user_id 
                                  ORDER BY k.nama_kelas");
} else {
    $kelas_result = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas");
}
$kelas_options = [];
while ($row = $kelas_result->fetch_assoc()) {
    $kelas_options[] = $row;
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

$total_result = $conn->query("SELECT COUNT(*) as total FROM siswa s $where");
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$query = "SELECT s.*, k.nama_kelas 
          FROM siswa s 
          LEFT JOIN kelas k ON s.kelas_id = k.id 
          $where 
          ORDER BY s.nama ASC 
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
?>

<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <button onclick="openModal('add')" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i> Tambah Siswa
            </button>
            <a href="admin.php?section=siswa_import" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-file-import mr-2"></i> Import Data
            </a>
        </div>
        
        <form method="GET" class="flex w-full md:w-auto no-ajax">
            <input type="hidden" name="section" value="siswa_management">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Login</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result->num_rows > 0): ?>
                    <?php $no = $offset + 1; while ($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['login_code']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-teal-100 text-teal-800">
                                <?php echo $row['nama_kelas'] ? htmlspecialchars($row['nama_kelas']) : 'Belum ada kelas'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick='openModal("edit", <?php echo json_encode($row); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i></button>
                            <button onclick='confirmDelete(<?php echo $row["id"]; ?>, "<?php echo htmlspecialchars($row["nama"]); ?>")' class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data siswa ditemukan.</td>
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
                <a href="?section=siswa_management&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                   class="nav-link relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i == $page ? 'text-teal-600 bg-teal-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="studentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modalTitle">Tambah Siswa</h3>
            <form id="studentForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="studentId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="nama">Nama Siswa</label>
                    <input type="text" name="nama" id="nama" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="login_code">Kode Login</label>
                    <input type="text" name="login_code" id="login_code" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="kelas_id">Kelas</label>
                    <select name="kelas_id" id="kelas_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Pilih Kelas</option>
                        <?php foreach ($kelas_options as $kelas): ?>
                            <option value="<?php echo $kelas['id']; ?>"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></option>
                        <?php endforeach; ?>
                    </select>
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
    function openModal(mode, data = null) {
        const modal = document.getElementById('studentModal');
        const title = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const studentId = document.getElementById('studentId');
        const nama = document.getElementById('nama');
        const loginCode = document.getElementById('login_code');
        const kelasId = document.getElementById('kelas_id');
        
        modal.classList.remove('hidden');
        
        if (mode === 'edit' && data) {
            title.textContent = 'Edit Siswa';
            formAction.value = 'edit';
            studentId.value = data.id;
            nama.value = data.nama;
            loginCode.value = data.login_code;
            kelasId.value = data.kelas_id;
        } else {
            title.textContent = 'Tambah Siswa';
            formAction.value = 'add';
            studentId.value = '';
            nama.value = '';
            loginCode.value = '';
            kelasId.value = '';
        }
    }

    function closeModal() {
        document.getElementById('studentModal').classList.add('hidden');
    }

    function confirmDelete(id, name) {
        if (confirm('Apakah Anda yakin ingin menghapus siswa ' + name + '?')) {
            document.getElementById('deleteId').value = id;
            // Manually submit the delete form since it's outside the main flow
            const form = document.getElementById('deleteForm');
            const formData = new FormData(form);
            
            // We need to trigger the same fetch logic as the main admin.php script
            // Or just submit it and let the global listener handle it?
            // The global listener handles 'submit' events on body.
            // So dispatching a submit event on the form should work.
            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('studentModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
