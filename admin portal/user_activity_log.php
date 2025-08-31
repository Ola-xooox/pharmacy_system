<?php 
session_start();
// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$currentPage = 'user_activity_log';

require_once '../db_connect.php'; 

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all activity log data, joining with the users table to get the user's name
$activityLogStmt = $conn->prepare("
    SELECT 
        u.name, 
        u.role,
        a.action_description, 
        a.timestamp 
    FROM user_activity_log a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.timestamp DESC
");
$activityLogStmt->execute();
$activityLog = $activityLogStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$activityLogStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - User Activity Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                <div id="user-activity-page">
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800">Activity History</h2>
                            <input type="text" id="activity-search" placeholder="Search user, action, or role..." class="w-1/3 p-2 rounded-lg border border-gray-300 focus:outline-none focus:ring focus:ring-green-500 focus:ring-opacity-50">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left" id="activity-table">
                                <thead>
                                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                                        <th class="py-3 px-4 font-semibold text-gray-600">User</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Role</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Action</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Time Stamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($activityLog)): ?>
                                        <?php foreach ($activityLog as $log): ?>
                                            <tr class="border-b border-gray-200">
                                                <td class="py-3 px-4"><?php echo htmlspecialchars($log['name']); ?></td>
                                                <td class="py-3 px-4"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 capitalize"><?php echo htmlspecialchars($log['role']); ?></span></td>
                                                <td class="py-3 px-4"><?php echo htmlspecialchars($log['action_description']); ?></td>
                                                <td class="py-3 px-4"><?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($log['timestamp']))); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-8 text-gray-500">No activity to display.</td>
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
            const dateTimeEl = document.getElementById('date-time');
            const searchInput = document.getElementById('activity-search');
            const table = document.getElementById('activity-table').getElementsByTagName('tbody')[0];

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
            if(overlay) { overlay.addEventListener('click', () => { if (sidebar) sidebar.classList.remove('open-mobile'); overlay.classList.add('hidden'); }); }
            if(userMenuButton && userMenu){
                userMenuButton.addEventListener('click', () => userMenu.classList.toggle('hidden'));
                window.addEventListener('click', (e) => {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) { userMenu.classList.add('hidden'); }
                });
            }
            function updateDateTime() { if(dateTimeEl){ const now = new Date(); const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }; dateTimeEl.textContent = now.toLocaleDateString('en-US', options); } }
            updateDateTime();
            setInterval(updateDateTime, 60000);

            searchInput.addEventListener('keyup', function() {
                const filter = searchInput.value.toLowerCase();
                const rows = table.getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    let rowVisible = false;
                    const cells = rows[i].getElementsByTagName('td');
                    for (let j = 0; j < cells.length; j++) {
                        if (cells[j]) {
                            if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                                rowVisible = true;
                                break;
                            }
                        }
                    }
                    rows[i].style.display = rowVisible ? "" : "none";
                }
            });
        });
    </script>
</body>
</html>