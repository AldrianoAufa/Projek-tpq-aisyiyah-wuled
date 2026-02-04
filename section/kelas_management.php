<?php
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // --- CLASS ACTIONS ---
    if ($action === 'add_kelas') {
        $nama_kelas = $conn->real_escape_string($_POST['nama_kelas']);
        $guru_ids = isset($_POST['guru_ids']) ? $_POST['guru_ids'] : [];
        
        $conn->begin_transaction();
        try {
            $query = "INSERT INTO kelas (nama_kelas) VALUES ('$nama_kelas')";
            if ($conn->query($query)) {
                $kelas_id = $conn->insert_id;
                if (!empty($guru_ids)) {
                    foreach ($guru_ids as $gid) {
                        $gid = (int)$gid;
                        if ($gid > 0) {
                            // Cek apakah guru sudah mengajar di kelas lain
                            $check = $conn->query("SELECT k.nama_kelas FROM guru_kelas gk 
                                                JOIN kelas k ON gk.kelas_id = k.id 
                                                WHERE gk.user_id = $gid AND gk.kelas_id != $kelas_id");
                            
                            if ($check->num_rows > 0) {
                                $existing_class = $check->fetch_assoc();
                                throw new Exception("Guru ini sudah mengajar di kelas " . $existing_class['nama_kelas']);
                            }
                            
                            $conn->query("REPLACE INTO guru_kelas (kelas_id, user_id) VALUES ($kelas_id, $gid)");
                        }
                    }
                }
                $conn->commit();
                echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                        <p class='font-bold'>Sukses!</p>
                        <p>Kelas berhasil ditambahkan.</p>
                      </div>";
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal menambahkan kelas: " . $e->getMessage() . "</p>
                  </div>";
        }
    } elseif ($action === 'edit_kelas') {
        $id = (int)$_POST['id'];
        $nama_kelas = $conn->real_escape_string($_POST['nama_kelas']);
        $guru_ids = isset($_POST['guru_ids']) ? $_POST['guru_ids'] : [];
        
        $conn->begin_transaction();
        try {
            $query = "UPDATE kelas SET nama_kelas='$nama_kelas' WHERE id=$id";
            if ($conn->query($query)) {
                $conn->query("DELETE FROM guru_kelas WHERE kelas_id=$id");
                if (!empty($guru_ids)) {
                    foreach ($guru_ids as $gid) {
                        $gid = (int)$gid;
                        if ($gid > 0) {
                            // Cek apakah guru sudah mengajar di kelas lain
                            $check = $conn->query("SELECT k.nama_kelas FROM guru_kelas gk 
                                                JOIN kelas k ON gk.kelas_id = k.id 
                                                WHERE gk.user_id = $gid AND gk.kelas_id != $id");
                            
                            if ($check->num_rows > 0) {
                                $existing_class = $check->fetch_assoc();
                                throw new Exception("Guru ini sudah mengajar di kelas " . $existing_class['nama_kelas']);
                            }
                            
                            $conn->query("REPLACE INTO guru_kelas (kelas_id, user_id) VALUES ($id, $gid)");
                        }
                    }
                }
                $conn->commit();
                echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                        <p class='font-bold'>Sukses!</p>
                        <p>Data kelas berhasil diperbarui.</p>
                      </div>";
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal memperbarui data: " . $e->getMessage() . "</p>
                  </div>";
        }
    } elseif ($action === 'delete_kelas') {
        $id = (int)$_POST['id'];
        $query = "DELETE FROM kelas WHERE id=$id";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Kelas berhasil dihapus.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal menghapus kelas: " . $conn->error . "</p>
                  </div>";
        }
    }
    
    // --- TEACHER ACTIONS ---
    elseif ($action === 'add_guru') {
        $name = $conn->real_escape_string($_POST['name']);
        $username = $conn->real_escape_string($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (name, username, password, role) VALUES ('$name', '$username', '$password', 'guru')";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Guru berhasil ditambahkan.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal menambahkan guru: " . $conn->error . "</p>
                  </div>";
        }
    } elseif ($action === 'edit_guru') {
        $id = (int)$_POST['id'];
        $name = $conn->real_escape_string($_POST['name']);
        $username = $conn->real_escape_string($_POST['username']);
        
        $sql = "UPDATE users SET name='$name', username='$username'";
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql .= ", password='$password'";
        }
        $sql .= " WHERE id=$id AND role='guru'";
        
        if ($conn->query($sql)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Data guru berhasil diperbarui.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal memperbarui data: " . $conn->error . "</p>
                  </div>";
        }
    } elseif ($action === 'delete_guru') {
        $id = (int)$_POST['id'];
        $query = "DELETE FROM users WHERE id=$id AND role='guru'";
        if ($conn->query($query)) {
            echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Sukses!</p>
                    <p>Guru berhasil dihapus.</p>
                  </div>";
        } else {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>
                    <p class='font-bold'>Error!</p>
                    <p>Gagal menghapus guru: " . $conn->error . "</p>
                  </div>";
        }
    }
}

// Fetch Teachers
$guru_result = $conn->query("SELECT id, name, username FROM users WHERE role = 'guru' ORDER BY name");
$guru_list = [];
while ($row = $guru_result->fetch_assoc()) {
    $guru_list[] = $row;
}

// Fetch Classes with Teachers (GROUP_CONCAT for multiple teachers)
$query = "SELECT k.*, GROUP_CONCAT(u.name SEPARATOR ', ') as nama_guru, GROUP_CONCAT(u.id) as guru_ids 
          FROM kelas k 
          LEFT JOIN guru_kelas gk ON k.id = gk.kelas_id 
          LEFT JOIN users u ON gk.user_id = u.id 
          GROUP BY k.id
          ORDER BY k.nama_kelas";
$kelas_result = $conn->query($query);
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Data Kelas Section -->
    <div class="bg-white rounded-xl shadow-md p-6 h-fit">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800 font-serif">Daftar Kelas</h3>
            <button onclick="openKelasModal('add')" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors flex items-center shadow-sm">
                <i class="fas fa-plus mr-2"></i> Tambah Kelas
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kelas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wali Kelas</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($kelas_result->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $kelas_result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"><?php echo htmlspecialchars($row['nama_kelas']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php if ($row['nama_guru']): ?>
                                    <?php 
                                    $gurus = explode(', ', $row['nama_guru']);
                                    foreach($gurus as $g): 
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mb-1">
                                        <i class="fas fa-user-tie mr-1"></i>
                                        <?php echo htmlspecialchars($g); ?>
                                    </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-gray-400 italic text-xs">Belum ada wali kelas</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick='openKelasModal("edit", <?php echo json_encode($row); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3 bg-indigo-50 p-2 rounded-full hover:bg-indigo-100 transition-colors"><i class="fas fa-edit"></i></button>
                                <button onclick='confirmDelete("kelas", <?php echo $row["id"]; ?>, "<?php echo htmlspecialchars($row["nama_kelas"]); ?>")' class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-full hover:bg-red-100 transition-colors"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada data kelas ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Data Guru Section -->
    <div class="bg-white rounded-xl shadow-md p-6 h-fit">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800 font-serif">Daftar Guru</h3>
            <button onclick="openGuruModal('add')" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors flex items-center shadow-sm">
                <i class="fas fa-user-plus mr-2"></i> Tambah Guru
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($guru_list) > 0): ?>
                        <?php $no = 1; foreach ($guru_list as $guru): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"><?php echo htmlspecialchars($guru['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($guru['username']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick='openGuruModal("edit", <?php echo json_encode($guru); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3 bg-indigo-50 p-2 rounded-full hover:bg-indigo-100 transition-colors"><i class="fas fa-edit"></i></button>
                                <button onclick='confirmDelete("guru", <?php echo $guru["id"]; ?>, "<?php echo htmlspecialchars($guru["name"]); ?>")' class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-full hover:bg-red-100 transition-colors"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada data guru ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Class Modal -->
<div id="classModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="classModalTitle">Tambah Kelas</h3>
            <form id="classForm" method="POST">
                <input type="hidden" name="action" id="classFormAction" value="add_kelas">
                <input type="hidden" name="id" id="classId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="nama_kelas">Nama Kelas</label>
                    <input type="text" name="nama_kelas" id="nama_kelas" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Wali Kelas</label>
                    <div class="max-h-48 overflow-y-auto border rounded p-2 bg-gray-50">
                        <?php foreach ($guru_list as $guru): ?>
                            <div class="flex items-center mb-2">
                                <input type="checkbox" name="guru_ids[]" value="<?php echo $guru['id']; ?>" id="guru_<?php echo $guru['id']; ?>" class="guru-checkbox mr-2 leading-tight h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                                <label for="guru_<?php echo $guru['id']; ?>" class="text-sm text-gray-700 cursor-pointer select-none"><?php echo htmlspecialchars($guru['name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Bisa pilih lebih dari satu guru.</p>
                </div>
                
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeModal('classModal')" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
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

<!-- Guru Modal -->
<div id="guruModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="guruModalTitle">Tambah Guru</h3>
            <form id="guruForm" method="POST">
                <input type="hidden" name="action" id="guruFormAction" value="add_guru">
                <input type="hidden" name="id" id="guruId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Nama Lengkap</label>
                    <input type="text" name="name" id="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                    <input type="text" name="username" id="username" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" autocomplete="username">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                    <input type="password" name="password" id="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" autocomplete="new-password">
                    <p class="text-xs text-gray-500 mt-1" id="passwordHint">Kosongkan jika tidak ingin mengubah password.</p>
                </div>
                
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeModal('guruModal')" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
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
    <input type="hidden" name="action" id="deleteAction" value="">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
    function openKelasModal(mode, data = null) {
        const modal = document.getElementById('classModal');
        const title = document.getElementById('classModalTitle');
        const formAction = document.getElementById('classFormAction');
        const classId = document.getElementById('classId');
        const namaKelas = document.getElementById('nama_kelas');
        
        // Reset checkboxes
        document.querySelectorAll('.guru-checkbox').forEach(cb => cb.checked = false);
        
        modal.classList.remove('hidden');
        
        if (mode === 'edit' && data) {
            title.textContent = 'Edit Kelas';
            formAction.value = 'edit_kelas';
            classId.value = data.id;
            namaKelas.value = data.nama_kelas;
            
            if (data.guru_ids) {
                const ids = data.guru_ids.split(',');
                ids.forEach(id => {
                    const checkbox = document.getElementById('guru_' + id);
                    if (checkbox) checkbox.checked = true;
                });
            }
        } else {
            title.textContent = 'Tambah Kelas';
            formAction.value = 'add_kelas';
            classId.value = '';
            namaKelas.value = '';
        }
    }

    function openGuruModal(mode, data = null) {
        const modal = document.getElementById('guruModal');
        const title = document.getElementById('guruModalTitle');
        const formAction = document.getElementById('guruFormAction');
        const guruId = document.getElementById('guruId');
        const name = document.getElementById('name');
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const passwordHint = document.getElementById('passwordHint');
        
        modal.classList.remove('hidden');
        
        if (mode === 'edit' && data) {
            title.textContent = 'Edit Guru';
            formAction.value = 'edit_guru';
            guruId.value = data.id;
            name.value = data.name;
            username.value = data.username;
            password.required = false;
            passwordHint.classList.remove('hidden');
        } else {
            title.textContent = 'Tambah Guru';
            formAction.value = 'add_guru';
            guruId.value = '';
            name.value = '';
            username.value = '';
            password.required = true;
            passwordHint.classList.add('hidden');
        }
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function confirmDelete(type, id, name) {
        const itemType = type === 'kelas' ? 'kelas' : 'guru';
        if (confirm('Apakah Anda yakin ingin menghapus ' + itemType + ' ' + name + '?')) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteAction').value = 'delete_' + type;
            const form = document.getElementById('deleteForm');
            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }
    }
    
    window.onclick = function(event) {
        const classModal = document.getElementById('classModal');
        const guruModal = document.getElementById('guruModal');
        if (event.target == classModal) {
            closeModal('classModal');
        }
        if (event.target == guruModal) {
            closeModal('guruModal');
        }
    }
</script>
