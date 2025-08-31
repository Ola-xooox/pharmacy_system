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
    case 'log_sale':
        handleLogSale($conn);
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

    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'customers' => $customers,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'totalResults' => (int)$totalResults,
        'limit' => $limit
    ]);
}

function handleLogSale($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        return;
    }

    $customerName = trim($data['customer_name'] ?? '');
    $customerId = trim($data['customer_id'] ?? '');
    $totalAmount = $data['total_amount'] ?? 0;

    if (empty($customerName)) $customerName = 'Walk-in';
    if ($totalAmount <= 0) {
        echo json_encode(['success' => true, 'message' => 'No amount to log.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT id FROM customer_history WHERE customer_name = ? AND customer_id_no = ?");
        $stmt->bind_param("ss", $customerName, $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $historyId = null;

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $historyId = $row['id'];
            $updateStmt = $conn->prepare("UPDATE customer_history SET total_visits = total_visits + 1, total_spent = total_spent + ?, last_visit = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->bind_param("di", $totalAmount, $historyId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $insertStmt = $conn->prepare("INSERT INTO customer_history (customer_name, customer_id_no, total_visits, total_spent) VALUES (?, ?, 1, ?)");
            $insertStmt->bind_param("ssd", $customerName, $customerId, $totalAmount);
            $insertStmt->execute();
            $historyId = $insertStmt->insert_id;
            $insertStmt->close();
        }
        $stmt->close();

        if ($historyId) {
            $transStmt = $conn->prepare("INSERT INTO transactions (customer_history_id, total_amount) VALUES (?, ?)");
            $transStmt->bind_param("id", $historyId, $totalAmount);
            $transStmt->execute();
            $transStmt->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Customer history logged successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

$conn->close();
?>