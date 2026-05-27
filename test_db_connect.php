<?php
// Quick DB connection test for megacity_taxi_association
include 'db.php';

if (!isset($conn) || !$conn) {
    echo "No \$conn handle available. Check db.php\n";
    exit(1);
}

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . "\n";
    exit(1);
}

$result = $conn->query("SELECT 1 as ok");
if ($result && $row = $result->fetch_assoc()) {
    echo "DB connected successfully. Test query returned: " . $row['ok'] . "\n";
    echo "MySQL server info: " . $conn->server_info . "\n";
    exit(0);
} else {
    echo "Test query failed: " . $conn->error . "\n";
    exit(1);
}
