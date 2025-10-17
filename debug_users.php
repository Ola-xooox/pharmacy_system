<?php
// Debug script to check users table
require 'db_connect.php';

echo "<h2>Users Table Debug</h2>";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ Users table doesn't exist!</p>";
    exit;
}

// Check table structure
echo "<h3>Table Structure:</h3>";
$result = $conn->query("DESCRIBE users");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check all users
echo "<h3>All Users:</h3>";
$result = $conn->query("SELECT id, username, email, role FROM users");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . ($row['email'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No users found in database!</p>";
}

// Check specifically for admin user
echo "<h3>Admin User Details:</h3>";
$stmt = $conn->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<p><strong>Username:</strong> " . $admin['username'] . "</p>";
    echo "<p><strong>Email:</strong> " . ($admin['email'] ?? 'NULL') . "</p>";
    echo "<p><strong>Role:</strong> " . $admin['role'] . "</p>";
    echo "<p><strong>Password Hash:</strong> " . substr($admin['password'], 0, 20) . "...</p>";
    
    // Test password verification
    $testPassword = 'admin24';
    if (password_verify($testPassword, $admin['password'])) {
        echo "<p style='color: green;'>✅ Password 'admin24' is correct</p>";
    } else {
        echo "<p style='color: red;'>❌ Password 'admin24' does not match</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Admin user not found!</p>";
}

$conn->close();
?>
