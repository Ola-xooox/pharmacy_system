<?php
header('Content-Type: application/json');
require 'db_connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'add_product':
        handleProductAddition($conn);
        break;
    case 'delete_product':
        handleProductDeletion($conn);
        break;
    case 'process_sale':
        handleSaleProcessing($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleSaleProcessing($conn) {
    $orderData = json_decode(file_get_contents('php://input'), true);

    if (empty($orderData) || !is_array($orderData)) {
        echo json_encode(['success' => false, 'message' => 'Invalid order data.']);
        return;
    }

    $conn->begin_transaction();

    try {
        // Prepare the statement once outside the loop for efficiency
        $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        
        foreach ($orderData as $item) {
            // Ensure data types are correct
            $quantity = (int) $item['quantity'];
            $id = (int) $item['id'];
            
            // Bind parameters and execute for each item in the order
            $stmt->bind_param("iii", $quantity, $id, $quantity);
            $stmt->execute();
            
            // Check if the update was successful for this item
            if ($stmt->affected_rows == 0) {
                // If 0 rows were affected, it means the stock was insufficient or product ID was invalid
                // This will trigger the catch block and rollback the entire transaction
                $productName = isset($item['name']) ? $item['name'] : "ID: " . $id;
                throw new Exception("Insufficient stock for product: " . $productName);
            }
        }
        
        $stmt->close();
        
        // If all items were processed successfully, commit the transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Sale processed and stock updated.']);

    } catch (Exception $e) {
        // If any part of the process fails, roll back all database changes
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to process sale: ' . $e->getMessage()]);
    }

    $conn->close();
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
        echo json_encode(['success' => true, 'message' => 'Product moved to history.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete product: ' . $e->getMessage()]);
    }
}


function handleProductAddition($conn) {
    $productName = trim($_POST['name']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE name = ? AND lot_number = ?");
    $stmt->bind_param("ss", $productName, $_POST['lot_number']);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingProduct = $result->fetch_assoc();
    $stmt->close();

    if ($existingProduct) {
        $newStock = $existingProduct['stock'] + (int)$_POST['stock'];
        $newItemTotal = $existingProduct['item_total'] + (float)$_POST['item_total'];

        $sql = "UPDATE products SET stock = ?, item_total = ?, price = ?, cost = ?, batch_number = ?, expiration_date = ?, supplier = ? WHERE id = ?";
        $updateStmt = $conn->prepare($sql);
        $updateStmt->bind_param(
            "iddssssi",
            $newStock,
            $newItemTotal,
            $_POST['price'],
            $_POST['cost'],
            $_POST['batch_number'],
            $_POST['expiration_date'],
            $_POST['supplier'],
            $existingProduct['id']
        );

        if ($updateStmt->execute()) {
            $fetchStmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
            $fetchStmt->bind_param("i", $existingProduct['id']);
            $fetchStmt->execute();
            $updatedProductData = $fetchStmt->get_result()->fetch_assoc();

            echo json_encode(['success' => true, 'action' => 'updated', 'product' => $updatedProductData]);
        } else {
            echo json_encode(['success' => false, 'message' => $updateStmt->error]);
        }
        $updateStmt->close();

    } else {
        $newlyCreatedCategory = null;
        $categoryId = $_POST['category'];
        $newCategoryName = isset($_POST['new_category']) ? trim($_POST['new_category']) : '';

        if ($categoryId === 'others' && !empty($newCategoryName)) {
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
                $newlyCreatedCategory = ['id' => $categoryId, 'name' => $newCategoryName];
                $insertCatStmt->close();
            }
            $catStmt->close();
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $targetDir = "../uploads/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = uniqid() . '_' . basename($_FILES["image"]["name"]);
            $targetFile = $targetDir . $fileName;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $imagePath = 'uploads/' . $fileName;
            }
        }

        $sql = "INSERT INTO products (name, lot_number, category_id, price, cost, date_added, expiration_date, supplier, batch_number, image_path, stock, item_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($sql);
        $dateAdded = date('Y-m-d H:i:s');

        $insertStmt->bind_param( "ssiddsssssid", $productName, $_POST['lot_number'], $categoryId, $_POST['price'], $_POST['cost'], $dateAdded, $_POST['expiration_date'], $_POST['supplier'], $_POST['batch_number'], $imagePath, $_POST['stock'], $_POST['item_total'] );

        if ($insertStmt->execute()) {
            $newProductId = $insertStmt->insert_id;
            $fetchStmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
            $fetchStmt->bind_param("i", $newProductId);
            $fetchStmt->execute();
            $newProductData = $fetchStmt->get_result()->fetch_assoc();

            $response = ['success' => true, 'action' => 'inserted', 'product' => $newProductData];
            if ($newlyCreatedCategory) {
                 $response['newCategory'] = $newlyCreatedCategory;
            }
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => $insertStmt->error]);
        }
        $insertStmt->close();
    }
}
?>
