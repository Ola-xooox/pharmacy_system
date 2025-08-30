<?php
session_start();
// Redirect if not logged in or not an inventory user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'inventory') {
    header("Location: ../login.php");
    exit();
}
    require '../db_connect.php';
    $currentPage = 'history';

    // Fetch all purchase history records, ordered by the most recent transaction
    $history_result = $conn->query("SELECT * FROM purchase_history ORDER BY transaction_date DESC");
    $purchase_history = [];
    while($row = $history_result->fetch_assoc()) {
        $purchase_history[] = $row;
    }
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Purchase History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --primary-green: #01A74F; --light-gray: #f3f4f6; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: #1f2937; }
        .sidebar { background-color: var(--primary-green); transition: width 0.3s ease-in-out, transform 0.3s ease-in-out; }
        @media (max-width: 767px) { .sidebar { width: 16rem; transform: translateX(-100%); position: fixed; height: 100%; z-index: 50; } .sidebar.open-mobile { transform: translateX(0); } .overlay { transition: opacity 0.3s ease-in-out; } }
        @media (min-width: 768px) { .sidebar { width: 5rem; } .sidebar.open-desktop { width: 16rem; } .sidebar .nav-text { opacity: 0; visibility: hidden; width: 0; transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease; white-space: nowrap; overflow: hidden; } .sidebar.open-desktop .nav-text { opacity: 1; visibility: visible; width: auto; transition: opacity 0.2s ease 0.1s; } .sidebar .nav-link { justify-content: center; gap: 0; } .sidebar.open-desktop .nav-link { justify-content: flex-start; gap: 1rem; } }
        .nav-link { color: rgba(255, 255, 255, 0.8); } .nav-link svg { color: white; } .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); } .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; } .nav-link.active svg { color: var(--primary-green); }
        .table-header { background-color: #f9fafb; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        
        <?php include '../partials/sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            
            <?php include '../partials/header.php'; ?>

            <main class="flex-1 overflow-y-auto p-6">
                <h2 class="text-3xl font-bold mb-6">Purchase History</h2>
                
                <div class="mb-4 relative">
                     <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input type="text" id="search-input" placeholder="Search by product name..." class="w-full pl-10 pr-4 py-2.5 rounded-lg border bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div class="mb-6">
                    <input type="date" id="date-picker" class="w-full md:w-auto p-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs table-header">
                                <tr>
                                    <th scope="col" class="px-6 py-3">#</th>
                                    <th scope="col" class="px-6 py-3">Product Name</th>
                                    <th scope="col" class="px-6 py-3">Item</th>
                                    <th scope="col" class="px-6 py-3">Total Price</th>
                                    <th scope="col" class="px-6 py-3">Date</th>
                                </tr>
                            </thead>
                            <tbody id="history-table-body">
                                <!-- Data will be injected here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 bg-gray-50 border-t flex justify-end gap-8 font-semibold">
                        <div>
                            <span>Total Items Sold:</span>
                            <span id="total-items" class="text-green-600">0</span>
                        </div>
                        <div>
                            <span>Total Sales:</span>
                            <span id="total-price" class="text-green-600">₱0.00</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

    <script>
        const purchaseHistory = <?php echo json_encode($purchase_history); ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const dateTimeEl = document.getElementById('date-time');
            const tableBody = document.getElementById('history-table-body');
            const searchInput = document.getElementById('search-input');
            const datePicker = document.getElementById('date-picker');
            const totalItemsEl = document.getElementById('total-items');
            const totalPriceEl = document.getElementById('total-price');
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');

            // --- Sidebar & Header Logic ---
            if (userMenuButton) {
                userMenuButton.addEventListener('click', () => userMenu.classList.toggle('hidden'));
                window.addEventListener('click', (e) => {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
            sidebarToggleBtn.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    sidebar.classList.toggle('open-mobile');
                    overlay.classList.toggle('hidden');
                } else {
                    sidebar.classList.toggle('open-desktop');
                }
            });
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open-mobile');
                overlay.classList.add('hidden');
            });

            function updateDateTime() {
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                dateTimeEl.textContent = now.toLocaleDateString('en-US', options);
            }
            updateDateTime();
            setInterval(updateDateTime, 60000);

            function renderTable(dataToRender) {
                if(dataToRender.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-gray-500">No purchase history found for the selected criteria.</td></tr>`;
                    return;
                }
                tableBody.innerHTML = dataToRender.map((item, index) => {
                    const transactionDate = new Date(item.transaction_date);
                    const formattedDate = transactionDate.toLocaleDateString('en-US');
                    return `
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">${index + 1}</td>
                            <td class="px-6 py-4 font-semibold text-gray-700">${item.product_name}</td>
                            <td class="px-6 py-4">${item.quantity}</td>
                            <td class="px-6 py-4 font-semibold text-gray-700">
                               ₱${Number(item.total_price).toFixed(2)}
                            </td>
                            <td class="px-6 py-4">${formattedDate}</td>
                        </tr>
                    `
                }).join('');
            }
            
            function calculateAndRenderTotals(data) {
                const totalItems = data.reduce((sum, item) => sum + parseInt(item.quantity), 0);
                const totalPrice = data.reduce((sum, item) => sum + parseFloat(item.total_price), 0);

                totalItemsEl.textContent = totalItems;
                totalPriceEl.textContent = `₱${totalPrice.toFixed(2)}`;
            }

            function updateHistoryView() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedDate = datePicker.value;
                
                let filteredHistory = purchaseHistory;

                if (selectedDate) {
                    filteredHistory = filteredHistory.filter(item => {
                        const transactionDate = item.transaction_date.split(' ')[0]; // Get only the YYYY-MM-DD part
                        return transactionDate === selectedDate;
                    });
                }
                
                if (searchTerm) {
                    filteredHistory = filteredHistory.filter(item => 
                        item.product_name.toLowerCase().includes(searchTerm)
                    );
                }

                renderTable(filteredHistory);
                calculateAndRenderTotals(filteredHistory);
            }

            // --- Event Listeners ---
            searchInput.addEventListener('input', updateHistoryView);
            datePicker.addEventListener('change', updateHistoryView);
            
            // --- Initial Page Load ---
            updateHistoryView();
        });
    </script>
</body>
</html>

