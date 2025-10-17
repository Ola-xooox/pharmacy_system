<?php
// View OTP Codes for Testing
// This page shows the OTP codes that would normally be sent via email

require 'db_connect.php';

echo "<h2>🔐 OTP Codes for Testing</h2>";
echo "<p><em>This page shows OTP codes that would normally be sent via email.</em></p>";

// Show recent OTP codes from database
$stmt = $conn->prepare("
    SELECT email, otp_code, created_at, expires_at, is_used 
    FROM otp_verification 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 10px;'>Email</th>";
    echo "<th style='padding: 10px;'>OTP Code</th>";
    echo "<th style='padding: 10px;'>Created</th>";
    echo "<th style='padding: 10px;'>Expires</th>";
    echo "<th style='padding: 10px;'>Status</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $isExpired = strtotime($row['expires_at']) < time();
        $isUsed = $row['is_used'] == 1;
        
        $status = '';
        $rowColor = '';
        
        if ($isUsed) {
            $status = '✅ Used';
            $rowColor = 'background-color: #d4edda;';
        } elseif ($isExpired) {
            $status = '❌ Expired';
            $rowColor = 'background-color: #f8d7da;';
        } else {
            $status = '🟢 Valid';
            $rowColor = 'background-color: #fff3cd;';
        }
        
        echo "<tr style='$rowColor'>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='padding: 10px; font-weight: bold; font-size: 18px;'>" . $row['otp_code'] . "</td>";
        echo "<td style='padding: 10px;'>" . $row['created_at'] . "</td>";
        echo "<td style='padding: 10px;'>" . $row['expires_at'] . "</td>";
        echo "<td style='padding: 10px;'>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No OTP codes found in the last hour.</p>";
}

// Show log file if it exists
$logFile = 'otp_codes.txt';
if (file_exists($logFile)) {
    echo "<h3>📋 OTP Log File:</h3>";
    echo "<pre style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars(file_get_contents($logFile));
    echo "</pre>";
    
    echo "<p><a href='?clear_log=1' style='color: red;'>Clear Log File</a></p>";
}

// Clear log file if requested
if (isset($_GET['clear_log'])) {
    if (file_exists($logFile)) {
        unlink($logFile);
        echo "<p style='color: green;'>✅ Log file cleared!</p>";
        echo "<script>setTimeout(function(){ window.location.href = 'view_otp_codes.php'; }, 1000);</script>";
    }
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Login</a></p>";
echo "<p><em>Refresh this page to see new OTP codes</em></p>";

$conn->close();
?>
