<?php
// Update login_approvals table to support 'no_response' status
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Update Login Approvals Status</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Update Login Approvals Table</h1>";

try {
    // Check if table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'login_approvals'");
    
    if ($checkTable->num_rows === 0) {
        echo "<div class='error'>Error: The 'login_approvals' table does not exist. Please run setup_login_approvals.php first.</div>";
        exit();
    }
    
    // Update the status column to include 'no_response'
    $sql = "ALTER TABLE login_approvals 
            MODIFY COLUMN status ENUM('pending', 'approved', 'declined', 'no_response') DEFAULT 'pending'";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✓ Successfully updated login_approvals table status column to include 'no_response' option.</div>";
    } else {
        // Check if it's just an informational message (already exists)
        if (strpos($conn->error, 'Duplicate') !== false || strpos($conn->error, 'already') !== false) {
            echo "<div class='info'>ℹ The 'no_response' status already exists in the table.</div>";
        } else {
            throw new Exception("Error updating table: " . $conn->error);
        }
    }
    
    echo "<div class='info'>
        <h3>Update Complete!</h3>
        <p>The login approvals system now supports automatic timeout handling:</p>
        <ul>
            <li>Pending requests that exceed 1 minute (60 seconds) will automatically be marked as 'no_response'</li>
            <li>Timed out requests will appear in the Recent Activity section of the admin panel</li>
            <li>Users will be notified when their request times out</li>
            <li>Admin panel auto-refreshes every 5 seconds when there are pending requests</li>
        </ul>
        <p><strong>No further action required.</strong> The system is ready to use.</p>
        <p><a href='admin_portal/login_approvals.php' style='color: #007bff;'>Go to Login Approvals →</a></p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

$conn->close();

echo "</body></html>";
?>
