<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
$currentPage = 'dashboard';

// --- Start of PHP Data Fetching ---
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

// Fetch Sales Data for selected date or all time
if ($isAllTime) {
    $salesStmt = $conn->prepare("SELECT SUM(total_price) AS total_sales_today FROM purchase_history");
    $salesStmt->execute();
} else {
    $salesStmt = $conn->prepare("SELECT SUM(total_price) AS total_sales_today FROM purchase_history WHERE DATE(transaction_date) = ?");
    $salesStmt->bind_param("s", $selectedDate);
    $salesStmt->execute();
}
$salesData = $salesStmt->get_result()->fetch_assoc();
$totalSalesToday = $salesData['total_sales_today'] ?? 0;
$salesStmt->close();

// Fetch Total Products
if ($isAllTime) {
    $productsStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS total_products FROM products");
    $productsStmt->execute();
} else {
    $productsStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS total_products FROM products WHERE DATE(date_added) <= ?");
    $productsStmt->bind_param("s", $selectedDate);
    $productsStmt->execute();
}
$productsData = $productsStmt->get_result()->fetch_assoc();
$totalProducts = $productsData['total_products'] ?? 0;
$productsStmt->close();

// Fetch Expiration Alert Count (within 1 month from selected date or today)
$referenceDate = $isAllTime ? date('Y-m-d') : $selectedDate;
if ($isAllTime) {
    $expAlertStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS exp_alert_count FROM products WHERE expiration_date > ? AND expiration_date <= DATE_ADD(?, INTERVAL 1 MONTH)");
    $expAlertStmt->bind_param("ss", $referenceDate, $referenceDate);
} else {
    $expAlertStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS exp_alert_count FROM products WHERE expiration_date > ? AND expiration_date <= DATE_ADD(?, INTERVAL 1 MONTH) AND DATE(date_added) <= ?");
    $expAlertStmt->bind_param("sss", $referenceDate, $referenceDate, $selectedDate);
}
$expAlertStmt->execute();
$expAlertData = $expAlertStmt->get_result()->fetch_assoc();
$expAlertCount = $expAlertData['exp_alert_count'] ?? 0;
$expAlertStmt->close();


// Fetch Low Stock Count (Sum of stock per product name is <= 5)
if ($isAllTime) {
    $lowStockStmt = $conn->prepare("
        SELECT COUNT(*) as low_stock_count FROM (
            SELECT name
            FROM products
            WHERE (expiration_date > ? OR expiration_date IS NULL)
            GROUP BY name
            HAVING SUM(stock) <= 5 AND SUM(stock) > 0
        ) AS low_stock_products
    ");
    $lowStockStmt->bind_param("s", $referenceDate);
} else {
    $lowStockStmt = $conn->prepare("
        SELECT COUNT(*) as low_stock_count FROM (
            SELECT name
            FROM products
            WHERE (expiration_date > ? OR expiration_date IS NULL) AND DATE(date_added) <= ?
            GROUP BY name
            HAVING SUM(stock) <= 5 AND SUM(stock) > 0
        ) AS low_stock_products
    ");
    $lowStockStmt->bind_param("ss", $referenceDate, $selectedDate);
}
$lowStockStmt->execute();
$lowStockData = $lowStockStmt->get_result()->fetch_assoc();
$lowStockCount = $lowStockData['low_stock_count'] ?? 0;
$lowStockStmt->close();


// Fetch Recent Transactions
if ($isAllTime) {
    $transactionsStmt = $conn->prepare("SELECT product_name, quantity, total_price, transaction_date FROM purchase_history ORDER BY transaction_date DESC LIMIT 10");
    $transactionsStmt->execute();
} else {
    $transactionsStmt = $conn->prepare("SELECT product_name, quantity, total_price, transaction_date FROM purchase_history WHERE DATE(transaction_date) = ? ORDER BY transaction_date DESC LIMIT 10");
    $transactionsStmt->bind_param("s", $selectedDate);
    $transactionsStmt->execute();
}
$recentTransactions = $transactionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$transactionsStmt->close();

// Fetch detailed sales data for modal
if ($isAllTime) {
    $allTransactionsStmt = $conn->prepare("SELECT product_name, quantity, total_price, transaction_date FROM purchase_history ORDER BY transaction_date DESC LIMIT 100");
    $allTransactionsStmt->execute();
} else {
    $allTransactionsStmt = $conn->prepare("SELECT product_name, quantity, total_price, transaction_date FROM purchase_history WHERE DATE(transaction_date) = ? ORDER BY transaction_date DESC");
    $allTransactionsStmt->bind_param("s", $selectedDate);
    $allTransactionsStmt->execute();
}
$allTransactions = $allTransactionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$allTransactionsStmt->close();

// Fetch detailed products data for modal
if ($isAllTime) {
    $allProductsStmt = $conn->prepare("SELECT name, SUM(stock) as total_stock, MIN(expiration_date) as earliest_expiry, GROUP_CONCAT(DISTINCT supplier) as suppliers, MAX(date_added) as last_added FROM products GROUP BY name ORDER BY total_stock DESC");
    $allProductsStmt->execute();
} else {
    $allProductsStmt = $conn->prepare("SELECT name, SUM(stock) as total_stock, MIN(expiration_date) as earliest_expiry, GROUP_CONCAT(DISTINCT supplier) as suppliers, MAX(date_added) as last_added FROM products WHERE DATE(date_added) <= ? GROUP BY name ORDER BY total_stock DESC");
    $allProductsStmt->bind_param("s", $selectedDate);
    $allProductsStmt->execute();
}
$allProducts = $allProductsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$allProductsStmt->close();

// Fetch expiring products for modal
if ($isAllTime) {
    $expiringProductsStmt = $conn->prepare("SELECT name, lot_number, batch_number, stock, expiration_date, supplier, DATEDIFF(expiration_date, ?) as days_until_expiry FROM products WHERE expiration_date > ? AND expiration_date <= DATE_ADD(?, INTERVAL 1 MONTH) ORDER BY expiration_date ASC");
    $expiringProductsStmt->bind_param("sss", $referenceDate, $referenceDate, $referenceDate);
} else {
    $expiringProductsStmt = $conn->prepare("SELECT name, lot_number, batch_number, stock, expiration_date, supplier, DATEDIFF(expiration_date, ?) as days_until_expiry FROM products WHERE expiration_date > ? AND expiration_date <= DATE_ADD(?, INTERVAL 1 MONTH) AND DATE(date_added) <= ? ORDER BY expiration_date ASC");
    $expiringProductsStmt->bind_param("ssss", $referenceDate, $referenceDate, $referenceDate, $selectedDate);
}
$expiringProductsStmt->execute();
$expiringProducts = $expiringProductsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$expiringProductsStmt->close();

// Fetch low stock products for modal
if ($isAllTime) {
    $lowStockProductsStmt = $conn->prepare("SELECT name, SUM(stock) as total_stock, MIN(expiration_date) as earliest_expiry, GROUP_CONCAT(DISTINCT supplier) as suppliers FROM products WHERE (expiration_date > ? OR expiration_date IS NULL) GROUP BY name HAVING SUM(stock) <= 5 AND SUM(stock) > 0 ORDER BY total_stock ASC");
    $lowStockProductsStmt->bind_param("s", $referenceDate);
} else {
    $lowStockProductsStmt = $conn->prepare("SELECT name, SUM(stock) as total_stock, MIN(expiration_date) as earliest_expiry, GROUP_CONCAT(DISTINCT supplier) as suppliers FROM products WHERE (expiration_date > ? OR expiration_date IS NULL) AND DATE(date_added) <= ? GROUP BY name HAVING SUM(stock) <= 5 AND SUM(stock) > 0 ORDER BY total_stock ASC");
    $lowStockProductsStmt->bind_param("ss", $referenceDate, $selectedDate);
}
$lowStockProductsStmt->execute();
$lowStockProducts = $lowStockProductsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lowStockProductsStmt->close();

// Fetch Sales Data for the Chart (daily sales for the last 7 days)
$chartDataStmt = $conn->prepare("
    SELECT DATE(transaction_date) as date, SUM(total_price) as total_sales
    FROM purchase_history
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(transaction_date)
    ORDER BY DATE(transaction_date) ASC
");
$chartDataStmt->execute();
$rawChartData = $chartDataStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$chartDataStmt->close();

// Fetch top 5 products for inventory chart
if ($isAllTime) {
    $inventoryChartStmt = $conn->prepare("
        SELECT name, SUM(stock) as total_stock 
        FROM products 
        GROUP BY name 
        ORDER BY total_stock DESC 
        LIMIT 5
    ");
    $inventoryChartStmt->execute();
} else {
    $inventoryChartStmt = $conn->prepare("
        SELECT name, SUM(stock) as total_stock 
        FROM products 
        WHERE DATE(date_added) <= ?
        GROUP BY name 
        ORDER BY total_stock DESC 
        LIMIT 5
    ");
    $inventoryChartStmt->bind_param("s", $selectedDate);
    $inventoryChartStmt->execute();
}
$inventoryChartResult = $inventoryChartStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$inventoryChartStmt->close();


$conn->close();

// Prepare sales chart data
$chartLabels = [];
$chartSalesData = [];
$period = new DatePeriod(new DateTime('-6 days'), new DateInterval('P1D'), new DateTime('+1 day'));
foreach ($period as $date) {
    $formattedDate = $date->format('Y-m-d');
    $chartLabels[] = $date->format('D');
    $found = false;
    foreach ($rawChartData as $row) {
        if ($row['date'] === $formattedDate) {
            $chartSalesData[] = $row['total_sales'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $chartSalesData[] = 0;
    }
}

// Prepare inventory chart data
$inventoryChartLabels = array_column($inventoryChartResult, 'name');
$inventoryChartData = array_column($inventoryChartResult, 'total_stock');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Dashboard</title>
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
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'admin_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'admin_header.php'; ?>

        <main class="flex-1 overflow-y-auto p-6">
            <div id="page-content">
                <div id="dashboard-page" class="space-y-8">
                    <!-- Date Filter Section -->
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                                <p class="text-sm text-gray-600 mt-1">Overview of your pharmacy operations and key metrics</p>
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
                                <span class="text-gray-600">Showing data for:</span>
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
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" id="sales-card">
                            <div>
                                <p class="text-sm text-gray-500" >
                                    <?php 
                                    if ($isAllTime) {
                                        echo 'Total sales (All Time)';
                                    } elseif ($selectedDate === date('Y-m-d')) {
                                        echo 'Total sales today';
                                    } else {
                                        echo 'Total sales';
                                    }
                                    ?>
                                </p>
                                <p class="text-2xl font-bold text-[#236B3D]" id="total-sales-today">₱<?php echo htmlspecialchars(number_format($totalSalesToday, 2)); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Click to view details</p>
                            </div>
                            <i class="ph-fill ph-chart-line text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" id="products-card">
                            <div>
                                <p class="text-sm text-gray-500">Total Products</p>
                                <p class="text-2xl font-bold text-[#236B3D]"><?php echo htmlspecialchars($totalProducts); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Click to view details</p>
                            </div>
                            <i class="ph-fill ph-package text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" id="expiration-card">
                            <div>
                                <p class="text-sm text-gray-500">Expiration Alert</p>
                                <p class="text-2xl font-bold text-orange-500" id="exp-alert-count"><?php echo htmlspecialchars($expAlertCount); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Click to view details</p>
                            </div>
                            <i class="ph-fill ph-clock-countdown text-4xl text-orange-500"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" id="low-stock-card">
                            <div>
                                <p class="text-sm text-gray-500">Low stock item</p>
                                <p class="text-2xl font-bold text-red-500" id="low-stock-item"><?php echo htmlspecialchars($lowStockCount); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Click to view details</p>
                            </div>
                            <i class="ph-fill ph-warning text-4xl text-red-500"></i>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-md">
                            <h2 class="text-xl font-bold mb-4 text-gray-800">Sales Overview</h2>
                            <div style="position: relative; height:300px;">
                                <canvas id="salesOverviewChart"></canvas>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md">
                            <h2 class="text-xl font-bold mb-4 flex justify-between items-center text-gray-800">
                                <span>Recent Transaction</span>
                                <a href="sales_report.php" class="text-[#236B3D] font-medium text-sm hover:underline">View all</a>
                            </h2>
                            <div class="space-y-4">
                                <?php if (!empty($recentTransactions)): ?>
                                    <?php foreach ($recentTransactions as $transaction): ?>
                                    <div class="flex justify-between items-center border-b pb-2">
                                        <div>
                                            <p class="font-medium"><?php echo htmlspecialchars($transaction['product_name']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars(date('M d, Y, g:i A', strtotime($transaction['transaction_date']))); ?></p>
                                        </div>
                                        <p class="text-lg font-bold text-[#236B3D]">₱<?php echo htmlspecialchars(number_format($transaction['total_price'], 2)); ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center text-gray-500 py-8">
                                        <?php 
                                        if ($isAllTime) {
                                            echo 'No recent transactions found.';
                                        } elseif ($selectedDate === date('Y-m-d')) {
                                            echo 'No recent transactions today.';
                                        } else {
                                            echo 'No transactions found for ' . date('M j, Y', strtotime($selectedDate)) . '.';
                                        }
                                        ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <h2 class="text-xl font-bold mb-4 flex justify-between items-center text-gray-800">
                            <span>Top 5 Inventory Overview</span>
                            <a href="inventory_report.php" class="text-[#236B3D] font-medium text-sm hover:underline">View Full Report</a>
                        </h2>
                        <div style="position: relative; height:300px;">
                            <canvas id="inventoryStockChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

    <!-- Sales Details Modal -->
    <div id="sales-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Sales Details</h3>
                    <button id="close-sales-modal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <p class="text-sm text-gray-600 mt-2">All transactions <?php echo $isAllTime ? 'for all time' : 'for ' . date('M d, Y', strtotime($selectedDate)); ?></p>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-4">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">Total Sales:</span>
                            <span class="text-2xl font-bold text-green-600">₱<?php echo number_format($totalSalesToday, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm text-gray-600">Total Transactions:</span>
                            <span class="text-sm font-semibold text-gray-700"><?php echo count($allTransactions); ?> transactions</span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="py-3 px-4 font-semibold text-gray-600">#</th>
                                <th class="py-3 px-4 font-semibold text-gray-600">Product Name</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Quantity</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Total Price</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($allTransactions)): ?>
                                <?php foreach ($allTransactions as $index => $transaction): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-4 text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                        <td class="py-3 px-4 text-center font-semibold text-blue-600"><?php echo $transaction['quantity']; ?></td>
                                        <td class="py-3 px-4 text-right font-semibold text-green-600">₱<?php echo number_format($transaction['total_price'], 2); ?></td>
                                        <td class="py-3 px-4 text-center text-sm text-gray-600"><?php echo date('g:i A', strtotime($transaction['transaction_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-gray-500">No transactions found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <button id="close-sales-modal-btn" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Products Details Modal -->
    <div id="products-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Products Details</h3>
                    <button id="close-products-modal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <p class="text-sm text-gray-600 mt-2">All products in inventory <?php echo $isAllTime ? 'for all time' : 'as of ' . date('M d, Y', strtotime($selectedDate)); ?></p>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">Total Products:</span>
                            <span class="text-2xl font-bold text-blue-600"><?php echo $totalProducts; ?></span>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($allProducts)): ?>
                                <?php foreach ($allProducts as $index => $product): ?>
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-gray-500">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <button id="close-products-modal-btn" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Expiration Alert Modal -->
    <div id="expiration-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Expiration Alert Details</h3>
                    <button id="close-expiration-modal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <p class="text-sm text-gray-600 mt-2">Products expiring within 1 month <?php echo $isAllTime ? 'from today' : 'from ' . date('M d, Y', strtotime($selectedDate)); ?></p>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-4">
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">Products Expiring Soon:</span>
                            <span class="text-2xl font-bold text-orange-600"><?php echo $expAlertCount; ?></span>
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
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Stock</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Days Until Expiry</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Expiration Date</th>
                                <th class="py-3 px-4 font-semibold text-gray-600">Supplier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($expiringProducts)): ?>
                                <?php foreach ($expiringProducts as $index => $product): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-4 text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($product['lot_number'] ?: 'N/A'); ?>
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
                                    <td colspan="7" class="text-center py-8 text-gray-500">No products expiring soon.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <button id="close-expiration-modal-btn" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Low Stock Modal -->
    <div id="low-stock-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Low Stock Alert Details</h3>
                    <button id="close-low-stock-modal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <p class="text-sm text-gray-600 mt-2">Products with low stock (5 or fewer items) <?php echo $isAllTime ? 'for all time' : 'as of ' . date('M d, Y', strtotime($selectedDate)); ?></p>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">Low Stock Items:</span>
                            <span class="text-2xl font-bold text-red-600"><?php echo $lowStockCount; ?></span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="py-3 px-4 font-semibold text-gray-600">#</th>
                                <th class="py-3 px-4 font-semibold text-gray-600">Product Name</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Current Stock</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Earliest Expiry</th>
                                <th class="py-3 px-4 font-semibold text-gray-600">Suppliers</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($lowStockProducts)): ?>
                                <?php foreach ($lowStockProducts as $index => $product): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-4 text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="py-3 px-4 text-center font-semibold text-red-600"><?php echo number_format($product['total_stock']); ?></td>
                                        <td class="py-3 px-4 text-center text-sm">
                                            <?php if ($product['earliest_expiry']): ?>
                                                <span class="text-gray-600"><?php echo date('M d, Y', strtotime($product['earliest_expiry'])); ?></span>
                                            <?php else: ?>
                                                <span class="text-gray-400">No expiry</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($product['suppliers']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <?php if ($product['total_stock'] <= 2): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Critical
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Low
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">No low stock items found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <button id="close-low-stock-modal-btn" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
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
                    
                    window.location.href = `dashboard.php?date=${selectedValue}`;
                });
            }

            // Allow Enter key to apply filter
            if (customDateInput) {
                customDateInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        const selectedDate = customDateInput.value;
                        if (selectedDate) {
                            window.location.href = `dashboard.php?date=${selectedDate}`;
                        }
                    }
                });
            }

            // Sales Overview Chart (Bar Chart)
            const salesOverviewChartCanvas = document.getElementById('salesOverviewChart');
            if (salesOverviewChartCanvas) {
                const ctx = salesOverviewChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chartLabels); ?>,
                        datasets: [{
                            label: 'Total Sales (₱)',
                            data: <?php echo json_encode($chartSalesData); ?>,
                            backgroundColor: '#01A74F',
                            borderColor: '#018d43',
                            borderWidth: 1,
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Sales: ₱' + context.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { 
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString('en-PH');
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Inventory Stock Chart (Line Chart)
            const inventoryStockChartCanvas = document.getElementById('inventoryStockChart');
            if (inventoryStockChartCanvas) {
                const ctx = inventoryStockChartCanvas.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(1, 167, 79, 0.5)');
                gradient.addColorStop(1, 'rgba(1, 167, 79, 0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($inventoryChartLabels); ?>,
                        datasets: [{
                            label: 'Total Stock',
                            data: <?php echo json_encode($inventoryChartData); ?>,
                            borderColor: '#01A74F',
                            backgroundColor: gradient,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#01A74F',
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#01A74F',
                            pointHoverBorderColor: '#fff',
                            borderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }

            // Modal functionality for dashboard cards
            const salesCard = document.getElementById('sales-card');
            const productsCard = document.getElementById('products-card');
            const expirationCard = document.getElementById('expiration-card');
            const lowStockCard = document.getElementById('low-stock-card');

            const salesModal = document.getElementById('sales-modal');
            const productsModal = document.getElementById('products-modal');
            const expirationModal = document.getElementById('expiration-modal');
            const lowStockModal = document.getElementById('low-stock-modal');

            // Sales Modal
            if (salesCard && salesModal) {
                salesCard.addEventListener('click', () => {
                    salesModal.classList.remove('hidden');
                });

                const closeSalesModal = document.getElementById('close-sales-modal');
                const closeSalesModalBtn = document.getElementById('close-sales-modal-btn');

                if (closeSalesModal) {
                    closeSalesModal.addEventListener('click', () => {
                        salesModal.classList.add('hidden');
                    });
                }

                if (closeSalesModalBtn) {
                    closeSalesModalBtn.addEventListener('click', () => {
                        salesModal.classList.add('hidden');
                    });
                }

                salesModal.addEventListener('click', (e) => {
                    if (e.target === salesModal) {
                        salesModal.classList.add('hidden');
                    }
                });
            }

            // Products Modal
            if (productsCard && productsModal) {
                productsCard.addEventListener('click', () => {
                    productsModal.classList.remove('hidden');
                });

                const closeProductsModal = document.getElementById('close-products-modal');
                const closeProductsModalBtn = document.getElementById('close-products-modal-btn');

                if (closeProductsModal) {
                    closeProductsModal.addEventListener('click', () => {
                        productsModal.classList.add('hidden');
                    });
                }

                if (closeProductsModalBtn) {
                    closeProductsModalBtn.addEventListener('click', () => {
                        productsModal.classList.add('hidden');
                    });
                }

                productsModal.addEventListener('click', (e) => {
                    if (e.target === productsModal) {
                        productsModal.classList.add('hidden');
                    }
                });
            }

            // Expiration Modal
            if (expirationCard && expirationModal) {
                expirationCard.addEventListener('click', () => {
                    expirationModal.classList.remove('hidden');
                });

                const closeExpirationModal = document.getElementById('close-expiration-modal');
                const closeExpirationModalBtn = document.getElementById('close-expiration-modal-btn');

                if (closeExpirationModal) {
                    closeExpirationModal.addEventListener('click', () => {
                        expirationModal.classList.add('hidden');
                    });
                }

                if (closeExpirationModalBtn) {
                    closeExpirationModalBtn.addEventListener('click', () => {
                        expirationModal.classList.add('hidden');
                    });
                }

                expirationModal.addEventListener('click', (e) => {
                    if (e.target === expirationModal) {
                        expirationModal.classList.add('hidden');
                    }
                });
            }

            // Low Stock Modal
            if (lowStockCard && lowStockModal) {
                lowStockCard.addEventListener('click', () => {
                    lowStockModal.classList.remove('hidden');
                });

                const closeLowStockModal = document.getElementById('close-low-stock-modal');
                const closeLowStockModalBtn = document.getElementById('close-low-stock-modal-btn');

                if (closeLowStockModal) {
                    closeLowStockModal.addEventListener('click', () => {
                        lowStockModal.classList.add('hidden');
                    });
                }

                if (closeLowStockModalBtn) {
                    closeLowStockModalBtn.addEventListener('click', () => {
                        lowStockModal.classList.add('hidden');
                    });
                }

                lowStockModal.addEventListener('click', (e) => {
                    if (e.target === lowStockModal) {
                        lowStockModal.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>
</html>