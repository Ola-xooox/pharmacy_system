<?php
// All PHP data fetching logic MUST go at the top of the file
session_start();
// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
$currentPage = 'inventory_report'; 

// --- Start of PHP Data Fetching ---
require_once '../db_connect.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the timezone
date_default_timezone_set('Asia/Manila');

// Get selected date from URL parameter, default to today
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate the date format
if (!DateTime::createFromFormat('Y-m-d', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Fetch Inventory Summary Data - Count of unique product names (up to selected date)
$inventorySummaryStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS total_products FROM products WHERE DATE(date_added) <= ?");
$inventorySummaryStmt->bind_param("s", $selectedDate);
$inventorySummaryStmt->execute();
$inventorySummary = $inventorySummaryStmt->get_result()->fetch_assoc();
$totalProducts = $inventorySummary['total_products'] ?? 0;
$inventorySummaryStmt->close();

// Fetch detailed products for modal
$totalProductsDetailStmt = $conn->prepare("
    SELECT name, SUM(stock) as total_stock, MIN(expiration_date) as earliest_expiry, 
           GROUP_CONCAT(DISTINCT supplier) as suppliers, MAX(date_added) as last_added
    FROM products 
    WHERE DATE(date_added) <= ?
    GROUP BY name 
    ORDER BY last_added DESC
");
$totalProductsDetailStmt->bind_param("s", $selectedDate);
$totalProductsDetailStmt->execute();
$totalProductsDetail = $totalProductsDetailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalProductsDetailStmt->close();

// Fetch Expiration Alert Count (within 1 month from selected date)
$expiringSoonStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS expiring_soon FROM products WHERE expiration_date > ? AND expiration_date <= DATE_ADD(?, INTERVAL 1 MONTH) AND DATE(date_added) <= ?");
$expiringSoonStmt->bind_param("sss", $selectedDate, $selectedDate, $selectedDate);
$expiringSoonStmt->execute();
$expiringSoon = $expiringSoonStmt->get_result()->fetch_assoc()['expiring_soon'] ?? 0;
$expiringSoonStmt->close();

// Fetch detailed expiring products for modal
$expiringDetailStmt = $conn->prepare("
    SELECT name, lot_number, batch_number, stock, expiration_date, supplier,
           DATEDIFF(expiration_date, ?) as days_until_expiry
    FROM products 
    WHERE expiration_date > ? AND expiration_date <= DATE_ADD(?, INTERVAL 1 MONTH) 
    AND DATE(date_added) <= ?
    ORDER BY expiration_date ASC
");
$expiringDetailStmt->bind_param("ssss", $selectedDate, $selectedDate, $selectedDate, $selectedDate);
$expiringDetailStmt->execute();
$expiringDetail = $expiringDetailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$expiringDetailStmt->close();

// Fetch Expired Products Count (products expired ON or BEFORE selected date)
$expiredStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS expired_count FROM products WHERE expiration_date <= ? AND DATE(date_added) <= ?");
$expiredStmt->bind_param("ss", $selectedDate, $selectedDate);
$expiredStmt->execute();
$expiredCount = $expiredStmt->get_result()->fetch_assoc()['expired_count'] ?? 0;
$expiredStmt->close();

// Fetch detailed expired products for modal
$expiredDetailStmt = $conn->prepare("
    SELECT name, lot_number, batch_number, stock, expiration_date, supplier,
           DATEDIFF(?, expiration_date) as days_expired
    FROM products 
    WHERE expiration_date <= ? AND DATE(date_added) <= ?
    ORDER BY expiration_date DESC
");
$expiredDetailStmt->bind_param("sss", $selectedDate, $selectedDate, $selectedDate);
$expiredDetailStmt->execute();
$expiredDetail = $expiredDetailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$expiredDetailStmt->close();

// Fetch All Products for the Table (Grouped by name, up to selected date)
$inventoryListStmt = $conn->prepare("
    SELECT name, SUM(stock) as stock, MIN(expiration_date) as expiration_date, 
           GROUP_CONCAT(DISTINCT supplier) as supplier, MAX(date_added) as date_added 
    FROM products 
    WHERE DATE(date_added) <= ?
    GROUP BY name 
    ORDER BY name ASC
");
$inventoryListStmt->bind_param("s", $selectedDate);
$inventoryListStmt->execute();
$inventoryList = $inventoryListStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$inventoryListStmt->close();

// Data for the chart (Top 10 products by stock, up to selected date)
$chartDataStmt = $conn->prepare("SELECT name, SUM(stock) as total_stock FROM products WHERE DATE(date_added) <= ? GROUP BY name ORDER BY total_stock DESC LIMIT 10");
$chartDataStmt->bind_param("s", $selectedDate);
$chartDataStmt->execute();
$chartData = $chartDataStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$chartDataStmt->close();

$conn->close();
// --- End of PHP Data Fetching ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Inventory Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include 'assets/admin_darkmode.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="icon" type="image/x-icon" href="../mjpharmacy.logo.jpg">
    <style>
        :root { --primary-green: #01A74F; --light-gray: #f3f4f6; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: #1f2937; }
        .sidebar { background-color: var(--primary-green); transition: width 0.3s ease-in-out, transform 0.3s ease-in-out; }
        @media (max-width: 767px) { .sidebar { width: 16rem; transform: translateX(-100%); position: fixed; height: 100%; z-index: 50; } .sidebar.open-mobile { transform: translateX(0); } .overlay { transition: opacity 0.3s ease-in-out; } }
        @media (min-width: 768px) { .sidebar { width: 5rem; } .sidebar.open-desktop { width: 16rem; } .sidebar .nav-text { opacity: 0; visibility: hidden; width: 0; transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease; white-space: nowrap; overflow: hidden; } .sidebar.open-desktop .nav-text { opacity: 1; visibility: visible; width: auto; transition: opacity 0.2s ease 0.1s; } .sidebar .nav-link { justify-content: center; gap: 0; } .sidebar.open-desktop .nav-link { justify-content: flex-start; gap: 1rem; } }
        .nav-link { color: rgba(255, 255, 255, 0.8); } .nav-link svg { color: white; } .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); } .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; } .nav-link.active svg { color: var(--primary-green); }
        
        /* Dark mode text color overrides */
        .dark * {
            color: white !important;
        }
        
        /* Preserve specific colors that should remain unchanged in dark mode */
        .dark .text-orange-500,
        .dark .text-red-500,
        .dark .text-green-500,
        .dark .text-blue-500,
        .dark .text-yellow-500,
        .dark .text-purple-500,
        .dark .text-pink-500,
        .dark .text-indigo-500 {
            color: inherit !important;
        }
        
        /* Preserve brand colors */
        .dark .text-\[#236B3D\] {
            color: #4ade80 !important; /* Light green for better visibility in dark mode */
        }
        
        /* Input and form elements in dark mode */
        .dark input,
        .dark select,
        .dark textarea {
            color: white !important;
            background-color: #374151 !important;
            border-color: #4b5563 !important;
        }
        
        .dark input::placeholder {
            color: #9ca3af !important;
        }
        
        /* Table specific overrides */
        .dark th {
            color: white !important;
        }
        
        .dark td {
            color: white !important;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'admin_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_header.php'; ?>

        <main class="flex-1 overflow-y-auto p-6">
            <div id="page-content">
                <div id="inventory-report-page" class="space-y-8">
                    <!-- Date Filter Section -->
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Inventory Report</h1>
                                <p class="text-sm text-gray-600 mt-1">Monitor your inventory status and product analytics</p>
                            </div>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                <label for="date-filter" class="text-sm font-medium text-gray-700 whitespace-nowrap">Select Date:</label>
                                <div class="flex items-center gap-2">
                                    <input type="date" id="date-filter" value="<?php echo $selectedDate; ?>" max="<?php echo date('Y-m-d'); ?>" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <button id="apply-date-filter" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg transition-colors">
                                        Apply
                                    </button>
                                    <button id="today-btn" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors">
                                        Today
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Showing inventory data as of:</span>
                                <span class="font-semibold text-gray-800"><?php echo date('l, F j, Y', strtotime($selectedDate)); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" id="total-products-card">
                            <div>
                                <p class="text-sm text-gray-500">Total products</p>
                                <p class="text-2xl font-bold text-[#236B3D]"><?php echo htmlspecialchars($totalProducts); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Click to view details</p>
                            </div>
                            <i class="ph-fill ph-package text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" id="expiring-alert-card">
                            <div>
                                <p class="text-sm text-gray-500">Expiration Alert</p>
                                <p class="text-2xl font-bold text-orange-500"><?php echo htmlspecialchars($expiringSoon); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Click to view details</p>
                            </div>
                            <i class="ph-fill ph-clock-countdown text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" id="expired-products-card">
                            <div>
                                <p class="text-sm text-gray-500">Expired Products</p>
                                <p class="text-2xl font-bold text-red-500"><?php echo htmlspecialchars($expiredCount); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Click to view details</p>
                            </div>
                            <i class="ph-fill ph-warning-circle text-4xl text-gray-400"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <h2 class="text-xl font-bold mb-4 text-gray-800">Inventory Status (Top 10)</h2>
                        <div style="position: relative; height:300px;">
                            <canvas id="inventoryStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800">Inventory Management</h2>
                            <input type="text" id="inventory-search" placeholder="Search by product name or supplier..." class="w-1/3 p-2 rounded-lg border border-gray-300 focus:outline-none focus:ring focus:ring-green-500 focus:ring-opacity-50">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left" id="inventory-table">
                                <thead>
                                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                                        <th class="py-3 px-4 font-semibold text-gray-600">Product</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Stock</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Earliest Expiry</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Supplier(s)</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Last Received</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($inventoryList)): ?>
                                        <?php foreach ($inventoryList as $product): ?>
                                        <tr class="border-b border-gray-200">
                                            <td class="py-3 px-4"><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td class="py-3 px-4 font-bold"><?php echo htmlspecialchars($product['stock']); ?></td>
                                            <td class="py-3 px-4"><?php echo $product['expiration_date'] ? htmlspecialchars(date('M d, Y', strtotime($product['expiration_date']))) : 'N/A'; ?></td>
                                            <td class="py-3 px-4"><?php echo htmlspecialchars($product['supplier']); ?></td>
                                            <td class="py-3 px-4"><?php echo htmlspecialchars(date('M d, Y', strtotime($product['date_added']))); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-8 text-gray-500">No inventory data found.</td></tr>
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

    <!-- Total Products Modal -->
    <div id="total-products-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Total Products Details</h3>
                    <button id="close-products-modal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <p class="text-sm text-gray-600 mt-2">All products in inventory as of <?php echo date('M d, Y', strtotime($selectedDate)); ?></p>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-4">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">Total Products:</span>
                            <span class="text-2xl font-bold text-green-600"><?php echo $totalProducts; ?></span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="py-3 px-4 font-semibold text-gray-600">#</th>
                                <th class="py-3 px-4 font-semibold text-gray-600">Product Name</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Total Stock</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Earliest Expiry</th>
                                <th class="py-3 px-4 font-semibold text-gray-600">Suppliers</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Last Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($totalProductsDetail)): ?>
                                <?php foreach ($totalProductsDetail as $index => $product): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-4 text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="py-3 px-4 text-center font-semibold text-blue-600"><?php echo number_format($product['total_stock']); ?></td>
                                        <td class="py-3 px-4 text-center text-sm">
                                            <?php if ($product['earliest_expiry']): ?>
                                                <span class="text-gray-600"><?php echo date('M d, Y', strtotime($product['earliest_expiry'])); ?></span>
                                            <?php else: ?>
                                                <span class="text-gray-400">No expiry</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($product['suppliers']); ?></td>
                                        <td class="py-3 px-4 text-center text-sm text-gray-600"><?php echo date('M d, Y', strtotime($product['last_added'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <button id="close-products-modal-btn" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Expiring Products Modal -->
    <div id="expiring-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Expiration Alert Details</h3>
                    <button id="close-expiring-modal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <p class="text-sm text-gray-600 mt-2">Products expiring within 1 month from <?php echo date('M d, Y', strtotime($selectedDate)); ?></p>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-4">
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">Products Expiring Soon:</span>
                            <span class="text-2xl font-bold text-orange-600"><?php echo $expiringSoon; ?></span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="py-3 px-4 font-semibold text-gray-600">#</th>
                                <th class="py-3 px-4 font-semibold text-gray-600">Product Name</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Lot #</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Batch #</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Stock</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Days Until Expiry</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Expiration Date</th>
                                <th class="py-3 px-4 font-semibold text-gray-600">Supplier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($expiringDetail)): ?>
                                <?php foreach ($expiringDetail as $index => $product): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-4 text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($product['lot_number'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <?php echo htmlspecialchars($product['batch_number'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center font-semibold"><?php echo number_format($product['stock']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <?php echo $product['days_until_expiry']; ?> days
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center text-sm text-orange-600 font-medium"><?php echo date('M d, Y', strtotime($product['expiration_date'])); ?></td>
                                        <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($product['supplier']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-gray-500">No products expiring soon.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <button id="close-expiring-modal-btn" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Expired Products Modal -->
    <div id="expired-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Expired Products Details</h3>
                    <button id="close-expired-modal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <p class="text-sm text-gray-600 mt-2">Products expired as of <?php echo date('M d, Y', strtotime($selectedDate)); ?></p>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">Expired Products:</span>
                            <span class="text-2xl font-bold text-red-600"><?php echo $expiredCount; ?></span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="py-3 px-4 font-semibold text-gray-600">#</th>
                                <th class="py-3 px-4 font-semibold text-gray-600">Product Name</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Lot #</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Batch #</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Stock</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Days Expired</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Expiration Date</th>
                                <th class="py-3 px-4 font-semibold text-gray-600">Supplier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($expiredDetail)): ?>
                                <?php foreach ($expiredDetail as $index => $product): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-4 text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($product['lot_number'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <?php echo htmlspecialchars($product['batch_number'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center font-semibold"><?php echo number_format($product['stock']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <?php echo $product['days_expired']; ?> days
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center text-sm text-red-600 font-medium"><?php echo date('M d, Y', strtotime($product['expiration_date'])); ?></td>
                                        <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($product['supplier']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-gray-500">No expired products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <button id="close-expired-modal-btn" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    Close
                </button>
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
            const dateTimeEl = document.getElementById('date-time');
            const searchInput = document.getElementById('inventory-search');
            const table = document.getElementById('inventory-table').getElementsByTagName('tbody')[0];

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

            if(userMenuButton && userMenu){
                userMenuButton.addEventListener('click', () => userMenu.classList.toggle('hidden'));
                window.addEventListener('click', (e) => {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
            
            function updateDateTime() {
                if(dateTimeEl){
                    const now = new Date();
                    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                    dateTimeEl.textContent = now.toLocaleDateString('en-US', options);
                }
            }
            updateDateTime();
            setInterval(updateDateTime, 60000);

            // Chart
            const chartLabels = <?php echo json_encode(array_column($chartData, 'name')); ?>;
            const chartStockData = <?php echo json_encode(array_column($chartData, 'total_stock')); ?>;
            const inventoryStatusChartCanvas = document.getElementById('inventoryStatusChart');
            if (inventoryStatusChartCanvas) {
                const ctx = inventoryStatusChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Total Stock',
                            backgroundColor: '#01A74F',
                            borderColor: '#018d43',
                            data: chartStockData,
                        }]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        scales: { y: { beginAtZero: true } } 
                    }
                });
            }

            // Search functionality
            searchInput.addEventListener('keyup', function() {
                const filter = searchInput.value.toLowerCase();
                const rows = table.getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    const productName = rows[i].getElementsByTagName('td')[0];
                    const supplier = rows[i].getElementsByTagName('td')[3];
                    if (productName || supplier) {
                        const productText = productName.textContent || productName.innerText;
                        const supplierText = supplier.textContent || supplier.innerText;
                        if (productText.toLowerCase().indexOf(filter) > -1 || supplierText.toLowerCase().indexOf(filter) > -1) {
                            rows[i].style.display = "";
                        } else {
                            rows[i].style.display = "none";
                        }
                    }
                }
            });

            // Date Filter functionality
            const dateFilter = document.getElementById('date-filter');
            const applyDateFilter = document.getElementById('apply-date-filter');
            const todayBtn = document.getElementById('today-btn');

            if (applyDateFilter) {
                applyDateFilter.addEventListener('click', () => {
                    const selectedDate = dateFilter.value;
                    if (selectedDate) {
                        window.location.href = `inventory_report.php?date=${selectedDate}`;
                    }
                });
            }

            if (todayBtn) {
                todayBtn.addEventListener('click', () => {
                    window.location.href = 'inventory_report.php';
                });
            }

            // Allow Enter key to apply filter
            if (dateFilter) {
                dateFilter.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        const selectedDate = dateFilter.value;
                        if (selectedDate) {
                            window.location.href = `inventory_report.php?date=${selectedDate}`;
                        }
                    }
                });
            }

            // Modal functionality
            const totalProductsCard = document.getElementById('total-products-card');
            const totalProductsModal = document.getElementById('total-products-modal');
            const closeTotalProductsModal = document.getElementById('close-products-modal');
            const closeTotalProductsModalBtn = document.getElementById('close-products-modal-btn');

            const expiringAlertCard = document.getElementById('expiring-alert-card');
            const expiringModal = document.getElementById('expiring-modal');
            const closeExpiringModal = document.getElementById('close-expiring-modal');
            const closeExpiringModalBtn = document.getElementById('close-expiring-modal-btn');

            const expiredProductsCard = document.getElementById('expired-products-card');
            const expiredModal = document.getElementById('expired-modal');
            const closeExpiredModal = document.getElementById('close-expired-modal');
            const closeExpiredModalBtn = document.getElementById('close-expired-modal-btn');

            // Total Products Modal
            if (totalProductsCard) {
                totalProductsCard.addEventListener('click', () => {
                    totalProductsModal.classList.remove('hidden');
                });
            }

            if (closeTotalProductsModal) {
                closeTotalProductsModal.addEventListener('click', () => {
                    totalProductsModal.classList.add('hidden');
                });
            }

            if (closeTotalProductsModalBtn) {
                closeTotalProductsModalBtn.addEventListener('click', () => {
                    totalProductsModal.classList.add('hidden');
                });
            }

            if (totalProductsModal) {
                totalProductsModal.addEventListener('click', (e) => {
                    if (e.target === totalProductsModal) {
                        totalProductsModal.classList.add('hidden');
                    }
                });
            }

            // Expiring Products Modal
            if (expiringAlertCard) {
                expiringAlertCard.addEventListener('click', () => {
                    expiringModal.classList.remove('hidden');
                });
            }

            if (closeExpiringModal) {
                closeExpiringModal.addEventListener('click', () => {
                    expiringModal.classList.add('hidden');
                });
            }

            if (closeExpiringModalBtn) {
                closeExpiringModalBtn.addEventListener('click', () => {
                    expiringModal.classList.add('hidden');
                });
            }

            if (expiringModal) {
                expiringModal.addEventListener('click', (e) => {
                    if (e.target === expiringModal) {
                        expiringModal.classList.add('hidden');
                    }
                });
            }

            // Expired Products Modal
            if (expiredProductsCard) {
                expiredProductsCard.addEventListener('click', () => {
                    expiredModal.classList.remove('hidden');
                });
            }

            if (closeExpiredModal) {
                closeExpiredModal.addEventListener('click', () => {
                    expiredModal.classList.add('hidden');
                });
            }

            if (closeExpiredModalBtn) {
                closeExpiredModalBtn.addEventListener('click', () => {
                    expiredModal.classList.add('hidden');
                });
            }

            if (expiredModal) {
                expiredModal.addEventListener('click', (e) => {
                    if (e.target === expiredModal) {
                        expiredModal.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>
</html>