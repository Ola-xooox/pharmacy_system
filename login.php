<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        // --- Important Lines Below ---
        $_SESSION['name'] = $user['name']; // Stores the user's full name
        $_SESSION['profile_image'] = $user['profile_image']; // Stores the profile image path

        switch ($user['role']) {
            case 'pos':
                header("Location: pos/pos.php");
                break;
            case 'inventory':
                header("Location: inventory/products.php");
                break;
            case 'cms':
                header("Location: cms/costumer.html");
                break;
            case 'admin':
                header("Location: admin portal/dashboard.php");
                break;
            default:
                header("Location: login.php");
                break;
        }
        exit();
    } else {
        echo "<script>alert('Invalid username or password'); window.location.href = 'login.php';</script>";
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
            background: linear-gradient(135deg, #eefcfd 0%, #d1f7fa 100%);
            min-height: 100vh;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            background: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-radius: 1rem;
            border: 1px solid #e5e7eb;
            width: 100%;
            max-width: 25rem;
            padding: 2.5rem;
        }
        
        .pharmacy-logo-bg {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1.5rem auto;
            border-radius: 50%;
            background-color: #e0f2f1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pharmacy-logo-svg {
            width: 2rem;
            height: 2rem;
            color: #01A74F;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 0.75rem;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }
        
        .form-input {
            width: 100%;
            padding: 0.65rem 1rem 0.65rem 2.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            transition: all 0.2s;
            background-color: #f9fafb;
        }
        
        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(1, 167, 79, 0.2);
            border-color: #01A74F;
            background-color: white;
        }
        
        .btn-primary {
            width: 100%;
            background-color: #01A74F;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background-color: #018d43;
            box-shadow: 0 4px 12px rgba(1, 167, 79, 0.2);
        }
        
        .btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(1, 167, 79, 0.4);
        }
        
        .footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        @media (max-width: 640px) {
            .login-container {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="text-center mb-8">
            <div class="pharmacy-logo-bg">
                <svg class="pharmacy-logo-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16l5-5 5 5" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">MJ Pharmacy</h1>
            <p class="text-gray-500 mt-2">Sign in to access your account</p>
        </div>
        
        <form id="loginForm" action="login.php" method="POST">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <input type="text" id="username" name="username" class="form-input" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                     <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
            </div>
            
            <div class="mt-8">
                <button type="submit" class="btn-primary">
                    Sign In
                </button>
            </div>
        </form>
        
        <div class="footer">
            <p>© 2025 MJ Pharmacy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>