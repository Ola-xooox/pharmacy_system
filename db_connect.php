<?php
$servername = "sql209.infinityfree.com";
$username = "if0_40188284"; // Default XAMPP username
$password = "mjpharmacy11770";     // Default XAMPP password
$dbname = "if0_40188284_pharmacy_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>