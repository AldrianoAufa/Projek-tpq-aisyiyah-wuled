<?php
// Start output buffering
while (ob_get_level()) ob_end_clean();
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../db_connect.php';

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Function to send JSON response
function sendResponse($success, $message = '', $data = []) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Function to safely redirect
function safe_redirect($url) {
    if (headers_sent()) {
        echo "<script>window.location.href='$url';</script>";
    } else {
        header("Location: $url");
    }
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['csv', 'xls', 'xlsx'];
    
    // Validate file type
    if (!in_array($file_ext, $allowed_ext)) {
        $error = "Format file tidak didukung. Harap unggah file CSV atau Excel.";
        if ($is_ajax) sendResponse(false, $error);
        $_SESSION['error'] = $error;
        safe_redirect("admin.php?section=siswa_import");
    }
    
    // Handle Excel files
    if (in_array($file_ext, ['xls', 'xlsx'])) {
        require_once '../vendor/autoload.php';
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $rows = $spreadsheet->getActiveSheet()->toArray();
        } catch (Exception $e) {
            $error = "Gagal memproses file Excel: " . $e->getMessage();
            if ($is_ajax) sendResponse(false, $error);
            $_SESSION['error'] = $error;
            safe_redirect("admin.php?section=siswa_import");
        }
    } else {
        // Handle CSV
        $handle = fopen($file['tmp_name'], 'r');
        $rows = [];
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rows[] = $data;
        }
        fclose($handle);
    }
    
    // Remove header if exists
    $header = array_shift($rows);
    $success = 0;
    $errors = [];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        foreach ($rows as $index => $row) {
            if (empty(array_filter($row))) continue;
            
            // Validate required fields
            if (count($row) < 2) {
                $errors[] = "Baris " . ($index + 2) . ": Format data tidak valid";
                continue;
            }
            
            $nama = $conn->real_escape_string(trim($row[0]));
            $login_code = $conn->real_escape_string(trim($row[1]));
            $kelas_id = isset($row[2]) ? trim($row[2]) : null;
            
            // Validate data
            if (empty($nama) || empty($login_code)) {
                $errors[] = "Baris " . ($index + 2) . ": Nama dan Kode Login tidak boleh kosong";
                continue;
            }
            
            // Validate login_code format
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $login_code)) {
                $errors[] = "Baris " . ($index + 2) . ": Kode Login hanya boleh berisi huruf, angka, dan underscore (_)";
                continue;
            }
            
            // Check if login_code exists
            $check = $conn->query("SELECT id FROM siswa WHERE login_code = '$login_code'");
            if ($check->num_rows > 0) {
                $errors[] = "Baris " . ($index + 2) . ": Kode Login '$login_code' sudah digunakan";
                continue;
            }
            
            // Validate kelas_id if provided
            if (!empty($kelas_id)) {
                $kelas_id = (int)$kelas_id;
                $check_kelas = $conn->query("SELECT id FROM kelas WHERE id = $kelas_id");
                if ($check_kelas->num_rows === 0) {
                    $errors[] = "Baris " . ($index + 2) . ": Kelas dengan ID $kelas_id tidak ditemukan";
                    continue;
                }
                $kelas_sql = $kelas_id;
            } else {
                $kelas_sql = 'NULL';
            }
            
            // Insert data
            $sql = "INSERT INTO siswa (nama, login_code, kelas_id) 
                    VALUES ('$nama', '$login_code', $kelas_sql)";
            
            if ($conn->query($sql)) {
                $success++;
            } else {
                $errors[] = "Baris " . ($index + 2) . ": Gagal menyimpan data - " . $conn->error;
            }
        }
        
        $conn->commit();
        $message = "Import selesai. Sukses: $success" . (!empty($errors) ? " (dengan beberapa kesalahan)" : "");
        
        if ($is_ajax) {
            sendResponse(true, $message, [
                'success_count' => $success,
                'error_count' => count($errors),
                'errors' => $errors
            ]);
        } else {
            $_SESSION['message'] = $message;
            if (!empty($errors)) {
                $_SESSION['import_errors'] = array_slice($errors, 0, 10); // Limit to 10 errors
            }
            safe_redirect("admin.php?section=siswa_import");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Terjadi kesalahan saat mengimpor data: " . $e->getMessage();
        if ($is_ajax) sendResponse(false, $error);
        $_SESSION['error'] = $error;
        safe_redirect("admin.php?section=siswa_import");
    }
}

// Only output HTML if not an AJAX request
if (!$is_ajax):
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data Siswa - TPQ Aisyiyah Wuled</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d9488;
            --primary-hover: #0f766e;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-700: #374151;
            --white: #ffffff;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 64rem;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            background: var(--white);
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }
        .card-body {
            padding: 1.5rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        .btn-outline {
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
            background: var(--white);
        }
        .btn-outline:hover {
            background-color: var(--gray-100);
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }
        .form-control {
            display: block;
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2);
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.375rem;
            border-left: 4px solid;
        }
        .alert-success {
            background-color: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        .alert-danger {
            background-color: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }
        .alert-warning {
            background-color: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem 0; display: flex; align-items: center;">
                            <i class="fas fa-file-import" style="color: #0d9488; margin-right: 0.5rem;"></i>
                            Import Data Siswa
                        </h1>
                        <p style="margin: 0; color: #6b7280;">Unggah file Excel/CSV untuk menambahkan data siswa</p>
                    </div>
                    <a href="admin.php?section=siswa_management" class="btn btn-outline">
                        <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Kembali
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success">
                        <p style="margin: 0; font-weight: 500;"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <p style="margin: 0; font-weight: 500;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($_SESSION['import_errors'])): ?>
                    <div class="alert alert-warning">
                        <h3 style="margin-top: 0; margin-bottom: 0.5rem; font-weight: 600;">Beberapa data gagal diimport</h3>
                        <ul style="margin: 0; padding-left: 1.25rem;">
                            <?php 
                            foreach ($_SESSION['import_errors'] as $error) {
                                echo "<li style=\"margin-bottom: 0.25rem;\">$error</li>";
                            }
                            unset($_SESSION['import_errors']);
                            ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div style="background-color: #f0fdfa; border-left: 4px solid #0d9488; padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.375rem;">
                    <h3 style="margin-top: 0; margin-bottom: 0.5rem; font-weight: 600; color: #0f766e;">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>Petunjuk Import Data
                    </h3>
                    <ol style="margin: 0; padding-left: 1.25rem;">
                        <li>Unduh template <a href="#" id="downloadTemplate" style="color: #0d9488; text-decoration: underline;">disini</a></li>
                        <li>Isi data sesuai dengan format yang telah disediakan</li>
                        <li>Unggah file yang sudah diisi</li>
                        <li>Klik tombol "Mulai Import"</li>
                    </ol>
                </div>

                <form action="admin.php?section=siswa_import" method="post" enctype="multipart/form-data" id="importForm">
                    <div class="form-group">
                        <label for="file" class="form-label">Pilih File Excel/CSV</label>
                        <div style="position: relative;">
                            <input type="file" 
                                   name="file" 
                                   id="file" 
                                   accept=".csv, .xlsx, .xls" 
                                   class="form-control" 
                                   style="padding: 0.5rem;"
                                   required>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #6b7280;">
                                Format yang didukung: .xlsx, .xls, .csv (Maks. 5MB)
                            </p>
                        </div>
                    </div>

                    <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" id="downloadTemplateBtn" class="btn btn-outline">
                            <i class="fas fa-file-excel" style="margin-right: 0.5rem;"></i> Unduh Template
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-upload" style="margin-right: 0.5rem;"></i> Mulai Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const downloadBtn = document.getElementById('downloadTemplateBtn');
        const downloadLink = document.getElementById('downloadTemplate');
        const fileInput = document.getElementById('file');
        const form = document.getElementById('importForm');
        const submitBtn = document.getElementById('submitBtn');

        // Function to download template
        function downloadTemplate(e) {
            if (e) e.preventDefault();
            
            // Sample data for template
            const data = [
                ['Nama', 'Login_Code', 'Kelas_ID'],
                ['Ahmad Fauzi', 'AF001', '1'],
                ['Siti Aminah', 'SA002', '2'],
                ['Budi Santoso', 'BS003', '1']
            ];
            
            // Convert to CSV with BOM for Excel
            let csvContent = "\uFEFF" + data.map(row => 
                row.map(field => `"${field}"`).join(',')
            ).join('\r\n');
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'template_import_siswa.csv');
            link.style.display = 'none';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        // Add event listeners
        if (downloadBtn) downloadBtn.addEventListener('click', downloadTemplate);
        if (downloadLink) downloadLink.addEventListener('click', downloadTemplate);

        // Show file name when selected
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const fileName = this.files[0]?.name || 'Pilih file';
                const label = this.nextElementSibling;
                if (label) {
                    label.textContent = `File terpilih: ${fileName}`;
                }
            });
        }

        // Form submission handling
        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                const file = fileInput?.files[0];
                if (!file) {
                    e.preventDefault();
                    alert('Silakan pilih file terlebih dahulu');
                    return false;
                }

                // Validate file size (5MB max)
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('Ukuran file melebihi batas maksimal 5MB');
                    return false;
                }

                // Show loading state
                const originalHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i> Mengimpor...';

                // Re-enable button if form submission takes too long
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                        alert('Proses import membutuhkan waktu lebih lama dari biasanya. Silakan coba lagi.');
                    }
                }, 30000); // 30 seconds timeout
            });
        }
    });
    </script>
</body>
</html>
<?php 
endif; 
// Clean output buffer
if (ob_get_level() > 0) ob_end_flush();
?>