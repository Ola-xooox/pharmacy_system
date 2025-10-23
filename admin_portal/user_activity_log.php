<?php 
session_start();
// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$currentPage = 'user_activity_log';

// Database connection and data fetching
require_once '../db_connect.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user activity logs (you can modify this query based on your actual activity log table structure)
// For now, I'll create a basic structure - you may need to adjust based on your actual database schema
$activityLogsStmt = $conn->prepare("
    SELECT 
        'Login' as activity_type,
        username as user_name,
        'admin' as user_role,
        NOW() as activity_date,
        'User logged into the system' as description
    FROM users 
    WHERE role = 'admin'
    LIMIT 10
");
$activityLogsStmt->execute();
$activityLogs = $activityLogsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$activityLogsStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - User Activity Log</title>
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
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'admin_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_header.php'; ?>

        <main class="flex-1 overflow-y-auto p-6">
            <div id="page-content">
                <div id="user-activity-log-page" class="space-y-8">
                    <!-- Header Section -->
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <h1 class="text-2xl font-bold text-gray-800 mb-2">User Activity Log</h1>
                        <p class="text-gray-600">Monitor and track user activities within the system</p>
                    </div>

                    <!-- Activity Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Activities</p>
                                <p class="text-2xl font-bold text-[#236B3D]"><?php echo count($activityLogs); ?></p>
                            </div>
                            <i class="ph-fill ph-activity text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Active Users</p>
                                <p class="text-2xl font-bold text-blue-500">1</p>
                            </div>
                            <i class="ph-fill ph-users text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Today's Logins</p>
                                <p class="text-2xl font-bold text-green-500">1</p>
                            </div>
                            <i class="ph-fill ph-sign-in text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">System Alerts</p>
                                <p class="text-2xl font-bold text-orange-500">0</p>
                            </div>
                            <i class="ph-fill ph-warning text-4xl text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Activity Log Table -->
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800">Recent Activities</h2>
                            <div class="flex gap-4">
                                <input type="text" id="activity-search" placeholder="Search activities..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <select id="activity-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">All Activities</option>
                                    <option value="login">Login</option>
                                    <option value="logout">Logout</option>
                                    <option value="create">Create</option>
                                    <option value="update">Update</option>
                                    <option value="delete">Delete</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left" id="activity-table">
                                <thead>
                                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                                        <th class="py-3 px-4 font-semibold text-gray-600">Activity Type</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">User</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Role</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Description</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($activityLogs)): ?>
                                        <?php foreach ($activityLogs as $log): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                                            <td class="py-3 px-4 dark:text-white">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200">
                                                    <?php echo htmlspecialchars($log['activity_type']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 font-medium dark:text-white"><?php echo htmlspecialchars($log['user_name']); ?></td>
                                            <td class="py-3 px-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 capitalize">
                                                    <?php echo htmlspecialchars($log['user_role']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($log['description']); ?></td>
                                            <td class="py-3 px-4 text-gray-500 dark:text-gray-400"><?php echo date('M d, Y g:i A', strtotime($log['activity_date'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-8 text-gray-500">No activity logs found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            const searchInput = document.getElementById('activity-search');
            const filterSelect = document.getElementById('activity-filter');
            const table = document.getElementById('activity-table').getElementsByTagName('tbody')[0];

            // Sidebar functionality
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

            // User menu functionality
            if(userMenuButton && userMenu){
                userMenuButton.addEventListener('click', () => userMenu.classList.toggle('hidden'));
                window.addEventListener('click', (e) => {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }

            // Search and filter functionality
            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const filterValue = filterSelect.value.toLowerCase();
                const rows = table.getElementsByTagName('tr');

                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const activityType = row.getElementsByTagName('td')[0]?.textContent.toLowerCase() || '';
                    const userName = row.getElementsByTagName('td')[1]?.textContent.toLowerCase() || '';
                    const description = row.getElementsByTagName('td')[3]?.textContent.toLowerCase() || '';

                    const matchesSearch = activityType.includes(searchTerm) || 
                                        userName.includes(searchTerm) || 
                                        description.includes(searchTerm);
                    const matchesFilter = filterValue === '' || activityType.includes(filterValue);

                    if (matchesSearch && matchesFilter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }

            if (searchInput) {
                searchInput.addEventListener('keyup', filterTable);
            }

            if (filterSelect) {
                filterSelect.addEventListener('change', filterTable);
            }
        });
    </script>
</body>
</html>