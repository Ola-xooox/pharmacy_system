<?php
// Debug OTP System
require 'db_connect.php';

echo "<h2>🔍 OTP System Debug</h2>";

// Check if otp_verification table exists
$result = $conn->query("SHOW TABLES LIKE 'otp_verification'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ otp_verification table doesn't exist!</p>";
    echo "<p>Please run the otp_setup.sql script first.</p>";
    exit;
}

// Check table structure
echo "<h3>📋 Table Structure:</h3>";
$result = $conn->query("DESCRIBE otp_verification");
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

// Show recent OTP data
echo "<h3>📊 Recent OTP Data:</h3>";
$stmt = $conn->prepare("
    SELECT id, email, otp_code, created_at, expires_at, is_used,
           (expires_at > NOW()) as is_valid,
           TIMESTAMPDIFF(MINUTE, NOW(), expires_at) as minutes_left
    FROM otp_verification 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px;'>ID</th>";
    echo "<th style='padding: 8px;'>Email</th>";
    echo "<th style='padding: 8px;'>OTP Code</th>";
    echo "<th style='padding: 8px;'>Created</th>";
    echo "<th style='padding: 8px;'>Expires</th>";
    echo "<th style='padding: 8px;'>Used</th>";
    echo "<th style='padding: 8px;'>Valid</th>";
    echo "<th style='padding: 8px;'>Minutes Left</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $rowColor = '';
        if ($row['is_used']) {
            $rowColor = 'background-color: #d4edda;'; // Green for used
        } elseif (!$row['is_valid']) {
            $rowColor = 'background-color: #f8d7da;'; // Red for expired
        } else {
            $rowColor = 'background-color: #fff3cd;'; // Yellow for valid
        }
        
        echo "<tr style='$rowColor'>";
        echo "<td style='padding: 8px;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='padding: 8px; font-weight: bold;'>" . $row['otp_code'] . "</td>";
        echo "<td style='padding: 8px;'>" . $row['created_at'] . "</td>";
        echo "<td style='padding: 8px;'>" . $row['expires_at'] . "</td>";
        echo "<td style='padding: 8px;'>" . ($row['is_used'] ? '✅ Yes' : '❌ No') . "</td>";
        echo "<td style='padding: 8px;'>" . ($row['is_valid'] ? '🟢 Valid' : '🔴 Expired') . "</td>";
        echo "<td style='padding: 8px;'>" . $row['minutes_left'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No OTP records found.</p>";
}

// Test OTP generation and storage
echo "<h3>🧪 Test OTP Generation:</h3>";
if (isset($_GET['test'])) {
    require_once 'simple_otp_mailer.php';
    $otpMailer = new SimpleOTPMailer($conn);
    
    $testEmail = 'lhandelpamisa0@gmail.com';
    $testOTP = $otpMailer->generateOTP();
    
    echo "<p><strong>Generated OTP:</strong> $testOTP</p>";
    
    if ($otpMailer->storeOTP($testEmail, $testOTP)) {
        echo "<p style='color: green;'>✅ OTP stored successfully!</p>";
        echo "<p><a href='debug_otp.php'>Refresh to see the new OTP</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to store OTP</p>";
    }
} else {
    echo "<p><a href='debug_otp.php?test=1'>Generate Test OTP</a></p>";
}

// Check PHP error log
echo "<h3>📝 Recent PHP Errors:</h3>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $errors = file_get_contents($errorLog);
    $recentErrors = array_slice(explode("\n", $errors), -20);
    echo "<pre style='background-color: #f8f9fa; padding: 10px; max-height: 200px; overflow-y: auto;'>";
    echo htmlspecialchars(implode("\n", $recentErrors));
    echo "</pre>";
} else {
    echo "<p>Error log not found or not configured.</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login</a> | <a href='view_otp_codes.php'>View OTP Codes</a></p>";

$conn->close();
?>
