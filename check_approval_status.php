<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// Check if user has pending approval session
if (!isset($_SESSION['pending_approval_user_id'])) {
    echo json_encode(['status' => 'no_session']);
    exit();
}

$user_id = $_SESSION['pending_approval_user_id'];

// Check current approval status
$stmt = $conn->prepare("SELECT approval_status, approval_time, approval_notes FROM user_approvals WHERE user_id = ? ORDER BY login_time DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$approval = $result->fetch_assoc();
$stmt->close();

if ($approval) {
    echo json_encode([
        'status' => $approval['approval_status'],
        'approval_time' => $approval['approval_time'],
        'notes' => $approval['approval_notes']
    ]);
} else {
    echo json_encode(['status' => 'pending']);
}
?>
