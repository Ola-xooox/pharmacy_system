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

// Set the timezone
date_default_timezone_set('Asia/Manila');

// Get selected date from URL parameter, default to 'all' for all data
$selectedDate = isset($_GET['date']) ? $_GET['date'] : 'all';

// Validate the date format or check for 'all' option
if ($selectedDate !== 'all' && !DateTime::createFromFormat('Y-m-d', $selectedDate)) {
    $selectedDate = 'all';
}

$isAllTime = ($selectedDate === 'all');

// Fetch user activity logs from the actual user_activity_log table
if ($isAllTime) {
    $activityLogsStmt = $conn->prepare("
        SELECT 
            ual.action_description,
            ual.timestamp,
            u.username,
            u.first_name,
            u.last_name,
            u.role
        FROM user_activity_log ual
        JOIN users u ON ual.user_id = u.id
        ORDER BY ual.timestamp DESC
        LIMIT 500
    ");
    $activityLogsStmt->execute();
} else {
    $activityLogsStmt = $conn->prepare("
        SELECT 
            ual.action_description,
            ual.timestamp,
            u.username,
            u.first_name,
            u.last_name,
            u.role
        FROM user_activity_log ual
        JOIN users u ON ual.user_id = u.id
        WHERE DATE(ual.timestamp) = ?
        ORDER BY ual.timestamp DESC
        LIMIT 200
    ");
    $activityLogsStmt->bind_param("s", $selectedDate);
    $activityLogsStmt->execute();
}
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
                    <!-- Date Filter Section -->
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">User Activity Log</h1>
                                <p class="text-sm text-gray-600 mt-1">Monitor and track user activities within the system</p>
                            </div>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                <label for="date-filter" class="text-sm font-medium text-gray-700 whitespace-nowrap">Select Period:</label>
                                <div class="flex items-center gap-2">
                                    <select id="date-filter" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        <option value="all" <?php echo $selectedDate === 'all' ? 'selected' : ''; ?>>All Time</option>
                                        <option value="<?php echo date('Y-m-d'); ?>" <?php echo $selectedDate === date('Y-m-d') ? 'selected' : ''; ?>>Today</option>
                                        <option value="custom" <?php echo ($selectedDate !== 'all' && $selectedDate !== date('Y-m-d')) ? 'selected' : ''; ?>>Custom Date</option>
                                    </select>
                                    <input type="date" id="custom-date-input" value="<?php echo ($selectedDate !== 'all' && $selectedDate !== date('Y-m-d')) ? $selectedDate : ''; ?>" max="<?php echo date('Y-m-d'); ?>" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 <?php echo ($selectedDate === 'all' || $selectedDate === date('Y-m-d')) ? 'hidden' : ''; ?>">
                                    <button id="apply-date-filter" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg transition-colors">
                                        Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Showing activities for:</span>
                                <span class="font-semibold text-gray-800">
                                    <?php 
                                    if ($isAllTime) {
                                        echo 'All Time';
                                    } else {
                                        echo date('l, F j, Y', strtotime($selectedDate));
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm mt-1">
                                <span class="text-gray-600">Total activities found:</span>
                                <span class="font-semibold text-blue-600"><?php echo count($activityLogs); ?> activities</span>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Log Table -->
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800">
                                <?php 
                                if ($isAllTime) {
                                    echo "All Time Activities";
                                } elseif ($selectedDate === date('Y-m-d')) {
                                    echo "Today's Activities";
                                } else {
                                    echo "Activities for " . date('M j, Y', strtotime($selectedDate));
                                }
                                ?>
                            </h2>
                            <div class="flex gap-4">
                                <input type="text" id="activity-search" placeholder="Search activities..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <select id="role-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">All Roles</option>
                                    <option value="admin">Admin</option>
                                    <option value="inventory">Inventory</option>
                                    <option value="pos">POS</option>
                                    <option value="cms">CMS</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left" id="activity-table">
                                <thead>
                                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                                        <th class="py-3 px-4 font-semibold text-gray-600">User</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Role</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Activity Description</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($activityLogs)): ?>
                                        <?php foreach ($activityLogs as $log): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                                            <td class="py-3 px-4 font-medium dark:text-white">
                                                <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                                <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($log['username']); ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 capitalize">
                                                    <?php echo htmlspecialchars($log['role']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($log['action_description']); ?></td>
                                            <td class="py-3 px-4 text-gray-500 dark:text-gray-400"><?php echo date('M d, Y g:i A', strtotime($log['timestamp'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-8 text-gray-500">No activity logs found.</td>
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
            const roleFilter = document.getElementById('role-filter');
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
                const roleValue = roleFilter.value.toLowerCase();
                const rows = table.getElementsByTagName('tr');

                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const userName = row.getElementsByTagName('td')[0]?.textContent.toLowerCase() || '';
                    const userRole = row.getElementsByTagName('td')[1]?.textContent.toLowerCase() || '';
                    const description = row.getElementsByTagName('td')[2]?.textContent.toLowerCase() || '';

                    const matchesSearch = userName.includes(searchTerm) || 
                                        description.includes(searchTerm);
                    const matchesRole = roleValue === '' || userRole.includes(roleValue);

                    if (matchesSearch && matchesRole) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }

            if (searchInput) {
                searchInput.addEventListener('keyup', filterTable);
            }

            if (roleFilter) {
                roleFilter.addEventListener('change', filterTable);
            }

            // Date Filter functionality
            const dateFilter = document.getElementById('date-filter');
            const customDateInput = document.getElementById('custom-date-input');
            const applyDateFilter = document.getElementById('apply-date-filter');

            // Show/hide custom date input based on selection
            if (dateFilter) {
                dateFilter.addEventListener('change', () => {
                    if (dateFilter.value === 'custom') {
                        customDateInput.classList.remove('hidden');
                    } else {
                        customDateInput.classList.add('hidden');
                    }
                });
            }

            if (applyDateFilter) {
                applyDateFilter.addEventListener('click', () => {
                    let selectedValue = dateFilter.value;
                    
                    if (selectedValue === 'custom') {
                        selectedValue = customDateInput.value;
                        if (!selectedValue) {
                            alert('Please select a custom date');
                            return;
                        }
                    }
                    
                    window.location.href = `user_activity_log.php?date=${selectedValue}`;
                });
            }

            // Allow Enter key to apply filter
            if (customDateInput) {
                customDateInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        const selectedDate = customDateInput.value;
                        if (selectedDate) {
                            window.location.href = `user_activity_log.php?date=${selectedDate}`;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>