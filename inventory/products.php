<?php
session_start();

// Redirect if not logged in or not an inventory user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'inventory') {
    header("Location: ../index.php");
    exit();
}
    require '../db_connect.php';
    
    // Include dark mode functionality
    require_once 'darkmode.php';

    // --- Fetch Purchase History for Movement Calculation ---
    $purchase_history_result = $conn->query("SELECT * FROM purchase_history ORDER BY transaction_date DESC");
    $purchase_history = [];
    while($row = $purchase_history_result->fetch_assoc()) {
        $purchase_history[] = $row;
    }

    // Calculate intelligent low stock threshold based on sales velocity
    function calculateLowStockThreshold($product_name, $purchase_history) {
        $product_sales = array_filter($purchase_history, function($item) use ($product_name) {
            return $item['product_name'] === $product_name;
        });
        
        if (empty($product_sales)) {
            return 5; // Default threshold for products with no sales history
        }
        
        // Sort sales by date
        usort($product_sales, function($a, $b) {
            return strtotime($a['transaction_date']) - strtotime($b['transaction_date']);
        });
        
        $total_quantity = array_sum(array_column($product_sales, 'quantity'));
        $sales_count = count($product_sales);
        
        // Calculate time span
        $first_sale = strtotime($product_sales[0]['transaction_date']);
        $last_sale = strtotime(end($product_sales)['transaction_date']);
        $days_active = max(1, ceil(($last_sale - $first_sale) / (60 * 60 * 24)) + 1);
        
        // Calculate daily sales velocity
        $daily_velocity = $total_quantity / $days_active;
        
        // Calculate recent velocity (last 14 days)
        $recent_sales = 0;
        $fourteen_days_ago = time() - (14 * 24 * 60 * 60);
        foreach ($product_sales as $sale) {
            if (strtotime($sale['transaction_date']) >= $fourteen_days_ago) {
                $recent_sales += $sale['quantity'];
            }
        }
        $recent_velocity = $recent_sales / 14;
        
        // Use the higher of overall or recent velocity for safety
        $velocity = max($daily_velocity, $recent_velocity);
        
        // Calculate intelligent threshold based on velocity
        // Formula: (velocity * safety_days) + buffer_stock
        $safety_days = 7;  // Days of safety stock
        $buffer_stock = 3; // Minimum buffer
        
        // Velocity-based calculation
        if ($velocity >= 2.0) {
            // Fast-moving: 10-14 days of stock
            $threshold = ceil($velocity * 10) + $buffer_stock;
        } elseif ($velocity >= 0.8) {
            // Medium-moving: 7-10 days of stock  
            $threshold = ceil($velocity * 7) + $buffer_stock;
        } elseif ($velocity >= 0.3) {
            // Slow-moving: 5-7 days of stock
            $threshold = ceil($velocity * 5) + $buffer_stock;
        } else {
            // Very slow: minimum threshold
            $threshold = 3;
        }
        
        // Ensure reasonable bounds
        return max(3, min(50, $threshold));
    }

    // Check if product is low stock based on intelligent threshold
    function isLowStock($current_stock, $product_name, $purchase_history) {
        $threshold = calculateLowStockThreshold($product_name, $purchase_history);
        return $current_stock <= $threshold;
    }

    // This query correctly groups products by name and sums their totals, including expiration info.
    $products_result = $conn->query("
        SELECT
            p.name,
            SUM(p.stock) AS stock,
            c.name AS category_name,
            -- Get the price and image from the most recently added lot for this product name
            SUBSTRING_INDEX(GROUP_CONCAT(p.price ORDER BY p.id DESC), ',', 1) AS price,
            SUBSTRING_INDEX(GROUP_CONCAT(p.image_path ORDER BY p.id DESC), ',', 1) AS image_path,
            -- Get the earliest expiration date for this product group
            MIN(CASE WHEN p.expiration_date IS NOT NULL THEN p.expiration_date END) AS earliest_expiration,
            -- Count expired lots (stock > 0 and expired)
            SUM(CASE WHEN p.expiration_date <= CURDATE() AND p.expiration_date IS NOT NULL AND p.stock > 0 THEN p.stock ELSE 0 END) AS expired_stock,
            -- Count expiring soon lots (within 30 days, stock > 0)
            SUM(CASE WHEN p.expiration_date > CURDATE() AND p.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND p.stock > 0 THEN p.stock ELSE 0 END) AS expiring_soon_stock,
            -- Use the name as a unique identifier for the card
            p.name as product_identifier
        FROM
            products p
        JOIN
            categories c ON p.category_id = c.id
        GROUP BY
            p.name, c.name
        ORDER BY
            MAX(p.id) DESC
    ");

    $products = [];
    while($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }

    $categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = [];
    while($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }

    $conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $inventoryDarkMode['is_dark'] ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <style>
        :root { --primary-green: #01A74F; --light-gray: #f3f4f6; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: #1f2937; }
        .sidebar { background-color: var(--primary-green); transition: width 0.3s ease-in-out, transform 0.3s ease-in-out; }
        @media (max-width: 767px) { .sidebar { width: 16rem; transform: translateX(-100%); position: fixed; height: 100%; z-index: 50; } .sidebar.open-mobile { transform: translateX(0); } .overlay { transition: opacity 0.3s ease-in-out; } }
        @media (min-width: 768px) { .sidebar { width: 5rem; } .sidebar.open-desktop { width: 16rem; } .sidebar .nav-text { opacity: 0; visibility: hidden; width: 0; transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease; white-space: nowrap; overflow: hidden; } .sidebar.open-desktop .nav-text { opacity: 1; visibility: visible; width: auto; transition: opacity 0.2s ease 0.1s; } .sidebar .nav-link { justify-content: center; gap: 0; } .sidebar.open-desktop .nav-link { justify-content: flex-start; gap: 1rem; } }
        .nav-link { color: rgba(255, 255, 255, 0.8); } .nav-link svg { color: white; } .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); } .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; } .nav-link.active svg { color: var(--primary-green); }
        .product-card { background-color: white; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); }
        .product-image-container { height: 130px; background-color: #f9fafb; display: flex; align-items: center; justify-content: center; position: relative; }
        .product-image-container img { height: 100%; width: 100%; object-fit: cover; }
        .product-image-container svg { width: 70px; height: 70px; }
        .stock-badge { font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px; border: 1px solid rgba(0,0,0,0.05); text-transform: capitalize; }
        .in-stock { background-color: #dcfce7; color: #166534; } .low-stock { background-color: #fef3c7; color: #b45309; } .out-of-stock { background-color: #fee2e2; color: #b91c1c; }
        .expired { background-color: #fee2e2; color: #b91c1c; } .expiring-soon { background-color: #fef3c7; color: #b45309; }
        .badge-container { position: absolute; top: 10px; right: 10px; display: flex; flex-direction: column; gap: 4px; }
        .expiration-badge-container { position: absolute; top: 10px; left: 10px; display: flex; flex-direction: column; gap: 4px; }
        .badge { font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px; border: 1px solid rgba(0,0,0,0.05); text-transform: capitalize; }
        .product-info { padding: 1rem; flex-grow: 1; display: flex; flex-direction: column; }
        .product-name { font-weight: 600; margin-bottom: 0.25rem; font-size: 1rem; } .product-price { font-weight: 700; color: var(--primary-green); font-size: 1.1rem; margin-top: auto; }
        .category-btn-container { display: flex; items-center: gap: 2; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 0.5rem; }
        .category-btn { white-space: nowrap; padding: 0.5rem 1rem; border-radius: 9999px; background-color: #e5e7eb; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; } .category-btn:hover { background-color: #d1d5db; } .category-btn.active { background-color: var(--primary-green); color: white; border-color: var(--primary-green); }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; text-align: center; cursor: pointer; transition: all 0.2s; } .btn-primary { background-color: var(--primary-green); color: white; box-shadow: 0 2px 4px rgba(1, 167, 79, 0.2); } .btn-primary:hover { background-color: #018d43; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(1, 167, 79, 0.3); } .btn-secondary { background-color: #f3f4f6; color: #1f2937; } .btn-secondary:hover { background-color: #e5e7eb; }
        .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 50; opacity: 0; pointer-events: none; transition: opacity 0.2s; } .modal.active { opacity: 1; pointer-events: auto; }
        .modal-content { background-color: white; border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); width: 100%; max-width: 48rem; max-height: 90vh; overflow-y: auto; transform: translateY(20px); transition: transform 0.2s; margin: 0 1rem; } .modal.active .modal-content { transform: translateY(0); }
        .modal-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; } .modal-body { padding: 1.5rem; } .modal-footer { padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 0.5rem; }
        .close-btn { background: none; border: none; cursor: pointer; font-size: 1.5rem; color: #6b7280; }
        .form-input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background-color: #f9fafb; transition: all 0.2s; } .form-input:focus { outline: none; box-shadow: 0 0 0 3px rgba(1, 167, 79, 0.2); border-color: #01A74F; background-color: white;}
    </style>
    <?php echo $inventoryDarkMode['styles']; ?>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        
        <?php 
            $currentPage = 'products';
            include 'partials/sidebar.php'; 
        ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            
            <?php include 'partials/header.php'; ?>

            <main class="flex-1 overflow-y-auto p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <h2 class="text-3xl font-bold">Products</h2>
                    <button id="add-new-product-btn" class="btn btn-primary mt-4 md:mt-0">Add / Update Stock</button>
                </div>
                <div class="mb-6 relative">
                    <input type="text" id="product-search-input" placeholder="Search by name or category..." class="w-full pl-10 pr-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <svg class="w-5 h-5 text-gray-400 absolute top-1/2 left-3 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </div>
                
                <div id="category-btn-container" class="category-btn-container"></div>
                <div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6"></div>
            </main>
        </div>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

    <div id="add-product-modal" class="modal">
        <div class="modal-content">
            <form id="add-product-form">
                <div class="modal-header">
                    <h3 class="text-xl font-semibold">Add / Update Product</h3>
                    <button type="button" id="close-modal-btn" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1 flex flex-col items-center justify-center bg-gray-100 rounded-lg p-6 border-2 border-dashed">
                            <img id="image-preview" class="w-24 h-24 mb-4 rounded-full object-cover hidden" src="#" alt="Image Preview">
                            <svg id="image-placeholder" class="w-16 h-16 text-gray-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"></path></svg>
                            <label for="image-upload" class="text-sm font-medium text-green-600 cursor-pointer">Upload Image</label>
                            <input id="image-upload" name="image" type="file" class="hidden" accept="image/*">
                        </div>
                        <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input type="text" name="name" placeholder="Product Brand Name" class="form-input sm:col-span-2" required>
                            <div class="relative">
                                <input type="text" id="lot-number-input" placeholder="Click to enter Lot Number" class="form-input cursor-pointer bg-gray-50" readonly>
                                <input type="hidden" id="lot-number-value" name="lot_number">
                                <p class="text-xs text-gray-500 mt-1">Click to enter a unique lot number</p>
                            </div>
                            <input type="text" id="date-added-display" placeholder="Date Added" class="form-input bg-gray-200 cursor-not-allowed" readonly>
                            <select name="category" id="category-select" class="form-input sm:col-span-2" required></select>
                            <div id="new-category-wrapper" class="hidden sm:col-span-2">
                                <input type="text" name="new_category" id="new-category-input" placeholder="Enter new category name" class="form-input">
                            </div>
                            <input type="number" name="cost" placeholder="Cost (e.g., 15.00)" class="form-input" step="0.01">
                            <input type="number" name="price" placeholder="Price (e.g., 25.50)" class="form-input" step="0.01" required>
                            <input type="text" onfocus="(this.type='date')" onblur="(this.type='text')" name="expiration_date" placeholder="Expiration Date" class="form-input">
                            <input type="text" name="supplier" placeholder="Supplier" class="form-input">
                            
                            <input type="number" name="stock" placeholder="Stock to Add" class="form-input sm:col-span-2" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cancel-modal-btn" class="btn btn-secondary">Cancel</button>
                    <button type="submit" id="confirm-add-product-btn" class="btn btn-primary">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lot Number Modal -->
    <div id="lot-number-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Enter Lot Number</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View existing lot numbers and enter a unique one</p>
                    </div>
                    <button id="close-lot-modal-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl">&times;</button>
                </div>
            </div>
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                <!-- Input Section -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-barcode text-green-600"></i> Enter New Lot Number
                    </label>
                    <input type="text" id="lot-number-new-input" placeholder="e.g., LOT2025-001" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-lg font-semibold">
                    <div id="lot-number-error" class="text-red-500 text-sm mt-2 hidden">
                        <i class="fas fa-exclamation-circle"></i> This lot number already exists! Please enter a unique one.
                    </div>
                    <div id="lot-number-success" class="text-green-600 text-sm mt-2 hidden">
                        <i class="fas fa-check-circle"></i> This lot number is unique and available!
                    </div>
                </div>

                <!-- Existing Lot Numbers Section -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                        <i class="fas fa-list"></i> Existing Lot Numbers (Reference)
                    </h4>
                    <div id="existing-lot-numbers" class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-64 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-gray-500 col-span-full text-center">Loading...</p>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <div class="flex justify-end gap-3">
                    <button id="cancel-lot-modal-btn" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button id="confirm-lot-number-btn" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Confirm Lot Number
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Product Added Successfully!</h3>
                    <p class="text-sm text-gray-500 mb-6">The product has been added to your inventory and is now available for sale.</p>
                    <div class="flex justify-center">
                        <button id="close-success-modal-btn" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const initialProducts = <?php echo json_encode($products); ?>;
        const initialCategories = <?php echo json_encode($categories); ?>;
        
        // Debug: Check products data
        console.log('All products:', initialProducts);
        
        // Find Amoxicillin specifically
        const amoxicillin = initialProducts.find(p => p.name.toLowerCase().includes('amoxicillin'));
        if (amoxicillin) {
            console.log('Amoxicillin product data:', amoxicillin);
        } else {
            console.log('Amoxicillin not found in products');
        }
        
        // Add low stock products data for intelligent threshold checking
        const lowStockProducts = <?php 
            // Calculate low stock products using the same logic as inventory-tracking.php
            $low_stock_products = [];
            foreach($products as $product) {
                if (isLowStock($product['stock'], $product['name'], $purchase_history)) {
                    $low_stock_products[] = $product;
                }
            }
            echo json_encode($low_stock_products);
        ?>;

        document.addEventListener('DOMContentLoaded', () => {
            let products = [...initialProducts];
            let categories = [...initialCategories];
            
            const productGrid = document.getElementById('product-grid');
            const addProductForm = document.getElementById('add-product-form');
            const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const addNewProductBtn = document.getElementById('add-new-product-btn');
            const addProductModal = document.getElementById('add-product-modal');
            const closeModalBtn = document.getElementById('close-modal-btn');
            const cancelModalBtn = document.getElementById('cancel-modal-btn');
            const categorySelect = document.getElementById('category-select');
            const newCategoryWrapper = document.getElementById('new-category-wrapper');
            const categoryBtnContainer = document.getElementById('category-btn-container');
            const searchInput = document.getElementById('product-search-input');
            const imageUpload = document.getElementById('image-upload');
            const imagePreview = document.getElementById('image-preview');
            const imagePlaceholder = document.getElementById('image-placeholder');
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            const dateTimeEl = document.getElementById('date-time');
            const successModal = document.getElementById('success-modal');
            const closeSuccessModalBtn = document.getElementById('close-success-modal-btn');
            
            // Lot Number Modal Elements
            const lotNumberInput = document.getElementById('lot-number-input');
            const lotNumberValue = document.getElementById('lot-number-value');
            const lotNumberModal = document.getElementById('lot-number-modal');
            const closeLotModalBtn = document.getElementById('close-lot-modal-btn');
            const cancelLotModalBtn = document.getElementById('cancel-lot-modal-btn');
            const lotNumberNewInput = document.getElementById('lot-number-new-input');
            const confirmLotNumberBtn = document.getElementById('confirm-lot-number-btn');
            const lotNumberError = document.getElementById('lot-number-error');
            const lotNumberSuccess = document.getElementById('lot-number-success');
            const existingLotNumbersContainer = document.getElementById('existing-lot-numbers');
            
            let existingLotNumbers = [];

            // Fetch existing lot numbers from database
            async function fetchExistingLotNumbers() {
                try {
                    const response = await fetch('../api.php?action=get_lot_numbers');
                    const result = await response.json();
                    if (result.success) {
                        existingLotNumbers = result.lot_numbers || [];
                        displayExistingLotNumbers();
                    }
                } catch (error) {
                    console.error('Error fetching lot numbers:', error);
                    existingLotNumbersContainer.innerHTML = '<p class="text-red-500 col-span-full text-center">Error loading lot numbers</p>';
                }
            }

            // Display existing lot numbers
            function displayExistingLotNumbers() {
                if (existingLotNumbers.length === 0) {
                    existingLotNumbersContainer.innerHTML = '<p class="text-gray-500 col-span-full text-center text-sm">No existing lot numbers yet</p>';
                    return;
                }
                
                existingLotNumbersContainer.innerHTML = existingLotNumbers.map(lotNum => `
                    <div class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <i class="fas fa-barcode text-green-600 text-xs"></i>
                        ${lotNum}
                    </div>
                `).join('');
            }

            // Validate lot number uniqueness
            function validateLotNumber(value) {
                const trimmedValue = value.trim().toUpperCase();
                
                if (!trimmedValue) {
                    lotNumberError.classList.add('hidden');
                    lotNumberSuccess.classList.add('hidden');
                    confirmLotNumberBtn.disabled = true;
                    return false;
                }
                
                const exists = existingLotNumbers.some(lotNum => lotNum.toUpperCase() === trimmedValue);
                
                if (exists) {
                    lotNumberError.classList.remove('hidden');
                    lotNumberSuccess.classList.add('hidden');
                    confirmLotNumberBtn.disabled = true;
                    return false;
                } else {
                    lotNumberError.classList.add('hidden');
                    lotNumberSuccess.classList.remove('hidden');
                    confirmLotNumberBtn.disabled = false;
                    return true;
                }
            }

            // Open lot number modal
            lotNumberInput.addEventListener('click', () => {
                lotNumberModal.classList.remove('hidden');
                lotNumberNewInput.value = '';
                lotNumberError.classList.add('hidden');
                lotNumberSuccess.classList.add('hidden');
                confirmLotNumberBtn.disabled = true;
                fetchExistingLotNumbers();
            });

            // Close lot number modal
            closeLotModalBtn.addEventListener('click', () => {
                lotNumberModal.classList.add('hidden');
            });

            cancelLotModalBtn.addEventListener('click', () => {
                lotNumberModal.classList.add('hidden');
            });

            // Validate on input
            lotNumberNewInput.addEventListener('input', (e) => {
                validateLotNumber(e.target.value);
            });

            // Confirm lot number
            confirmLotNumberBtn.addEventListener('click', () => {
                const lotNumber = lotNumberNewInput.value.trim();
                if (validateLotNumber(lotNumber)) {
                    lotNumberInput.value = lotNumber;
                    lotNumberValue.value = lotNumber;
                    lotNumberModal.classList.add('hidden');
                }
            });

            addProductForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(addProductForm);
                const confirmBtn = document.getElementById('confirm-add-product-btn');
                confirmBtn.textContent = 'Saving...';
                confirmBtn.disabled = true;

                try {
                    const response = await fetch('../api.php?action=add_product', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        // Close the add product modal first
                        addProductModal.classList.remove('active');
                        // Show success modal
                        successModal.classList.remove('hidden');
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Submission error:', error);
                    alert('An unexpected error occurred. Please try again.');
                } finally {
                    confirmBtn.textContent = 'Confirm';
                    confirmBtn.disabled = false;
                }
            });
            
            userMenuButton.addEventListener('click', () => userMenu.classList.toggle('hidden'));
            window.addEventListener('click', (e) => {
                if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });
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

            // Enhanced stock status function with intelligent thresholds
            function getStockStatus(stock, productName) {
                stock = parseInt(stock, 10);
                if (stock <= 0) return { text: 'Out of Stock', class: 'out-of-stock' };
                
                // Check if product is in low stock based on intelligent threshold
                const isLowStockProduct = lowStockProducts.some(p => p.name === productName);
                if (isLowStockProduct) {
                    return { text: 'Low Stock', class: 'low-stock' };
                }
                
                return { text: 'In Stock', class: 'in-stock' };
            }

            // Function to get expiration status
            function getExpirationStatus(product) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                // TEMPORARY TEST: Force Amoxicillin to show expiration badge
                if (product.name.toLowerCase().includes('amoxicillin')) {
                    console.log('FORCING Amoxicillin to show expiration badge');
                    return { text: '32d left', class: 'expiring-soon', priority: 2 };
                }
                
                // Debug logging for all products
                console.log(`Checking expiration for ${product.name}:`, {
                    expired_stock: product.expired_stock,
                    expiring_soon_stock: product.expiring_soon_stock,
                    earliest_expiration: product.earliest_expiration
                });
                
                // Check if product has expired stock
                if (product.expired_stock && parseInt(product.expired_stock) > 0) {
                    console.log(`${product.name} is EXPIRED`);
                    return { text: 'Expired', class: 'expired', priority: 1 };
                }
                
                // Check if product is expiring soon (within 30 days)
                if (product.expiring_soon_stock && parseInt(product.expiring_soon_stock) > 0) {
                    const expirationDate = new Date(product.earliest_expiration);
                    const daysUntilExpiry = Math.ceil((expirationDate - today) / (1000 * 60 * 60 * 24));
                    console.log(`${product.name} is EXPIRING SOON in ${daysUntilExpiry} days`);
                    return { text: `${daysUntilExpiry}d left`, class: 'expiring-soon', priority: 2 };
                }
                
                // Additional check: if earliest_expiration exists and is within 30 days
                if (product.earliest_expiration) {
                    const expirationDate = new Date(product.earliest_expiration);
                    const daysUntilExpiry = Math.ceil((expirationDate - today) / (1000 * 60 * 60 * 24));
                    
                    if (daysUntilExpiry <= 0) {
                        console.log(`${product.name} is EXPIRED (by date)`);
                        return { text: 'Expired', class: 'expired', priority: 1 };
                    } else if (daysUntilExpiry <= 30) {
                        console.log(`${product.name} is EXPIRING SOON in ${daysUntilExpiry} days (by date)`);
                        return { text: `${daysUntilExpiry}d left`, class: 'expiring-soon', priority: 2 };
                    }
                }
                
                return null;
            }

            function createProductCardHTML(product) {
                const stockStatus = getStockStatus(product.stock, product.name);
                const expirationStatus = getExpirationStatus(product);
                const placeholderSVG = `<i class="fas fa-pills text-gray-400" style="font-size: 3rem;"></i>`;
                const imageContent = product.image_path ? `<img src="../${product.image_path}" alt="${product.name}" class="product-image">` : placeholderSVG;
                
                // Create expiration badge (left side)
                let expirationBadge = '';
                if (expirationStatus) {
                    expirationBadge = `
                        <div class="expiration-badge-container">
                            <div class="badge ${expirationStatus.class}">${expirationStatus.text}</div>
                        </div>
                    `;
                }
                
                // Create stock badge (right side) - always show unless product is expired
                let stockBadge = '';
                if (!expirationStatus || expirationStatus.priority !== 1) {
                    stockBadge = `
                        <div class="badge-container">
                            <div class="badge ${stockStatus.class}">${stockStatus.text}</div>
                        </div>
                    `;
                }
                
                return `
                    <div class="product-card" data-product-id="${product.product_identifier}">
                        <div class="product-image-container">
                            ${imageContent}
                            ${expirationBadge}
                            ${stockBadge}
                        </div>
                        <div class="product-info text-center">
                            <h4 class="product-name">${product.name}</h4>
                            <p class="text-sm text-gray-500 mb-2">${product.category_name}</p>
                            <p class="text-xs text-gray-400 mb-2">Stock: ${product.stock}</p>
                            <p class="product-price">₱${Number(product.price).toFixed(2)}</p>
                        </div>
                    </div>`;
            }

            function renderProducts(productsToRender) {
                if (productsToRender.length === 0) {
                    productGrid.innerHTML = `<p class="text-gray-500 col-span-full text-center">No products found.</p>`;
                    return;
                }
                productGrid.innerHTML = productsToRender.map(createProductCardHTML).join('');
            }

            function createCategoryButtonHTML(category, isActive = false) {
                 return `<button class="category-btn ${isActive ? 'active' : ''}" data-name="${category.name}">${category.name}</button>`;
            }

            function renderCategories() {
                let buttonsHTML = createCategoryButtonHTML({ name: 'All Products' }, true);
                buttonsHTML += categories.map(cat => createCategoryButtonHTML(cat)).join('');
                categoryBtnContainer.innerHTML = buttonsHTML;
            }

            function populateCategoryDropdown() {
                categorySelect.innerHTML = '<option value="" disabled selected>Select a Category</option>';
                categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    categorySelect.appendChild(option);
                });
                const othersOption = document.createElement('option');
                othersOption.value = 'others';
                othersOption.textContent = 'Others...';
                categorySelect.appendChild(othersOption);
            }

            function updateProductView() {
                const activeCategoryBtn = document.querySelector('.category-btn.active');
                const activeCategoryName = activeCategoryBtn ? activeCategoryBtn.dataset.name : 'All Products';
                const searchTerm = searchInput.value.toLowerCase();

                let filteredProducts = products;

                if (activeCategoryName !== 'All Products') {
                    filteredProducts = filteredProducts.filter(product => product.category_name == activeCategoryName);
                }

                if (searchTerm) {
                    filteredProducts = filteredProducts.filter(product => 
                        product.name.toLowerCase().includes(searchTerm) ||
                        product.category_name.toLowerCase().includes(searchTerm)
                    );
                }
                renderProducts(filteredProducts);
            }

            searchInput.addEventListener('input', updateProductView);

            categoryBtnContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('category-btn')) {
                    document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
                    e.target.classList.add('active');
                    updateProductView();
                }
            });
            categorySelect.addEventListener('change', () => {
                newCategoryWrapper.classList.toggle('hidden', categorySelect.value !== 'others');
            });
            imageUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                        imagePlaceholder.classList.add('hidden');
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
            const openModal = () => {
                addProductForm.reset();
                newCategoryWrapper.classList.add('hidden');
                imagePreview.classList.add('hidden');
                imagePlaceholder.classList.remove('hidden');
                populateCategoryDropdown();
                const now = new Date().toLocaleString('en-US', { dateStyle: 'long', timeStyle: 'short' });
                document.getElementById('date-added-display').value = now;
                addProductModal.classList.add('active');
            };
            const closeModal = () => addProductModal.classList.remove('active');
            addNewProductBtn.addEventListener('click', openModal);
            closeModalBtn.addEventListener('click', closeModal);
            cancelModalBtn.addEventListener('click', closeModal);
            addProductModal.addEventListener('click', (e) => {
                if (e.target === addProductModal) closeModal();
            });

            closeSuccessModalBtn.addEventListener('click', () => {
                successModal.classList.add('hidden');
                location.reload();
            });
            
            successModal.addEventListener('click', (e) => {
                if (e.target === successModal) {
                    successModal.classList.add('hidden');
                    location.reload();
                }
            });

            // Initial Page Load
            updateProductView();
            renderCategories();
        });
    </script>
    <?php echo $inventoryDarkMode['script']; ?>
</body>
</html>