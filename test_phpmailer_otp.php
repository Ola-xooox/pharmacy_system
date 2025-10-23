<?php
// Test PHPMailer OTP System
require_once 'db_connect.php';
require_once 'phpmailer_otp.php';

echo "<h1>PHPMailer OTP System Test</h1>";

// Initialize the OTP system
$otpMailer = new PHPMailerOTP($conn);

// Test connection
echo "<h2>1. Testing PHPMailer Connection</h2>";
echo "<p>" . $otpMailer->testConnection() . "</p>";

// Test email sending (you can uncomment and modify the email below)
/*
echo "<h2>2. Testing Email Sending</h2>";
$testEmail = "your-test-email@gmail.com"; // Change this to your email
echo "<p>" . $otpMailer->sendTestEmail($testEmail) . "</p>";
*/

// Test OTP generation and storage
echo "<h2>3. Testing OTP Generation and Storage</h2>";
$testEmail = "lhandelpamisa0@gmail.com"; // Using the configured email
$testOTP = $otpMailer->generateOTP();
echo "<p>Generated OTP: <strong>$testOTP</strong></p>";

if ($otpMailer->storeOTP($testEmail, $testOTP)) {
    echo "<p>✅ OTP stored successfully in database</p>";
} else {
    echo "<p>❌ Failed to store OTP in database</p>";
}

// Test OTP verification
echo "<h2>4. Testing OTP Verification</h2>";
if ($otpMailer->verifyOTP($testEmail, $testOTP)) {
    echo "<p>✅ OTP verification successful</p>";
} else {
    echo "<p>❌ OTP verification failed</p>";
}

// Check database structure
echo "<h2>5. Database Structure Check</h2>";
$result = $conn->query("DESCRIBE otp_verification");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Could not check otp_verification table structure</p>";
}

// Show recent OTP codes for testing
echo "<h2>6. Recent OTP Codes (for testing)</h2>";
$stmt = $conn->prepare("SELECT email, otp_code, created_at, expires_at, is_used FROM otp_verification ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Email</th><th>OTP Code</th><th>Created</th><th>Expires</th><th>Used</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td><strong>" . $row['otp_code'] . "</strong></td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "<td>" . $row['expires_at'] . "</td>";
        echo "<td>" . ($row['is_used'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No OTP codes found in database</p>";
}

echo "<hr>";
echo "<h2>Instructions:</h2>";
echo "<ol>";
echo "<li>Make sure your Gmail configuration is correct in <code>gmail_config.php</code></li>";
echo "<li>Ensure you have enabled 2-factor authentication and created an App Password</li>";
echo "<li>To test actual email sending, uncomment the email test section above and add your email</li>";
echo "<li>Check the <code>otp_codes.log</code> file for detailed logging</li>";
echo "</ol>";

echo "<p><a href='index.php'>← Back to Login Page</a></p>";
?>
