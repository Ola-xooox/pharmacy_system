<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Inventory Report</title>
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
            <div id="inventory-report-page" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total stocks</p>
                            <p class="text-2xl font-bold text-[#236B3D]">750</p>
                            <span class="text-xs text-gray-400">8 products</span>
                        </div>
                        <i class="ph-fill ph-package text-4xl text-gray-400"></i>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Expiring Soon</p>
                            <p class="text-2xl font-bold text-orange-500">50</p>
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
                            <tbody id="inventory-table-body">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="admin_script.js"></script>
</body>
</html>
