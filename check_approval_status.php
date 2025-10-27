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

// Check approval status with timestamp
$stmt = $conn->prepare("SELECT id, status, requested_at FROM login_approvals WHERE user_id = ? ORDER BY requested_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$approval = $result->fetch_assoc();
$stmt->close();

if ($approval) {
    // Check if request has timed out (1 minute = 60 seconds)
    $requestedTime = strtotime($approval['requested_at']);
    $currentTime = time();
    $elapsedSeconds = $currentTime - $requestedTime;
    
    if ($approval['status'] === 'pending' && $elapsedSeconds >= 60) {
        // Update status to 'no_response' when timeout occurs
        $updateStmt = $conn->prepare("UPDATE login_approvals SET status = 'no_response', reviewed_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $approval['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Log the timeout
        $logStmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_description, timestamp) VALUES (?, ?, NOW())");
        $logAction = "Login approval request timed out (no response)";
        $logStmt->bind_param("is", $userId, $logAction);
        $logStmt->execute();
        $logStmt->close();
        
        echo json_encode(['status' => 'no_response']);
    } else {
        echo json_encode(['status' => $approval['status']]);
    }
} else {
    echo json_encode(['status' => 'pending']);
}

$conn->close();
?>
