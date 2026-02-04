<?php
require_once 'db_connect.php';

// Create settings table
$sql = "CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` VARCHAR(255) NOT NULL,
    `description` TEXT
)";

if ($conn->query($sql) === TRUE) {
    echo "Table settings created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Insert default values
$defaults = [
    'default_spp_harian' => ['2000', 'Nominal default untuk pembayaran SPP Harian (Ikhsan)'],
    'default_spp_mingguan' => ['2000', 'Nominal default untuk pembayaran SPP Mingguan (Infaq)'],
    'target_ikhsan_tahunan' => ['146000', 'Target tahunan Ikhsan per siswa'],
    'target_infaq_tahunan' => ['0', 'Target tahunan Infaq per siswa (0 jika sukarela)']
];

foreach ($defaults as $key => $data) {
    $val = $data[0];
    $desc = $data[1];
    $sql = "INSERT IGNORE INTO settings (`key`, `value`, `description`) VALUES ('$key', '$val', '$desc')";
    if ($conn->query($sql) === TRUE) {
        echo "Inserted $key\n";
    } else {
        echo "Error inserting $key: " . $conn->error . "\n";
    }
}

$conn->close();
?>
