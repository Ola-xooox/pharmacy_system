<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$currentPage = 'dashboard';

// --- Start of PHP Data Fetching ---
require_once '../db_connect.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Today's Sales Data
$today = date('Y-m-d');
$salesStmt = $conn->prepare("SELECT SUM(total_price) AS total_sales_today FROM purchase_history WHERE DATE(transaction_date) = ?");
$salesStmt->bind_param("s", $today);
$salesStmt->execute();
$salesData = $salesStmt->get_result()->fetch_assoc();
$totalSalesToday = $salesData['total_sales_today'] ?? 0;
$salesStmt->close();

// Fetch Total Products
$productsStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS total_products FROM products");
$productsStmt->execute();
$productsData = $productsStmt->get_result()->fetch_assoc();
$totalProducts = $productsData['total_products'] ?? 0;
$productsStmt->close();

// Fetch Expiration Alert Count (within 1 month, excluding expired)
$expAlertStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS exp_alert_count FROM products WHERE expiration_date > CURDATE() AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH)");
$expAlertStmt->execute();
$expAlertData = $expAlertStmt->get_result()->fetch_assoc();
$expAlertCount = $expAlertData['exp_alert_count'] ?? 0;
$expAlertStmt->close();


// Fetch Low Stock Count (Sum of items per product name is <= 5)
$lowStockStmt = $conn->prepare("
    SELECT COUNT(*) as low_stock_count FROM (
        SELECT name
        FROM products
        WHERE (expiration_date > CURDATE() OR expiration_date IS NULL)
        GROUP BY name
        HAVING SUM(item_total) <= 5 AND SUM(item_total) > 0
    ) AS low_stock_products
");
$lowStockStmt->execute();
$lowStockData = $lowStockStmt->get_result()->fetch_assoc();
$lowStockCount = $lowStockData['low_stock_count'] ?? 0;
$lowStockStmt->close();


// Fetch Recent Transactions
$transactionsStmt = $conn->prepare("SELECT product_name, total_price, transaction_date FROM purchase_history ORDER BY transaction_date DESC LIMIT 5");
$transactionsStmt->execute();
$recentTransactions = $transactionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

// --- FIXED QUERY ---
// Fetch top 5 products for inventory chart using SUM(stock)
$inventoryChartStmt = $conn->prepare("
    SELECT name, SUM(stock) as total_stock 
    FROM products 
    GROUP BY name 
    ORDER BY total_stock DESC 
    LIMIT 5
");
$inventoryChartStmt->execute();
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
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500" >Total sales today</p>
                                <p class="text-2xl font-bold text-[#236B3D]" id="total-sales-today">₱<?php echo htmlspecialchars(number_format($totalSalesToday, 2)); ?></p>
                            </div>
                            <i class="ph-fill ph-chart-line text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Products</p>
                                <p class="text-2xl font-bold text-[#236B3D]"><?php echo htmlspecialchars($totalProducts); ?></p>
                            </div>
                            <i class="ph-fill ph-user-list text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Expiration Alert</p>
                                <p class="text-2xl font-bold text-orange-500" id="exp-alert-count"><?php echo htmlspecialchars($expAlertCount); ?></p>
                            </div>
                            <i class="ph-fill ph-clock-countdown text-4xl text-orange-500"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Low stock item</p>
                                <p class="text-2xl font-bold text-red-500" id="low-stock-item"><?php echo htmlspecialchars($lowStockCount); ?></p>
                                <span class="text-xs text-gray-400">needs attention</span>
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
                                    <p class="text-center text-gray-500 py-8">No recent transactions today.</p>
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

            // Sales Overview Chart
            const salesOverviewChartCanvas = document.getElementById('salesOverviewChart');
            if (salesOverviewChartCanvas) {
                const ctx = salesOverviewChartCanvas.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(1, 167, 79, 0.5)');
                gradient.addColorStop(1, 'rgba(1, 167, 79, 0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($chartLabels); ?>,
                        datasets: [{
                            label: 'Total Sales (₱)',
                            data: <?php echo json_encode($chartSalesData); ?>,
                            borderColor: '#01A74F',
                            backgroundColor: gradient,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#01A74F',
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#01A74F',
                            pointHoverBorderColor: '#fff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
            
            // Inventory Stock Chart
            const inventoryStockChartCanvas = document.getElementById('inventoryStockChart');
            if (inventoryStockChartCanvas) {
                const ctx = inventoryStockChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($inventoryChartLabels); ?>,
                        datasets: [{
                            label: 'Total Stock',
                            data: <?php echo json_encode($inventoryChartData); ?>,
                            backgroundColor: '#01A74F',
                            borderColor: '#018d43',
                            borderWidth: 1,
                            borderRadius: 5,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>