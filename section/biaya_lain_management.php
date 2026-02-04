<?php
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama = $conn->real_escape_string($_POST['nama']);
        $jumlah = (float)$_POST['jumlah'];
        
        $query = "INSERT INTO biaya_lain_master (nama, jumlah) VALUES ('$nama', $jumlah)";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Biaya lain berhasil ditambahkan.</p>
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
        $jumlah = (float)$_POST['jumlah'];
        
        $query = "UPDATE biaya_lain_master SET nama='$nama', jumlah=$jumlah WHERE id=$id";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Data berhasil diperbarui.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal memperbarui data: " . $conn->error . "</p>
                  </div>";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $query = "DELETE FROM biaya_lain_master WHERE id=$id";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Data berhasil dihapus.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal menghapus data: " . $conn->error . "</p>
                  </div>";
        }
    }
}

// Fetch Data
$result = $conn->query("SELECT * FROM biaya_lain_master ORDER BY nama");
?>

<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <button onclick="openModal('add')" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors flex items-center">
            <i class="fas fa-plus mr-2"></i> Tambah Biaya
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Biaya</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result->num_rows > 0): ?>
                    <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick='openModal("edit", <?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i></button>
                            <button onclick='confirmDelete(<?php echo $row["id"]; ?>, "<?php echo htmlspecialchars($row["nama"]); ?>")' class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada data biaya lain ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="biayaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modalTitle">Tambah Biaya Lain</h3>
            <form id="biayaForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="biayaId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="nama">Nama Biaya</label>
                    <input type="text" name="nama" id="nama" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
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
    function openModal(mode, data = null) {
        const modal = document.getElementById('biayaModal');
        const title = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const biayaId = document.getElementById('biayaId');
        const nama = document.getElementById('nama');
        const jumlah = document.getElementById('jumlah');
        
        modal.classList.remove('hidden');
        
        if (mode === 'edit' && data) {
            title.textContent = 'Edit Biaya Lain';
            formAction.value = 'edit';
            biayaId.value = data.id;
            nama.value = data.nama;
            jumlah.value = data.jumlah;
        } else {
            title.textContent = 'Tambah Biaya Lain';
            formAction.value = 'add';
            biayaId.value = '';
            nama.value = '';
            jumlah.value = '';
        }
    }

    function closeModal() {
        document.getElementById('biayaModal').classList.add('hidden');
    }

    function confirmDelete(id, name) {
        if (confirm('Apakah Anda yakin ingin menghapus biaya ' + name + '?')) {
            document.getElementById('deleteId').value = id;
            const form = document.getElementById('deleteForm');
            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }
    }
    
    window.onclick = function(event) {
        const modal = document.getElementById('biayaModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
