<?php
require_once 'db_connect.php';

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
} else {
    echo "Database connection successful.";

    // Test a simple query
    $result = $conn->query("SELECT 1 as test");
    if ($result) {
        $row = $result->fetch_assoc();
        echo " Query test: " . $row['test'];
    } else {
        echo " Query failed.";
    }
}

$conn->close();
