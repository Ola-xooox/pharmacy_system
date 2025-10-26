<?php
session_start();
require 'db_connect.php';

// Check if user has pending approval
if (!isset($_SESSION['pending_approval_user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['pending_approval_user_id'];
$user_email = $_SESSION['pending_approval_email'] ?? '';
$user_name = $_SESSION['pending_approval_name'] ?? '';

// Check current approval status
$stmt = $conn->prepare("SELECT approval_status, approval_time, approval_notes FROM user_approvals WHERE user_id = ? ORDER BY login_time DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$approval = $result->fetch_assoc();
$stmt->close();

if ($approval) {
    if ($approval['approval_status'] === 'approved') {
        // User has been approved, redirect to success page
        $_SESSION['approval_success'] = true;
        header("Location: approval_success.php");
        exit();
    } elseif ($approval['approval_status'] === 'disapproved') {
        // User has been disapproved
        $_SESSION['approval_denied'] = true;
        $_SESSION['denial_reason'] = $approval['approval_notes'];
        header("Location: index.php");
        exit();
    }
}

// Auto-refresh every 5 minutes to check for approval updates
$refresh_interval = 300;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting for Approval - MJ Pharmacy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta http-equiv="refresh" content="<?php echo $refresh_interval; ?>">
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
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23ffffff' fill-opacity='0.2' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,117.3C1248,117,1344,139,1392,149.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E") no-repeat bottom;
            background-size: cover;
            animation: wave 15s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes wave {
            0%, 100% { transform: translateX(0px) translateY(0px) scale(1); }
            25% { transform: translateX(-20px) translateY(-10px) scale(1.02); }
            50% { transform: translateX(20px) translateY(-20px) scale(1.04); }
            75% { transform: translateX(-10px) translateY(-5px) scale(1.02); }
        }

        .content-wrapper {
            position: relative;
            z-index: 2;
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #22C55E;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .countdown {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="content-wrapper min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-2xl p-8 text-center">
            
            <!-- Logo -->
            <div class="mb-6">
                <img src="mjpharmacy.logo.jpg" alt="MJ Pharmacy Logo" class="w-20 h-20 mx-auto rounded-full object-cover border-4 border-green-200">
            </div>

            <!-- Waiting Icon -->
            <div class="mb-6">
                <div class="w-16 h-16 mx-auto bg-yellow-100 rounded-full flex items-center justify-center pulse-animation">
                    <i class="fas fa-clock text-3xl text-yellow-600"></i>
                </div>
            </div>

            <!-- Title -->
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Waiting for Approval</h1>
            <p class="text-gray-600 mb-6">Your login request is being reviewed by an administrator</p>

            <!-- User Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-center mb-2">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                        <?php echo strtoupper(substr($user_name ?: $user_email, 0, 2)); ?>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($user_name ?: 'User'); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user_email); ?></p>
                    </div>
                </div>
            </div>

            <!-- Status Message -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <div class="spinner mr-3"></div>
                    <div class="text-left">
                        <p class="text-sm font-medium text-yellow-800">Status: Pending Review</p>
                        <p class="text-xs text-yellow-600">An administrator will review your request shortly</p>
                    </div>
                </div>
            </div>

            <!-- Auto-refresh countdown -->
            <div class="mb-6">
                <p class="text-sm text-gray-500 mb-2">Page will refresh automatically in:</p>
                <div class="countdown text-2xl text-green-600" id="countdown"><?php echo $refresh_interval; ?></div>
                <p class="text-xs text-gray-400">seconds</p>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <button onclick="window.location.reload()" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>Check Status Now
                </button>
                <a href="index.php" class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Login
                </a>
            </div>

            <!-- Help Text -->
            <div class="mt-6 text-xs text-gray-500">
                <p>If you've been waiting for an extended period, please contact your system administrator.</p>
            </div>
        </div>
    </div>

    <script>
        // Countdown timer
        let timeLeft = <?php echo $refresh_interval; ?>;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            timeLeft--;
            countdownElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                countdownElement.textContent = 'Refreshing...';
            }
        }, 1000);

        // Check approval status via AJAX every 5 seconds
        setInterval(() => {
            fetch('check_approval_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'approved') {
                        window.location.href = 'approval_success.php';
                    } else if (data.status === 'disapproved') {
                        window.location.href = 'index.php';
                    }
                })
                .catch(error => console.log('Status check failed:', error));
        }, 5000);
    </script>
</body>
</html>
