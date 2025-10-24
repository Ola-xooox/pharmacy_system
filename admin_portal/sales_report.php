<?php 
session_start();
// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
$currentPage = 'sales_report';

// --- Start of PHP Data Fetching ---
require_once '../db_connect.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all purchase history for movement calculation
$all_history_result = $conn->query("SELECT * FROM purchase_history ORDER BY transaction_date DESC");
$all_purchase_history = [];
while($row = $all_history_result->fetch_assoc()) {
    $all_purchase_history[] = $row;
}

// Calculate product movement speed
function calculateProductMovement($product_name, $purchase_history) {
    $product_sales = array_filter($purchase_history, function($item) use ($product_name) {
        return $item['product_name'] === $product_name;
    });
    
    if (empty($product_sales)) {
        return 'slow';
    }
    
    $total_quantity = array_sum(array_column($product_sales, 'quantity'));
    
    // Determine movement speed based on total quantity sold
    // Fast: High volume sales (10+ units sold)
    if ($total_quantity >= 10) {
        return 'fast';
    } 
    // Medium: Moderate volume sales (5-9 units sold)
    elseif ($total_quantity >= 5) {
        return 'medium';
    } 
    // Slow: Low volume sales (1-4 units sold)
    else {
        return 'slow';
    }
}

// Set the timezone to your local timezone
date_default_timezone_set('Asia/Manila');

// Get selected date from URL parameter, default to 'all' for all data
$selectedDate = isset($_GET['date']) ? $_GET['date'] : 'all';

// Validate the date format or check for 'all' option
if ($selectedDate !== 'all' && !DateTime::createFromFormat('Y-m-d', $selectedDate)) {
    $selectedDate = 'all';
}

$today = $selectedDate;
$isAllTime = ($selectedDate === 'all');

// Total Revenue
if ($isAllTime) {
    $totalRevenueStmt = $conn->prepare("SELECT SUM(total_price) AS total_revenue FROM purchase_history");
    $totalRevenueStmt->execute();
} else {
    $totalRevenueStmt = $conn->prepare("SELECT SUM(total_price) AS total_revenue FROM purchase_history WHERE DATE(transaction_date) = ?");
    $totalRevenueStmt->bind_param("s", $today);
    $totalRevenueStmt->execute();
}
$totalRevenue = $totalRevenueStmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
$totalRevenueStmt->close();

// Total Orders (Sum of all quantities sold)
if ($isAllTime) {
    $totalOrdersStmt = $conn->prepare("SELECT SUM(quantity) AS total_orders FROM purchase_history");
    $totalOrdersStmt->execute();
} else {
    $totalOrdersStmt = $conn->prepare("SELECT SUM(quantity) AS total_orders FROM purchase_history WHERE DATE(transaction_date) = ?");
    $totalOrdersStmt->bind_param("s", $today);
    $totalOrdersStmt->execute();
}
$totalOrders = $totalOrdersStmt->get_result()->fetch_assoc()['total_orders'] ?? 0;
$totalOrdersStmt->close();

// Total Cost (Sum of all product costs sold)
if ($isAllTime) {
    $totalCostStmt = $conn->prepare("
        SELECT SUM(ph.quantity * COALESCE(p.cost, 0)) AS total_cost
        FROM purchase_history ph
        LEFT JOIN (SELECT name, AVG(cost) as cost FROM products GROUP BY name) p ON ph.product_name = p.name
    ");
    $totalCostStmt->execute();
} else {
    $totalCostStmt = $conn->prepare("
        SELECT SUM(ph.quantity * COALESCE(p.cost, 0)) AS total_cost
        FROM purchase_history ph
        LEFT JOIN (SELECT name, AVG(cost) as cost FROM products GROUP BY name) p ON ph.product_name = p.name
        WHERE DATE(ph.transaction_date) = ?
    ");
    $totalCostStmt->bind_param("s", $today);
    $totalCostStmt->execute();
}
$totalCost = $totalCostStmt->get_result()->fetch_assoc()['total_cost'] ?? 0;
$totalCostStmt->close();

// Detailed Cost Breakdown for modal with batch and lot numbers
$costBreakdownStmt = $conn->prepare("
    SELECT 
        ph.product_name,
        p.lot_number,
        p.batch_number,
        SUM(ph.quantity) as total_quantity,
        p.cost as unit_cost,
        SUM(ph.quantity * COALESCE(p.cost, 0)) as total_product_cost,
        AVG(ph.total_price / ph.quantity) as selling_price,
        p.expiration_date
    FROM purchase_history ph
    LEFT JOIN products p ON ph.product_name = p.name
    WHERE DATE(ph.transaction_date) = ? AND p.cost IS NOT NULL
    GROUP BY ph.product_name, p.lot_number, p.batch_number, p.cost, p.expiration_date
    ORDER BY total_product_cost DESC
");
$costBreakdownStmt->bind_param("s", $today);
$costBreakdownStmt->execute();
$costBreakdown = $costBreakdownStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$costBreakdownStmt->close();

// Detailed Revenue Breakdown for modal - All transactions
$revenueBreakdownStmt = $conn->prepare("
    SELECT 
        ph.product_name,
        ph.quantity,
        ph.total_price,
        ph.transaction_date,
        (ph.total_price / ph.quantity) as unit_price
    FROM purchase_history ph
    WHERE DATE(ph.transaction_date) = ?
    ORDER BY ph.transaction_date DESC
");
$revenueBreakdownStmt->bind_param("s", $today);
$revenueBreakdownStmt->execute();
$revenueBreakdown = $revenueBreakdownStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$revenueBreakdownStmt->close();

// Calculate Total Profit (Total Revenue - Total Cost)
$totalProfit = $totalRevenue - $totalCost;

// Detailed Profit Breakdown for modal
$profitBreakdownStmt = $conn->prepare("
    SELECT 
        ph.product_name,
        p.lot_number,
        p.batch_number,
        SUM(ph.quantity) as total_quantity,
        p.cost as unit_cost,
        AVG(ph.total_price / ph.quantity) as selling_price,
        SUM(ph.total_price) as total_revenue,
        SUM(ph.quantity * COALESCE(p.cost, 0)) as total_cost,
        (SUM(ph.total_price) - SUM(ph.quantity * COALESCE(p.cost, 0))) as total_profit,
        ((AVG(ph.total_price / ph.quantity) - COALESCE(p.cost, 0)) / AVG(ph.total_price / ph.quantity) * 100) as profit_margin_percent,
        p.expiration_date
    FROM purchase_history ph
    LEFT JOIN products p ON ph.product_name = p.name
    WHERE DATE(ph.transaction_date) = ?
    GROUP BY ph.product_name, p.lot_number, p.batch_number, p.cost, p.expiration_date
    ORDER BY total_profit DESC
");
$profitBreakdownStmt->bind_param("s", $today);
$profitBreakdownStmt->execute();
$profitBreakdown = $profitBreakdownStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$profitBreakdownStmt->close();

// --- START: GROUPED TRANSACTIONS LIKE PURCHASE HISTORY ---
// Group transactions by product name to eliminate duplicates and show aggregated data
if ($isAllTime) {
    $transactionsStmt = $conn->prepare("
        SELECT 
            product_name,
            SUM(quantity) as total_quantity,
            SUM(total_price) as total_sales,
            COUNT(*) as transaction_count,
            MAX(transaction_date) as last_transaction_date
        FROM purchase_history 
        GROUP BY product_name 
        ORDER BY MAX(transaction_date) DESC
    ");
    $transactionsStmt->execute();
} else {
    $transactionsStmt = $conn->prepare("
        SELECT 
            product_name,
            SUM(quantity) as total_quantity,
            SUM(total_price) as total_sales,
            COUNT(*) as transaction_count,
            MAX(transaction_date) as last_transaction_date
        FROM purchase_history 
        WHERE DATE(transaction_date) = ? 
        GROUP BY product_name 
        ORDER BY MAX(transaction_date) DESC
    ");
    $transactionsStmt->bind_param("s", $today);
    $transactionsStmt->execute();
}
$transactionsList = $transactionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$transactionsStmt->close();

// Add movement status to each grouped transaction
foreach ($transactionsList as &$transaction) {
    $transaction['movement_status'] = calculateProductMovement($transaction['product_name'], $all_purchase_history);
}
unset($transaction); // Break the reference
// --- END: GROUPED TRANSACTIONS ---

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

$conn->close();

// Prepare chart data for JavaScript
$chartLabels = [];
$chartSalesData = [];
$period = new DatePeriod(new DateTime('-6 days'), new DateInterval('P1D'), new DateTime('+1 day'));
foreach ($period as $date) {
    $formattedDate = $date->format('Y-m-d');
    $chartLabels[] = $date->format('D'); // Day of the week
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
// --- End of PHP Data Fetching ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Sales Report</title>
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
                <div id="sales-report-page" class="space-y-8">
                    <!-- Date Filter Section -->
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Sales Report</h1>
                                <p class="text-sm text-gray-600 mt-1">Monitor your daily sales performance and analytics</p>
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
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" id="total-revenue-card">
                            <div>
                                <p class="text-sm text-gray-500">Total Revenue</p>
                                <p class="text-2xl font-bold text-[#236B3D]" id="total-revenue">₱<?php echo htmlspecialchars(number_format($totalRevenue, 2)); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Click to view all transactions</p>
                            </div>
                            <i class="ph-fill ph-currency-rub text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" id="total-cost-card">
                            <div>
                                <p class="text-sm text-gray-500">Total Cost</p>
                                <p class="text-2xl font-bold text-orange-500" id="total-cost">₱<?php echo htmlspecialchars(number_format($totalCost, 2)); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Click to view breakdown</p>
                            </div>
                            <i class="ph-fill ph-receipt text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" id="total-profit-card">
                            <div>
                                <p class="text-sm text-gray-500">Total Profit</p>
                                <p class="text-2xl font-bold <?php echo $totalProfit >= 0 ? 'text-green-500' : 'text-red-500'; ?>" id="total-profit">₱<?php echo htmlspecialchars(number_format($totalProfit, 2)); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Click to view profit breakdown</p>
                            </div>
                            <i class="ph-fill ph-trend-up text-4xl text-gray-400"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <h2 class="text-xl font-bold mb-4 text-gray-800">Sales Performance (Last 7 Days)</h2>
                        <div style="position: relative; height:300px;">
                            <canvas id="salesPerformanceChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <h2 class="text-xl font-bold mb-4 text-gray-800">
                            <?php 
                            if ($isAllTime) {
                                echo "All Time Transactions";
                            } elseif ($selectedDate === date('Y-m-d')) {
                                echo "Today's Transactions";
                            } else {
                                echo "Transactions for " . date('M j, Y', strtotime($selectedDate));
                            }
                            ?>
                        </h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                                        <th class="py-3 px-4 font-semibold text-gray-600">Product Name</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600 text-center">Total Sold</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600 text-center">Total Sales</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600 text-center">Transactions</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600 text-center">Movement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($transactionsList)): ?>
                                        <?php foreach ($transactionsList as $transaction): ?>
                                            <tr class="border-b border-gray-200">
                                                <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                                <td class="py-3 px-4 text-center font-semibold text-blue-600"><?php echo htmlspecialchars($transaction['total_quantity']); ?></td>
                                                <td class="py-3 px-4 text-center font-semibold text-green-600">₱<?php echo htmlspecialchars(number_format($transaction['total_sales'], 2)); ?></td>
                                                <td class="py-3 px-4 text-center font-medium text-gray-600"><?php echo htmlspecialchars($transaction['transaction_count']); ?></td>
                                                <td class="py-3 px-4 text-center">
                                                    <?php 
                                                    $movementStatus = $transaction['movement_status'];
                                                    $badgeClass = '';
                                                    $icon = '';
                                                    if ($movementStatus === 'fast') {
                                                        $badgeClass = 'bg-green-100 text-green-700';
                                                        $icon = '↗';
                                                    } elseif ($movementStatus === 'medium') {
                                                        $badgeClass = 'bg-yellow-100 text-yellow-700';
                                                        $icon = '→';
                                                    } else {
                                                        $badgeClass = 'bg-red-100 text-red-700';
                                                        $icon = '↘';
                                                    }
                                                    ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium <?php echo $badgeClass; ?>">
                                                        <span><?php echo $icon; ?></span>
                                                        <?php echo ucfirst($movementStatus); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-8 text-gray-500">No transactions to display for today.</td>
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

    <!-- Total Cost Breakdown Modal -->
    <div id="cost-breakdown-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Total Cost Breakdown</h3>
                    <button id="close-cost-modal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <p class="text-sm text-gray-600 mt-2">Detailed breakdown of costs for products sold today</p>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-4">
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">Total Cost <?php echo $isAllTime ? 'for All Time' : 'for ' . date('M j, Y', strtotime($selectedDate)); ?>:</span>
                            <span class="text-2xl font-bold text-orange-600">₱<?php echo number_format($totalCost, 2); ?></span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="py-3 px-4 font-semibold text-gray-600">Product Name</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Lot Number</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Batch Number</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Qty Sold</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Unit Cost</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Selling Price</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Total Cost</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Profit/Item</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Exp. Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($costBreakdown)): ?>
                                <?php foreach ($costBreakdown as $item): ?>
                                    <?php 
                                        $profitPerItem = $item['selling_price'] - $item['unit_cost'];
                                        $profitClass = $profitPerItem >= 0 ? 'text-green-600' : 'text-red-600';
                                    ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($item['lot_number'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <?php echo htmlspecialchars($item['batch_number'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center font-semibold"><?php echo number_format($item['total_quantity']); ?></td>
                                        <td class="py-3 px-4 text-right">₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                                        <td class="py-3 px-4 text-right">₱<?php echo number_format($item['selling_price'], 2); ?></td>
                                        <td class="py-3 px-4 text-right font-semibold text-orange-600">₱<?php echo number_format($item['total_product_cost'], 2); ?></td>
                                        <td class="py-3 px-4 text-right font-semibold <?php echo $profitClass; ?>">₱<?php echo number_format($profitPerItem, 2); ?></td>
                                        <td class="py-3 px-4 text-center text-sm">
                                            <?php if ($item['expiration_date']): ?>
                                                <span class="text-gray-600"><?php echo date('M d, Y', strtotime($item['expiration_date'])); ?></span>
                                            <?php else: ?>
                                                <span class="text-gray-400">No expiry</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-8 text-gray-500">No cost data available for today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <button id="close-cost-modal-btn" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Total Revenue Breakdown Modal -->
    <div id="revenue-breakdown-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Total Revenue Breakdown</h3>
                    <button id="close-revenue-modal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <p class="text-sm text-gray-600 mt-2">All transactions <?php echo $isAllTime ? 'for all time' : 'for ' . date('M d, Y', strtotime($selectedDate)); ?></p>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-4">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">Total Revenue <?php echo $isAllTime ? 'for All Time' : 'for ' . date('M j, Y', strtotime($selectedDate)); ?>:</span>
                            <span class="text-2xl font-bold text-green-600">₱<?php echo number_format($totalRevenue, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm text-gray-600">Total Transactions:</span>
                            <span class="text-sm font-semibold text-gray-700"><?php echo count($revenueBreakdown); ?> transactions</span>
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
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Unit Price</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Total Price</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($revenueBreakdown)): ?>
                                <?php foreach ($revenueBreakdown as $index => $transaction): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-4 text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo number_format($transaction['quantity']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">₱<?php echo number_format($transaction['unit_price'], 2); ?></td>
                                        <td class="py-3 px-4 text-right font-semibold text-green-600">₱<?php echo number_format($transaction['total_price'], 2); ?></td>
                                        <td class="py-3 px-4 text-center text-sm text-gray-600">
                                            <?php echo date('g:i A', strtotime($transaction['transaction_date'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Summary Row -->
                                <tr class="bg-green-50 border-t-2 border-green-200 font-semibold">
                                    <td class="py-3 px-4 text-sm text-gray-600">Total</td>
                                    <td class="py-3 px-4 text-gray-700"><?php echo count($revenueBreakdown); ?> transactions</td>
                                    <td class="py-3 px-4 text-center text-blue-700">
                                        <?php echo number_format(array_sum(array_column($revenueBreakdown, 'quantity'))); ?>
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-600">-</td>
                                    <td class="py-3 px-4 text-right text-green-700 text-lg">₱<?php echo number_format($totalRevenue, 2); ?></td>
                                    <td class="py-3 px-4 text-center text-gray-600">-</td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">No transactions found for today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <button id="close-revenue-modal-btn" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Total Profit Breakdown Modal -->
    <div id="profit-breakdown-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Total Profit Breakdown</h3>
                    <button id="close-profit-modal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <p class="text-sm text-gray-600 mt-2">Detailed profit analysis for products sold <?php echo $isAllTime ? 'for all time' : 'on ' . date('M d, Y', strtotime($selectedDate)); ?></p>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-4">
                    <div class="<?php echo $totalProfit >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?> border rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">Total Profit <?php echo $isAllTime ? 'for All Time' : 'for ' . date('M j, Y', strtotime($selectedDate)); ?>:</span>
                            <span class="text-2xl font-bold <?php echo $totalProfit >= 0 ? 'text-green-600' : 'text-red-600'; ?>">₱<?php echo number_format($totalProfit, 2); ?></span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Total Revenue:</span>
                                <span class="text-sm font-semibold text-green-600">₱<?php echo number_format($totalRevenue, 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Total Cost:</span>
                                <span class="text-sm font-semibold text-orange-600">₱<?php echo number_format($totalCost, 2); ?></span>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-t border-gray-200">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Overall Profit Margin:</span>
                                <span class="text-sm font-semibold <?php echo $totalProfit >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $totalRevenue > 0 ? number_format(($totalProfit / $totalRevenue) * 100, 1) : '0'; ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="py-3 px-4 font-semibold text-gray-600">Product Name</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Lot #</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Batch #</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Qty</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Unit Cost</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Selling Price</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Revenue</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Cost</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-right">Profit</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Margin %</th>
                                <th class="py-3 px-4 font-semibold text-gray-600 text-center">Exp. Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($profitBreakdown)): ?>
                                <?php foreach ($profitBreakdown as $item): ?>
                                    <?php 
                                        $profitClass = $item['total_profit'] >= 0 ? 'text-green-600' : 'text-red-600';
                                        $marginClass = $item['profit_margin_percent'] >= 0 ? 'text-green-600' : 'text-red-600';
                                    ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($item['lot_number'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <?php echo htmlspecialchars($item['batch_number'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center font-semibold"><?php echo number_format($item['total_quantity']); ?></td>
                                        <td class="py-3 px-4 text-right">₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                                        <td class="py-3 px-4 text-right">₱<?php echo number_format($item['selling_price'], 2); ?></td>
                                        <td class="py-3 px-4 text-right font-semibold text-green-600">₱<?php echo number_format($item['total_revenue'], 2); ?></td>
                                        <td class="py-3 px-4 text-right font-semibold text-orange-600">₱<?php echo number_format($item['total_cost'], 2); ?></td>
                                        <td class="py-3 px-4 text-right font-bold <?php echo $profitClass; ?>">₱<?php echo number_format($item['total_profit'], 2); ?></td>
                                        <td class="py-3 px-4 text-center font-semibold <?php echo $marginClass; ?>">
                                            <?php echo number_format($item['profit_margin_percent'], 1); ?>%
                                        </td>
                                        <td class="py-3 px-4 text-center text-sm">
                                            <?php if ($item['expiration_date']): ?>
                                                <span class="text-gray-600"><?php echo date('M d, Y', strtotime($item['expiration_date'])); ?></span>
                                            <?php else: ?>
                                                <span class="text-gray-400">No expiry</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Summary Row -->
                                <tr class="<?php echo $totalProfit >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?> border-t-2 font-semibold">
                                    <td class="py-3 px-4 text-gray-700">TOTAL</td>
                                    <td class="py-3 px-4 text-center text-gray-600">-</td>
                                    <td class="py-3 px-4 text-center text-gray-600">-</td>
                                    <td class="py-3 px-4 text-center text-blue-700">
                                        <?php echo number_format(array_sum(array_column($profitBreakdown, 'total_quantity'))); ?>
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-600">-</td>
                                    <td class="py-3 px-4 text-right text-gray-600">-</td>
                                    <td class="py-3 px-4 text-right text-green-700 text-lg">₱<?php echo number_format($totalRevenue, 2); ?></td>
                                    <td class="py-3 px-4 text-right text-orange-700 text-lg">₱<?php echo number_format($totalCost, 2); ?></td>
                                    <td class="py-3 px-4 text-right <?php echo $totalProfit >= 0 ? 'text-green-700' : 'text-red-700'; ?> text-lg font-bold">₱<?php echo number_format($totalProfit, 2); ?></td>
                                    <td class="py-3 px-4 text-center <?php echo $totalProfit >= 0 ? 'text-green-700' : 'text-red-700'; ?> font-bold">
                                        <?php echo $totalRevenue > 0 ? number_format(($totalProfit / $totalRevenue) * 100, 1) : '0'; ?>%
                                    </td>
                                    <td class="py-3 px-4 text-center text-gray-600">-</td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center py-8 text-gray-500">No profit data available for today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <button id="close-profit-modal-btn" class="w-full <?php echo $totalProfit >= 0 ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600'; ?> text-white font-semibold py-2 px-4 rounded-lg transition-colors">
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

            // Chart
            const salesPerformanceChartCanvas = document.getElementById('salesPerformanceChart');
            if (salesPerformanceChartCanvas) {
                const ctx = salesPerformanceChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($chartLabels); ?>,
                        datasets: [{
                            label: 'Total Sales (₱)',
                            data: <?php echo json_encode($chartSalesData); ?>,
                            borderColor: '#01A74F',
                            backgroundColor: 'rgba(1, 167, 79, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });
            }

            // Total Cost Modal functionality
            const totalCostCard = document.getElementById('total-cost-card');
            const costBreakdownModal = document.getElementById('cost-breakdown-modal');
            const closeCostModal = document.getElementById('close-cost-modal');
            const closeCostModalBtn = document.getElementById('close-cost-modal-btn');

            if (totalCostCard) {
                totalCostCard.addEventListener('click', () => {
                    costBreakdownModal.classList.remove('hidden');
                });
            }

            if (closeCostModal) {
                closeCostModal.addEventListener('click', () => {
                    costBreakdownModal.classList.add('hidden');
                });
            }

            if (closeCostModalBtn) {
                closeCostModalBtn.addEventListener('click', () => {
                    costBreakdownModal.classList.add('hidden');
                });
            }

            // Close modal when clicking outside
            if (costBreakdownModal) {
                costBreakdownModal.addEventListener('click', (e) => {
                    if (e.target === costBreakdownModal) {
                        costBreakdownModal.classList.add('hidden');
                    }
                });
            }

            // Total Revenue Modal functionality
            const totalRevenueCard = document.getElementById('total-revenue-card');
            const revenueBreakdownModal = document.getElementById('revenue-breakdown-modal');
            const closeRevenueModal = document.getElementById('close-revenue-modal');
            const closeRevenueModalBtn = document.getElementById('close-revenue-modal-btn');

            if (totalRevenueCard) {
                totalRevenueCard.addEventListener('click', () => {
                    revenueBreakdownModal.classList.remove('hidden');
                });
            }

            if (closeRevenueModal) {
                closeRevenueModal.addEventListener('click', () => {
                    revenueBreakdownModal.classList.add('hidden');
                });
            }

            if (closeRevenueModalBtn) {
                closeRevenueModalBtn.addEventListener('click', () => {
                    revenueBreakdownModal.classList.add('hidden');
                });
            }

            // Close modal when clicking outside
            if (revenueBreakdownModal) {
                revenueBreakdownModal.addEventListener('click', (e) => {
                    if (e.target === revenueBreakdownModal) {
                        revenueBreakdownModal.classList.add('hidden');
                    }
                });
            }

            // Total Profit Modal functionality
            const totalProfitCard = document.getElementById('total-profit-card');
            const profitBreakdownModal = document.getElementById('profit-breakdown-modal');
            const closeProfitModal = document.getElementById('close-profit-modal');
            const closeProfitModalBtn = document.getElementById('close-profit-modal-btn');

            if (totalProfitCard) {
                totalProfitCard.addEventListener('click', () => {
                    profitBreakdownModal.classList.remove('hidden');
                });
            }

            if (closeProfitModal) {
                closeProfitModal.addEventListener('click', () => {
                    profitBreakdownModal.classList.add('hidden');
                });
            }

            if (closeProfitModalBtn) {
                closeProfitModalBtn.addEventListener('click', () => {
                    profitBreakdownModal.classList.add('hidden');
                });
            }

            // Close modal when clicking outside
            if (profitBreakdownModal) {
                profitBreakdownModal.addEventListener('click', (e) => {
                    if (e.target === profitBreakdownModal) {
                        profitBreakdownModal.classList.add('hidden');
                    }
                });
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
                    
                    window.location.href = `sales_report.php?date=${selectedValue}`;
                });
            }

            // Allow Enter key to apply filter
            if (customDateInput) {
                customDateInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        const selectedDate = customDateInput.value;
                        if (selectedDate) {
                            window.location.href = `sales_report.php?date=${selectedDate}`;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>