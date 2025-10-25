<?php
// Check what IP address the server sees
echo "<h2>IP Address Debug Information</h2>";
echo "<p><strong>Your IP as seen by server:</strong> " . $_SERVER['REMOTE_ADDR'] . "</p>";
echo "<p><strong>HTTP_X_FORWARDED_FOR:</strong> " . (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : 'Not set') . "</p>";
echo "<p><strong>HTTP_X_REAL_IP:</strong> " . (isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : 'Not set') . "</p>";
echo "<p><strong>HTTP_CLIENT_IP:</strong> " . (isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : 'Not set') . "</p>";

// Show all server variables related to IP
echo "<h3>All IP-related server variables:</h3>";
foreach($_SERVER as $key => $value) {
    if(strpos(strtolower($key), 'addr') !== false || strpos(strtolower($key), 'ip') !== false) {
        echo "<p><strong>$key:</strong> $value</p>";
    }
}
?>
