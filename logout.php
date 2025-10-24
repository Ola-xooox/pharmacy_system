<?php
session_start();

// Log the logout activity before destroying the session
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    require 'db_connect.php';
    
    $logStmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_description, timestamp) VALUES (?, ?, NOW())");
    $logoutAction = ucfirst($_SESSION['role']) . " System: User logged out successfully";
    $logStmt->bind_param("is", $_SESSION['user_id'], $logoutAction);
    $logStmt->execute();
    $logStmt->close();
    $conn->close();
}

session_unset();
session_destroy();
header("Location: index.php");
exit();
?>