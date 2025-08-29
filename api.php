<?php
header('Content-Type: application/json');
require 'db_connect.php'; 

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'add_product':
        handleProductAddition($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleProductAddition($conn) {
    // Sanitize product name to ensure consistent matching
    $productName = trim($_POST['name']);
    
    // Check if product already exists
    $stmt = $conn->prepare("SELECT * FROM products WHERE name = ?");
    $stmt->bind_param("s", $productName);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingProduct = $result->fetch_assoc();
    $stmt->close();

    if ($existingProduct) {
        // --- PRODUCT EXISTS: UPDATE STOCK ---
        $newStock = $existingProduct['stock'] + (int)$_POST['stock'];
        $newItemTotal = $existingProduct['item_total'] + (float)$_POST['item_total'];

        $sql = "UPDATE products SET stock = ?, item_total = ?, price = ?, cost = ?, lot_number = ?, batch_number = ?, expiration_date = ?, supplier = ? WHERE id = ?";
        $updateStmt = $conn->prepare($sql);
        $updateStmt->bind_param(
            "iddsssssi",
            $newStock,
            $newItemTotal,
            $_POST['price'],
            $_POST['cost'],
            $_POST['lot_number'],
            $_POST['batch_number'],
            $_POST['expiration_date'],
            $_POST['supplier'],
            $existingProduct['id']
        );

        if ($updateStmt->execute()) {
            // Fetch the updated product data to send back
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
        // --- NEW PRODUCT: INSERT ---
        $newlyCreatedCategory = null;
        $categoryId = $_POST['category'];
        $newCategoryName = isset($_POST['new_category']) ? trim($_POST['new_category']) : '';

        if ($categoryId === 'others' && !empty($newCategoryName)) {
            // Logic to create a new category (remains the same)
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
            $targetDir = "uploads/";
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
    $conn->close();
}
?>