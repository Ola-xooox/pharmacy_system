<?php 
session_start();
// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$currentPage = 'sales_report';

// --- Start of PHP Data Fetching ---
require_once '../db_connect.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the timezone to your local timezone
date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// Total Revenue for today
$totalRevenueStmt = $conn->prepare("SELECT SUM(total_price) AS total_revenue FROM purchase_history WHERE DATE(transaction_date) = ?");
$totalRevenueStmt->bind_param("s", $today);
$totalRevenueStmt->execute();
$totalRevenue = $totalRevenueStmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
$totalRevenueStmt->close();

// Total Orders for today (Sum of all quantities sold)
$totalOrdersStmt = $conn->prepare("SELECT SUM(quantity) AS total_orders FROM purchase_history WHERE DATE(transaction_date) = ?");
$totalOrdersStmt->bind_param("s", $today);
$totalOrdersStmt->execute();
$totalOrders = $totalOrdersStmt->get_result()->fetch_assoc()['total_orders'] ?? 0;
$totalOrdersStmt->close();

// Net Profit for today (Total Price - Total Cost)
$netProfitStmt = $conn->prepare("
    SELECT SUM(ph.total_price) - SUM(ph.quantity * COALESCE(p.cost, 0)) AS net_profit
    FROM purchase_history ph
    LEFT JOIN (SELECT name, AVG(cost) as cost FROM products GROUP BY name) p ON ph.product_name = p.name
    WHERE DATE(ph.transaction_date) = ?
");
$netProfitStmt->bind_param("s", $today);
$netProfitStmt->execute();
$netProfit = $netProfitStmt->get_result()->fetch_assoc()['net_profit'] ?? 0;
$netProfitStmt->close();

// Fetch Today's Transactions for the table
$transactionsStmt = $conn->prepare("SELECT product_name, quantity, total_price, transaction_date FROM purchase_history WHERE DATE(transaction_date) = ? ORDER BY transaction_date DESC");
$transactionsStmt->bind_param("s", $today);
$transactionsStmt->execute();
$transactionsList = $transactionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$transactionsStmt->close();

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
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Revenue</p>
                                <p class="text-2xl font-bold text-[#236B3D]" id="total-revenue">₱<?php echo htmlspecialchars(number_format($totalRevenue, 2)); ?></p>
                            </div>
                            <i class="ph-fill ph-currency-rub text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Net Profit</p>
                                <p class="text-2xl font-bold text-green-500" id="net-profit">₱<?php echo htmlspecialchars(number_format($netProfit, 2)); ?></p>
                            </div>
                            <i class="ph-fill ph-piggy-bank text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Items Sold</p>
                                <p class="text-2xl font-bold text-[#236B3D]" id="total-orders"><?php echo htmlspecialchars($totalOrders ?? 0); ?></p>
                            </div>
                            <i class="ph-fill ph-shopping-bag text-4xl text-gray-400"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <h2 class="text-xl font-bold mb-4 text-gray-800">Sales Performance Daily</h2>
                        <div style="position: relative; height:300px;">
                            <canvas id="salesPerformanceChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <h2 class="text-xl font-bold mb-4 text-gray-800">Today's Transaction</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                                        <th class="py-3 px-4 font-semibold text-gray-600">Product Name</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600 text-center">Quantity</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Total Price</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Time Stamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($transactionsList)): ?>
                                        <?php foreach ($transactionsList as $transaction): ?>
                                            <tr class="border-b border-gray-200">
                                                <td class="py-3 px-4"><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                                <td class="py-3 px-4 text-center"><?php echo htmlspecialchars($transaction['quantity']); ?></td>
                                                <td class="py-3 px-4">₱<?php echo htmlspecialchars(number_format($transaction['total_price'], 2)); ?></td>
                                                <td class="py-3 px-4"><?php echo htmlspecialchars(date('g:i A', strtotime($transaction['transaction_date']))); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-8 text-gray-500">No transactions to display for today.</td>
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
        });
    </script>
</body>
</html>

