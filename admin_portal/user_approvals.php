<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
$currentPage = 'user_approvals';

require_once '../db_connect.php';

$success_message = '';
$error_message = '';

// Handle approval/disapproval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['approval_id'])) {
        $approval_id = (int)$_POST['approval_id'];
        $action = $_POST['action'];
        $notes = $_POST['notes'] ?? '';
        $admin_id = $_SESSION['user_id'];
        
        if ($action === 'approve' || $action === 'disapprove') {
            $status = ($action === 'approve') ? 'approved' : 'disapproved';
            
            $stmt = $conn->prepare("UPDATE user_approvals SET approval_status = ?, approved_by = ?, approval_time = NOW(), approval_notes = ? WHERE id = ?");
            $stmt->bind_param("sisi", $status, $admin_id, $notes, $approval_id);
            
            if ($stmt->execute()) {
                $success_message = "User " . $status . " successfully!";
                
                // Log the admin action
                $logStmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_description, timestamp) VALUES (?, ?, NOW())");
                $logAction = "Admin System: " . ucfirst($action) . "d user login request (ID: $approval_id)";
                $logStmt->bind_param("is", $admin_id, $logAction);
                $logStmt->execute();
                $logStmt->close();
                
                // Set session flag for showing success modal
                if ($action === 'approve') {
                    $_SESSION['show_approval_success'] = true;
                }
            } else {
                $error_message = "Error updating approval status: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch pending approvals
$pendingQuery = "
    SELECT ua.*, u.first_name, u.last_name, u.middle_name
    FROM user_approvals ua 
    LEFT JOIN users u ON ua.user_id = u.id 
    WHERE ua.approval_status = 'pending' 
    ORDER BY ua.login_time DESC
";
$pendingResult = $conn->query($pendingQuery);

// Fetch recent approvals (last 50)
$recentQuery = "
    SELECT ua.*, u.first_name, u.last_name, u.middle_name, admin.username as approved_by_username
    FROM user_approvals ua 
    LEFT JOIN users u ON ua.user_id = u.id 
    LEFT JOIN users admin ON ua.approved_by = admin.id
    WHERE ua.approval_status IN ('approved', 'disapproved') 
    ORDER BY ua.approval_time DESC 
    LIMIT 50
";
$recentResult = $conn->query($recentQuery);

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(CASE WHEN approval_status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN approval_status = 'approved' THEN 1 END) as approved_count,
        COUNT(CASE WHEN approval_status = 'disapproved' THEN 1 END) as disapproved_count,
        COUNT(*) as total_count
    FROM user_approvals
    WHERE DATE(login_time) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Approvals - Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include 'assets/admin_darkmode.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../mjpharmacy.logo.jpg">
    <style>
        :root { --primary-green: #01A74F; --light-gray: #f3f4f6; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: #1f2937; }
        .sidebar { background-color: var(--primary-green); transition: width 0.3s ease-in-out, transform 0.3s ease-in-out; }
        @media (max-width: 767px) { .sidebar { width: 16rem; transform: translateX(-100%); position: fixed; height: 100%; z-index: 50; } .sidebar.open-mobile { transform: translateX(0); } .overlay { transition: opacity 0.3s ease-in-out; } }
        @media (min-width: 768px) { .sidebar { width: 5rem; } .sidebar.open-desktop { width: 16rem; } .sidebar .nav-text { opacity: 0; visibility: hidden; width: 0; transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease; white-space: nowrap; overflow: hidden; } .sidebar.open-desktop .nav-text { opacity: 1; visibility: visible; width: auto; transition: opacity 0.2s ease 0.1s; } .sidebar .nav-link { justify-content: center; gap: 0; } .sidebar.open-desktop .nav-link { justify-content: flex-start; gap: 1rem; } }
        .nav-link { color: rgba(255, 255, 255, 0.8); } .nav-link svg { color: white; } .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); } .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; } .nav-link.active svg { color: var(--primary-green); }
        .approval-card { transition: all 0.3s ease; }
        .approval-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_header.php'; ?>
        
        <main class="flex-1 overflow-y-auto p-6">
            
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">User Login Approvals</h1>
                    <p class="text-gray-600">Manage user login requests and access permissions</p>
                </div>

                <?php if ($success_message): ?>
                    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-yellow-500 text-white p-6 rounded-lg shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold">Pending</h3>
                                <p class="text-3xl font-bold"><?php echo $stats['pending_count']; ?></p>
                            </div>
                            <i class="fas fa-clock text-4xl opacity-80"></i>
                        </div>
                    </div>
                    <div class="bg-green-500 text-white p-6 rounded-lg shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold">Approved</h3>
                                <p class="text-3xl font-bold"><?php echo $stats['approved_count']; ?></p>
                            </div>
                            <i class="fas fa-check-circle text-4xl opacity-80"></i>
                        </div>
                    </div>
                    <div class="bg-red-500 text-white p-6 rounded-lg shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold">Disapproved</h3>
                                <p class="text-3xl font-bold"><?php echo $stats['disapproved_count']; ?></p>
                            </div>
                            <i class="fas fa-times-circle text-4xl opacity-80"></i>
                        </div>
                    </div>
                    <div class="bg-blue-500 text-white p-6 rounded-lg shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold">Total (30d)</h3>
                                <p class="text-3xl font-bold"><?php echo $stats['total_count']; ?></p>
                            </div>
                            <i class="fas fa-users text-4xl opacity-80"></i>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals Section -->
                <div class="bg-white rounded-lg shadow-lg mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-clock text-yellow-500 mr-2"></i>
                            Pending Login Requests
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if ($pendingResult->num_rows > 0): ?>
                            <div class="grid gap-4">
                                <?php while ($approval = $pendingResult->fetch_assoc()): ?>
                                    <div class="approval-card bg-gray-50 border border-gray-200 rounded-lg p-4">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center mb-2">
                                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                                        <?php echo strtoupper(substr($approval['username'], 0, 2)); ?>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-semibold text-gray-900">
                                                            <?php 
                                                            $displayName = '';
                                                            if ($approval['first_name'] && $approval['last_name']) {
                                                                $displayName = $approval['first_name'];
                                                                if ($approval['middle_name']) {
                                                                    $displayName .= ' ' . $approval['middle_name'];
                                                                }
                                                                $displayName .= ' ' . $approval['last_name'];
                                                            } else {
                                                                $displayName = $approval['username'];
                                                            }
                                                            echo htmlspecialchars($displayName);
                                                            ?>
                                                        </h3>
                                                        <p class="text-sm text-gray-600">@<?php echo htmlspecialchars($approval['username']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                    <div>
                                                        <span class="text-gray-500">Email:</span>
                                                        <p class="font-medium"><?php echo htmlspecialchars($approval['email']); ?></p>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-500">Role:</span>
                                                        <p class="font-medium capitalize"><?php echo htmlspecialchars($approval['role']); ?></p>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-500">Login Time:</span>
                                                        <p class="font-medium"><?php echo date('M j, Y g:i A', strtotime($approval['login_time'])); ?></p>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-500">IP Address:</span>
                                                        <p class="font-medium"><?php echo htmlspecialchars($approval['ip_address']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-4 md:mt-0 md:ml-6 flex flex-col sm:flex-row gap-2">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="approval_id" value="<?php echo $approval['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="notes" value="Approved by admin">
                                                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                                                        <i class="fas fa-check mr-2"></i>Approve
                                                    </button>
                                                </form>
                                                <button onclick="openDisapprovalModal(<?php echo $approval['id']; ?>)" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                                                    <i class="fas fa-times mr-2"></i>Disapprove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Pending Requests</h3>
                                <p class="text-gray-600">All login requests have been processed.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Approvals Section -->
                <div class="bg-white rounded-lg shadow-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-history text-blue-500 mr-2"></i>
                            Recent Decisions
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if ($recentResult->num_rows > 0): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php while ($recent = $recentResult->fetch_assoc()): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold mr-3">
                                                            <?php echo strtoupper(substr($recent['username'], 0, 2)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900">
                                                                <?php 
                                                                $displayName = '';
                                                                if ($recent['first_name'] && $recent['last_name']) {
                                                                    $displayName = $recent['first_name'];
                                                                    if ($recent['middle_name']) {
                                                                        $displayName .= ' ' . $recent['middle_name'];
                                                                    }
                                                                    $displayName .= ' ' . $recent['last_name'];
                                                                } else {
                                                                    $displayName = $recent['username'];
                                                                }
                                                                echo htmlspecialchars($displayName);
                                                                ?>
                                                            </div>
                                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($recent['email']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 capitalize">
                                                        <?php echo htmlspecialchars($recent['role']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php if ($recent['approval_status'] === 'approved'): ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            <i class="fas fa-check mr-1"></i>Approved
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                            <i class="fas fa-times mr-1"></i>Disapproved
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($recent['approved_by_username'] ?? 'N/A'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo date('M j, Y g:i A', strtotime($recent['approval_time'])); ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($recent['approval_notes'] ?? '-'); ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-history text-6xl text-gray-400 mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Recent Decisions</h3>
                                <p class="text-gray-600">No approval decisions have been made yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Disapproval Modal -->
    <div id="disapprovalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Disapprove Login Request</h3>
                    <form id="disapprovalForm" method="POST">
                        <input type="hidden" name="approval_id" id="disapprovalApprovalId">
                        <input type="hidden" name="action" value="disapprove">
                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Reason for disapproval:</label>
                            <textarea name="notes" id="notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Enter reason for disapproval..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeDisapprovalModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">Disapprove</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Admin Success Modal -->
    <?php if (isset($_SESSION['show_approval_success'])): ?>
    <div id="adminSuccessModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full transform transition-all">
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">User Approved Successfully!</h3>
                    <p class="text-gray-600 mb-4">The user has been notified and can now access the system.</p>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                        <div class="flex items-center text-sm text-green-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span>The user will see a success message and be automatically redirected to their dashboard.</span>
                        </div>
                    </div>
                    <button onclick="closeAdminSuccessModal()" class="w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                        <i class="fas fa-check mr-2"></i>Got it!
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php 
        unset($_SESSION['show_approval_success']); 
    endif; 
    ?>

    <script>
        // Sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');

            // Sidebar toggle functionality
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function() {
                    if (window.innerWidth >= 768) {
                        sidebar.classList.toggle('open-desktop');
                    } else {
                        sidebar.classList.toggle('open-mobile');
                    }
                });
            }

            // User menu toggle
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function() {
                    userMenu.classList.toggle('hidden');
                });

                // Close user menu when clicking outside
                document.addEventListener('click', function(event) {
                    if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }

            // Close sidebar on mobile when clicking outside
            document.addEventListener('click', function(event) {
                if (window.innerWidth < 768 && 
                    !sidebar.contains(event.target) && 
                    !sidebarToggleBtn.contains(event.target) && 
                    sidebar.classList.contains('open-mobile')) {
                    sidebar.classList.remove('open-mobile');
                }
            });
        });

        // Approval modal functions
        function openDisapprovalModal(approvalId) {
            document.getElementById('disapprovalApprovalId').value = approvalId;
            document.getElementById('disapprovalModal').classList.remove('hidden');
        }

        function closeDisapprovalModal() {
            document.getElementById('disapprovalModal').classList.add('hidden');
            document.getElementById('notes').value = '';
        }

        function closeAdminSuccessModal() {
            const modal = document.getElementById('adminSuccessModal');
            if (modal) {
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }

        // Close modal when clicking outside
        document.getElementById('disapprovalModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDisapprovalModal();
            }
        });
    </script>
</body>
</html>
