<?php
// Check which users have email addresses for OTP login
require 'db_connect.php';

echo "<h2>👥 User Email Status for OTP Login</h2>";

$stmt = $conn->prepare("SELECT id, username, email, role FROM users ORDER BY username ASC");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($users)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 10px;'>Username</th>";
    echo "<th style='padding: 10px;'>Email</th>";
    echo "<th style='padding: 10px;'>Role</th>";
    echo "<th style='padding: 10px;'>OTP Status</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        $hasEmail = !empty($user['email']);
        $statusColor = $hasEmail ? '#d4edda' : '#f8d7da';
        $statusText = $hasEmail ? '✅ Can use OTP' : '❌ No email - Cannot use OTP';
        
        echo "<tr style='background-color: $statusColor;'>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td style='padding: 10px;'>" . ($hasEmail ? htmlspecialchars($user['email']) : '<em>No email</em>') . "</td>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td style='padding: 10px;'>$statusText</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found.</p>";
}

echo "<hr>";
echo "<h3>🔧 How to Enable OTP for Users Without Email:</h3>";
echo "<ol>";
echo "<li><strong>Add email addresses</strong> to users via the admin panel</li>";
echo "<li><strong>Or run SQL</strong>: <code>UPDATE users SET email = 'user@example.com' WHERE username = 'username'</code></li>";
echo "<li><strong>Then they can use OTP login</strong> with their email address</li>";
echo "</ol>";

echo "<h3>📧 Current OTP System:</h3>";
echo "<ul>";
echo "<li><strong>Mailtrap configured</strong>: All OTP emails go to your Mailtrap inbox</li>";
echo "<li><strong>Admin email</strong>: lhandelpamisa0@gmail.com</li>";
echo "<li><strong>OTP expiration</strong>: 5 minutes</li>";
echo "<li><strong>Login page</strong>: <a href='index.php'>index.php</a> (Email + OTP tab)</li>";
echo "</ul>";

echo "<p><a href='index.php'>← Back to Login</a></p>";

$conn->close();
?>
