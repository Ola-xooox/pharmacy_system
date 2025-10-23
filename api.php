<?php
// CRITICAL: This must be the very first line of your PHP file.
session_start();

header('Content-Type: application/json');
require 'db_connect.php';

// This function now gets the user ID and role from the session
function logUserActivity($conn, $action_description) {
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; // Default to 0 if not set
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

    // Prevent logging if user is not properly logged in (user_id is 0)
    if ($userId === 0) {
        return;
    }

    // Embed the user's role/system name into the action description
    $fullAction = ucfirst($userRole) . " System: " . $action_description;
    
    $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_description, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $userId, $fullAction);
    $stmt->execute();
    $stmt->close();
}


/**
 * Handles processing a sale and logs the transaction to purchase_history.
 */
function handleSaleProcessing($conn) {
    $orderData = json_decode(file_get_contents('php://input'), true);

    if (empty($orderData) || !is_array($orderData)) {
        echo json_encode(['success' => false, 'message' => 'Invalid order data.']);
        return;
    }

    $conn->begin_transaction();

    try {
        $updateStmt = $conn->prepare("UPDATE products SET stock = ?, item_total = ? WHERE id = ?");
        $historyStmt = $conn->prepare("INSERT INTO purchase_history (product_name, quantity, total_price, transaction_date) VALUES (?, ?, ?, ?)");
        
        $totalItemsSold = 0;
        $productNames = [];

        foreach ($orderData as $item) {
            $product_name = $item['name'];
            $quantity_to_sell = (int) $item['quantity'];
            $totalItemsSold += $quantity_to_sell;
            $productNames[] = $product_name . " (x" . $quantity_to_sell . ")";


            $fetchLotsStmt = $conn->prepare(
                "SELECT id, item_total, items_per_stock FROM products 
                 WHERE name = ? AND item_total > 0 AND (expiration_date > CURDATE() OR expiration_date IS NULL)
                 ORDER BY expiration_date ASC"
            );
            $fetchLotsStmt->bind_param("s", $product_name);
            $fetchLotsStmt->execute();
            $lots = $fetchLotsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $fetchLotsStmt->close();

            $total_available_items = array_sum(array_column($lots, 'item_total'));
            if ($quantity_to_sell > $total_available_items) {
                throw new Exception("Insufficient stock for product: " . htmlspecialchars($product_name) . ". Requested: " . $quantity_to_sell, 400);
            }

            $quantity_remaining_to_sell = $quantity_to_sell;
            foreach ($lots as $lot) {
                if ($quantity_remaining_to_sell <= 0) break;

                $items_in_this_lot = (int) $lot['item_total'];
                $items_to_take_from_lot = min($quantity_remaining_to_sell, $items_in_this_lot);
                
                $new_item_total = $items_in_this_lot - $items_to_take_from_lot;
                $items_per_stock = (int) $lot['items_per_stock'];

                if ($items_per_stock <= 0) {
                    throw new Exception("Product configuration error: 'items_per_stock' is not set for product ID: " . $lot['id']);
                }
                
                $new_stock = ceil($new_item_total / $items_per_stock);

                if ($new_item_total <= 0) $new_stock = 0;

                $updateStmt->bind_param("iii", $new_stock, $new_item_total, $lot['id']);
                $updateStmt->execute();

                $quantity_remaining_to_sell -= $items_to_take_from_lot;
            }

            $total_price = (float)$item['price'] * $quantity_to_sell;
            $transaction_date = date('Y-m-d H:i:s');
            $historyStmt->bind_param("sids", $product_name, $quantity_to_sell, $total_price, $transaction_date);
            $historyStmt->execute();
        }
        
        $updateStmt->close();
        $historyStmt->close();
        $conn->commit();

        // Log the activity after a successful commit
        $logMessage = "Processed a sale of " . $totalItemsSold . " item(s): " . implode(', ', $productNames) . ".";
        logUserActivity($conn, $logMessage);

        echo json_encode(['success' => true, 'message' => 'Sale processed successfully.']);

    } catch (Exception $e) {
        $conn->rollback();
        $errorCode = $e->getCode() == 400 ? 400 : 500;
        http_response_code($errorCode);
        echo json_encode(['success' => false, 'message' => 'Failed to process sale: ' . $e->getMessage()]);
    }
}

function handleProductAddition($conn) {
    $productName = trim($_POST['name']);
    $lotNumber = $_POST['lot_number'];
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE name = ? AND lot_number = ?");
    $stmt->bind_param("ss", $productName, $lotNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingProduct = $result->fetch_assoc();
    $stmt->close();

    if ($existingProduct) {
        // Update existing product
        $addedStock = (int)$_POST['stock'];
        $newStock = $existingProduct['stock'] + $addedStock;
        $newItemTotal = $existingProduct['item_total'] + (int)$_POST['item_total'];

        $sql = "UPDATE products SET stock = ?, item_total = ?, price = ?, cost = ?, batch_number = ?, expiration_date = ?, supplier = ? WHERE id = ?";
        $updateStmt = $conn->prepare($sql);
        $updateStmt->bind_param( "iddssssi", $newStock, $newItemTotal, $_POST['price'], $_POST['cost'], $_POST['batch_number'], $_POST['expiration_date'], $_POST['supplier'], $existingProduct['id'] );

        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'action' => 'updated']);
            logUserActivity($conn, "Updated stock for '$productName' (Lot: $lotNumber). Added $addedStock stock.");
        } else {
            echo json_encode(['success' => false, 'message' => $updateStmt->error]);
        }
        $updateStmt->close();

    } else {
        // Add new product
        $stock_to_add = (int)$_POST['stock'];
        $items_to_add = (int)$_POST['item_total'];
        $items_per_stock = ($stock_to_add > 0) ? ($items_to_add / $stock_to_add) : 0;
        
        $categoryId = $_POST['category'];
        $newCategoryName = isset($_POST['new_category']) ? trim($_POST['new_category']) : '';

        if ($categoryId === 'others' && !empty($newCategoryName)) {
            // Logic to add new category...
            $catStmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
            $catStmt->bind_param("s", $newCategoryName);
            $catStmt->execute();
            $catResult = $catStmt->get_result();
            if ($row = $catResult->fetch_assoc()) {
                $categoryId = $row['id'];
            } else {
                $insertCatStmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                $insertCatStmt->bind_param("s", $newCategoryName);
                $insertCatStmt->execute();
                $categoryId = $insertCatStmt->insert_id;
                $insertCatStmt->close();
                logUserActivity($conn, "Created new category: '$newCategoryName'.");
            }
            $catStmt->close();
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $uploadDir = 'uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $imageExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = uniqid() . '_' . time() . '.' . $imageExtension;
            $imagePath = $uploadDir . $imageName;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                $imagePath = null;
            }
        }

        $sql = "INSERT INTO products (name, lot_number, category_id, price, cost, date_added, expiration_date, supplier, batch_number, image_path, stock, item_total, items_per_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($sql);
        $dateAdded = date('Y-m-d H:i:s');

        $insertStmt->bind_param( "ssiddsssssidi", $productName, $lotNumber, $categoryId, $_POST['price'], $_POST['cost'], $dateAdded, $_POST['expiration_date'], $_POST['supplier'], $_POST['batch_number'], $imagePath, $stock_to_add, $items_to_add, $items_per_stock );

        if ($insertStmt->execute()) {
            echo json_encode(['success' => true, 'action' => 'inserted']);
            logUserActivity($conn, "Added new product: '$productName' (Lot: $lotNumber).");
        } else {
            echo json_encode(['success' => false, 'message' => $insertStmt->error]);
        }
        $insertStmt->close();
    }
}

function handleProductDeletion($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = isset($data['id']) ? (int)$data['id'] : 0;

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Product ID']);
        return;
    }

    $conn->begin_transaction();

    try {
        $fetchStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $fetchStmt->bind_param("i", $productId);
        $fetchStmt->execute();
        $productData = $fetchStmt->get_result()->fetch_assoc();
        $fetchStmt->close();

        if (!$productData) {
            throw new Exception("Product not found.");
        }

        $historySql = "INSERT INTO product_history (product_id, name, lot_number, category_id, price, cost, stock, item_total, date_added, expiration_date, supplier, batch_number, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $historyStmt = $conn->prepare($historySql);
        $historyStmt->bind_param(
            "issiddsdsssss",
            $productData['id'],
            $productData['name'],
            $productData['lot_number'],
            $productData['category_id'],
            $productData['price'],
            $productData['cost'],
            $productData['stock'],
            $productData['item_total'],
            $productData['date_added'],
            $productData['expiration_date'],
            $productData['supplier'],
            $productData['batch_number'],
            $productData['image_path']
        );
        $historyStmt->execute();
        $historyStmt->close();

        $deleteStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $deleteStmt->bind_param("i", $productId);
        $deleteStmt->execute();
        $deleteStmt->close();

        $conn->commit();
        logUserActivity($conn, "Deleted product: '" . $productData['name'] . "' (Lot: " . $productData['lot_number'] . ") and moved to history.");
        echo json_encode(['success' => true, 'message' => 'Product moved to history.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete product: ' . $e->getMessage()]);
    }
}

// Master Switch for all actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'add_product':
        handleProductAddition($conn);
        break;
    case 'delete_product':
        handleProductDeletion($conn);
        break;
    case 'process_sale':
        // DEPRECATED: This action has been moved to customer_api.php to prevent duplicate entries
        http_response_code(410); // Gone
        echo json_encode(['success' => false, 'message' => 'This endpoint has been deprecated. Use customer_api.php?action=complete_sale instead.']);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
}

// Close the connection at the very end
$conn->close();
?>
