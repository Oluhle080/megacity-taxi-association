<?php
// Seeder: create an admin user if it doesn't exist
include 'db.php';

$username = 'admin';
$fullname = 'Administrator';
$password = 'admin123';
$role = 'admin';

// Check if user exists
$check = $conn->prepare("SELECT UserID FROM users WHERE Username = ?");
$check->bind_param('s', $username);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo "User 'admin' already exists.\n";
    exit(0);
}
$check->close();

// Insert user with hashed password
$hash = password_hash($password, PASSWORD_DEFAULT);
$insert = $conn->prepare("INSERT INTO users (FullName, Username, Password, Role) VALUES (?, ?, ?, ?)");
$insert->bind_param('ssss', $fullname, $username, $hash, $role);
if ($insert->execute()) {
    echo "Admin user created. Username: admin, Password: admin123\n";
} else {
    echo "Failed to create admin: " . $conn->error . "\n";
}
$insert->close();
