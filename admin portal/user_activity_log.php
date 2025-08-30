<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - User Activity Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <div id="user-activity-page">
                <div class="bg-white p-6 rounded-2xl shadow-md">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Activity History</h2>
                        <input type="text" id="activity-search" placeholder="search user/action" class="w-1/3 p-2 rounded-lg border border-gray-300 focus:outline-none focus:ring focus:ring-green-500 focus:ring-opacity-50">
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                    <th class="py-3 px-4 font-semibold text-gray-600">User</th>
                                    <th class="py-3 px-4 font-semibold text-gray-600">Action</th>
                                    <th class="py-3 px-4 font-semibold text-gray-600">Time Stamp</th>
                                </tr>
                            </thead>
                            <tbody id="activity-table-body">
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
