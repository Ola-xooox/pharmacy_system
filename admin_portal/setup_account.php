<?php
session_start();
// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
require '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Set content type for JSON response
        header('Content-Type: application/json');
        
        $lastName = $_POST['last_name'] ?? '';
        $firstName = $_POST['first_name'] ?? '';
        $middleName = $_POST['middle_name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        // Correctly combine the names into one string for the 'name' column
        $name = $lastName . ', ' . $firstName;
        if (!empty($middleName)) {
            $name .= ' ' . $middleName;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $roles = $_POST['roles'] ?? [];

        // Validate required fields
        if (empty($lastName) || empty($firstName) || empty($username) || empty($password) || empty($roles)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields and select at least one role.']);
            exit();
        }
        
        // Validate email if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit();
        }
        
        // Validate password length
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
            exit();
        }
        
        $role = $roles[0]; 

        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            throw new Exception("Database execute error: " . $stmt->error);
        }
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists. Please choose another one.']);
            $stmt->close();
            exit();
        }
        $stmt->close();
        
        // Hash the password for security
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Handle profile image upload
        $profileImagePath = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $targetDir = "../uploads/profiles/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true); // Ensure the directory exists
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $fileType = $_FILES['profile_image']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG and PNG files are allowed.']);
                exit();
            }
            
            // Validate file size (5MB max)
            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'File size too large. Maximum size is 5MB.']);
                exit();
            }
            
            $fileName = uniqid() . '_' . basename($_FILES["profile_image"]["name"]);
            $targetFile = $targetDir . $fileName;
            
            // Move the uploaded file
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFile)) {
                $profileImagePath = 'uploads/profiles/' . $fileName; // Store relative path
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload profile image.']);
                exit();
            }
        }

        // Insert new user with hashed password
        // First, let's check what columns exist in the users table
        $checkStmt = $conn->prepare("DESCRIBE users");
        $checkStmt->execute();
        $columns = $checkStmt->get_result();
        $columnNames = [];
        while ($col = $columns->fetch_assoc()) {
            $columnNames[] = $col['Field'];
        }
        $checkStmt->close();
        
        // Check if 'name' column exists, if not, use separate first_name, last_name columns
        if (in_array('name', $columnNames)) {
            if (in_array('email', $columnNames)) {
                $insertStmt = $conn->prepare("INSERT INTO users (name, username, email, password, role, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$insertStmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }
                $insertStmt->bind_param("ssssss", $name, $username, $email, $hashedPassword, $role, $profileImagePath);
            } else {
                $insertStmt = $conn->prepare("INSERT INTO users (name, username, password, role, profile_image) VALUES (?, ?, ?, ?, ?)");
                if (!$insertStmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }
                $insertStmt->bind_param("sssss", $name, $username, $hashedPassword, $role, $profileImagePath);
            }
        } else if (in_array('first_name', $columnNames) && in_array('last_name', $columnNames)) {
            // Use separate columns
            if (in_array('middle_name', $columnNames) && in_array('email', $columnNames)) {
                $insertStmt = $conn->prepare("INSERT INTO users (first_name, last_name, middle_name, username, email, password, role, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$insertStmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }
                $insertStmt->bind_param("ssssssss", $firstName, $lastName, $middleName, $username, $email, $hashedPassword, $role, $profileImagePath);
            } else if (in_array('middle_name', $columnNames)) {
                $insertStmt = $conn->prepare("INSERT INTO users (first_name, last_name, middle_name, username, password, role, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if (!$insertStmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }
                $insertStmt->bind_param("sssssss", $firstName, $lastName, $middleName, $username, $hashedPassword, $role, $profileImagePath);
            } else if (in_array('email', $columnNames)) {
                $insertStmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, password, role, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if (!$insertStmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }
                $insertStmt->bind_param("sssssss", $firstName, $lastName, $username, $email, $hashedPassword, $role, $profileImagePath);
            } else {
                $insertStmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, password, role, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$insertStmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }
                $insertStmt->bind_param("ssssss", $firstName, $lastName, $username, $hashedPassword, $role, $profileImagePath);
            }
        } else {
            // Log the actual column structure for debugging
            error_log("Users table columns: " . implode(', ', $columnNames));
            throw new Exception("Unknown table structure. Available columns: " . implode(', ', $columnNames));
        }

        if ($insertStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'New account created successfully!']);
        } else {
            throw new Exception("Database execution error: " . $insertStmt->error);
        }

        $insertStmt->close();
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Setup Account Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
    exit();
}

$currentPage = 'setup_account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Set Up Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include 'assets/admin_darkmode.php'; ?>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="icon" type="image/x-icon" href="../mjpharmacy.logo.jpg">
    <style>
        :root { --primary-green: #01A74F; --light-gray: #f3f4f6; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: #1f2937; }
        .sidebar { background-color: var(--primary-green); transition: width 0.3s ease-in-out, transform 0.3s ease-in-out; }
        @media (max-width: 767px) { .sidebar { width: 16rem; transform: translateX(-100%); position: fixed; height: 100%; z-index: 50; } .sidebar.open-mobile { transform: translateX(0); } .overlay { transition: opacity 0.3s ease-in-out; } }
        @media (min-width: 768px) { .sidebar { width: 5rem; } .sidebar.open-desktop { width: 16rem; } .sidebar .nav-text { opacity: 0; visibility: hidden; width: 0; transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease; white-space: nowrap; overflow: hidden; } .sidebar.open-desktop .nav-text { opacity: 1; visibility: visible; width: auto; transition: opacity 0.2s ease 0.1s; } .sidebar .nav-link { justify-content: center; gap: 0; } .sidebar.open-desktop .nav-link { justify-content: flex-start; gap: 1rem; } }
        .nav-link { color: rgba(255, 255, 255, 0.8); } .nav-link svg { color: white; } .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); } .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; } .nav-link.active svg { color: var(--primary-green); }
        
        /* Dark mode icon colors */
        .dark svg {
            color: white !important;
        }
        .dark .text-gray-400 {
            color: #d1d5db !important;
        }
        .dark .text-gray-500 {
            color: #9ca3af !important;
        }
        .dark i {
            color: white !important;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'admin_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_header.php'; ?>

        <main class="flex-1 overflow-y-auto p-8">
            <div id="page-content" class="w-full">
                <div id="setup-account-page" class="w-full">
                    <div class="bg-white p-10 rounded-2xl shadow-lg w-full">
                        <div class="mb-10 pb-6 border-b border-gray-200">
                            <h2 class="text-3xl font-bold text-gray-800">Create New User Account</h2>
                            <p class="text-sm text-gray-500 mt-2">Fill in the details below to set up a new user and assign their roles.</p>
                        </div>

                        <form id="setup-account-form" class="space-y-10">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">User Credentials</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                                    <div class="sm:col-span-1 flex flex-col items-center justify-center bg-gray-50 rounded-lg p-4 border-2 border-dashed">
                                        <img id="image-preview" class="w-24 h-24 mb-3 rounded-full object-cover hidden shadow-md" src="#" alt="Profile Preview">
                                        <div id="image-placeholder" class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                        </div>
                                        <label for="profile_image" class="mt-3 text-sm font-medium text-green-600 cursor-pointer hover:underline">Upload Profile Image</label>
                                        <input id="profile_image" name="profile_image" type="file" class="hidden" accept="image/*">
                                        <p class="text-xs text-gray-400 mt-1 text-center">Optional: PNG or JPG</p>
                                    </div>

                                    <div class="sm:col-span-2 grid grid-cols-1 gap-6 content-start">
                                        <div>
                                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                            <input type="text" id="last_name" name="last_name" class="block w-full rounded-md border-gray-300 shadow-sm p-2.5 focus:border-green-500 focus:ring-green-500" placeholder="Last Name" required>
                                        </div>
                                        <div>
                                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                            <input type="text" id="first_name" name="first_name" class="block w-full rounded-md border-gray-300 shadow-sm p-2.5 focus:border-green-500 focus:ring-green-500" placeholder="First Name" required>
                                        </div>
                                        <div>
                                            <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name (Optional)</label>
                                            <input type="text" id="middle_name" name="middle_name" class="block w-full rounded-md border-gray-300 shadow-sm p-2.5 focus:border-green-500 focus:ring-green-500" placeholder="Middle Name">
                                        </div>
                                        <div>
                                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address (Optional)</label>
                                            <div class="mt-1 relative">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                                    </svg>
                                                </div>
                                                <input type="email" id="email" name="email" class="block w-full rounded-md border-gray-300 shadow-sm pl-10 p-2.5 focus:border-green-500 focus:ring-green-500" placeholder="user@example.com">
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Required for OTP login functionality</p>
                                        </div>
                                        <div>
                                            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                            <div class="mt-1 relative"><div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"><svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></div><input type="text" id="username" name="username" class="block w-full rounded-md border-gray-300 shadow-sm pl-10 p-2.5 focus:border-green-500 focus:ring-green-500" placeholder="Username" required></div>
                                        </div>
                                        <div>
                                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                            <div class="mt-1 relative">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" /></svg>
                                                </div>
                                                <input type="password" id="password" name="password" class="block w-full rounded-md border-gray-300 shadow-sm pl-10 pr-10 p-2.5 focus:border-green-500 focus:ring-green-500" placeholder="••••••••" required>
                                                <button type="button" id="password-toggle-btn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                                    <i id="eye-icon" class="ph ph-eye text-xl"></i>
                                                    <i id="eye-slashed-icon" class="ph ph-eye-slash text-xl hidden"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Assign Roles</h3>
                                <p class="text-sm text-gray-500 mb-4">Note: Only one role will be assigned as the primary role.</p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <label class="border rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer transition-all duration-200 hover:bg-gray-50 peer-checked:bg-green-50 peer-checked:border-green-500 peer-checked:ring-2 peer-checked:ring-green-200"><input type="checkbox" name="roles[]" value="pos" class="sr-only peer"><svg class="h-8 w-8 text-gray-500 peer-checked:text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg><span class="font-semibold text-gray-700 mt-2 text-center text-sm">POS Access</span></label>
                                    <label class="border rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer transition-all duration-200 hover:bg-gray-50 peer-checked:bg-green-50 peer-checked:border-green-500 peer-checked:ring-2 peer-checked:ring-green-200"><input type="checkbox" name="roles[]" value="inventory" class="sr-only peer"><svg class="h-8 w-8 text-gray-500 peer-checked:text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4" /></svg><span class="font-semibold text-gray-700 mt-2 text-center text-sm">Inventory Access</span></label>
                                    <label class="border rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer transition-all duration-200 hover:bg-gray-50 peer-checked:bg-green-50 peer-checked:border-green-500 peer-checked:ring-2 peer-checked:ring-green-200"><input type="checkbox" name="roles[]" value="cms" class="sr-only peer"><svg class="h-8 w-8 text-gray-500 peer-checked:text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-4.663l.001.001M9 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg><span class="font-semibold text-gray-700 mt-2 text-center text-sm">Customer Mgmt.</span></label>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-200"><div class="flex justify-end"><button type="submit" class="inline-flex items-center gap-2 justify-center rounded-md border border-transparent bg-green-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"><svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd" /></svg>Create Account</button></div></div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>
    <div id="success-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden justify-center items-center"><div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-sm text-center"><div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100"><svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></div><h3 class="text-2xl font-bold text-gray-800 mt-4">Success!</h3><p class="text-gray-500 mt-2">The new user account has been created successfully.</p><button id="close-modal-btn" class="mt-6 w-full bg-green-600 text-white py-2 rounded-lg font-semibold hover:bg-green-700 transition-colors">OK</button></div></div>
        <div id="error-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-sm text-center">
        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mt-4">Error!</h3>
        <p id="error-message" class="text-gray-500 mt-2">An error occurred.</p>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        const dateTimeEl = document.getElementById('date-time');
        const form = document.getElementById('setup-account-form');
        const successModal = document.getElementById('success-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const imageUploadInput = document.getElementById('profile_image');
        const imagePreview = document.getElementById('image-preview');
        const imagePlaceholder = document.getElementById('image-placeholder');
        
        // --- START: Add constants for the new error modal ---
        const errorModal = document.getElementById('error-modal');
        const errorMessageEl = document.getElementById('error-message');
        // --- END: Add constants for the new error modal ---

        const passwordInput = document.getElementById('password');
        const passwordToggleBtn = document.getElementById('password-toggle-btn');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeSlashedIcon = document.getElementById('eye-slashed-icon');

        if (sidebarToggleBtn && sidebar) {
            sidebarToggleBtn.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    sidebar.classList.toggle('open-mobile');
                    overlay.classList.toggle('hidden');
                } else {
                    sidebar.classList.toggle('open-desktop');
                }
            });
        }
        if (overlay) { overlay.addEventListener('click', () => { if (sidebar) sidebar.classList.remove('open-mobile'); overlay.classList.add('hidden'); }); }
        if (userMenuButton && userMenu) {
            userMenuButton.addEventListener('click', () => userMenu.classList.toggle('hidden'));
            window.addEventListener('click', (e) => {
                if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('hidden'); }
            });
        }
        function updateDateTime() { if (dateTimeEl) { const now = new Date(); const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }; dateTimeEl.textContent = now.toLocaleDateString('en-US', options); } }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        if (passwordToggleBtn) {
            passwordToggleBtn.addEventListener('click', () => {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                eyeIcon.classList.toggle('hidden', isPassword);
                eyeSlashedIcon.classList.toggle('hidden', !isPassword);
            });
        }

        imageUploadInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                    imagePlaceholder.classList.add('hidden');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            
            fetch('setup_account.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    successModal.classList.remove('hidden');
                    successModal.classList.add('flex');
                    form.reset();
                    imagePreview.classList.add('hidden');
                    imagePlaceholder.classList.remove('hidden');
                    document.querySelectorAll('input[name="roles[]"]').forEach(checkbox => { checkbox.checked = false; });
                } else {
                    // --- MODIFIED: Show custom error modal instead of alert ---
                    errorMessageEl.textContent = result.message;
                    errorModal.classList.remove('hidden');
                    errorModal.classList.add('flex');

                    // Hide the modal after 2 seconds
                    setTimeout(() => {
                        errorModal.classList.add('hidden');
                        errorModal.classList.remove('flex');
                    }, 2000); // 2000 milliseconds = 2 seconds
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessageEl.textContent = 'An unexpected error occurred. Please try again.';
                errorModal.classList.remove('hidden');
                errorModal.classList.add('flex');
                setTimeout(() => {
                    errorModal.classList.add('hidden');
                    errorModal.classList.remove('flex');
                }, 2000);
            });
        });

        closeModalBtn.addEventListener('click', function() {
            successModal.classList.add('hidden');
            successModal.classList.remove('flex');
        });
    });
</script>