<?php
// All PHP data fetching logic MUST go at the top of the file
session_start();
// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$currentPage = 'inventory_report'; 

// --- Start of PHP Data Fetching ---
require_once '../db_connect.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Inventory Summary Data - Count of unique product names
$inventorySummaryStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS total_products FROM products");
$inventorySummaryStmt->execute();
$inventorySummary = $inventorySummaryStmt->get_result()->fetch_assoc();
$totalProducts = $inventorySummary['total_products'] ?? 0;
$inventorySummaryStmt->close();

// Fetch Expiration Alert Count (within 1 month, EXCLUDING today and past dates)
$expiringSoonStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS expiring_soon FROM products WHERE expiration_date > CURDATE() AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH)");
$expiringSoonStmt->execute();
$expiringSoon = $expiringSoonStmt->get_result()->fetch_assoc()['expiring_soon'] ?? 0;
$expiringSoonStmt->close();

// Fetch Expired Products Count (products expired ON or BEFORE today)
$expiredStmt = $conn->prepare("SELECT COUNT(DISTINCT name) AS expired_count FROM products WHERE expiration_date <= CURDATE()");
$expiredStmt->execute();
$expiredCount = $expiredStmt->get_result()->fetch_assoc()['expired_count'] ?? 0;
$expiredStmt->close();

// Fetch All Products for the Table (Grouped by name)
$inventoryListStmt = $conn->prepare("
    SELECT name, SUM(stock) as stock, MIN(expiration_date) as expiration_date, GROUP_CONCAT(DISTINCT supplier) as supplier, MAX(date_added) as date_added 
    FROM products 
    GROUP BY name 
    ORDER BY name ASC
");
$inventoryListStmt->execute();
$inventoryList = $inventoryListStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$inventoryListStmt->close();

// Data for the chart (Top 10 products by stock)
$chartDataStmt = $conn->prepare("SELECT name, SUM(stock) as total_stock FROM products GROUP BY name ORDER BY total_stock DESC LIMIT 10");
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
                <div id="inventory-report-page" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total products</p>
                                <p class="text-2xl font-bold text-[#236B3D]"><?php echo htmlspecialchars($totalProducts); ?></p>
                            </div>
                            <i class="ph-fill ph-package text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Expiration Alert</p>
                                <p class="text-2xl font-bold text-orange-500"><?php echo htmlspecialchars($expiringSoon); ?></p>
                            </div>
                            <i class="ph-fill ph-clock-countdown text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Expired Products</p>
                                <p class="text-2xl font-bold text-red-500"><?php echo htmlspecialchars($expiredCount); ?></p>
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
        });
    </script>
</body>
</html>