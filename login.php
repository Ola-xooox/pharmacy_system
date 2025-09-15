<?php
session_start();
// Make sure you have a db_connect.php file that establishes a connection ($conn) to your database.
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Get user by username only
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Check if password is hashed (starts with $2y$ for bcrypt)
        $passwordValid = false;
        
        if (password_get_info($user['password'])['algo'] !== null) {
            // Password is hashed, use password_verify
            $passwordValid = password_verify($password, $user['password']);
        } else {
            // Password is plain text (for backward compatibility)
            $passwordValid = ($password === $user['password']);
        }
        
        if ($passwordValid) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Handle name field dynamically based on table structure
            if (isset($user['name'])) {
                $_SESSION['name'] = $user['name'];
            } else if (isset($user['first_name']) && isset($user['last_name'])) {
                $name = $user['first_name'] . ' ' . $user['last_name'];
                if (isset($user['middle_name']) && !empty($user['middle_name'])) {
                    $name = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'];
                }
                $_SESSION['name'] = $name;
            } else {
                $_SESSION['name'] = $user['username']; // fallback to username
            }
            
            $_SESSION['profile_image'] = $user['profile_image'];

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
                case 'admin':
                    header("Location: admin portal/dashboard.php");
                    break;
                default:
                    header("Location: login.php");
                    break;
            }
            exit();
        }
    }
    
    echo "<script>alert('Invalid username or password'); window.location.href = 'login.php';</script>";
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
                <h2 class="text-2xl font-bold text-gray-700 mb-1">Sign In</h2>
                <p class="text-gray-600 mb-8">Sign in to Access your Account</p>

                <form id="loginForm" action="login.php" method="POST">
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" required>
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
                    </div>
                    
                    <div class="mt-8">
                        <button type="submit" class="btn-primary">
                            Sign In
                        </button>
                    </div>
                </form>
                
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500">© 2025 MJ Pharmacy. All rights reserved.</p>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');

            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                eyeOpen.classList.toggle('hidden');
                eyeClosed.classList.toggle('hidden');
            });
        });
    </script>

</body>
</html>