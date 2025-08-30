<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Sales Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="icon" type="image/x-icon" href="../mjpharmacy.logo.jpg">
    <style>body {
    font-family: 'Inter', sans-serif;
    background-color: #f3f4f6;
}
</style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'admin_sidebar.php'; ?>

    <main class="flex-1 p-8">
        <?php include 'admin_header.php'; ?>

        <div id="page-content">
            <div id="sales-report-page" class="space-y-8">
                 <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Revenue</p>
                            <p class="text-2xl font-bold text-[#236B3D]">₱4,850</p>
                        </div>
                        <i class="ph-fill ph-currency-rub text-4xl text-gray-400"></i>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Net Profit</p>
                            <p class="text-2xl font-bold text-green-500">₱1,445</p>
                        </div>
                        <i class="ph-fill ph-piggy-bank text-4xl text-gray-400"></i>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Orders</p>
                            <p class="text-2xl font-bold text-[#236B3D]">18</p>
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
                                    <th class="py-3 px-4 font-semibold text-gray-600">Date's</th>
                                    <th class="py-3 px-4 font-semibold text-gray-600">Item's Profit</th>
                                    <th class="py-3 px-4 font-semibold text-gray-600">Profit</th>
                                </tr>
                            </thead>
                            <tbody id="sales-table-body">
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
