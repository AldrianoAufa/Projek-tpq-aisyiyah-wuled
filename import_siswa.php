<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];
    $handle = fopen($file, "r");
    
    if ($handle) {
        $success = 0;
        $failed = 0;
        
        // Skip header if exists (optional, assuming no header or handled)
        // fgetcsv($handle); 
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Format: nama, login_code, kelas_id
            if (count($data) >= 3) {
                $nama = $conn->real_escape_string($data[0]);
                $login_code = $conn->real_escape_string($data[1]);
                $kelas_id = (int)$data[2];
                
                $sql = "INSERT INTO siswa (nama, login_code, kelas_id) VALUES ('$nama', '$login_code', '$kelas_id')";
                if ($conn->query($sql)) {
                    $success++;
                } else {
                    $failed++;
                }
            }
        }
        fclose($handle);
        
        $_SESSION['message'] = "Import selesai. Sukses: $success, Gagal: $failed";
    } else {
        $_SESSION['message'] = "Gagal membuka file.";
    }
}

header("Location: admin.php?section=siswa_management");
exit;
?>
