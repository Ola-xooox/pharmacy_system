<?php
header('Content-Type: application/json');
require '../db_connect.php';

// Fetch all purchase history records, ordered by the most recent transaction
$history_result = $conn->query("SELECT * FROM purchase_history ORDER BY transaction_date DESC");

$purchase_history = [];
while($row = $history_result->fetch_assoc()) {
    $purchase_history[] = $row;
}

echo json_encode($purchase_history);

$conn->close();
?>
