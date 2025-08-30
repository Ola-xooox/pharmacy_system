<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Set Up Account</title>
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
            <div id="setup-account-page">
                <div class="bg-white p-8 rounded-2xl shadow-md max-w-2xl mx-auto">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Set Up New Account</h2>
                    <form class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Enter Name</label>
                            <input type="text" id="name" name="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-3 focus:border-[#236B3D] focus:ring focus:ring-[#236B3D] focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">User Name</label>
                            <input type="text" id="username" name="username" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-3 focus:border-[#236B3D] focus:ring focus:ring-[#236B3D] focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" id="password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-3 focus:border-[#236B3D] focus:ring focus:ring-[#236B3D] focus:ring-opacity-50">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button type="button" class="bg-green-500 text-white py-3 rounded-lg font-medium hover:bg-green-700 transition-colors duration-200">
                                POS Access
                            </button>
                            <button type="button" class="bg-green-500 text-white py-3 rounded-lg font-medium hover:bg-green-700 transition-colors duration-200">
                                Inventory Access
                            </button>
                            <button type="button" class="bg-green-500 text-white py-3 rounded-lg font-medium hover:bg-green-700 transition-colors duration-200">
                                Customer Mgmt.
                            </button>
                        </div>
                        <button type="submit" class="w-full bg-green-500 text-white py-3 rounded-lg font-bold text-lg hover:bg-green-700 transition-colors duration-200">
                            Create Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="admin_script.js"></script>
</body>
</html>
