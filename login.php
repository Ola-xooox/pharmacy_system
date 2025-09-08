<?php
session_start();
// Make sure you have a db_connect.php file that establishes a connection ($conn) to your database.
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // --- SECURITY WARNING ---
    // Storing and comparing passwords in plain text is not secure.
    // It is highly recommended to use password_hash() and password_verify().
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
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
            background: linear-gradient(to right, #01A74F, #385d35);
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
            box-shadow: 0 0 0 3px rgba(1, 167, 79, 0.2);
            border-color: #01A74F; 
            background-color: #ffffff;
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
    </style>
</head>
<body>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-4xl mx-auto rounded-2xl shadow-2xl flex overflow-hidden">
            
            <div class="w-1/2 bg-white/20 backdrop-blur-lg p-12 text-white hidden md:flex flex-col justify-center items-center text-center">
                 <img src="mjpharmacy.logo.jpg" alt="MJ Pharmacy Logo" class="w-28 h-28 mx-auto rounded-full object-cover border-4 border-white/50">
                 <h1 class="text-3xl font-bold mt-6">MJ Pharmacy</h1>
                 <p class="mt-2 text-gray-200">Innovation Starts Here</p>
                 <p class="text-sm text-gray-300 mt-8 leading-relaxed">
                   Secure access to MJ Pharmacy’s management system. Log in to continue.
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