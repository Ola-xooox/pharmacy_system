<?php
// Test IP Access Control
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$userIP = getUserIP();
$authorizedIP = '192.168.100.142';

echo "<h2>WiFi Network Access Control Test</h2>";
echo "<p><strong>Your Current IP:</strong> " . htmlspecialchars($userIP) . "</p>";
echo "<p><strong>Required WiFi Network IP:</strong> " . htmlspecialchars($authorizedIP) . "</p>";

if ($userIP === $authorizedIP) {
    echo "<p style='color: green;'><strong>✅ ACCESS GRANTED</strong> - You are connected to the authorized WiFi network</p>";
    echo "<p style='color: green;'>You can access POS, CMS, and Inventory modules</p>";
} else {
    echo "<p style='color: red;'><strong>❌ ACCESS DENIED</strong> - You are not connected to the authorized WiFi network</p>";
    echo "<p style='color: orange;'><strong>💡 To gain access:</strong> Connect to the WiFi network with IP 192.168.100.142</p>";
}

echo "<p><strong>Note:</strong> Admin users can access from any IP address.</p>";

// Test links
echo "<h3>Test Access:</h3>";
echo "<ul>";
echo "<li><a href='pos/pos.php' target='_blank'>POS Module</a> (Requires IP: $authorizedIP)</li>";
echo "<li><a href='cms/customer_history.php' target='_blank'>CMS Module</a> (Requires IP: $authorizedIP)</li>";
echo "<li><a href='inventory/products.php' target='_blank'>Inventory Module</a> (Requires IP: $authorizedIP)</li>";
echo "<li><a href='admin_portal/dashboard.php' target='_blank'>Admin Portal</a> (Accessible from any IP)</li>";
echo "</ul>";

echo "<p><a href='index.php'>← Back to Login</a></p>";
?>
