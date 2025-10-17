<?php
// Simple database connection test for InfinityFree
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

$servername = "sql209.infinityfree.com";
$username = "if0_40188284";
$password = "mjpharmacy11770";
$dbname = "if0_40188284_pharmacy_system";

echo "<p><strong>Testing connection to:</strong></p>";
echo "<ul>";
echo "<li>Server: $servername</li>";
echo "<li>Database: $dbname</li>";
echo "<li>Username: $username</li>";
echo "</ul>";

try {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ <strong>Database connection successful!</strong></p>";
    
    // Test if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Users table exists</p>";
        
        // Count users
        $count_result = $conn->query("SELECT COUNT(*) as count FROM users");
        $count = $count_result->fetch_assoc()['count'];
        echo "<p>📊 Total users in database: $count</p>";
        
    } else {
        echo "<p style='color: orange;'>⚠️ Users table not found - you may need to import your database</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Database connection failed:</strong></p>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    
    echo "<h3>Common InfinityFree Issues:</h3>";
    echo "<ul>";
    echo "<li>Database server might be temporarily down</li>";
    echo "<li>Database credentials might be incorrect</li>";
    echo "<li>Database might not be created yet</li>";
    echo "<li>Your hosting account might not be fully activated</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Login</a></p>";
?>
