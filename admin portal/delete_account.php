<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$currentPage = 'delete_account';

// Handle account deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user_id'])) {
    try {
        header('Content-Type: application/json');
        
        $userId = intval($_POST['delete_user_id']);
        
        // Prevent admin from deleting their own account
        if ($userId == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
            exit();
        }
        
        // Check if user exists
        $checkStmt = $conn->prepare("SELECT username, profile_image FROM users WHERE id = ?");
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $user = $checkStmt->get_result()->fetch_assoc();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit();
        }
        
        // Delete profile image if exists
        if ($user['profile_image'] && file_exists('../' . $user['profile_image'])) {
            unlink('../' . $user['profile_image']);
        }
        
        // Delete user from database
        $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->bind_param("i", $userId);
        
        if ($deleteStmt->execute()) {
            // Log the deletion activity
            $logStmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_description) VALUES (?, ?)");
            $actionDescription = "Deleted user account: " . $user['username'];
            $logStmt->bind_param("is", $_SESSION['user_id'], $actionDescription);
            $logStmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Account deleted successfully.']);
        } else {
            throw new Exception("Failed to delete user account.");
        }
        
    } catch (Exception $e) {
        error_log("Delete Account Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the account.']);
    }
    exit();
}

// Fetch all users except current admin
$usersStmt = $conn->prepare("SELECT id, username, role, profile_image FROM users WHERE id != ? ORDER BY username ASC");
$usersStmt->bind_param("i", $_SESSION['user_id']);
$usersStmt->execute();
$users = $usersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - MJ Pharmacy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --primary-green: #01A74F; --light-gray: #f3f4f6; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: #1f2937; }
        .sidebar { background-color: var(--primary-green); transition: width 0.3s ease-in-out, transform 0.3s ease-in-out; }
        @media (max-width: 767px) { .sidebar { width: 16rem; transform: translateX(-100%); position: fixed; height: 100%; z-index: 50; } .sidebar.open-mobile { transform: translateX(0); } .overlay { transition: opacity 0.3s ease-in-out; } }
        @media (min-width: 768px) { .sidebar { width: 5rem; } .sidebar.open-desktop { width: 16rem; } .sidebar .nav-text { opacity: 0; visibility: hidden; width: 0; transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease; white-space: nowrap; overflow: hidden; } .sidebar.open-desktop .nav-text { opacity: 1; visibility: visible; width: auto; transition: opacity 0.2s ease 0.1s; } .sidebar .nav-link { justify-content: center; gap: 0; } .sidebar.open-desktop .nav-link { justify-content: flex-start; gap: 1rem; } }
        .nav-link { color: rgba(255, 255, 255, 0.8); } .nav-link svg { color: white; } .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); } .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; } .nav-link.active svg { color: var(--primary-green); }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>
        <?php include 'admin_sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
                <div class="max-w-6xl mx-auto">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-800">Delete User Account</h2>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <p class="text-red-700 text-sm font-medium">⚠️ Warning: This action cannot be undone</p>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left" id="users-table">
                                <thead>
                                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                                        <th class="py-3 px-4 font-semibold text-gray-600">User</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Username</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Role</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($users)): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr class="border-b border-gray-200">
                                                <td class="py-3 px-4">
                                                    <div class="flex items-center">
                                                        <?php if ($user['profile_image']): ?>
                                                            <img class="w-10 h-10 rounded-full object-cover mr-3" src="../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                                                        <?php else: ?>
                                                            <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white font-bold mr-3">
                                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <span class="font-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4"><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 capitalize">
                                                        <?php echo htmlspecialchars($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                                        Delete Account
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-8 text-gray-500">No users to display.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all my-8 max-w-md w-full">
                <div class="bg-white p-6">
                    <div class="flex items-center">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Delete User Account
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to delete the account for <strong id="delete-username"></strong>? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirm-delete-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete Account
                    </button>
                    <button type="button" id="cancel-delete-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            if(sidebarToggleBtn && sidebar) {
                sidebarToggleBtn.addEventListener('click', () => {
                    if (window.innerWidth < 768) {
                        sidebar.classList.toggle('open-mobile');
                        overlay.classList.toggle('hidden');
                    } else {
                        sidebar.classList.toggle('open-desktop');
                    }
                });
            }

            if(overlay) {
                overlay.addEventListener('click', () => {
                    if (sidebar) sidebar.classList.remove('open-mobile');
                    overlay.classList.add('hidden');
                });
            }
        });

        let deleteUserId = null;

        function confirmDelete(userId, username) {
            deleteUserId = userId;
            document.getElementById('delete-username').textContent = username;
            document.getElementById('delete-modal').classList.remove('hidden');
        }

        document.getElementById('confirm-delete-btn').addEventListener('click', function() {
            if (deleteUserId) {
                const formData = new FormData();
                formData.append('delete_user_id', deleteUserId);

                fetch('delete_account.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Account deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });

                document.getElementById('delete-modal').classList.add('hidden');
                deleteUserId = null;
            }
        });

        document.getElementById('cancel-delete-btn').addEventListener('click', function() {
            document.getElementById('delete-modal').classList.add('hidden');
            deleteUserId = null;
        });

        // Close modal when clicking outside
        document.getElementById('delete-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                deleteUserId = null;
            }
        });
    </script>
</body>
</html>
