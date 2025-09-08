<?php
session_start();
// Redirect if not logged in or not an inventory user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'inventory') {
    header("Location: ../login.php");
    exit();
}
    require '../db_connect.php';

    // --- Fetch Grouped Data for JavaScript ---
    $products_result = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC");
    $all_products_json = [];
    while($row = $products_result->fetch_assoc()) {
        $all_products_json[] = $row;
    }

    // --- Fetch Grouped LOW STOCK Data ---
    $low_stock_grouped_result = $conn->query("
        SELECT 
            p.name, 
            p.category_id,
            c.name as category_name,
            SUM(p.stock) as stock, 
            SUM(p.item_total) as item_total
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE (p.expiration_date > CURDATE() OR p.expiration_date IS NULL) AND p.item_total > 0
        GROUP BY p.name, p.category_id, c.name
        HAVING SUM(p.stock) <= 5 AND SUM(p.stock) > 0
    ");
    $low_stock_grouped_json = [];
    while($row = $low_stock_grouped_result->fetch_assoc()) {
        $low_stock_grouped_json[] = $row;
    }

    // --- Fetch Grouped Out of Stock Data ---
    $out_of_stock_grouped_result = $conn->query("
        SELECT 
            p.name, 
            p.category_id,
            c.name as category_name,
            SUM(p.stock) as stock, 
            SUM(p.item_total) as item_total
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE (p.expiration_date > CURDATE() OR p.expiration_date IS NULL)
        GROUP BY p.name, p.category_id, c.name
        HAVING SUM(p.item_total) <= 0
    ");
    $out_of_stock_grouped_json = [];
    while($row = $out_of_stock_grouped_result->fetch_assoc()) {
        $out_of_stock_grouped_json[] = $row;
    }


    // --- Fetch Categories ---
    $categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = [];
    while($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    // --- Fetch Product History ---
    $history_result = $conn->query("SELECT h.*, c.name as category_name FROM product_history h LEFT JOIN categories c ON h.category_id = c.id ORDER BY h.deleted_at DESC");
    $product_history = [];
    while($row = $history_result->fetch_assoc()) {
        $product_history[] = $row;
    }


    // --- Calculate Summary Counts ---
    $not_expired_condition = "(expiration_date > CURDATE() OR expiration_date IS NULL)";
    
    $available_count_result = $conn->query("SELECT COUNT(*) as count FROM products WHERE item_total > 0 AND " . $not_expired_condition);
    $available_count = $available_count_result->fetch_assoc()['count'];
    
    $low_stock_count = count($low_stock_grouped_json);

    $out_of_stock_count = count($out_of_stock_grouped_json);

    $exp_alert_count_result = $conn->query("SELECT COUNT(*) as count FROM products WHERE item_total > 0 AND expiration_date > CURDATE() AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH)");
    $exp_alert_count = $exp_alert_count_result->fetch_assoc()['count'];
    
    $expired_count_result = $conn->query("SELECT COUNT(*) as count FROM products WHERE item_total > 0 AND expiration_date <= CURDATE()");
    $expired_count = $expired_count_result->fetch_assoc()['count'];
    
    $history_count = count($product_history);


    $summary_counts = [
        'available' => $available_count,
        'lowStock' => $low_stock_count,
        'outOfStock' => $out_of_stock_count,
        'expAlert' => $exp_alert_count,
        'expired' => $expired_count,
        'history' => $history_count
    ];

    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --primary-green: #01A74F; --light-gray: #f3f4f6; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: #1f2937; }
        .sidebar { background-color: var(--primary-green); transition: width 0.3s ease-in-out, transform 0.3s ease-in-out; }
        @media (max-width: 767px) { .sidebar { width: 16rem; transform: translateX(-100%); position: fixed; height: 100%; z-index: 50; } .sidebar.open-mobile { transform: translateX(0); } .overlay { transition: opacity 0.3s ease-in-out; } }
        @media (min-width: 768px) { .sidebar { width: 5rem; } .sidebar.open-desktop { width: 16rem; } .sidebar .nav-text { opacity: 0; visibility: hidden; width: 0; transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease; white-space: nowrap; overflow: hidden; } .sidebar.open-desktop .nav-text { opacity: 1; visibility: visible; width: auto; transition: opacity 0.2s ease 0.1s; } .sidebar .nav-link { justify-content: center; gap: 0; } .sidebar.open-desktop .nav-link { justify-content: flex-start; gap: 1rem; } }
        .nav-link { color: rgba(255, 255, 255, 0.8); } .nav-link svg { color: white; } .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); } .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; } .nav-link.active svg { color: var(--primary-green); }
        .summary-card { background-color: white; border: 1px solid #e5e7eb; color: #4b5563; transition: all 0.2s ease-in-out; }
        .summary-card.active { background-color: var(--primary-green); color: white; transform: translateY(-4px); box-shadow: 0 4px 12px rgba(1, 167, 79, 0.2); border-color: var(--primary-green); }
        .category-btn { white-space: nowrap; padding: 0.5rem 1rem; border-radius: 9999px; background-color: #e5e7eb; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; } .category-btn:hover { background-color: #d1d5db; } .category-btn.active { background-color: #374151; color: white; }
        .table-header { background-color: #f9fafb; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">

        <?php
            $currentPage = 'inventory';
            include '../partials/sidebar.php';
        ?>

        <div class="flex-1 flex flex-col overflow-hidden">

            <?php include '../partials/header.php'; ?>

            <main class="flex-1 overflow-y-auto p-6">
                <h2 class="text-3xl font-bold mb-6">Inventory Tracking</h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                    <button class="summary-card active p-4 rounded-lg text-left" data-view="available">
                        <p class="text-sm">Available Products</p>
                        <p id="count-available" class="text-2xl font-bold">0</p>
                    </button>
                    <button class="summary-card p-4 rounded-lg text-left" data-view="low-stock">
                        <p class="text-sm">Low Stock</p>
                        <p id="count-low-stock" class="text-2xl font-bold">0</p>
                    </button>
                    <button class="summary-card p-4 rounded-lg text-left" data-view="out-of-stock">
                        <p class="text-sm">Out of Stock</p>
                        <p id="count-out-of-stock" class="text-2xl font-bold">0</p>
                    </button>
                    <button class="summary-card p-4 rounded-lg text-left" data-view="expiration-alert">
                        <p class="text-sm">Expiration Alert</p>
                        <p id="count-exp-alert" class="text-2xl font-bold">0</p>
                    </button>
                    <button class="summary-card p-4 rounded-lg text-left" data-view="expired">
                        <p class="text-sm">Expired Products</p>
                        <p id="count-expired" class="text-2xl font-bold">0</p>
                    </button>
                     <button class="summary-card p-4 rounded-lg text-left" data-view="history">
                        <p class="text-sm">Product History</p>
                        <p id="count-history" class="text-2xl font-bold">0</p>
                    </button>
                </div>

                <div class="mb-6 relative">
                    <input type="text" id="search-input" placeholder="Search by product name or lot number..." class="w-full pl-10 pr-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <svg class="w-5 h-5 text-gray-400 absolute top-1/2 left-3 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </div>

                <div id="category-btn-container" class="flex items-center gap-2 mb-6 overflow-x-auto pb-2"></div>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead id="inventory-table-head" class="text-xs table-header"></thead>
                            <tbody id="inventory-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

    <script>
        let allProducts = <?php echo json_encode($all_products_json); ?>;
        const lowStockGrouped = <?php echo json_encode($low_stock_grouped_json); ?>;
        const outOfStockGrouped = <?php echo json_encode($out_of_stock_grouped_json); ?>;
        const allHistory = <?php echo json_encode($product_history); ?>;
        const allCategories = <?php echo json_encode($categories); ?>;
        const summaryCounts = <?php echo json_encode($summary_counts); ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const tableHead = document.getElementById('inventory-table-head');
            const tableBody = document.getElementById('inventory-table-body');
            const searchInput = document.getElementById('search-input');
            const summaryCards = document.querySelectorAll('.summary-card');
            const categoryBtnContainer = document.getElementById('category-btn-container');
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            const dateTimeEl = document.getElementById('date-time');

            if(userMenuButton){
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

            // UPDATED: Table Headers Configuration
            const tableHeaders = {
                available: ["#", "Product Name", "Lot Number", "Batch Number", "Stock", "Item Total", "Price", "Date Added", "Expiration Date", "Action"],
                'low-stock': ["#", "Product Name", "Stock Level", "Item Total"],
                'out-of-stock': ["#", "Product Name", "Stock Level", "Item Total"],
                'expiration-alert': ["#", "Product Name", "Lot Number", "Batch Number", "Stock", "Expires In", "Expiration Date"],
                'expired': ["#", "Product Name", "Lot Number", "Batch Number", "Stock", "Expired On", "Supplier"],
                'history': ["#", "Product Name", "Lot Num", "Batch Num", "Stock", "Date Deleted"]
            };

            function updateSummaryCounts() {
                document.getElementById('count-available').textContent = summaryCounts.available;
                document.getElementById('count-low-stock').textContent = summaryCounts.lowStock;
                document.getElementById('count-out-of-stock').textContent = summaryCounts.outOfStock;
                document.getElementById('count-exp-alert').textContent = summaryCounts.expAlert;
                document.getElementById('count-expired').textContent = summaryCounts.expired;
                document.getElementById('count-history').textContent = summaryCounts.history;
            }

            function renderCategoryButtons() {
                let buttonsHTML = `<button class="category-btn active" data-id="all">All Products</button>`;
                buttonsHTML += allCategories.map(cat => `<button class="category-btn" data-id="${cat.id}">${cat.name}</button>`).join('');
                categoryBtnContainer.innerHTML = buttonsHTML;
            }

            function updateTableView() {
                const activeView = document.querySelector('.summary-card.active').dataset.view;
                const activeCategory = document.querySelector('.category-btn.active').dataset.id;
                const searchTerm = searchInput.value.toLowerCase();

                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const oneMonthFromNow = new Date(today);
                oneMonthFromNow.setMonth(today.getMonth() + 1);

                const parseDate = (dateString) => {
                    if (!dateString) return null;
                    const parts = dateString.split('-');
                    return new Date(parts[0], parseInt(parts[1]) - 1, parts[2]);
                };

                let viewFilteredProducts;

                switch (activeView) {
                    case 'available':
                        viewFilteredProducts = allProducts.filter(p => {
                            const expDate = parseDate(p.expiration_date);
                            const isNotExpired = !expDate || expDate > today;
                            return p.item_total > 0 && isNotExpired;
                        });
                        break;
                    case 'low-stock':
                        viewFilteredProducts = lowStockGrouped;
                        break;
                    case 'out-of-stock':
                        viewFilteredProducts = outOfStockGrouped;
                        break;
                    case 'expiration-alert':
                        viewFilteredProducts = allProducts.filter(p => {
                            const expDate = parseDate(p.expiration_date);
                            return p.item_total > 0 && expDate && expDate > today && expDate <= oneMonthFromNow;
                        });
                        break;
                    case 'expired':
                        viewFilteredProducts = allProducts.filter(p => {
                            const expDate = parseDate(p.expiration_date);
                            return p.item_total > 0 && expDate && expDate <= today;
                        });
                        break;
                    case 'history':
                        viewFilteredProducts = allHistory;
                        break;
                    default:
                        viewFilteredProducts = allProducts;
                        break;
                }

                let categoryFilteredProducts = viewFilteredProducts;
                if (activeCategory !== 'all') {
                    categoryFilteredProducts = viewFilteredProducts.filter(p => p.category_id == activeCategory);
                }

                let finalProducts = categoryFilteredProducts;
                if (searchTerm) {
                    finalProducts = categoryFilteredProducts.filter(p =>
                        p.name.toLowerCase().includes(searchTerm) ||
                        (p.lot_number && p.lot_number.toLowerCase().includes(searchTerm))
                    );
                }

                renderTable(finalProducts, activeView);
            }

            function renderTable(productsToRender, view) {
                tableHead.innerHTML = `<tr>${tableHeaders[view].map(h => `<th scope="col" class="px-6 py-3">${h}</th>`).join('')}</tr>`;

                if (productsToRender.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="${tableHeaders[view].length}" class="text-center py-8 text-gray-500">No products found.</td></tr>`;
                    return;
                }

                const today = new Date();
                today.setHours(0, 0, 0, 0);

                tableBody.innerHTML = productsToRender.map((p, index) => {
                    let rowContent = '';
                    let actionButton = `<button class="text-red-500 hover:text-red-700 delete-btn" title="Delete" data-id="${p.id}"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 pointer-events-none" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>`;

                    switch(view) {
                        case 'available':
                            rowContent = `
                                <td class="px-6 py-4">${p.name}</td>
                                <td class="px-6 py-4">${p.lot_number || 'N/A'}</td>
                                <td class="px-6 py-4">${p.batch_number || 'N/A'}</td>
                                <td class="px-6 py-4 font-bold">${p.stock}</td>
                                <td class="px-6 py-4 font-semibold">${p.item_total}</td>
                                <td class="px-6 py-4">₱${Number(p.price).toFixed(2)}</td>
                                <td class="px-6 py-4">${new Date(p.date_added).toLocaleDateString()}</td>
                                <td class="px-6 py-4">${p.expiration_date || 'N/A'}</td>
                                <td class="px-6 py-4">${actionButton}</td>`;
                            break;
                        case 'low-stock':
                             rowContent = `
                                <td class="px-6 py-4">${p.name}</td>
                                <td class="px-6 py-4 font-bold text-orange-600">${p.stock}</td>
                                <td class="px-6 py-4 font-semibold">${p.item_total}</td>`;
                            break;
                        case 'out-of-stock':
                             rowContent = `
                                <td class="px-6 py-4">${p.name}</td>
                                <td class="px-6 py-4 font-bold text-red-600">${p.stock}</td>
                                <td class="px-6 py-4 font-semibold">${p.item_total}</td>`;
                            break;
                        case 'expiration-alert':
                            const expDateAlert = new Date(p.expiration_date);
                            const timeDiff = expDateAlert.getTime() - today.getTime();
                            const daysUntilExp = Math.ceil(timeDiff / (1000 * 3600 * 24));
                            rowContent = `
                                <td class="px-6 py-4">${p.name}</td>
                                <td class="px-6 py-4">${p.lot_number || 'N/A'}</td>
                                <td class="px-6 py-4">${p.batch_number || 'N/A'}</td>
                                <td class="px-6 py-4 font-bold">${p.stock}</td>
                                <td class="px-6 py-4 font-semibold text-yellow-600">${daysUntilExp} days</td>
                                <td class="px-6 py-4">${p.expiration_date}</td>`;
                            break;
                        case 'expired':
                             rowContent = `
                                <td class="px-6 py-4">${p.name}</td>
                                <td class="px-6 py-4">${p.lot_number || 'N/A'}</td>
                                <td class="px-6 py-4">${p.batch_number || 'N/A'}</td>
                                <td class="px-6 py-4 font-bold">${p.stock}</td>
                                <td class="px-6 py-4 font-bold text-red-700">${p.expiration_date}</td>
                                <td class="px-6 py-4">${p.supplier || 'N/A'}</td>`;
                            break;
                        case 'history':
                             rowContent = `
                                <td class="px-6 py-4">${p.name}</td>
                                <td class="px-6 py-4">${p.lot_number || 'N/A'}</td>
                                <td class="px-6 py-4">${p.batch_number || 'N/A'}</td>
                                <td class="px-6 py-4 font-bold">${p.stock}</td>
                                <td class="px-6 py-4">${new Date(p.deleted_at).toLocaleString()}</td>`;
                            break;
                    }
                    return `<tr class="bg-white border-b hover:bg-gray-50"><td class="px-6 py-4 font-medium text-gray-900">${index + 1}</td>${rowContent}</tr>`;
                }).join('');
            }

            async function deleteProduct(productId) {
                if (!confirm('Are you sure you want to delete this product lot? This will move it to history.')) {
                    return;
                }

                try {
                    const response = await fetch('../api.php?action=delete_product', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: productId })
                    });
                    const result = await response.json();

                    if (result.success) {
                        alert('Product successfully moved to history.');
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Deletion error:', error);
                    alert('An unexpected error occurred during deletion.');
                }
            }


            // --- Event Listeners ---
            searchInput.addEventListener('input', updateTableView);

            summaryCards.forEach(card => {
                card.addEventListener('click', () => {
                    summaryCards.forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                    categoryBtnContainer.style.display = card.dataset.view === 'history' ? 'none' : 'flex';
                    updateTableView();
                });
            });

            categoryBtnContainer.addEventListener('click', (e) => {
                if(e.target.classList.contains('category-btn')) {
                    document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
                    e.target.classList.add('active');
                    updateTableView();
                }
            });

            tableBody.addEventListener('click', (e) => {
                const deleteButton = e.target.closest('.delete-btn');
                if (deleteButton) {
                    const productId = deleteButton.dataset.id;
                    deleteProduct(productId);
                }
            });


            // --- Initial Page Load ---
            updateSummaryCounts();
            renderCategoryButtons();
            updateTableView();
        });
    </script>
</body>
</html>