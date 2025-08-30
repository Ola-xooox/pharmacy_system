<?php
session_start();
require '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // We expect JSON data from the fetch request
    $data = json_decode(file_get_contents('php://input'), true);

    $name = $data['name'] ?? '';
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $roles = $data['roles'] ?? [];

    // Basic validation
    if (empty($name) || empty($username) || empty($password) || empty($roles)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields and select at least one role.']);
        exit();
    }
    
    // For simplicity, we'll assign the first selected role.
    // A more complex system might involve a separate user_roles table.
    $role = $roles[0]; 

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists. Please choose another one.']);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Insert the new user into the database
    // In a real-world application, you should hash the password
    // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $insertStmt = $conn->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
    $insertStmt->bind_param("ssss", $name, $username, $password, $role);

    if ($insertStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'New account created successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $insertStmt->error]);
    }

    $insertStmt->close();
    $conn->close();
    exit(); // Stop script execution after handling the POST request
}

// Set the current page for the sidebar active state
$currentPage = 'setup_account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Set Up Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="icon" type="image/x-icon" href="../mjpharmacy.logo.jpg">
    <style>
        :root { --primary-green: #01A74F; --light-gray: #f3f4f6; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: #1f2937; }
        .sidebar { background-color: var(--primary-green); transition: width 0.3s ease-in-out, transform 0.3s ease-in-out; }
        @media (max-width: 767px) { .sidebar { width: 16rem; transform: translateX(-100%); position: fixed; height: 100%; z-index: 50; } .sidebar.open-mobile { transform: translateX(0); } .overlay { transition: opacity 0.3s ease-in-out; } }
        @media (min-width: 768px) { .sidebar { width: 5rem; } .sidebar.open-desktop { width: 16rem; } .sidebar .nav-text { opacity: 0; visibility: hidden; width: 0; transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease; white-space: nowrap; overflow: hidden; } .sidebar.open-desktop .nav-text { opacity: 1; visibility: visible; width: auto; transition: opacity 0.2s ease 0.1s; } .sidebar .nav-link { justify-content: center; gap: 0; } .sidebar.open-desktop .nav-link { justify-content: flex-start; gap: 1rem; } }
        .nav-link { color: rgba(255, 255, 255, 0.8); } .nav-link svg { color: white; } .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); } .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; } .nav-link.active svg { color: var(--primary-green); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'admin_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_header.php'; ?>

        <main class="flex-1 overflow-y-auto p-6">
            <div id="page-content">
                <div id="setup-account-page">
                    <div class="bg-white p-8 rounded-2xl shadow-lg max-w-3xl mx-auto">
                        <div class="mb-8 pb-4 border-b border-gray-200">
                            <h2 class="text-2xl font-bold text-gray-800">Create New User Account</h2>
                            <p class="text-sm text-gray-500 mt-1">Fill in the details below to set up a new user and assign their roles.</p>
                        </div>

                        <form id="setup-account-form" class="space-y-10">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">User Credentials</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="sm:col-span-2">
                                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                        <div class="mt-1 relative">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"><svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg></div>
                                            <input type="text" id="name" name="name" class="block w-full rounded-md border-gray-300 shadow-sm pl-10 p-2.5 focus:border-green-500 focus:ring-green-500" placeholder="e.g., Mark James" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                        <div class="mt-1 relative">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"><svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></div>
                                            <input type="text" id="username" name="username" class="block w-full rounded-md border-gray-300 shadow-sm pl-10 p-2.5 focus:border-green-500 focus:ring-green-500" placeholder="e.g., mark@james" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                        <div class="mt-1 relative">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"><svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" /></svg></div>
                                            <input type="password" id="password" name="password" class="block w-full rounded-md border-gray-300 shadow-sm pl-10 p-2.5 focus:border-green-500 focus:ring-green-500" placeholder="••••••••" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Assign Roles</h3>
                                <p class="text-sm text-gray-500 mb-4">Note: If multiple roles are selected, the first one will be assigned as the primary role.</p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <label class="border rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer transition-all duration-200 hover:bg-gray-50 peer-checked:bg-green-50 peer-checked:border-green-500 peer-checked:ring-2 peer-checked:ring-green-200">
                                        <input type="checkbox" name="roles[]" value="pos" class="sr-only peer">
                                        <svg class="h-8 w-8 text-gray-500 peer-checked:text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                        <span class="font-semibold text-gray-700 mt-2 text-center text-sm">POS Access</span>
                                    </label>
                                    <label class="border rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer transition-all duration-200 hover:bg-gray-50 peer-checked:bg-green-50 peer-checked:border-green-500 peer-checked:ring-2 peer-checked:ring-green-200">
                                        <input type="checkbox" name="roles[]" value="inventory" class="sr-only peer">
                                        <svg class="h-8 w-8 text-gray-500 peer-checked:text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4" /></svg>
                                        <span class="font-semibold text-gray-700 mt-2 text-center text-sm">Inventory Access</span>
                                    </label>
                                    <label class="border rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer transition-all duration-200 hover:bg-gray-50 peer-checked:bg-green-50 peer-checked:border-green-500 peer-checked:ring-2 peer-checked:ring-green-200">
                                        <input type="checkbox" name="roles[]" value="cms" class="sr-only peer">
                                        <svg class="h-8 w-8 text-gray-500 peer-checked:text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-4.663l.001.001M9 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        <span class="font-semibold text-gray-700 mt-2 text-center text-sm">Customer Mgmt.</span>
                                    </label>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-200">
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center gap-2 justify-center rounded-md border border-transparent bg-green-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd" /></svg>
                                        Create Account
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden justify-center items-center">
        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-sm text-center">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mt-4">Success!</h3>
            <p class="text-gray-500 mt-2">The new user account has been created successfully.</p>
            <button id="close-modal-btn" class="mt-6 w-full bg-green-600 text-white py-2 rounded-lg font-semibold hover:bg-green-700 transition-colors">OK</button>
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

            if (overlay) {
                overlay.addEventListener('click', () => {
                    if (sidebar) sidebar.classList.remove('open-mobile');
                    overlay.classList.add('hidden');
                });
            }

            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', () => userMenu.classList.toggle('hidden'));
                window.addEventListener('click', (e) => {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
            
            function updateDateTime() {
                if (dateTimeEl) {
                    const now = new Date();
                    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                    dateTimeEl.textContent = now.toLocaleDateString('en-US', options);
                }
            }
            updateDateTime();
            setInterval(updateDateTime, 60000);

            // Form submission handler
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(form);
                const roles = [];
                formData.getAll('roles[]').forEach(role => roles.push(role));

                const data = {
                    name: formData.get('name'),
                    username: formData.get('username'),
                    password: formData.get('password'),
                    roles: roles
                };
                
                fetch('setup_account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data),
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        successModal.classList.remove('hidden');
                        successModal.classList.add('flex');
                        form.reset();
                        // Uncheck the role cards visually
                        document.querySelectorAll('input[name="roles[]"]').forEach(checkbox => {
                            checkbox.checked = false;
                        });
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred. Please try again.');
                });
            });

            // Close modal handler
            closeModalBtn.addEventListener('click', function() {
                successModal.classList.add('hidden');
                successModal.classList.remove('flex');
            });
        });
    </script>
</body>
</html>