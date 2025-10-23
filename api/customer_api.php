<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require '../db_connect.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Database Connection failed: " . $conn->connect_error]);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_history':
        handleGetHistory($conn);
        break;
    case 'complete_sale': // UPDATED: New action to handle the entire sale process
        handleCompleteSale($conn);
        break;
    case 'get_customer_transactions':
        handleGetCustomerTransactions($conn);
        break;
    case 'get_receipt_details':
        handleGetReceiptDetails($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid or no action specified.']);
        break;
}

function handleGetHistory($conn) {
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 6;
    $offset = ($page - 1) * $limit;
    $searchTerm = "%$search%";

    $countQuery = "SELECT COUNT(*) as total FROM customer_history WHERE customer_name LIKE ? OR customer_id_no LIKE ?";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $totalResults = $stmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalResults / $limit);
    $stmt->close();

    $query = "SELECT * FROM customer_history WHERE customer_name LIKE ? OR customer_id_no LIKE ? ORDER BY last_visit DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $customers = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'customers' => $customers,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'totalResults' => (int)$totalResults,
        'limit' => $limit
    ]);
}

// NEW UNIFIED FUNCTION
function handleCompleteSale($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['items']) || !isset($data['total_amount'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
        return;
    }

    $customerName = trim($data['customer_name'] ?? 'Walk-in');
    $customerIdNo = trim($data['customer_id'] ?? '');
    $totalAmount = $data['total_amount'];
    $items = $data['items'];

    if (empty($items) || $totalAmount <= 0) {
        echo json_encode(['success' => true, 'message' => 'No items to log.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Step 1: Find or create customer_history record
        $stmt = $conn->prepare("SELECT id FROM customer_history WHERE customer_name = ? AND customer_id_no = ?");
        $stmt->bind_param("ss", $customerName, $customerIdNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $historyId = null;

        if ($row = $result->fetch_assoc()) {
            $historyId = $row['id'];
            $updateStmt = $conn->prepare("UPDATE customer_history SET total_visits = total_visits + 1, total_spent = total_spent + ?, last_visit = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->bind_param("di", $totalAmount, $historyId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $insertStmt = $conn->prepare("INSERT INTO customer_history (customer_name, customer_id_no, total_visits, total_spent) VALUES (?, ?, 1, ?)");
            $insertStmt->bind_param("ssd", $customerName, $customerIdNo, $totalAmount);
            $insertStmt->execute();
            $historyId = $insertStmt->insert_id;
            $insertStmt->close();
        }
        $stmt->close();

        if (!$historyId) {
            throw new Exception("Failed to create or find customer history record.");
        }

        // Step 2: Create a single transaction record and get its ID
        $transStmt = $conn->prepare("INSERT INTO transactions (customer_history_id, total_amount) VALUES (?, ?)");
        $transStmt->bind_param("id", $historyId, $totalAmount);
        $transStmt->execute();
        $transactionId = $transStmt->insert_id; // CRITICAL: Get the new transaction ID
        $transStmt->close();

        if (!$transactionId) {
            throw new Exception("Failed to create transaction record.");
        }

        // Step 3: Update inventory for each item
        $updateStmt = $conn->prepare("UPDATE products SET stock = ?, item_total = ? WHERE id = ?");
        foreach ($items as $item) {
            $product_name = $item['name'];
            $quantity_to_sell = (int)$item['quantity'];

            // Fetch lots for this product (FIFO - First In, First Out)
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
                throw new Exception("Insufficient stock for product: " . htmlspecialchars($product_name) . ". Requested: " . $quantity_to_sell);
            }

            $quantity_remaining_to_sell = $quantity_to_sell;
            foreach ($lots as $lot) {
                if ($quantity_remaining_to_sell <= 0) break;

                $items_in_this_lot = (int)$lot['item_total'];
                $items_to_take_from_lot = min($quantity_remaining_to_sell, $items_in_this_lot);
                
                $new_item_total = $items_in_this_lot - $items_to_take_from_lot;
                $items_per_stock = (int)$lot['items_per_stock'];

                if ($items_per_stock <= 0) {
                    throw new Exception("Product configuration error: 'items_per_stock' is not set for product ID: " . $lot['id']);
                }
                
                $new_stock = ceil($new_item_total / $items_per_stock);
                if ($new_item_total <= 0) $new_stock = 0;

                $updateStmt->bind_param("iii", $new_stock, $new_item_total, $lot['id']);
                $updateStmt->execute();

                $quantity_remaining_to_sell -= $items_to_take_from_lot;
            }
        }
        $updateStmt->close();

        // Step 4: Insert each purchased item into purchase_history, linking it to the transaction ID
        $purchaseStmt = $conn->prepare("INSERT INTO purchase_history (transaction_id, product_name, quantity, total_price, transaction_date) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
        foreach ($items as $item) {
            $product_name = $item['name'];
            $quantity = (int)$item['quantity'];
            $item_total_price = (float)$item['price'] * $quantity;
            
            // Check for recent duplicates (within last 30 seconds) to prevent double insertion
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_history WHERE product_name = ? AND quantity = ? AND total_price = ? AND transaction_date > DATE_SUB(NOW(), INTERVAL 30 SECOND)");
            $checkStmt->bind_param("sid", $product_name, $quantity, $item_total_price);
            $checkStmt->execute();
            $duplicateCount = $checkStmt->get_result()->fetch_assoc()['count'];
            $checkStmt->close();
            
            if ($duplicateCount == 0) {
                $purchaseStmt->bind_param("isid", $transactionId, $product_name, $quantity, $item_total_price);
                $purchaseStmt->execute();
            }
        }
        $purchaseStmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Sale logged successfully.']);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}


function handleGetCustomerTransactions($conn) {
    $customerId = $_GET['id'] ?? 0;
    if (!$customerId) {
        http_response_code(400);
        echo json_encode([]);
        return;
    }

    // Fetch transactions for the customer
    $stmt = $conn->prepare("SELECT id, total_amount, transaction_date FROM transactions WHERE customer_history_id = ? ORDER BY transaction_date DESC");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $transactions_result = $stmt->get_result();
    $transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($transactions)) {
        echo json_encode([]);
        return;
    }

    // UPDATED LOGIC: Fetch items for each transaction using the correct transaction_id
    $itemsStmt = $conn->prepare("SELECT product_name, quantity, total_price FROM purchase_history WHERE transaction_id = ?");
    foreach ($transactions as &$tx) { 
        $itemsStmt->bind_param("i", $tx['id']); // Bind the transaction ID
        $itemsStmt->execute();
        $items_result = $itemsStmt->get_result();
        $items = $items_result->fetch_all(MYSQLI_ASSOC);
        $tx['items'] = $items;
    }
    unset($tx);
    $itemsStmt->close();

    echo json_encode($transactions);
}

function handleGetReceiptDetails($conn) {
    // UPDATED LOGIC: Use transaction_id for a reliable lookup
    $transactionId = $_GET['id'] ?? 0;

    if (empty($transactionId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing transaction ID for lookup.']);
        return;
    }

    $stmt = $conn->prepare("SELECT product_name, quantity, total_price FROM purchase_history WHERE transaction_id = ?");
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($items)) {
         echo json_encode(['success' => false, 'message' => 'No purchased items were found for this transaction.']);
         return;
    }

    echo json_encode(['success' => true, 'items' => $items]);
}

$conn->close();
?>