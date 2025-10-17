<?php
// Simple OTP Code Viewer - Always works
require 'db_connect.php';

echo "<h2>🏥 MJ Pharmacy - Current OTP Codes</h2>";

try {
    // Get the most recent OTP codes (last 5)
    $stmt = $conn->prepare("
        SELECT email, otp_code, created_at, expires_at 
        FROM otp_verification 
        WHERE expires_at > NOW() AND is_used = 0 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<div style='font-family: Arial; max-width: 600px;'>";
        
        while ($row = $result->fetch_assoc()) {
            $timeLeft = strtotime($row['expires_at']) - time();
            $minutesLeft = floor($timeLeft / 60);
            
            echo "<div style='border: 2px solid #22C55E; padding: 20px; margin: 10px 0; border-radius: 10px; background: #f0f9f0;'>";
            echo "<h3>Email: " . htmlspecialchars($row['email']) . "</h3>";
            echo "<div style='font-size: 36px; font-weight: bold; color: #22C55E; letter-spacing: 5px; text-align: center; margin: 15px 0;'>";
            echo $row['otp_code'];
            echo "</div>";
            echo "<p><strong>Generated:</strong> " . $row['created_at'] . "</p>";
            echo "<p><strong>Expires:</strong> " . $row['expires_at'] . " (" . $minutesLeft . " minutes left)</p>";
            echo "</div>";
        }
        
        echo "</div>";
        
    } else {
        echo "<p style='color: #ff6b6b;'>❌ No active OTP codes found.</p>";
        echo "<p>Try requesting an OTP first, then refresh this page.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: #ff6b6b;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Login</a> | <a href='get_otp.php'>🔄 Refresh</a></p>";
echo "<p><small>This page shows active OTP codes from the database.</small></p>";
?>
