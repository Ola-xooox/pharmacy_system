<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// Check if user has a pending approval session
if (!isset($_SESSION['approval_pending_user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No pending approval']);
    exit();
}

$userId = $_SESSION['approval_pending_user_id'];

// Check approval status
$stmt = $conn->prepare("SELECT status FROM login_approvals WHERE user_id = ? ORDER BY requested_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$approval = $result->fetch_assoc();
$stmt->close();
$conn->close();

if ($approval) {
    echo json_encode(['status' => $approval['status']]);
} else {
    echo json_encode(['status' => 'pending']);
}
?>
