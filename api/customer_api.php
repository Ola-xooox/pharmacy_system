<?php
session_start(); // Start session to access user data
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require '../db_connect.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Database Connection failed: " . $conn->connect_error]);
    exit();
}

// Activity logging function
function logUserActivity($conn, $action_description) {
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

    // Prevent logging if user is not properly logged in
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
    $dateFilter = $_GET['date'] ?? '';
    $customerTypeFilter = $_GET['customer_type'] ?? '';
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 6;
    $offset = ($page - 1) * $limit;
    $searchTerm = "%$search%";

    // Build WHERE clause based on filters
    $whereConditions = [];
    $params = [];
    $types = '';

    // Search filter
    if (!empty($search)) {
        $whereConditions[] = "(customer_name LIKE ? OR customer_id_no LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ss';
    }

    // Date filter - filter by last_visit date
    if (!empty($dateFilter)) {
        $whereConditions[] = "DATE(last_visit) = ?";
        $params[] = $dateFilter;
        $types .= 's';
    }

    // Customer type filter
    if (!empty($customerTypeFilter)) {
        if ($customerTypeFilter === 'walk-in') {
            $whereConditions[] = "(customer_name = 'Walk-in' OR customer_name = '')";
        } else if ($customerTypeFilter === 'discounted') {
            $whereConditions[] = "(customer_name != 'Walk-in' AND customer_name != '' AND customer_name IS NOT NULL)";
        }
    }

    // Construct final WHERE clause
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    // Count query
    $countQuery = "SELECT COUNT(*) as total FROM customer_history $whereClause";
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $totalResults = $stmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalResults / $limit);
    $stmt->close();

    // Main query
    $query = "SELECT * FROM customer_history $whereClause ORDER BY last_visit DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
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
        // For Walk-in customers (no discount), always create new entries
        if ($customerName === 'Walk-in' && empty($customerIdNo)) {
            // Always create a new Walk-in entry for each transaction
            $insertStmt = $conn->prepare("INSERT INTO customer_history (customer_name, customer_id_no, total_visits, total_spent) VALUES (?, ?, 1, ?)");
            $insertStmt->bind_param("ssd", $customerName, $customerIdNo, $totalAmount);
            $insertStmt->execute();
            $historyId = $insertStmt->insert_id;
            $insertStmt->close();
        } else {
            // For customers with names/IDs, find existing or create new
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
        }

        if (!$historyId) {
            throw new Exception("Failed to create or find customer history record.");
        }

        // Step 2: Create a single transaction record with payment information and get its ID
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $cashAmount = $data['cash_amount'] ?? null;
        $changeAmount = $data['change_amount'] ?? null;
        $subtotal = $data['subtotal'] ?? $totalAmount;
        $discountAmount = $data['discount_amount'] ?? 0;
        
        $transStmt = $conn->prepare("INSERT INTO transactions (customer_history_id, total_amount, payment_method, cash_amount, change_amount, subtotal, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $transStmt->bind_param("idsdddd", $historyId, $totalAmount, $paymentMethod, $cashAmount, $changeAmount, $subtotal, $discountAmount);
        $transStmt->execute();
        $transactionId = $transStmt->insert_id; // CRITICAL: Get the new transaction ID
        $transStmt->close();

        if (!$transactionId) {
            throw new Exception("Failed to create transaction record.");
        }

        // Step 3: Update inventory for each item
        $updateStmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
        foreach ($items as $item) {
            $product_name = $item['name'];
            $quantity_to_sell = (int)$item['quantity'];

            // Fetch lots for this product (FIFO - First In, First Out)
            $fetchLotsStmt = $conn->prepare(
                "SELECT id, stock FROM products 
                 WHERE name = ? AND stock > 0 AND (expiration_date > CURDATE() OR expiration_date IS NULL)
                 ORDER BY expiration_date ASC"
            );
            $fetchLotsStmt->bind_param("s", $product_name);
            $fetchLotsStmt->execute();
            $lots = $fetchLotsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $fetchLotsStmt->close();

            $total_available_stock = array_sum(array_column($lots, 'stock'));
            if ($quantity_to_sell > $total_available_stock) {
                throw new Exception("Insufficient stock for product: " . htmlspecialchars($product_name) . ". Requested: " . $quantity_to_sell);
            }

            $quantity_remaining_to_sell = $quantity_to_sell;
            foreach ($lots as $lot) {
                if ($quantity_remaining_to_sell <= 0) break;

                $stock_in_this_lot = (int)$lot['stock'];
                $stock_to_take_from_lot = min($quantity_remaining_to_sell, $stock_in_this_lot);
                
                $new_stock = $stock_in_this_lot - $stock_to_take_from_lot;

                $updateStmt->bind_param("ii", $new_stock, $lot['id']);
                $updateStmt->execute();

                $quantity_remaining_to_sell -= $stock_to_take_from_lot;
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
        
        // Log the activity after successful transaction
        $totalItemsSold = array_sum(array_column($items, 'quantity'));
        $productNames = [];
        foreach ($items as $item) {
            $productNames[] = $item['name'] . " (x" . $item['quantity'] . ")";
        }
        $logMessage = "Processed a sale of " . $totalItemsSold . " item(s) for customer '" . $customerName . "': " . implode(', ', $productNames) . ". Total: ₱" . number_format($totalAmount, 2);
        logUserActivity($conn, $logMessage);
        
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

    // Get transaction details including payment information
    $transStmt = $conn->prepare("SELECT payment_method, cash_amount, change_amount, subtotal, discount_amount FROM transactions WHERE id = ?");
    $transStmt->bind_param("i", $transactionId);
    $transStmt->execute();
    $transResult = $transStmt->get_result();
    $transactionData = $transResult->fetch_assoc();
    $transStmt->close();
    
    // Get purchased items
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

    // Combine items with transaction data
    $response = [
        'success' => true, 
        'items' => $items,
        'payment_method' => $transactionData['payment_method'] ?? 'cash',
        'cash_amount' => $transactionData['cash_amount'] ?? 0,
        'change_amount' => $transactionData['change_amount'] ?? 0,
        'subtotal' => $transactionData['subtotal'] ?? 0,
        'discount' => $transactionData['discount_amount'] ?? 0
    ];
    
    echo json_encode($response);
}

$conn->close();
?>