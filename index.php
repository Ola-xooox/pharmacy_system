<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db_connect.php';
require_once 'gmail_config.php';

// Use PHPMailer for professional email delivery
require_once 'phpmailer_otp.php';
$otpMailer = new PHPMailerOTP($conn);

// IP Access Control Functions
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function isAuthorizedIP($userRole) {
    $userIP = getUserIP();
    
    // Admin users can access from any IP
    if ($userRole === 'admin') {
        return true;
    }
    
    // POS, CMS, and Inventory users must be from authorized IPs
    if (in_array($userRole, ['pos', 'cms', 'inventory'])) {
        // Allow WiFi network IP
        if ($userIP === '192.168.100.142') {
            return true;
        }
        
        // Allow your ISP's IP range (112.203.x.x)
        if (preg_match('/^112\.203\.\d+\.\d+$/', $userIP)) {
            return true;
        }
    }
    
    return false;
}

$error = '';
$success_message = '';
$step = 'login'; // 'login' or 'otp'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'login_with_otp') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($email) || empty($password)) {
                $error = "Please enter both email and password.";
            } else {
                // Check if user exists with this email and password
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $user = null;
                $passwordValid = false;
                
                // Check all users with this email to find the one with matching password
                while ($row = $result->fetch_assoc()) {
                    if (password_get_info($row['password'])['algo'] !== null) {
                        $valid = password_verify($password, $row['password']);
                    } else {
                        $valid = ($password === $row['password']);
                    }
                    
                    if ($valid) {
                        $user = $row;
                        $passwordValid = true;
                        break;
                    }
                }
                
                if ($user && $passwordValid) {
                    // Check IP authorization for non-admin users
                    if (!isAuthorizedIP($user['role'])) {
                        $userIP = getUserIP();
                        
                        // Log unauthorized access attempt
                        $logStmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_description, timestamp) VALUES (?, ?, NOW())");
                        $logAction = ucfirst($user['role']) . " System: Unauthorized access attempt from IP: $userIP";
                        $logStmt->bind_param("is", $user['id'], $logAction);
                        $logStmt->execute();
                        $logStmt->close();
                        
                        // Redirect to access denied page
                        header("Location: access_denied.php?module=" . $user['role']);
                        exit();
                    } else {
                        // Check if OTP was recently sent (within last 30 seconds) to prevent spam
                        $recentOtp = false;
                        if (isset($_SESSION['last_otp_time']) && isset($_SESSION['last_otp_email'])) {
                            if ($_SESSION['last_otp_email'] === $email && (time() - $_SESSION['last_otp_time']) < 30) {
                                $recentOtp = true;
                            }
                        }
                        
                        if ($recentOtp) {
                            // OTP was sent recently, just show the existing one
                            $_SESSION['otp_email'] = $email;
                            $_SESSION['pending_user'] = $user;
                            $_SESSION['otp_success'] = "An OTP was recently sent to your email. Please check your inbox or use the backup codes below.";
                            header("Location: index.php");
                            exit();
                        }
                        
                        // Send OTP (the method will generate and store the OTP automatically)
                        $otpResult = $otpMailer->sendOTP($email);
                        
                        if ($otpResult !== false) {
                            // Always proceed to OTP step if OTP is stored
                            $_SESSION['otp_email'] = $email;
                            $_SESSION['pending_user'] = $user;
                            $_SESSION['last_otp_time'] = time();
                            $_SESSION['last_otp_email'] = $email;
                            
                            if ($otpResult === true) {
                                $_SESSION['otp_success'] = "OTP sent to your email successfully! Please check your inbox.";
                            } else {
                                $_SESSION['otp_success'] = "OTP generated successfully! Your backup codes are displayed below - use any of them to complete login.";
                            }
                            
                            // Redirect to prevent form resubmission on refresh (Post/Redirect/Get pattern)
                            header("Location: index.php");
                            exit();
                        } else {
                            $error = "Failed to generate OTP. Please try again.";
                        }
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            }
        } elseif ($_POST['action'] == 'verify_otp') {
            $otp = trim($_POST['otp']);
            $email = $_SESSION['otp_email'] ?? '';
            
            if (empty($otp)) {
                $error = "Please enter the OTP code.";
                $step = 'otp';
            } else {
                if ($otpMailer->verifyOTP($email, $otp)) {
                    $user = $_SESSION['pending_user'];
                    
                    // All users can login directly after OTP verification
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
                    $loginAction = ucfirst($user['role']) . " System: User logged in successfully";
                    $logStmt->bind_param("is", $user['id'], $loginAction);
                    $logStmt->execute();
                    $logStmt->close();
                    
                    // Clean up temporary session data
                    unset($_SESSION['otp_email']);
                    unset($_SESSION['pending_user']);
                    unset($_SESSION['last_otp_time']);
                    unset($_SESSION['last_otp_email']);
                    
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
                            header("Location: admin_portal/dashboard.php");
                            break;
                    }
                    exit();
                } else {
                    $error = "Invalid or expired OTP code.";
                    $step = 'otp';
                }
            }
        }
    }
}

// Handle back to login request
if (isset($_GET['back']) && $_GET['back'] == 'login') {
    unset($_SESSION['otp_email']);
    unset($_SESSION['pending_user']);
    unset($_SESSION['last_otp_time']);
    unset($_SESSION['last_otp_email']);
    $step = 'login';
    header("Location: index.php");
    exit();
}


// Check if we should show OTP step (only if explicitly set via login)
if (isset($_SESSION['otp_email']) && isset($_SESSION['pending_user'])) {
    $step = 'otp';
    // Display success message from session if available
    if (isset($_SESSION['otp_success'])) {
        $success_message = $_SESSION['otp_success'];
        unset($_SESSION['otp_success']); // Clear it after displaying
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 0.75rem;
            transform: translateY(-50%);
            color: #4a5568;
            pointer-events: none;
        }
        
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 0.75rem;
            transform: translateY(-50%);
            color: #6b7280;
            cursor: pointer;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #cdd4d1;
            border-radius: 0.5rem;
            transition: all 0.2s;
            background-color: #f0f4f2;
            color: #1a202c; 
        }
        
        .password-input {
            padding-right: 2.5rem;
        }

        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
            border-color: #22C55E; 
            background-color: #ffffff;
        }
        
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #22C55E 0%, #EAB308 100%);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #16A34A 0%, #D97706 100%);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); 
            transform: translateY(-1px);
        }
        
        .btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.4); 
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
                   Secure Access to MJ Pharmacy’s Management System. Log in to continue.
                 </p>
            </div>

            <div class="w-full md:w-1/2 p-8 sm:p-12 bg-white text-gray-800">
                <div class="text-center md:hidden mb-8">
                     <img src="mjpharmacy.logo.jpg" alt="MJ Pharmacy Logo" class="w-20 h-20 mx-auto rounded-full object-cover">
                </div>
                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($step == 'login'): ?>
                    <h2 class="text-2xl font-bold text-gray-700 mb-1">Sign In with Email + OTP</h2>
                    <p class="text-gray-600 mb-6">Enter your email and password to receive an OTP code</p>

                    <!-- Email + OTP Form -->
                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="login_with_otp">
                        
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <div class="input-wrapper">
                                <span class="input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    </svg>
                                </span>
                                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <div class="input-wrapper">
                                 <span class="input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                 </span>
                                <input type="password" id="password" name="password" class="form-input password-input" placeholder="Enter your password" required>
                                <span class="toggle-password" id="togglePassword">
                                    <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.022 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                    </svg>
                                    <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.955 9.955 0 00-4.542 1.071L3.707 2.293zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        <path d="M2 10s3.923-6 8-6 8 6 8 6-3.923 6-8 6-8-6-8-6z" />
                                    </svg>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">We'll send an OTP to your email after verifying your password</p>
                        </div>
                        
                        <div class="mt-8">
                            <button type="submit" class="btn-primary">
                                Send OTP Code
                            </button>
                        </div>
                    </form>

                <?php elseif ($step == 'otp'): ?>
                    <h2 class="text-2xl font-bold text-gray-700 mb-1">Enter OTP Code</h2>
                    <p class="text-gray-600 mb-6">We've sent a 6-digit code to <?php echo htmlspecialchars($_SESSION['otp_email'] ?? ''); ?></p>

                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="verify_otp">
                        
                        <div class="mb-6">
                            <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">OTP Code</label>
                            <div class="input-wrapper">
                                <span class="input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-0.257-0.257A6 6 0 1118 8zM10 4a2 2 0 100 4 2 2 0 000-4z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <input type="text" id="otp" name="otp" class="form-input text-center text-lg tracking-widest" placeholder="000000" maxlength="6" required>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Code expires in 5 minutes</p>
                        </div>
                        
                        <div class="mt-8">
                            <button type="submit" class="btn-primary">
                                Verify & Sign In
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <a href="index.php?back=login" class="text-sm text-gray-500 hover:text-gray-700">
                            ← Back to login
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500">© 2025 MJ Pharmacy. All rights reserved.</p>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Password toggle functionality
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');

            if (togglePassword) {
                togglePassword.addEventListener('click', function () {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    eyeOpen.classList.toggle('hidden');
                    eyeClosed.classList.toggle('hidden');
                });
            }

            // Auto-focus OTP input and format
            const otpInput = document.getElementById('otp');
            if (otpInput) {
                otpInput.focus();
                
                // Format OTP input (numbers only)
                otpInput.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                    if (value.length > 6) value = value.slice(0, 6); // Limit to 6 digits
                    e.target.value = value;
                });
            }
        });
    </script>

</body>
</html>