<?php
// Create user approval table
require_once '../db_connect.php';

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
    echo "Table 'user_approvals' created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create index for faster queries
$indexSql = "CREATE INDEX IF NOT EXISTS idx_user_approval_status ON user_approvals (user_id, approval_status)";
if ($conn->query($indexSql) === TRUE) {
    echo "Index created successfully.<br>";
} else {
    echo "Error creating index: " . $conn->error . "<br>";
}

$conn->close();
echo "Database setup completed!";
?>
