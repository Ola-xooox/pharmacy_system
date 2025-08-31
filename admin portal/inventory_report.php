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
require_once '../db_connect.php'; // Corrected path from previous errors

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Inventory Summary Data
$inventorySummaryStmt = $conn->prepare("SELECT SUM(item_total) AS total_stocks, COUNT(*) AS total_products FROM products");
$inventorySummaryStmt->execute();
$inventorySummary = $inventorySummaryStmt->get_result()->fetch_assoc();
$totalStocks = $inventorySummary['total_stocks'] ?? 0;
$totalProducts = $inventorySummary['total_products'] ?? 0;
$inventorySummaryStmt->close();

// Fetch Expiring Soon Count
$expiringSoonStmt = $conn->prepare("SELECT COUNT(*) AS expiring_soon FROM products WHERE expiration_date IS NOT NULL AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)");
$expiringSoonStmt->execute();
$expiringSoon = $expiringSoonStmt->get_result()->fetch_assoc()['expiring_soon'] ?? 0;
$expiringSoonStmt->close();

// Fetch All Products for the Table
$inventoryListStmt = $conn->prepare("SELECT name, stock, expiration_date, supplier, date_added FROM products ORDER BY name ASC");
$inventoryListStmt->execute();
$inventoryList = $inventoryListStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$inventoryListStmt->close();

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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total stocks</p>
                                <p class="text-2xl font-bold text-[#236B3D]"><?php echo htmlspecialchars($totalStocks); ?></p>
                                <span class="text-xs text-gray-400"><?php echo htmlspecialchars($totalProducts); ?> products</span>
                            </div>
                            <i class="ph-fill ph-package text-4xl text-gray-400"></i>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Expiring Soon</p>
                                <p class="text-2xl font-bold text-orange-500"><?php echo htmlspecialchars($expiringSoon); ?></p>
                                <span class="text-xs text-gray-400">within 3 months</span>
                            </div>
                            <i class="ph-fill ph-clock-countdown text-4xl text-gray-400"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <h2 class="text-xl font-bold mb-4 text-gray-800">Inventory Status</h2>
                        <div style="position: relative; height:300px;">
                            <canvas id="inventoryStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800">Inventory Management</h2>
                            <input type="text" id="inventory-search" placeholder="search" class="w-1/3 p-2 rounded-lg border border-gray-300 focus:outline-none focus:ring focus:ring-green-500 focus:ring-opacity-50">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                                        <th class="py-3 px-4 font-semibold text-gray-600">Product</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Stock</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Expiry date</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Supplier</th>
                                        <th class="py-3 px-4 font-semibold text-gray-600">Recieved Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventoryList as $product): ?>
                                    <tr class="border-b border-gray-200">
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($product['stock']); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($product['expiration_date']); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($product['supplier']); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($product['date_added']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
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
        });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // PHP variables are used here to prepare data for the chart
        const chartLabels = <?php echo json_encode(array_column($inventoryList, 'name')); ?>;
        const chartData = <?php echo json_encode(array_column($inventoryList, 'stock')); ?>;

        const data = {
            labels: chartLabels,
            datasets: [{
                label: 'Total Stock',
                backgroundColor: '#01A74F',
                borderColor: '#01A74F',
                data: chartData,
            }]
        };

        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };

        const myChart = new Chart(
            document.getElementById('inventoryStatusChart'),
            config
        );
    });
</script>
</body>
</html>