<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$currentPage = 'login_approvals';
require '../db_connect.php';

// Handle approval/decline actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['approval_id'])) {
    $approvalId = intval($_POST['approval_id']);
    $action = $_POST['action'];
    $adminId = $_SESSION['user_id'];
    
    if ($action === 'approve' || $action === 'decline') {
        $status = ($action === 'approve') ? 'approved' : 'declined';
        
        $updateStmt = $conn->prepare("UPDATE login_approvals SET status = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $updateStmt->bind_param("sii", $status, $adminId, $approvalId);
        
        if ($updateStmt->execute()) {
            // Get user info for logging
            $userStmt = $conn->prepare("SELECT u.username, la.role FROM login_approvals la JOIN users u ON la.user_id = u.id WHERE la.id = ?");
            $userStmt->bind_param("i", $approvalId);
            $userStmt->execute();
            $userInfo = $userStmt->get_result()->fetch_assoc();
            $userStmt->close();
            
            // Log the action
            $logStmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_description, timestamp) VALUES (?, ?, NOW())");
            $logAction = "Admin " . ($action === 'approve' ? 'approved' : 'declined') . " login request for " . $userInfo['username'] . " (" . $userInfo['role'] . ")";
            $logStmt->bind_param("is", $adminId, $logAction);
            $logStmt->execute();
            $logStmt->close();
            
            $_SESSION['success_message'] = "Login request has been " . $status . " successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update approval status.";
        }
        
        $updateStmt->close();
        header("Location: login_approvals.php");
        exit();
    }
}

// Get pending approvals
$pendingQuery = "SELECT la.*, u.username, u.profile_image 
                 FROM login_approvals la 
                 JOIN users u ON la.user_id = u.id 
                 WHERE la.status = 'pending' 
                 ORDER BY la.requested_at DESC";
$pendingResult = $conn->query($pendingQuery);

// Get recent approvals (approved, declined, or no_response in last 24 hours)
$recentQuery = "SELECT la.*, u.username, u.profile_image, admin.username as admin_username
                FROM login_approvals la 
                JOIN users u ON la.user_id = u.id 
                LEFT JOIN users admin ON la.reviewed_by = admin.id
                WHERE la.status IN ('approved', 'declined', 'no_response') 
                AND la.reviewed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY la.reviewed_at DESC 
                LIMIT 20";
$recentResult = $conn->query($recentQuery);

// Store pending count for auto-refresh logic
$hasPendingRequests = $pendingResult->num_rows > 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Approvals - Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../mjpharmacy.logo.jpg">
    <?php include 'assets/admin_darkmode.php'; ?>
    <style>
        :root { --primary-green: #01A74F; --light-gray: #f3f4f6; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: #1f2937; }
        .sidebar { background-color: var(--primary-green); transition: width 0.3s ease-in-out, transform 0.3s ease-in-out; }
        @media (max-width: 767px) { .sidebar { width: 16rem; transform: translateX(-100%); position: fixed; height: 100%; z-index: 50; } .sidebar.open-mobile { transform: translateX(0); } .overlay { transition: opacity 0.3s ease-in-out; } }
        @media (min-width: 768px) { .sidebar { width: 5rem; } .sidebar.open-desktop { width: 16rem; } .sidebar .nav-text { opacity: 0; visibility: hidden; width: 0; transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease; white-space: nowrap; overflow: hidden; } .sidebar.open-desktop .nav-text { opacity: 1; visibility: visible; width: auto; transition: opacity 0.2s ease 0.1s; } .sidebar .nav-link { justify-content: center; gap: 0; } .sidebar.open-desktop .nav-link { justify-content: flex-start; gap: 1rem; } }
        .nav-link { color: rgba(255, 255, 255, 0.8); } .nav-link svg { color: white; } .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); } .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; } .nav-link.active svg { color: var(--primary-green); }
        
        .badge-pending {
            background-color: #fef3c7;
            color: #b45309;
        }
        
        .badge-approved {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .badge-declined {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .badge-no_response {
            background-color: #fff7ed;
            color: #c2410c;
        }
        
        .approval-card {
            transition: all 0.3s ease;
        }
        
        .approval-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert {
            animation: slideIn 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>
        <?php include 'admin_sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include 'admin_header.php'; ?>

            <main class="flex-1 overflow-y-auto p-6">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-user-check text-green-600 mr-2"></i>
                        Login Approvals
                    </h1>
                    <p class="text-gray-600">Review and manage user login requests</p>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <p class="text-green-800"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                        </div>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-800"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
                        </div>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Pending Approvals Section -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-hourglass-half text-yellow-600 mr-2"></i>
                        Pending Requests
                        <span class="ml-3 bg-yellow-100 text-yellow-800 text-sm font-semibold px-3 py-1 rounded-full">
                            <?php echo $pendingResult->num_rows; ?>
                        </span>
                    </h2>

                    <?php if ($pendingResult->num_rows > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php while ($approval = $pendingResult->fetch_assoc()): ?>
                                <div class="approval-card border border-gray-200 rounded-lg p-4 bg-gradient-to-br from-white to-gray-50">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center">
                                            <?php if ($approval['profile_image']): ?>
                                                <img src="../<?php echo htmlspecialchars($approval['profile_image']); ?>" 
                                                     alt="Profile" 
                                                     class="w-12 h-12 rounded-full object-cover mr-3">
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-indigo-500 flex items-center justify-center mr-3">
                                                    <span class="text-white font-bold text-lg">
                                                        <?php echo strtoupper(substr($approval['name'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($approval['name']); ?></h3>
                                                <p class="text-sm text-gray-500">@<?php echo htmlspecialchars($approval['username']); ?></p>
                                            </div>
                                        </div>
                                        <span class="badge-pending text-xs font-semibold px-3 py-1 rounded-full uppercase">
                                            <?php echo strtoupper($approval['role']); ?>
                                        </span>
                                    </div>

                                    <div class="mb-3 space-y-2 text-sm">
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-envelope w-5 mr-2"></i>
                                            <span class="truncate"><?php echo htmlspecialchars($approval['email']); ?></span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-clock w-5 mr-2"></i>
                                            <span><?php echo date('M j, Y g:i A', strtotime($approval['requested_at'])); ?></span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-network-wired w-5 mr-2"></i>
                                            <span class="truncate"><?php echo htmlspecialchars($approval['ip_address']); ?></span>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <form method="POST" class="flex-1">
                                            <input type="hidden" name="approval_id" value="<?php echo $approval['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" 
                                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                                <i class="fas fa-check mr-2"></i>
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST" class="flex-1">
                                            <input type="hidden" name="approval_id" value="<?php echo $approval['id']; ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <button type="submit" 
                                                    class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center"
                                                    onclick="return confirm('Are you sure you want to decline this login request?');">
                                                <i class="fas fa-times mr-2"></i>
                                                Decline
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-lg">No pending approval requests</p>
                            <p class="text-gray-400 text-sm mt-2">New login requests will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Approvals Section -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-history text-blue-600 mr-2"></i>
                        Recent Activity (Last 24 Hours)
                    </h2>

                    <?php if ($recentResult->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewed By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($recent = $recentResult->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <?php if ($recent['profile_image']): ?>
                                                        <img src="../<?php echo htmlspecialchars($recent['profile_image']); ?>" 
                                                             alt="Profile" 
                                                             class="w-8 h-8 rounded-full object-cover mr-3">
                                                    <?php else: ?>
                                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-400 to-indigo-500 flex items-center justify-center mr-3">
                                                            <span class="text-white font-bold text-xs">
                                                                <?php echo strtoupper(substr($recent['name'], 0, 1)); ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($recent['name']); ?></div>
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($recent['email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-semibold text-gray-700 uppercase"><?php echo htmlspecialchars($recent['role']); ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="badge-<?php echo $recent['status']; ?> text-xs font-semibold px-3 py-1 rounded-full uppercase">
                                                    <?php echo htmlspecialchars($recent['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($recent['admin_username'] ?? 'N/A'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M j, g:i A', strtotime($recent['reviewed_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No recent activity in the last 24 hours</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');

            // Sidebar toggle functionality
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

            // Overlay click to close sidebar on mobile
            if(overlay) {
                overlay.addEventListener('click', () => {
                    if (sidebar) sidebar.classList.remove('open-mobile');
                    overlay.classList.add('hidden');
                });
            }

            // User menu dropdown toggle
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
        });

        // Auto-refresh ONLY when someone is waiting for approval
        // Refresh after 1:05 (65 seconds) to catch timeout events
        <?php if ($hasPendingRequests): ?>
        setTimeout(function() {
            location.reload();
        }, 65000); // Refresh after 1 minute 5 seconds (after timeout occurs)
        <?php endif; ?>
    </script>
</body>
</html>
