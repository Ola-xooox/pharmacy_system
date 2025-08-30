<?php
header('Content-Type: application/json');
require '../db_connect.php';

$today = date('Y-m-d');
$oneMonthFromNow = date('Y-m-d', strtotime('+1 month'));

// --- Fetch products expiring within one month ---
$expiring_soon_stmt = $conn->prepare(
    "SELECT name, lot_number, expiration_date 
     FROM products 
     WHERE expiration_date > ? AND expiration_date <= ? AND item_total > 0
     ORDER BY expiration_date ASC"
);
$expiring_soon_stmt->bind_param("ss", $today, $oneMonthFromNow);
$expiring_soon_stmt->execute();
$expiring_soon_result = $expiring_soon_stmt->get_result();
$expiring_soon_products = $expiring_soon_result->fetch_all(MYSQLI_ASSOC);
$expiring_soon_stmt->close();

// --- Fetch products that have already expired ---
$expired_stmt = $conn->prepare(
    "SELECT name, lot_number, expiration_date 
     FROM products 
     WHERE expiration_date <= ? AND item_total > 0
     ORDER BY expiration_date DESC"
);
$expired_stmt->bind_param("s", $today);
$expired_stmt->execute();
$expired_result = $expired_stmt->get_result();
$expired_products = $expired_result->fetch_all(MYSQLI_ASSOC);
$expired_stmt->close();

$total_notifications = count($expiring_soon_products) + count($expired_products);

echo json_encode([
    'expiring_soon' => $expiring_soon_products,
    'expired' => $expired_products,
    'total_notifications' => $total_notifications,
]);

$conn->close();
?>
