<?php
session_start();
require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];

    if ($type == 'admin') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, password, role, name FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                header("Location: admin.php");
                exit;
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
    } else if ($type == 'siswa') {
        $login_code = $_POST['login_code'];

        $stmt = $conn->prepare("SELECT id, nama, kelas_id FROM siswa WHERE login_code = ?");
        $stmt->bind_param("s", $login_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $siswa = $result->fetch_assoc();
            $_SESSION['siswa_id'] = $siswa['id'];
            $_SESSION['nama'] = $siswa['nama'];
            $_SESSION['role'] = 'siswa';
            header("Location: index.php");
            exit;
        } else {
            $error = "Kode Login tidak valid!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TPQ Aisyiyah Wuled</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3 { font-family: 'Merriweather', serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-teal-600 to-teal-800 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md transition-all duration-300 transform hover:scale-[1.01]">
        <div class="text-center mb-8">
            <div class="inline-block p-3 rounded-full bg-teal-50 mb-4">
                <i class="fas fa-graduation-cap text-4xl text-teal-700"></i>
            </div>
            <h1 class="text-3xl font-bold text-teal-900 mb-2">Sistem Pembayaran TPQ</h1>
            <p class="text-gray-500">Masuk sebagai Admin atau Siswa</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Error</p>
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <div class="flex mb-8 bg-gray-100 p-1 rounded-lg">
            <button id="btn-admin" class="w-1/2 py-2 text-center font-semibold rounded-md transition-all duration-200 bg-white text-teal-700 shadow-sm">Admin/Guru</button>
            <button id="btn-siswa" class="w-1/2 py-2 text-center font-semibold rounded-md text-gray-500 hover:text-gray-700 transition-all duration-200">Siswa/Wali</button>
        </div>

        <form id="form-admin" method="POST" class="space-y-5">
            <input type="hidden" name="type" value="admin">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition duration-200" placeholder="Masukkan username" required autocomplete="username">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition duration-200" placeholder="Masukkan password" required autocomplete="current-password">
            </div>
            <button type="submit" class="w-full bg-teal-700 text-white font-bold py-3 rounded-lg hover:bg-teal-800 transition duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">Masuk</button>
        </form>

        <form id="form-siswa" method="POST" class="space-y-5 hidden">
            <input type="hidden" name="type" value="siswa">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Kode Login</label>
                <input type="text" name="login_code" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition duration-200" placeholder="Masukkan Kode Unik Siswa" required autocomplete="username">
            </div>
            <button type="submit" class="w-full bg-teal-700 text-white font-bold py-3 rounded-lg hover:bg-teal-800 transition duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">Masuk</button>
        </form>
    </div>

    <script>
        const btnAdmin = document.getElementById('btn-admin');
        const btnSiswa = document.getElementById('btn-siswa');
        const formAdmin = document.getElementById('form-admin');
        const formSiswa = document.getElementById('form-siswa');

        function switchTab(tab) {
            if (tab === 'admin') {
                formAdmin.classList.remove('hidden');
                formSiswa.classList.add('hidden');
                
                btnAdmin.classList.add('bg-white', 'text-teal-700', 'shadow-sm');
                btnAdmin.classList.remove('text-gray-500');
                
                btnSiswa.classList.remove('bg-white', 'text-teal-700', 'shadow-sm');
                btnSiswa.classList.add('text-gray-500');
            } else {
                formSiswa.classList.remove('hidden');
                formAdmin.classList.add('hidden');
                
                btnSiswa.classList.add('bg-white', 'text-teal-700', 'shadow-sm');
                btnSiswa.classList.remove('text-gray-500');
                
                btnAdmin.classList.remove('bg-white', 'text-teal-700', 'shadow-sm');
                btnAdmin.classList.add('text-gray-500');
            }
        }

        btnAdmin.addEventListener('click', () => switchTab('admin'));
        btnSiswa.addEventListener('click', () => switchTab('siswa'));
    </script>
</body>
</html>
