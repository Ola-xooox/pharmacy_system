<?php
date_default_timezone_set('Asia/Manila');

#$servername = "localhost";
#$username = "u311438770_mjpharmacy"; 
#$password = "Ola_xooox241728";
#$dbname = "u311438770_pharmacysystem";

$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "pharmacy_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$conn->query("SET time_zone = '+08:00'");
?>  