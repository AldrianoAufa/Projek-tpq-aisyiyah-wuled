<?php
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated_count = 0;
    foreach ($_POST['settings'] as $key => $value) {
        $key = $conn->real_escape_string($key);
        $value = $conn->real_escape_string($value);
        if ($conn->query("UPDATE settings SET value='$value' WHERE `key`='$key'")) {
            $updated_count++;
        }
    }
    
    if ($updated_count > 0) {
        echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'>
                <p class='font-bold'>Sukses!</p>
                <p>Pengaturan berhasil diperbarui.</p>
              </div>";
    }
}

// Fetch Settings
$settings = [];
$res = $conn->query("SELECT * FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['key']] = $row;
}
?>

<div class="bg-white rounded-xl shadow-md p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6 font-serif border-b pb-2">Pengaturan Sistem</h2>
    
    <form method="POST" class="space-y-6">
        <input type="hidden" name="section" value="settings">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- SPP Harian Settings -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-bold text-teal-800 mb-4 flex items-center">
                    <i class="fas fa-calendar-day mr-2"></i> SPP Harian (Ikhsan)
                </h3>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Default Nominal (Rp)</label>
                    <input type="number" name="settings[default_spp_harian]" value="<?php echo htmlspecialchars($settings['default_spp_harian']['value'] ?? '2000'); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings['default_spp_harian']['description'] ?? ''); ?></p>
                </div>
            </div>

            <!-- SPP Mingguan Settings -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-bold text-teal-800 mb-4 flex items-center">
                    <i class="fas fa-calendar-week mr-2"></i> SPP Mingguan (Infaq)
                </h3>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Default Nominal (Rp)</label>
                    <input type="number" name="settings[default_spp_mingguan]" value="<?php echo htmlspecialchars($settings['default_spp_mingguan']['value'] ?? '2000'); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings['default_spp_mingguan']['description'] ?? ''); ?></p>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-6">
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors flex items-center">
                <i class="fas fa-save mr-2"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>
