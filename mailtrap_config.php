<?php
// Mailtrap Configuration - Free Email Testing Service
// Sign up at mailtrap.io for free SMTP credentials

// Mailtrap SMTP Configuration (Free - No Gmail needed)
define('MAILTRAP_HOST', 'sandbox.smtp.mailtrap.io');
define('MAILTRAP_PORT', 2525);
define('MAILTRAP_USERNAME', '1ad0b5f8a1400f'); // From Mailtrap inbox settings
define('MAILTRAP_PASSWORD', '00b2226dcb9c80'); // From Mailtrap inbox settings

// Instructions to get Mailtrap credentials:
/*
1. Go to https://mailtrap.io
2. Sign up for free account
3. Create a new inbox
4. Copy SMTP credentials from the inbox settings
5. Update the credentials above
6. All emails will be captured in Mailtrap (won't go to real inboxes)
7. Perfect for testing!
*/

function testMailtrapConfig() {
    if (MAILTRAP_USERNAME === 'your-mailtrap-username') {
        return "❌ Mailtrap credentials not configured. Please sign up at mailtrap.io";
    }
    return "✅ Mailtrap configuration ready!";
}

// Display status if accessed directly
if (basename($_SERVER['PHP_SELF']) == 'mailtrap_config.php') {
    echo "<h2>Mailtrap Email Configuration</h2>";
    echo "<p>" . testMailtrapConfig() . "</p>";
    
    echo "<h3>Setup Instructions:</h3>";
    echo "<ol>";
    echo "<li>Go to <a href='https://mailtrap.io' target='_blank'>mailtrap.io</a></li>";
    echo "<li>Sign up for a free account</li>";
    echo "<li>Create a new inbox</li>";
    echo "<li>Copy the SMTP credentials</li>";
    echo "<li>Update this file with your credentials</li>";
    echo "</ol>";
    
    echo "<h3>Benefits:</h3>";
    echo "<ul>";
    echo "<li>✅ No Gmail App Password needed</li>";
    echo "<li>✅ Free tier available</li>";
    echo "<li>✅ Perfect for testing</li>";
    echo "<li>✅ View emails in web interface</li>";
    echo "<li>✅ No real emails sent (safe testing)</li>";
    echo "</ul>";
}
?>
