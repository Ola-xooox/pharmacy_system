<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="icon" type="image/x-icon" href="../mjpharmacy.logo.jpg">
</head>
<style>body {
    font-family: 'Inter', sans-serif;
    background-color: #f3f4f6;
}
</style>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'admin_sidebar.php'; ?>

    <main class="flex-1 p-8">
        <?php include 'admin_header.php'; ?>

        <div id="page-content">
            <div id="dashboard-page" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total sales today</p>
                            <p class="text-2xl font-bold text-[#236B3D]">₱24,000</p>
                        </div>
                        <i class="ph-fill ph-chart-line text-4xl text-gray-400"></i>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Active User</p>
                            <p class="text-2xl font-bold text-[#236B3D]">5</p>
                        </div>
                        <i class="ph-fill ph-user-list text-4xl text-gray-400"></i>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total inventory</p>
                            <p class="text-2xl font-bold text-[#236B3D]">1,560</p>
                        </div>
                        <i class="ph-fill ph-package text-4xl text-gray-400"></i>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Low stock item</p>
                            <p class="text-2xl font-bold text-red-500">10</p>
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
                            <a href="#" class="text-[#236B3D] font-medium text-sm hover:underline">View all</a>
                        </h2>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center border-b pb-2">
                                <div>
                                    <p class="font-medium">Amoxicillin 500mg</p>
                                    <p class="text-sm text-gray-500">10:20 AM</p>
                                </div>
                                <p class="text-lg font-bold text-[#236B3D]">₱420</p>
                            </div>
                            <div class="flex justify-between items-center border-b pb-2">
                                <div>
                                    <p class="font-medium">Paracetamol 500mg</p>
                                    <p class="text-sm text-gray-500">09:45 AM</p>
                                </div>
                                <p class="text-lg font-bold text-gray-800">₱150</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-md">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Inventory Stock</h2>
                    <div style="position: relative; height:300px;">
                        <canvas id="inventoryStockChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <div id="notification-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden justify-center items-center">
        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-800">Notifications</h3>
                <button id="close-notification-modal" class="text-gray-500 hover:text-gray-800">
                    <i class="ph-fill ph-x text-2xl"></i>
                </button>
            </div>
            <div id="notification-list" class="space-y-4">
                </div>
        </div>
    </div>

    <script src="admin_script.js"></script>
</body>
</html>
