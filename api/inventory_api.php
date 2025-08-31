<?php
header('Content-Type: application/json');
require 'db_connect.php'; // Ensure your db_connect file is in the same directory

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_inventory_report') {
    // Total Stocks and Total Products
    $totalStocksStmt = $conn->prepare("SELECT SUM(item_total) AS total_stocks, COUNT(*) AS total_products FROM products");
    $totalStocksStmt->execute();
    $inventorySummary = $totalStocksStmt->get_result()->fetch_assoc();
    $totalStocksStmt->close();

    // Expiring Soon
    $expiringStmt = $conn->prepare("SELECT COUNT(*) AS expiring_soon FROM products WHERE expiration_date IS NOT NULL AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)");
    $expiringStmt->execute();
    $expiringSoon = $expiringStmt->get_result()->fetch_assoc()['expiring_soon'] ?? 0;
    $expiringStmt->close();

    // Inventory Management Table
    $inventoryListStmt = $conn->prepare("SELECT name, stock, expiration_date, supplier, date_added FROM products ORDER BY name ASC");
    $inventoryListStmt->execute();
    $inventoryList = $inventoryListStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $inventoryListStmt->close();

    echo json_encode([
        'success' => true,
        'total_stocks' => $inventorySummary['total_stocks'] ?? 0,
        'total_products' => $inventorySummary['total_products'] ?? 0,
        'expiring_soon' => $expiringSoon,
        'inventory_list' => $inventoryList
    ]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

$conn->close();
?>