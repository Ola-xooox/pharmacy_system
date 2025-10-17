<?php
// File structure and error checking for InfinityFree
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>File Structure Check</h2>";

$required_files = [
    'index.php',
    'db_connect.php',
    'gmail_config.php',
    'mailtrap_otp_mailer.php'
];

echo "<h3>Required Files:</h3>";
foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file - Found</p>";
    } else {
        echo "<p style='color: red;'>❌ $file - Missing</p>";
    }
}

echo "<h3>Directory Structure:</h3>";
$directories = [
    'admin_portal',
    'pos',
    'inventory',
    'cms'
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "<p style='color: green;'>✅ $dir/ - Directory exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $dir/ - Directory missing</p>";
    }
}

echo "<h3>PHP Configuration:</h3>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' . "</li>";
echo "<li><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' . "</li>";
echo "<li><strong>Current Directory:</strong> " . getcwd() . "</li>";
echo "</ul>";

echo "<h3>Session Test:</h3>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✅ Sessions working</p>";
} else {
    echo "<p style='color: red;'>❌ Session issues detected</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Login</a></p>";
?>
