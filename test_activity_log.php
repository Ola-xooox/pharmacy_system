<?php
session_start();
require 'db_connect.php';

// This is a test file to verify activity logging is working
// You can run this file to test if the activity logging system works

// Simulate a logged-in user for testing
if (!isset($_SESSION['user_id'])) {
    echo "<h1>Activity Logging Test</h1>";
    echo "<p>Please log in first to test activity logging.</p>";
    echo "<a href='index.php'>Go to Login</a>";
    exit();
}

// Test the activity logging function
function logUserActivity($conn, $action_description) {
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

    if ($userId === 0) {
        return false;
    }

    $fullAction = ucfirst($userRole) . " System: " . $action_description;
    
    $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_description, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $userId, $fullAction);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Test activity logging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_action'])) {
    $testAction = $_POST['test_action'];
    $result = logUserActivity($conn, "Test activity: " . $testAction);
    
    if ($result) {
        $message = "✅ Activity logged successfully: " . $testAction;
    } else {
        $message = "❌ Failed to log activity: " . $testAction;
    }
}

// Get recent activities for this user
$stmt = $conn->prepare("
    SELECT ual.action_description, ual.timestamp, u.username, u.role
    FROM user_activity_log ual
    JOIN users u ON ual.user_id = u.id
    WHERE ual.user_id = ?
    ORDER BY ual.timestamp DESC
    LIMIT 10
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logging Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Activity Logging Test</h1>
            
            <div class="mb-6">
                <p class="text-gray-600 mb-4">
                    <strong>Current User:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?> 
                    (<?php echo htmlspecialchars($_SESSION['role']); ?>)
                </p>
                
                <?php if (isset($message)): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Test Activity Logging -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Test Activity Logging</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="test_action" class="block text-sm font-medium text-gray-700 mb-2">
                                Test Action Description:
                            </label>
                            <input 
                                type="text" 
                                id="test_action" 
                                name="test_action" 
                                placeholder="e.g., Viewed test page, Clicked test button"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            >
                        </div>
                        <button 
                            type="submit" 
                            class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                        >
                            Log Test Activity
                        </button>
                    </form>
                </div>

                <!-- Recent Activities -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Your Recent Activities</h2>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <?php if (!empty($activities)): ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="bg-white rounded border p-3">
                                    <p class="text-sm font-medium text-gray-800">
                                        <?php echo htmlspecialchars($activity['action_description']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?php echo date('M d, Y g:i A', strtotime($activity['timestamp'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm">No activities found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-4">
                <a href="admin_portal/user_activity_log.php" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    View Full Activity Log
                </a>
                <a href="logout.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Test Logout (Will Log Activity)
                </a>
            </div>
        </div>
    </div>
</body>
</html>
