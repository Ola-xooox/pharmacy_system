<?php 
session_start();
// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$currentPage = 'dashboard'; ?>
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
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

    <script src="admin_script.js"></script>
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
</body>
</html>