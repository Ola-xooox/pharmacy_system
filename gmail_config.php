<?php
// Gmail Configuration for OTP System
// Follow these steps to set up Gmail for sending OTP emails

/*
=== GMAIL SETUP INSTRUCTIONS ===

1. ENABLE 2-FACTOR AUTHENTICATION
   - Go to: https://myaccount.google.com/security
   - Turn on 2-Step Verification

2. CREATE APP PASSWORD
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and your device
   - Copy the 16-character password (e.g., "abcd efgh ijkl mnop")

3. UPDATE CONFIGURATION BELOW
   - Replace 'your-gmail@gmail.com' with your Gmail address
   - Replace 'your-app-password' with the 16-character app password

4. TEST THE CONFIGURATION
   - Use the test script below to verify it works
*/

// Gmail SMTP Configuration
define('GMAIL_USERNAME', 'lhandelpamisa0@gmail.com'); // Your Gmail address
define('GMAIL_APP_PASSWORD', 'tepyysjnvbxzmsgx'); // Your Gmail App Password (16 characters)

// SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// Test function to verify Gmail configuration
function testGmailConfig() {
    if (empty(GMAIL_APP_PASSWORD)) {
        return "❌ Gmail App Password not configured. Please set GMAIL_APP_PASSWORD in gmail_config.php";
    }
    
    if (strlen(GMAIL_APP_PASSWORD) != 16) {
        return "❌ Gmail App Password should be 16 characters long (with spaces removed)";
    }
    
    return "✅ Gmail configuration looks good! App password is set.";
}

// Display configuration status
if (basename($_SERVER['PHP_SELF']) == 'gmail_config.php') {
    echo "<h2>Gmail OTP Configuration Status</h2>";
    echo "<p>" . testGmailConfig() . "</p>";
    
    if (!empty(GMAIL_APP_PASSWORD)) {
        echo "<h3>Current Settings:</h3>";
        echo "<ul>";
        echo "<li><strong>Gmail:</strong> " . GMAIL_USERNAME . "</li>";
        echo "<li><strong>App Password:</strong> " . str_repeat('*', 12) . substr(GMAIL_APP_PASSWORD, -4) . "</li>";
        echo "<li><strong>SMTP Host:</strong> " . SMTP_HOST . "</li>";
        echo "<li><strong>SMTP Port:</strong> " . SMTP_PORT . "</li>";
        echo "</ul>";
    }
    
    echo "<h3>Setup Instructions:</h3>";
    echo "<ol>";
    echo "<li>Go to <a href='https://myaccount.google.com/security' target='_blank'>Google Account Security</a></li>";
    echo "<li>Enable 2-Step Verification</li>";
    echo "<li>Go to <a href='https://myaccount.google.com/apppasswords' target='_blank'>App Passwords</a></li>";
    echo "<li>Generate an App Password for 'Mail'</li>";
    echo "<li>Copy the 16-character password and update GMAIL_APP_PASSWORD above</li>";
    echo "</ol>";
}
?>
