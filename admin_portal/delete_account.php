<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
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

// Fetch all users except current admin, grouped by role
$usersStmt = $conn->prepare("SELECT id, username, email, role, profile_image FROM users WHERE id != ? ORDER BY role ASC, username ASC");
$usersStmt->bind_param("i", $_SESSION['user_id']);
$usersStmt->execute();
$allUsers = $usersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group users by role
$usersByRole = [];
$roleCounts = [];
foreach ($allUsers as $user) {
    $role = $user['role'];
    if (!isset($usersByRole[$role])) {
        $usersByRole[$role] = [];
        $roleCounts[$role] = 0;
    }
    $usersByRole[$role][] = $user;
    $roleCounts[$role]++;
}

// Define role order and colors
$roleOrder = ['admin', 'inventory', 'pos', 'cms'];
$roleColors = [
    'admin' => 'bg-red-100 text-red-800 border-red-200',
    'inventory' => 'bg-blue-100 text-blue-800 border-blue-200',
    'pos' => 'bg-green-100 text-green-800 border-green-200',
    'cms' => 'bg-purple-100 text-purple-800 border-purple-200'
];

$roleIcons = [
    'admin' => 'ph-crown',
    'inventory' => 'ph-package',
    'pos' => 'ph-cash-register',
    'cms' => 'ph-article'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - MJ Pharmacy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include 'assets/admin_darkmode.php'; ?>
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
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-8">
                <div class="w-full">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 w-full">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Delete User Account</h2>
                                <p class="text-sm text-gray-600 mt-1">Manage user accounts organized by roles</p>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-3">
                                <p class="text-red-700 dark:text-red-300 text-sm font-medium">⚠️ Warning: This action cannot be undone</p>
                            </div>
                        </div>
                        
                        <!-- Search Bar -->
                        <div class="mb-6">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ph ph-magnifying-glass text-gray-400"></i>
                                </div>
                                <input type="text" id="user-search" placeholder="Search users by name, username, email, or role..." 
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div class="mt-2 text-sm text-gray-500">
                                <span id="search-results-count">Showing all users</span>
                            </div>
                        </div>
                        
                        <!-- Role Summary Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8" id="role-summary-cards">
                            <?php foreach ($roleOrder as $role): ?>
                                <?php if (isset($roleCounts[$role]) && $roleCounts[$role] > 0): ?>
                                    <div class="<?php echo $roleColors[$role]; ?> border rounded-lg p-4 text-center">
                                        <i class="<?php echo $roleIcons[$role]; ?> text-2xl mb-2"></i>
                                        <div class="text-lg font-bold"><?php echo $roleCounts[$role]; ?></div>
                                        <div class="text-sm font-medium capitalize"><?php echo $role; ?> Users</div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Users by Role -->
                        <?php if (!empty($usersByRole)): ?>
                            <?php foreach ($roleOrder as $role): ?>
                                <?php if (isset($usersByRole[$role]) && !empty($usersByRole[$role])): ?>
                                    <div class="mb-8 role-section" data-role="<?php echo $role; ?>">
                                        <div class="flex items-center mb-4">
                                            <i class="<?php echo $roleIcons[$role]; ?> text-xl mr-2 text-gray-600"></i>
                                            <h3 class="text-lg font-semibold text-gray-800 capitalize"><?php echo $role; ?> Users</h3>
                                            <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full <?php echo $roleColors[$role]; ?> role-count" data-role="<?php echo $role; ?>">
                                                <?php echo count($usersByRole[$role]); ?> users
                                            </span>
                                        </div>
                                        
                                        <div class="overflow-x-auto bg-gray-50 rounded-lg">
                                            <table class="w-full text-left">
                                                <thead>
                                                    <tr class="bg-gray-100 border-b border-gray-200">
                                                        <th class="py-3 px-4 font-semibold text-gray-600">User</th>
                                                        <th class="py-3 px-4 font-semibold text-gray-600">Username</th>
                                                        <th class="py-3 px-4 font-semibold text-gray-600">Email</th>
                                                        <th class="py-3 px-4 font-semibold text-gray-600">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($usersByRole[$role] as $user): ?>
                                                        <tr class="border-b border-gray-200 hover:bg-white transition-colors user-row" 
                                                            data-username="<?php echo strtolower(htmlspecialchars($user['username'])); ?>"
                                                            data-email="<?php echo strtolower(htmlspecialchars($user['email'] ?? '')); ?>"
                                                            data-role="<?php echo strtolower($role); ?>"
                                                            data-fullname="<?php echo strtolower(htmlspecialchars($user['username'])); ?>">
                                                            <td class="py-3 px-4">
                                                                <div class="flex items-center">
                                                                    <?php if ($user['profile_image']): ?>
                                                                        <img class="w-10 h-10 rounded-full object-cover mr-3" src="../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                                                                    <?php else: ?>
                                                                        <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white font-bold mr-3">
                                                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></span>
                                                                        <div class="text-xs text-gray-500 capitalize"><?php echo $role; ?></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($user['username']); ?></td>
                                                            <td class="py-3 px-4">
                                                                <?php if (!empty($user['email'])): ?>
                                                                    <span class="text-gray-700"><?php echo htmlspecialchars($user['email']); ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-gray-400 italic">No email</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="py-3 px-4">
                                                                <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo $role; ?>')" 
                                                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                                                    <i class="ph ph-trash text-sm"></i>
                                                                    Delete
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-12" id="no-users-message">
                                <i class="ph ph-users text-6xl text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Users Found</h3>
                                <p class="text-gray-500">There are no users to display at this time.</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- No Search Results Message -->
                        <div class="text-center py-12 hidden" id="no-search-results">
                            <i class="ph ph-magnifying-glass text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Users Found</h3>
                            <p class="text-gray-500">No users match your search criteria. Try adjusting your search terms.</p>
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
                                    Are you sure you want to delete the <span id="delete-role" class="font-medium"></span> account for <strong id="delete-username"></strong>? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirm-delete-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete Account
                    </button>
                    <button type="button" id="cancel-delete-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Popup Modal -->
    <div id="success-popup" class="fixed inset-0 flex items-center justify-center hidden z-50">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full text-center relative z-50">
            <div class="flex justify-center mb-3">
                <div class="flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Success</h3>
            <p class="mt-2 text-sm text-gray-600">The account has been deleted successfully.</p>
            <div class="mt-4">
                <button id="success-ok-btn"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                    OK
                </button>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="error-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all my-8 max-w-md w-full">
                <div class="bg-white p-6">
                    <div class="flex items-center">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </div>
                        <div class="ml-4 text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Error</h3>
                            <p id="error-message" class="mt-2 text-sm text-gray-500">An error occurred.</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="button" id="error-ok-btn" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:ml-3 sm:w-auto sm:text-sm">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        
        // Modals
        const successPopup = document.getElementById('success-popup');
        const successOkBtn = document.getElementById('success-ok-btn');
        const errorModal = document.getElementById('error-modal');
        const errorOkBtn = document.getElementById('error-ok-btn');
        const errorMessageEl = document.getElementById('error-message');

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

        if (userMenuButton && userMenu) {
            userMenuButton.addEventListener('click', () => {
                userMenu.classList.toggle('hidden');
            });
            window.addEventListener('click', (e) => {
                if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        }

        // Success popup OK
        if(successOkBtn) {
            successOkBtn.addEventListener('click', () => {
                successPopup.classList.add('hidden');
                location.reload();
            });
        }

        // Error modal OK
        if(errorOkBtn) {
            errorOkBtn.addEventListener('click', () => {
                errorModal.classList.add('hidden');
            });
        }
        
        // Search functionality
        const searchInput = document.getElementById('user-search');
        const searchResultsCount = document.getElementById('search-results-count');
        const noSearchResults = document.getElementById('no-search-results');
        const roleSections = document.querySelectorAll('.role-section');
        const userRows = document.querySelectorAll('.user-row');
        const roleSummaryCards = document.getElementById('role-summary-cards');
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                if (searchTerm === '') {
                    // Show all users and role sections
                    userRows.forEach(row => {
                        row.style.display = '';
                    });
                    roleSections.forEach(section => {
                        section.style.display = '';
                    });
                    roleSummaryCards.style.display = '';
                    noSearchResults.classList.add('hidden');
                    searchResultsCount.textContent = 'Showing all users';
                    updateRoleCounts();
                    return;
                }
                
                let visibleCount = 0;
                const visibleRoles = new Set();
                
                // Filter user rows
                userRows.forEach(row => {
                    const username = row.dataset.username || '';
                    const email = row.dataset.email || '';
                    const role = row.dataset.role || '';
                    const fullname = row.dataset.fullname || '';
                    
                    const matches = username.includes(searchTerm) || 
                                  email.includes(searchTerm) || 
                                  role.includes(searchTerm) || 
                                  fullname.includes(searchTerm);
                    
                    if (matches) {
                        row.style.display = '';
                        visibleCount++;
                        visibleRoles.add(role);
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Show/hide role sections based on visible users
                roleSections.forEach(section => {
                    const sectionRole = section.dataset.role.toLowerCase();
                    if (visibleRoles.has(sectionRole)) {
                        section.style.display = '';
                    } else {
                        section.style.display = 'none';
                    }
                });
                
                // Hide role summary cards during search
                roleSummaryCards.style.display = 'none';
                
                // Update search results count
                if (visibleCount === 0) {
                    noSearchResults.classList.remove('hidden');
                    searchResultsCount.textContent = 'No users found';
                } else {
                    noSearchResults.classList.add('hidden');
                    searchResultsCount.textContent = `Found ${visibleCount} user${visibleCount !== 1 ? 's' : ''}`;
                }
                
                updateRoleCounts(searchTerm);
            });
        }
        
        function updateRoleCounts(searchTerm = '') {
            const roleCounts = document.querySelectorAll('.role-count');
            roleCounts.forEach(countElement => {
                const role = countElement.dataset.role;
                const roleSection = document.querySelector(`[data-role="${role}"]`);
                if (roleSection) {
                    const visibleRows = roleSection.querySelectorAll('.user-row:not([style*="display: none"])');
                    const count = visibleRows.length;
                    if (searchTerm) {
                        countElement.textContent = `${count} user${count !== 1 ? 's' : ''} found`;
                    } else {
                        // Get original count from PHP
                        const originalCount = roleSection.querySelectorAll('.user-row').length;
                        countElement.textContent = `${originalCount} user${originalCount !== 1 ? 's' : ''}`;
                    }
                }
            });
        }
    });

    let deleteUserId = null;

    function confirmDelete(userId, username, role) {
        deleteUserId = userId;
        document.getElementById('delete-username').textContent = username;
        document.getElementById('delete-role').textContent = role;
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
            .then(res => res.json())
            .then(data => {
                document.getElementById('delete-modal').classList.add('hidden');
                if (data.success) {
                    document.getElementById('success-popup').classList.remove('hidden');
                } else {
                    document.getElementById('error-message').textContent = data.message;
                    document.getElementById('error-modal').classList.remove('hidden');
                }
            })
            .catch(err => {
                document.getElementById('delete-modal').classList.add('hidden');
                document.getElementById('error-message').textContent = "An error occurred.";
                document.getElementById('error-modal').classList.remove('hidden');
            });
        }
    });

    document.getElementById('cancel-delete-btn').addEventListener('click', function() {
        document.getElementById('delete-modal').classList.add('hidden');
    });
    </script>
</body>
</html>
