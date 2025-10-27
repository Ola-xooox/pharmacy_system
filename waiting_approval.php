<?php
session_start();
require 'db_connect.php';

// Check if user has a pending approval
if (!isset($_SESSION['approval_pending_user_id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['approval_pending_user_id'];
$email = $_SESSION['approval_pending_email'];
$role = $_SESSION['approval_pending_role'];

// Check approval status
$stmt = $conn->prepare("SELECT status, reviewed_at FROM login_approvals WHERE user_id = ? ORDER BY requested_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$approval = $result->fetch_assoc();
$stmt->close();

if ($approval) {
    if ($approval['status'] === 'approved') {
        // Get full user details for session
        $userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Handle name field
        if (isset($user['name'])) {
            $_SESSION['name'] = $user['name'];
        } else if (isset($user['first_name']) && isset($user['last_name'])) {
            $name = $user['first_name'];
            if (isset($user['middle_name']) && !empty($user['middle_name'])) {
                $name .= ' ' . $user['middle_name'];
            }
            $name .= ' ' . $user['last_name'];
            $_SESSION['name'] = $name;
        } else {
            $_SESSION['name'] = $user['username'];
        }
        
        $_SESSION['profile_image'] = $user['profile_image'];
        
        // Log the login activity
        $logStmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_description, timestamp) VALUES (?, ?, NOW())");
        $loginAction = ucfirst($user['role']) . " System: User logged in successfully after approval";
        $logStmt->bind_param("is", $user['id'], $loginAction);
        $logStmt->execute();
        $logStmt->close();
        
        // Clean up approval session data
        unset($_SESSION['approval_pending_user_id']);
        unset($_SESSION['approval_pending_email']);
        unset($_SESSION['approval_pending_role']);
        
        // Redirect based on role
        switch ($user['role']) {
            case 'pos':
                header("Location: pos/pos.php");
                break;
            case 'inventory':
                header("Location: inventory/products.php");
                break;
            case 'cms':
                header("Location: cms/customer_history.php");
                break;
            default:
                header("Location: index.php");
                break;
        }
        exit();
    } elseif ($approval['status'] === 'declined') {
        // Clean up session and redirect to login
        session_destroy();
        header("Location: index.php?declined=1");
        exit();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting for Approval - MJ Pharmacy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .spin-animation {
            animation: spin 2s linear infinite;
        }
    </style>
</head>
<body>
    <div class="w-full max-w-md mx-auto p-6">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-8 text-center">
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 spin-animation">
                    <i class="fas fa-clock text-4xl text-purple-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">Waiting for Approval</h1>
                <p class="text-purple-100 text-sm">Your login request is pending admin approval</p>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start mb-3">
                        <i class="fas fa-info-circle text-purple-600 mt-1 mr-3"></i>
                        <div>
                            <p class="text-sm text-purple-900 font-semibold">Login Request Details</p>
                            <p class="text-xs text-purple-700 mt-1">
                                <strong>Email:</strong> <?php echo htmlspecialchars($email); ?><br>
                                <strong>Role:</strong> <?php echo strtoupper(htmlspecialchars($role)); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-center mb-6">
                    <div class="flex justify-center items-center space-x-2 mb-3">
                        <div class="w-3 h-3 bg-purple-600 rounded-full pulse-animation"></div>
                        <div class="w-3 h-3 bg-purple-600 rounded-full pulse-animation" style="animation-delay: 0.2s;"></div>
                        <div class="w-3 h-3 bg-purple-600 rounded-full pulse-animation" style="animation-delay: 0.4s;"></div>
                    </div>
                    <p class="text-gray-600 text-sm">
                        Please wait while an administrator reviews your login request.<br>
                        This page will automatically refresh when a decision is made.
                    </p>
                </div>

                <!-- Status Message -->
                <div id="status-message" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-center">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-hourglass-half mr-2"></i>
                        Checking approval status...
                    </p>
                </div>

                <!-- Cancel Button -->
                <a href="index.php" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 px-6 rounded-lg transition-colors duration-200 inline-flex items-center justify-center">
                    <i class="fas fa-times mr-2"></i>
                    Cancel & Return to Login
                </a>

                <!-- Information Box -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 text-center">
                        <i class="fas fa-shield-alt text-green-600"></i>
                        This security measure ensures only authorized personnel access the system.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let checkCount = 0;
        const maxChecks = 600; // 10 minutes (600 checks * 1 second)
        
        function checkApprovalStatus() {
            checkCount++;
            
            if (checkCount >= maxChecks) {
                document.getElementById('status-message').innerHTML = `
                    <p class="text-sm text-red-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Request timed out. Please try logging in again.
                    </p>
                `;
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 3000);
                return;
            }
            
            // Check every second
            fetch('check_approval_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'approved') {
                        document.getElementById('status-message').innerHTML = `
                            <p class="text-sm text-green-800">
                                <i class="fas fa-check-circle mr-2"></i>
                                Approved! Redirecting to your dashboard...
                            </p>
                        `;
                        document.getElementById('status-message').className = 'bg-green-50 border border-green-200 rounded-lg p-3 mb-4 text-center';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else if (data.status === 'declined') {
                        document.getElementById('status-message').innerHTML = `
                            <p class="text-sm text-red-800">
                                <i class="fas fa-times-circle mr-2"></i>
                                Your login request was declined. Redirecting...
                            </p>
                        `;
                        document.getElementById('status-message').className = 'bg-red-50 border border-red-200 rounded-lg p-3 mb-4 text-center';
                        setTimeout(() => {
                            window.location.href = 'index.php?declined=1';
                        }, 2000);
                    } else {
                        // Still pending, update status message
                        const minutes = Math.floor(checkCount / 60);
                        const seconds = checkCount % 60;
                        document.getElementById('status-message').innerHTML = `
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-hourglass-half mr-2"></i>
                                Waiting for approval... (${minutes}:${seconds.toString().padStart(2, '0')})
                            </p>
                        `;
                        setTimeout(checkApprovalStatus, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                    setTimeout(checkApprovalStatus, 2000);
                });
        }
        
        // Start checking after page load
        setTimeout(checkApprovalStatus, 1000);
    </script>
</body>
</html>
