<?php
// Check what IP address the server sees for CMS access
echo "<h1>🔍 CMS IP Address Detection</h1>";
echo "<div style='background: #f0f0f0; padding: 20px; margin: 10px; border-radius: 5px;'>";
echo "<h2>📍 Your IP as seen by the server:</h2>";
echo "<h3 style='color: red; font-size: 24px;'>" . $_SERVER['REMOTE_ADDR'] . "</h3>";
echo "</div>";

echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px; border-radius: 5px;'>";
echo "<h3>🌐 Additional IP Information:</h3>";
echo "<p><strong>HTTP_X_FORWARDED_FOR:</strong> " . (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : 'Not set') . "</p>";
echo "<p><strong>HTTP_X_REAL_IP:</strong> " . (isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : 'Not set') . "</p>";
echo "<p><strong>HTTP_CLIENT_IP:</strong> " . (isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : 'Not set') . "</p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; margin: 10px; border-radius: 5px;'>";
echo "<h3>📋 Instructions:</h3>";
echo "<p>1. Copy the <strong>red IP address</strong> above</p>";
echo "<p>2. Send it to me so I can update the CMS .htaccess file</p>";
echo "<p>3. This is the IP that needs to be allowed for CMS access</p>";
echo "</div>";
?>
