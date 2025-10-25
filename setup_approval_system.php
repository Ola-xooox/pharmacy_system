<?php
// Setup script for the user approval system
require_once 'db_connect.php';

echo "<h2>Setting up User Approval System...</h2>";

// Create user_approvals table
$sql = "CREATE TABLE IF NOT EXISTS user_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approval_status ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending',
    approved_by INT NULL,
    approval_time TIMESTAMP NULL,
    approval_notes TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_approval_status (approval_status),
    INDEX idx_login_time (login_time)
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'user_approvals' created successfully.<br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
}

// Create additional indexes
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_user_approval_status ON user_approvals (user_id, approval_status)",
    "CREATE INDEX IF NOT EXISTS idx_approval_time ON user_approvals (approval_time)"
];

foreach ($indexes as $indexSql) {
    if ($conn->query($indexSql) === TRUE) {
        echo "✅ Index created successfully.<br>";
    } else {
        echo "❌ Error creating index: " . $conn->error . "<br>";
    }
}

echo "<br><h3>Setup Complete!</h3>";
echo "<p><strong>What's been implemented:</strong></p>";
echo "<ul>";
echo "<li>✅ User approval database table created</li>";
echo "<li>✅ Modified login flow to require admin approval for non-admin users</li>";
echo "<li>✅ Created admin approval interface at <code>admin_portal/user_approvals.php</code></li>";
echo "<li>✅ Added approval management to admin sidebar</li>";
echo "</ul>";

echo "<br><p><strong>How it works:</strong></p>";
echo "<ol>";
echo "<li>Users login with email/password and receive OTP</li>";
echo "<li>After OTP verification, non-admin users are placed in pending approval status</li>";
echo "<li>Admin users can access the 'User Approvals' section to approve/disapprove login requests</li>";
echo "<li>Approved users can access the system, disapproved users are denied access</li>";
echo "<li>Admin users bypass the approval system and can login directly</li>";
echo "</ol>";

echo "<br><p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Test the login flow with a non-admin user</li>";
echo "<li>Login as admin and check the User Approvals section</li>";
echo "<li>Approve or disapprove pending requests</li>";
echo "</ul>";

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
ul, ol { margin: 10px 0; }
li { margin: 5px 0; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>
