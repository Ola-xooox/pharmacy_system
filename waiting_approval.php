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
            background: linear-gradient(135deg, #22C55E 0%, #16A34A 25%, #15803D 50%, #166534 75%, #14532D 100%);
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23ffffff' fill-opacity='0.2' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,117.3C1248,117,1344,139,1392,149.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3Cpath fill='%23ffffff' fill-opacity='0.3' d='M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,218.7C672,235,768,245,864,240C960,235,1056,213,1152,197.3C1248,181,1344,171,1392,165.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3Cpath fill='%23ffffff' fill-opacity='0.4' d='M0,256L48,245.3C96,235,192,213,288,208C384,203,480,213,576,213.3C672,213,768,203,864,208C960,213,1056,235,1152,240C1248,245,1344,235,1392,229.3L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E") no-repeat bottom;
            background-size: cover;
            animation: wave 15s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes wave {
            0%, 100% {
                transform: translateX(0px) translateY(0px) scale(1);
            }
            25% {
                transform: translateX(-20px) translateY(-10px) scale(1.02);
            }
            50% {
                transform: translateX(20px) translateY(-20px) scale(1.04);
            }
            75% {
                transform: translateX(-10px) translateY(-5px) scale(1.02);
            }
        }

        .content-wrapper {
            position: relative;
            z-index: 2;
        }

        /* Floating particles animation */
        .particle {
            position: absolute;
            background: rgba(234, 179, 8, 0.3);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
            box-shadow: 0 0 20px rgba(234, 179, 8, 0.2);
        }

        .particle:nth-child(1) {
            width: 8px;
            height: 8px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 12px;
            height: 12px;
            top: 60%;
            left: 80%;
            animation-delay: 2s;
        }

        .particle:nth-child(3) {
            width: 6px;
            height: 6px;
            top: 80%;
            left: 20%;
            animation-delay: 4s;
        }

        .particle:nth-child(4) {
            width: 10px;
            height: 10px;
            top: 40%;
            left: 70%;
            animation-delay: 1s;
        }

        .particle:nth-child(5) {
            width: 8px;
            height: 8px;
            top: 10%;
            left: 60%;
            animation-delay: 3s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.7;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 1;
            }
        }

        /* Bubble particles */
        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            animation: bubble 8s infinite ease-in-out;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .bubble:nth-child(6) {
            width: 15px;
            height: 15px;
            top: 85%;
            left: 15%;
            animation-delay: 0s;
        }

        .bubble:nth-child(7) {
            width: 20px;
            height: 20px;
            top: 90%;
            left: 45%;
            animation-delay: 2s;
        }

        .bubble:nth-child(8) {
            width: 12px;
            height: 12px;
            top: 95%;
            left: 75%;
            animation-delay: 4s;
        }

        .bubble:nth-child(9) {
            width: 18px;
            height: 18px;
            top: 88%;
            left: 25%;
            animation-delay: 1s;
        }

        .bubble:nth-child(10) {
            width: 14px;
            height: 14px;
            top: 92%;
            left: 65%;
            animation-delay: 3s;
        }

        .bubble:nth-child(11) {
            width: 16px;
            height: 16px;
            top: 87%;
            left: 85%;
            animation-delay: 5s;
        }

        @keyframes bubble {
            0% {
                transform: translateY(0px) scale(0);
                opacity: 0;
            }
            10% {
                transform: translateY(-20px) scale(1);
                opacity: 0.8;
            }
            90% {
                transform: translateY(-100vh) scale(1);
                opacity: 0.8;
            }
            100% {
                transform: translateY(-100vh) scale(0);
                opacity: 0;
            }
        }

        /* Pharmacy-themed effects */
        .pill {
            position: absolute;
            background: linear-gradient(135deg, #EAB308, #F59E0B);
            border-radius: 20px;
            animation: pill-float 12s infinite ease-in-out;
            box-shadow: 0 2px 8px rgba(234, 179, 8, 0.3);
        }

        .pill::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 50%;
            bottom: 0;
            background: linear-gradient(135deg, #22C55E, #16A34A);
            border-radius: 20px 0 0 20px;
        }

        .pill:nth-child(12) {
            width: 35px;
            height: 18px;
            top: 25%;
            left: 5%;
            animation-delay: 0s;
        }

        .pill:nth-child(13) {
            width: 30px;
            height: 15px;
            top: 45%;
            left: 90%;
            animation-delay: 4s;
        }

        .pill:nth-child(14) {
            width: 32px;
            height: 16px;
            top: 65%;
            left: 8%;
            animation-delay: 8s;
        }

        @keyframes pill-float {
            0%, 100% {
                transform: translateX(0px) translateY(0px) rotate(0deg);
                opacity: 0.6;
            }
            25% {
                transform: translateX(20px) translateY(-15px) rotate(45deg);
                opacity: 0.8;
            }
            50% {
                transform: translateX(-10px) translateY(-30px) rotate(90deg);
                opacity: 1;
            }
            75% {
                transform: translateX(15px) translateY(-15px) rotate(135deg);
                opacity: 0.8;
            }
        }

        /* Medical cross particles */
        .medical-cross {
            position: absolute;
            color: rgba(255, 255, 255, 0.4);
            font-size: 24px;
            animation: cross-pulse 6s infinite ease-in-out;
        }

        .medical-cross:nth-child(15) {
            top: 15%;
            left: 85%;
            animation-delay: 0s;
        }

        .medical-cross:nth-child(16) {
            top: 75%;
            left: 12%;
            animation-delay: 2s;
        }

        .medical-cross:nth-child(17) {
            top: 35%;
            left: 92%;
            animation-delay: 4s;
        }

        @keyframes cross-pulse {
            0%, 100% {
                transform: scale(1) rotate(0deg);
                opacity: 0.4;
            }
            50% {
                transform: scale(1.3) rotate(180deg);
                opacity: 0.8;
            }
        }

        /* Heartbeat pulse effect on logo */
        .heartbeat {
            animation: heartbeat 2s infinite ease-in-out;
        }

        @keyframes heartbeat {
            0%, 100% {
                transform: scale(1);
            }
            14% {
                transform: scale(1.05);
            }
            28% {
                transform: scale(1);
            }
            42% {
                transform: scale(1.05);
            }
            70% {
                transform: scale(1);
            }
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
        
        .btn-cancel {
            width: 100%;
            background: #e5e7eb;
            color: #374151;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-cancel:hover {
            background: #d1d5db;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <!-- Floating particles -->
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    
    <!-- Bubble particles -->
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    
    <!-- Pharmacy-themed elements -->
    <div class="pill"></div>
    <div class="pill"></div>
    <div class="pill"></div>
    <div class="medical-cross">✚</div>
    <div class="medical-cross">✚</div>
    <div class="medical-cross">✚</div>

    <div class="content-wrapper min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-4xl mx-auto rounded-2xl shadow-2xl flex overflow-hidden">
            
            <div class="w-1/2 bg-white/20 backdrop-blur-lg p-12 text-white hidden md:flex flex-col justify-center items-center text-center">
                <img src="mjpharmacy.logo.jpg" alt="MJ Pharmacy Logo" class="w-28 h-28 mx-auto rounded-full object-cover border-4 border-white/50 heartbeat">
                <h1 class="text-3xl font-bold mt-6">MJ Pharmacy</h1>
                <p class="mt-2 text-gray-200">Innovation Starts Here</p>
                <p class="text-sm text-gray-300 mt-8 leading-relaxed">
                    Your login request is being reviewed by our administrative team. Please wait for approval.
                </p>
            </div>

            <div class="w-full md:w-1/2 p-8 sm:p-12 bg-white text-gray-800">
                <div class="text-center md:hidden mb-8">
                    <img src="mjpharmacy.logo.jpg" alt="MJ Pharmacy Logo" class="w-20 h-20 mx-auto rounded-full object-cover">
                </div>
                
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 spin-animation">
                        <i class="fas fa-clock text-4xl text-green-600"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-700 mb-1">Waiting for Approval</h2>
                    <p class="text-gray-600">Your login request is pending admin approval</p>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start mb-3">
                        <i class="fas fa-info-circle text-green-600 mt-1 mr-3"></i>
                        <div>
                            <p class="text-sm text-green-900 font-semibold">Login Request Details</p>
                            <p class="text-xs text-green-700 mt-1">
                                <strong>Email:</strong> <?php echo htmlspecialchars($email); ?><br>
                                <strong>Role:</strong> <?php echo strtoupper(htmlspecialchars($role)); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-center mb-6">
                    <div class="flex justify-center items-center space-x-2 mb-3">
                        <div class="w-3 h-3 bg-green-600 rounded-full pulse-animation"></div>
                        <div class="w-3 h-3 bg-green-600 rounded-full pulse-animation" style="animation-delay: 0.2s;"></div>
                        <div class="w-3 h-3 bg-green-600 rounded-full pulse-animation" style="animation-delay: 0.4s;"></div>
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
                <a href="index.php" class="btn-cancel">
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
                
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500">© 2025 MJ Pharmacy. All rights reserved.</p>
                </div>
            </div>

        </div>
    </div>

    <script>
        let checkCount = 0;
        const maxChecks = 60;
        
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
