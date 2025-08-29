<?php
header('Content-Type: application/json');
require '../db_connect.php'; // Adjust path if necessary

$result = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$categories = [];
while($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

echo json_encode($categories);

$conn->close();
?>