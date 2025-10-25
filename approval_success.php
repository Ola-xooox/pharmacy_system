<?php
session_start();
require 'db_connect.php';

// Check if user should be here
if (!isset($_SESSION['approval_success']) || !isset($_SESSION['pending_approval_user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['pending_approval_user_id'];

// Get user details and complete the login process
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: index.php");
    exit();
}

// Set up the session for the approved user
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

// Handle name field
if (isset($user['first_name']) && isset($user['last_name'])) {
    $name = $user['first_name'];
    if ($user['middle_name']) {
        $name .= ' ' . $user['middle_name'];
    }
    $name .= ' ' . $user['last_name'];
    $_SESSION['name'] = $name;
} else {
    $_SESSION['name'] = $user['username'];
}

$_SESSION['profile_image'] = $user['profile_image'];

// Log the successful login
$logStmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_description, timestamp) VALUES (?, ?, NOW())");
$loginAction = ucfirst($user['role']) . " System: User logged in successfully (approved)";
$logStmt->bind_param("is", $user['id'], $loginAction);
$logStmt->execute();
$logStmt->close();

// Clean up approval session data
unset($_SESSION['pending_approval_user_id']);
unset($_SESSION['pending_approval_email']);
unset($_SESSION['pending_approval_name']);
unset($_SESSION['approval_success']);

// Determine redirect URL based on role
$redirectUrl = '';
switch ($user['role']) {
    case 'pos':
        $redirectUrl = 'pos/pos.php';
        break;
    case 'inventory':
        $redirectUrl = 'inventory/products.php';
        break;
    case 'cms':
        $redirectUrl = 'cms/customer_history.php';
        break;
    case 'admin':
        $redirectUrl = 'admin_portal/dashboard.php';
        break;
    default:
        $redirectUrl = 'admin_portal/dashboard.php';
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Success - MJ Pharmacy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #22C55E 0%, #16A34A 25%, #15803D 50%, #166534 75%, #14532D 100%);
            position: relative;
            overflow: hidden;
        }

        .content-wrapper {
            position: relative;
            z-index: 2;
        }

        .success-animation {
            animation: successPulse 1.5s ease-in-out;
        }

        @keyframes successPulse {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }

        .checkmark {
            animation: checkmarkDraw 1s ease-in-out 0.5s both;
        }

        @keyframes checkmarkDraw {
            0% { stroke-dasharray: 0 100; }
            100% { stroke-dasharray: 100 0; }
        }

        .modal-backdrop {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 modal-backdrop z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 text-center modal-content">
            
            <!-- Success Icon -->
            <div class="mb-6 success-animation">
                <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path class="checkmark" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>

            <!-- Success Message -->
            <h1 class="text-2xl font-bold text-gray-900 mb-2">🎉 Approval Successful!</h1>
            <p class="text-gray-600 mb-6">Your login request has been approved by the administrator.</p>

            <!-- User Welcome -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-center mb-2">
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 2)); ?>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
                        <p class="text-sm text-gray-600 capitalize"><?php echo htmlspecialchars($user['role']); ?> Access Granted</p>
                    </div>
                </div>
            </div>

            <!-- Success Details -->
            <div class="text-left bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex items-center mb-2">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span class="text-sm text-gray-700">Account verified and activated</span>
                </div>
                <div class="flex items-center mb-2">
                    <i class="fas fa-key text-green-500 mr-2"></i>
                    <span class="text-sm text-gray-700">System access permissions granted</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-green-500 mr-2"></i>
                    <span class="text-sm text-gray-700">Secure login session established</span>
                </div>
            </div>

            <!-- Redirect Info -->
            <div class="mb-6">
                <p class="text-sm text-gray-500 mb-2">You will be redirected to your dashboard in:</p>
                <div class="text-3xl font-bold text-green-600" id="countdown">5</div>
                <p class="text-xs text-gray-400">seconds</p>
            </div>

            <!-- Action Button -->
            <button onclick="redirectNow()" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors mb-4">
                <i class="fas fa-arrow-right mr-2"></i>Enter System Now
            </button>

            <!-- Additional Info -->
            <p class="text-xs text-gray-500">
                You can now access all features available to your role.
            </p>
        </div>
    </div>

    <script>
        let timeLeft = 5;
        const countdownElement = document.getElementById('countdown');
        const redirectUrl = '<?php echo $redirectUrl; ?>';
        
        // Countdown timer
        const timer = setInterval(() => {
            timeLeft--;
            countdownElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                redirectToSystem();
            }
        }, 1000);

        function redirectNow() {
            clearInterval(timer);
            redirectToSystem();
        }

        function redirectToSystem() {
            // Add a nice transition effect
            document.getElementById('successModal').style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 300);
        }

        // Prevent accidental navigation away
        window.addEventListener('beforeunload', function(e) {
            if (timeLeft > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>

    <style>
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</body>
</html>
