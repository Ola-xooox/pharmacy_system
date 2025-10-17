<?php
// Set timezone for proper time display
date_default_timezone_set('Asia/Manila'); // Philippine timezone

$servername = "sql209.infinityfree.com";
$username = "if0_40188284"; // InfinityFree username
$password = "mjpharmacy11770";     // InfinityFree password
$dbname = "if0_40188284_pharmacy_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Set MySQL timezone to match PHP timezone
$conn->query("SET time_zone = '+08:00'");
?>