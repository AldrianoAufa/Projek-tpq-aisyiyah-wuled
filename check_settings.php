<?php
require_once 'db_connect.php';

$result = $conn->query("SELECT * FROM settings");
if ($result) {
    echo "<h2>Daftar Pengaturan</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Key</th><th>Value</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['value']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
