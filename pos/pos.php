<?php
    session_start();
    
    // Redirect if not logged in or not a POS user
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pos') {
        header("Location: ../index.php");
        exit();
    }
    
    // Include dark mode functionality
    require_once 'darkmode.php';
    
    $currentPage = 'pos';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $posDarkMode['is_dark'] ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy POS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'brand-green': '#01A74F',
                    }
                }
            }
        }
    </script>
    <style>
        :root { 
            --primary-green: #01A74F; 
            --light-gray: #f7fafc; 
            --border-gray: #e2e8f0;
        }
        html { scroll-behavior: smooth; }
        body { 
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background-color: var(--light-gray); 
            color: #2d3748; 
        }

        /* Card design from products.php */
        .product-card { 
            background-color: white; 
            border-radius: 0.75rem; 
            border: 1px solid #e5e7eb; 
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); 
            overflow: hidden; 
            transition: transform 0.2s, box-shadow 0.2s; 
            display: flex; 
            flex-direction: column;
            cursor: pointer;
        }
        .product-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); 
        }
        .product-image-container { 
            height: 100px; 
            background-color: #f9fafb; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            position: relative; 
        }
        .product-image-container img { 
            height: 100%; 
            width: 100%; 
            object-fit: cover; 
        }
        .product-image-container svg { 
            width: 70px; 
            height: 70px; 
            color: #cbd5e0;
        }
        .stock-badge { 
            position: absolute; 
            top: 10px; 
            right: 10px; 
            font-size: 0.75rem; 
            font-weight: 600; 
            padding: 0.25rem 0.6rem; 
            border-radius: 9999px; 
            border: 1px solid rgba(0,0,0,0.05);
            text-transform: capitalize;
        }
        .in-stock { background-color: #dcfce7; color: #166534; } 
        .low-stock { background-color: #fef3c7; color: #b45309; } 
        .out-of-stock { background-color: #fee2e2; color: #b91c1c; }
        .product-info { 
            padding: 0.75rem; 
            flex-grow: 1; 
            display: flex; 
            flex-direction: column; 
        }
        .product-name { 
            font-weight: 600; 
            margin-bottom: 0.25rem; 
            font-size: 1rem; 
        } 
        .product-price { 
            font-weight: 700; 
            color: var(--primary-green); 
            font-size: 1.1rem; 
            margin-top: auto; 
        }

        .order-summary { 
            background-color: white; 
            border-radius: 0.75rem; 
            border: 1px solid var(--border-gray);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        @media (min-width: 1024px) { .order-summary-wrapper { position: sticky; top: 90px; } }
        
        .btn {
            padding: 0.65rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-primary { 
            background-color: var(--primary-green); 
            color: white; 
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .btn-primary:hover { 
            background-color: #018d43; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .btn-primary:disabled { 
            background-color: #a3e6be; 
            cursor: not-allowed; 
            box-shadow: none; 
        }
        .btn-secondary {
            background-color: #edf2f7;
            color: #4a5568;
        }
        .btn-secondary:hover {
             background-color: #e2e8f0;
        }
        .category-btn { 
            white-space: nowrap; 
            padding: 0.5rem 1rem; 
            border-radius: 0.5rem; 
            background-color: #fff; 
            font-size: 0.875rem; 
            font-weight: 500; 
            cursor: pointer; 
            transition: all 0.2s; 
            border: 1px solid var(--border-gray);
            color: #4a5568;
        }
        .category-btn:hover { background-color: #f7fafc; } 
        .category-btn.active { 
            background-color: var(--primary-green); 
            color: white; 
            border-color: var(--primary-green); 
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .quantity-selector button:hover { background-color: #edf2f7; }
        .remove-item-btn:hover { color: #e53e3e; }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
            pointer-events: none;
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 28rem;
            transform: scale(0.95);
            transition: transform 0.2s ease-in-out;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-overlay.active .modal-content {
            transform: scale(1);
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #receipt-modal, #receipt-modal * {
                visibility: visible;
            }
            #receipt-modal {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
             .no-print, .no-print * {
                display: none !important;
                visibility: hidden !important;
            }
        }
        
        /* Custom Calendar Dropdown for Purchase History */
        .calendar-dropdown-history {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border: 2px solid #01A74F;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            width: 320px;
            padding: 1rem;
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
            transition: all 0.3s ease;
        }
        .dark .calendar-dropdown-history {
            background: #1f2937;
            border-color: #01A74F;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        .calendar-dropdown-history.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
        }
        .calendar-header-history {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .dark .calendar-header-history {
            border-bottom-color: #374151;
        }
        .calendar-grid-history {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.25rem;
        }
        .calendar-day-name-history {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            padding: 0.5rem 0;
        }
        .dark .calendar-day-name-history {
            color: #9ca3af;
        }
        .calendar-day-history {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            color: #374151;
        }
        .dark .calendar-day-history {
            color: #d1d5db;
        }
        .calendar-day-history:hover {
            background-color: #e0f2e9;
            color: #01A74F;
            transform: scale(1.05);
        }
        .dark .calendar-day-history:hover {
            background-color: #065f46;
            color: white;
        }
        .calendar-day-history.today {
            border: 2px solid #01A74F;
            font-weight: 600;
        }
        .calendar-day-history.selected {
            background-color: #01A74F;
            color: white;
            font-weight: 600;
        }
        .calendar-day-history.other-month {
            color: #d1d5db;
        }
        .dark .calendar-day-history.other-month {
            color: #4b5563;
        }
        .nav-btn-history {
            background: #f3f4f6;
            border: none;
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            color: #374151;
        }
        .dark .nav-btn-history {
            background: #374151;
            color: #d1d5db;
        }
        .nav-btn-history:hover {
            background: #01A74F;
            color: white;
            transform: scale(1.1);
        }
        .dark .nav-btn-history:hover {
            background: #01A74F;
            color: white;
        }
        #history-month-select,
        #history-year-select {
            color: #374151;
        }
        .dark #history-month-select,
        .dark #history-year-select {
            background: #374151;
            color: #e5e7eb;
            border-color: #4b5563;
        }
        #history-today-btn {
            background: #dcfce7;
            color: #166534;
        }
        .dark #history-today-btn {
            background: #065f46;
            color: #d1fae5;
        }
        #history-today-btn:hover {
            background: #bbf7d0;
        }
        .dark #history-today-btn:hover {
            background: #047857;
        }
        #history-clear-calendar-btn {
            background: #f3f4f6;
            color: #374151;
        }
        .dark #history-clear-calendar-btn {
            background: #374151;
            color: #d1d5db;
        }
        #history-clear-calendar-btn:hover {
            background: #e5e7eb;
        }
        .dark #history-clear-calendar-btn:hover {
            background: #4b5563;
        }
        /* Calendar footer border */
        .calendar-dropdown-history .border-t {
            border-color: #e5e7eb;
        }
        .dark .calendar-dropdown-history .border-t {
            border-color: #374151;
        }
        #history-date-display {
            transition: all 0.3s ease;
        }
        .dark #history-date-display {
            background: #374151;
            border-color: #4b5563;
            color: #e5e7eb;
        }
        .dark #history-date-display:hover {
            border-color: #01A74F;
        }
        .dark #history-date-display::placeholder {
            color: #9ca3af;
        }
        .dark .fa-calendar-alt {
            color: #9ca3af !important;
        }
        
        /* Custom scrollbar for modal */
        #purchase-history-modal .modal-content::-webkit-scrollbar {
            width: 8px;
        }
        #purchase-history-modal .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        #purchase-history-modal .modal-content::-webkit-scrollbar-thumb {
            background: #01A74F;
            border-radius: 4px;
        }
        #purchase-history-modal .modal-content::-webkit-scrollbar-thumb:hover {
            background: #018d43;
        }
        .dark #purchase-history-modal .modal-content::-webkit-scrollbar-track {
            background: #1f2937;
        }
        .dark #purchase-history-modal .modal-content::-webkit-scrollbar-thumb {
            background: #01A74F;
        }
        
        /* Hide scrollbar for category filter container */
        #category-filter-container {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
            scroll-behavior: smooth; /* Smooth scrolling */
        }
        
        #category-filter-container::-webkit-scrollbar {
            display: none; /* WebKit browsers (Chrome, Safari, Edge) */
        }
        
        /* Enhanced category container styling */
        #category-filter-container {
            position: relative;
        }
        
        /* Add subtle gradient indicators for scrollable content */
        #category-filter-container::before,
        #category-filter-container::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 20px;
            pointer-events: none;
            z-index: 1;
            transition: opacity 0.3s ease;
        }
        
        #category-filter-container::before {
            left: 0;
            background: linear-gradient(to right, rgba(247, 250, 252, 0.8), transparent);
        }
        
        #category-filter-container::after {
            right: 0;
            background: linear-gradient(to left, rgba(247, 250, 252, 0.8), transparent);
        }
        
        /* Dark mode gradient indicators */
        .dark #category-filter-container::before {
            background: linear-gradient(to right, rgba(31, 41, 55, 0.8), transparent);
        }
        
        .dark #category-filter-container::after {
            background: linear-gradient(to left, rgba(31, 41, 55, 0.8), transparent);
        }
        
        /* Improve touch scrolling on mobile */
        #category-filter-container {
            -webkit-overflow-scrolling: touch;
        }
    </style>
    <script>
        // Add brand color to Tailwind config
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-green': '#01A74F',
                    }
                }
            }
        }
    </script>
    <?php echo $posDarkMode['styles']; ?>
</head>
<body class="bg-gray-100">
    <?php include 'pos_header.php'; ?>

    <main class="p-2 sm:p-4 max-w-full mx-auto h-[calc(100vh-80px)]">
        <div class="grid grid-cols-1 lg:grid-cols-4 xl:grid-cols-7 gap-4 h-full">
            <div class="lg:col-span-3 xl:col-span-5">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Point of Sale</h1>
                    <p class="text-gray-500 mt-1">Select products to add them to the order.</p>
                </div>
                
                <div class="relative mb-6">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
                    <input type="text" id="product-search" placeholder="Search by product name..." class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green transition-shadow">
                </div>

                <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2">
                    <div id="stock-status-filter" class="flex items-center gap-2 flex-shrink-0">
                        <button class="category-btn active" data-stock-status="available">Available Products</button>
                        <button class="category-btn" data-stock-status="outOfStock">Out of Stock</button>
                    </div>
                    <div class="border-l border-gray-300 h-6 mx-2 flex-shrink-0"></div>
                    <div id="category-filter-container" class="flex items-center gap-2 overflow-x-auto flex-1 min-w-0">
                        </div>
                </div>
                <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-4" id="product-grid"></div>
            </div>
            
            <div class="lg:col-span-1 xl:col-span-2 mt-4 lg:mt-0">
                <div class="order-summary-wrapper max-h-[calc(100vh-120px)] lg:max-h-full">
                    <div class="order-summary max-h-[80vh] lg:max-h-full flex flex-col">
                        <div class="p-4 border-b border-gray-200 flex-shrink-0">
                           <h2 class="text-lg font-semibold">Order Summary</h2>
                        </div>
                        <div id="order-items" class="p-2 flex-1 overflow-y-auto min-h-[200px] max-h-[50vh]">
                            <div class="text-center text-gray-400 py-8 px-4">
                                <i data-lucide="shopping-cart" class="mx-auto h-12 w-12"></i>
                                <p class="mt-4 text-sm">Your cart is empty</p>
                            </div>
                        </div>
                        
                        <div class="p-5 bg-gray-50 rounded-b-lg">
                            <div class="space-y-3">
                                <div class="flex justify-between items-center text-gray-600">
                                    <span>Subtotal</span>
                                    <span id="subtotal" class="font-medium">₱0.00</span>
                                </div>
                                 <div class="flex justify-between items-center text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <label for="discount-selector" class="font-medium">Discount</label>
                                    </div>
                                    <select id="discount-selector" class="text-sm bg-gray-100 border-gray-300 rounded-md p-1 focus:ring-brand-green focus:border-brand-green">
                                        <option value="0">No Discount</option>
                                        <option value="0.20">Senior/PWD (20%)</option>
                                    </select>
                                </div>
                                <div class="flex justify-between items-center text-gray-600">
                                    <span>Discount Amount</span>
                                    <span id="discount-amount" class="font-medium text-red-500">-₱0.00</span>
                                </div>
                                <div class="flex justify-between items-center text-xl font-bold text-gray-800 pt-3 border-t border-gray-200">
                                    <span>Total</span>
                                    <span id="total" class="text-brand-green">₱0.00</span>
                                </div>
                            </div>
                            
                            <button id="checkout-btn" class="btn btn-primary w-full mt-6" disabled>
                                <i class="fas fa-credit-card w-5 h-5"></i>
                                <span>Proceed to Payment</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="discount-payment-modal" class="modal-overlay">
        <div class="modal-content !max-w-lg">
             <div class="p-4 sm:p-6">
                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-4 text-center">Complete Purchase</h3>
                
                <form id="discount-payment-form">
                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <h4 class="font-semibold mb-3 text-gray-700">Customer Information</h4>
                        <div class="space-y-3">
                            <div>
                                <label for="discount-customer-name" class="text-xs sm:text-sm font-medium text-gray-600">Customer Name</label>
                                <input type="text" id="discount-customer-name" placeholder="Mr. Example" class="mt-1 w-full p-2 border rounded-md bg-white text-sm" required>
                            </div>
                            <div>
                                <label for="discount-id-number" class="text-xs sm:text-sm font-medium text-gray-600">ID Number</label>
                                <input type="text" id="discount-id-number" placeholder="12345" class="mt-1 w-full p-2 border rounded-md bg-white text-sm" required>
                            </div>
                            <div>
                                <label class="text-xs sm:text-sm font-medium text-gray-600">Discount Type</label>
                                <div class="flex items-center gap-4 mt-1 sm:mt-2 text-sm">
                                    <label class="flex items-center gap-2"><input type="radio" name="discount-type" value="senior" class="form-radio text-brand-green" required> Senior Citizen</label>
                                    <label class="flex items-center gap-2"><input type="radio" name="discount-type" value="pwd" class="form-radio text-brand-green"> PWD</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h4 class="font-semibold mb-3 text-gray-700">Select Payment Method</h4>
                        <div id="discount-payment-method-container" class="space-y-2">
                            <label class="payment-method-option flex items-center p-3 border-2 border-gray-200 rounded-lg bg-gray-50 cursor-pointer">
                                <input type="radio" name="discount-payment-method" value="cash" class="form-radio text-brand-green" checked>
                                <i class="fas fa-wallet w-5 h-5 sm:w-6 sm:h-6 mx-3 text-gray-600"></i>
                                <span class="font-semibold text-sm sm:text-base">Cash</span>
                            </label>
                             <label class="payment-method-option flex items-center p-3 border-2 border-gray-200 rounded-lg bg-gray-50 cursor-pointer">
                                <input type="radio" name="discount-payment-method" value="gcash" class="form-radio text-brand-green">
                                <i class="fas fa-mobile-alt w-5 h-5 sm:w-6 sm:h-6 mx-3 text-gray-600"></i>
                                <span class="font-semibold text-sm sm:text-base">GCash</span>
                            </label>
                        </div>
                        
                        <!-- Cash Payment Details -->
                        <div id="discount-cash-details" class="mt-4 bg-green-50 p-4 rounded-lg border border-green-200">
                            <h5 class="font-semibold mb-3 text-gray-700">Cash Payment</h5>
                            <div class="space-y-3">
                                <div>
                                    <label for="discount-cash-amount" class="text-sm font-medium text-gray-600">Amount Received</label>
                                    <div class="relative mt-1">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                                        <input type="number" id="discount-cash-amount" placeholder="0.00" step="0.01" min="0" class="w-full pl-8 pr-4 py-2 border rounded-md bg-white text-sm focus:ring-brand-green focus:border-brand-green">
                                    </div>
                                </div>
                                <div class="bg-white p-3 rounded-md border">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-600">Change:</span>
                                        <span id="discount-change-amount" class="text-lg font-bold text-brand-green">₱0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GCash Payment Details -->
                        <div id="discount-gcash-details" class="mt-4 bg-blue-50 p-4 rounded-lg border border-blue-200 hidden">
                            <h5 class="font-semibold mb-2 text-gray-700">GCash Payment</h5>
                            <p class="text-sm text-gray-600">Please confirm payment has been received via GCash.</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg mb-4 text-sm">
                        <h4 class="font-semibold mb-3 text-gray-700">Order Summary</h4>
                        <div class="space-y-1">
                            <div class="flex justify-between"><span class="text-gray-600">Items:</span><span id="discount-modal-items-count" class="font-medium">0</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Subtotal:</span><span id="discount-modal-subtotal" class="font-medium">₱0.00</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Discount:</span><span id="discount-modal-discount" class="font-medium text-red-500">-₱0.00</span></div>
                            <div class="flex justify-between font-bold text-base sm:text-lg"><span class="text-gray-800">Total:</span><span id="discount-modal-total-amount" class="text-brand-green">₱0.00</span></div>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-3">
                        <button type="button" id="cancel-discount-payment-btn" class="btn btn-secondary w-full">Cancel</button>
                        <button type="submit" class="btn btn-primary w-full">Complete Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div id="regular-payment-modal" class="modal-overlay">
        <div class="modal-content !max-w-lg">
             <div class="p-4 sm:p-6">
                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-4 text-center">Complete Purchase</h3>
                
                <form id="regular-payment-form">
                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <h4 class="font-semibold mb-3 text-gray-700">Customer Information</h4>
                        <div>
                            <label for="regular-customer-name" class="text-xs sm:text-sm font-medium text-gray-600">Customer Name (Optional)</label>
                            <input type="text" id="regular-customer-name" placeholder="Walk-in Customer" class="mt-1 w-full p-2 border rounded-md bg-white text-sm">
                        </div>
                    </div>

                    <div class="mb-4">
                        <h4 class="font-semibold mb-3 text-gray-700">Select Payment Method</h4>
                        <div id="regular-payment-method-container" class="space-y-2">
                            <label class="payment-method-option flex items-center p-3 border-2 border-gray-200 rounded-lg bg-gray-50 cursor-pointer">
                                <input type="radio" name="regular-payment-method" value="cash" class="form-radio text-brand-green" checked>
                                <i class="fas fa-wallet w-5 h-5 sm:w-6 sm:h-6 mx-3 text-gray-600"></i>
                                <span class="font-semibold text-sm sm:text-base">Cash</span>
                            </label>
                             <label class="payment-method-option flex items-center p-3 border-2 border-gray-200 rounded-lg bg-gray-50 cursor-pointer">
                                <input type="radio" name="regular-payment-method" value="gcash" class="form-radio text-brand-green">
                                <i class="fas fa-mobile-alt w-5 h-5 sm:w-6 sm:h-6 mx-3 text-gray-600"></i>
                                <span class="font-semibold text-sm sm:text-base">GCash</span>
                            </label>
                        </div>
                        
                        <!-- Cash Payment Details -->
                        <div id="regular-cash-details" class="mt-4 bg-green-50 p-4 rounded-lg border border-green-200">
                            <h5 class="font-semibold mb-3 text-gray-700">Cash Payment</h5>
                            <div class="space-y-3">
                                <div>
                                    <label for="regular-cash-amount" class="text-sm font-medium text-gray-600">Amount Received</label>
                                    <div class="relative mt-1">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                                        <input type="number" id="regular-cash-amount" placeholder="0.00" step="0.01" min="0" class="w-full pl-8 pr-4 py-2 border rounded-md bg-white text-sm focus:ring-brand-green focus:border-brand-green">
                                    </div>
                                </div>
                                <div class="bg-white p-3 rounded-md border">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-600">Change:</span>
                                        <span id="regular-change-amount" class="text-lg font-bold text-brand-green">₱0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GCash Payment Details -->
                        <div id="regular-gcash-details" class="mt-4 bg-blue-50 p-4 rounded-lg border border-blue-200 hidden">
                            <h5 class="font-semibold mb-2 text-gray-700">GCash Payment</h5>
                            <p class="text-sm text-gray-600">Please confirm payment has been received via GCash.</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg mb-4 text-sm">
                        <h4 class="font-semibold mb-3 text-gray-700">Order Summary</h4>
                        <div class="space-y-1">
                            <div class="flex justify-between"><span class="text-gray-600">Items:</span><span id="regular-modal-items-count" class="font-medium">0</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Subtotal:</span><span id="regular-modal-subtotal" class="font-medium">₱0.00</span></div>
                            <div class="flex justify-between font-bold text-base sm:text-lg"><span class="text-gray-800">Total:</span><span id="regular-modal-total-amount" class="text-brand-green">₱0.00</span></div>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-3">
                        <button type="button" id="cancel-regular-payment-btn" class="btn btn-secondary w-full">Cancel</button>
                        <button type="submit" class="btn btn-primary w-full">Complete Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div id="receipt-modal" class="modal-overlay">
        <div class="modal-content !max-w-sm">
             <div class="p-6">
                <div class="text-center">
                    <img src="../mjpharmacy.logo.jpg" alt="MJ Pharmacy Logo" class="w-16 h-16 mx-auto mb-2 rounded-full">
                    <h2 class="text-xl font-bold mt-2">MJ PHARMACY</h2>
                </div>
                
                <div class="my-6 border-t border-dashed"></div>

                <div class="text-sm space-y-2 text-gray-600">
                    <div class="flex justify-between"><span class="font-medium">Date:</span><span id="receipt-date"></span></div>
                    <div class="flex justify-between"><span class="font-medium">Receipt #:</span><span id="receipt-no"></span></div>
                    <div class="flex justify-between"><span class="font-medium">Customer:</span><span id="receipt-customer"></span></div>
                    <div class="flex justify-between"><span class="font-medium">ID Number:</span><span id="receipt-id"></span></div>
                    <div class="flex justify-between"><span class="font-medium">Payment Method:</span><span id="receipt-payment-method"></span></div>
                </div>

                <div class="my-6 border-t border-dashed"></div>
                
                <div id="receipt-items" class="text-sm">
                    <div class="flex justify-between font-bold mb-2">
                        <span>Item</span>
                        <div class="grid grid-cols-3 gap-4 w-1/2 text-right">
                            <span>Qty</span>
                            <span>Price</span>
                            <span>Total</span>
                        </div>
                    </div>
                </div>

                 <div class="my-6 border-t border-dashed"></div>

                 <div class="text-sm space-y-1">
                    <div class="flex justify-between"><span class="text-gray-600">Subtotal:</span><span id="receipt-subtotal" class="font-medium">₱0.00</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Discount:</span><span id="receipt-discount" class="font-medium text-red-500">-₱0.00</span></div>
                    <div class="flex justify-between font-bold text-lg"><span class="text-gray-800">Total:</span><span id="receipt-total" class="text-brand-green">₱0.00</span></div>
                    <div id="receipt-cash-details" class="mt-3 pt-2 border-t border-dashed" style="display: none;">
                        <div class="flex justify-between"><span class="text-gray-600">Cash Received:</span><span id="receipt-cash-amount" class="font-medium">₱0.00</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Change:</span><span id="receipt-change-amount" class="font-medium text-brand-green">₱0.00</span></div>
                    </div>
                 </div>
                 
                 <div class="text-center mt-8 text-xs text-gray-500">
                    <p>Thank you for your purchase!</p>
                    <p>Please keep this receipt for your records</p>
                 </div>

                 <div class="mt-8 flex gap-3 no-print">
                    <button id="print-receipt-btn" class="btn btn-secondary w-full"><i class="fas fa-print w-4 h-4"></i> Print Receipt</button>
                    <button id="new-transaction-btn" class="btn btn-primary w-full">New Transaction</button>
                 </div>
             </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== POS JavaScript Starting ===');
            
            // Initialize Lucide icons on page load
            lucide.createIcons();
            
            let allProducts = [];
            let allCategories = [];
            let orderItems = [];

            // DOM Elements
            const productSearchInput = document.getElementById('product-search');
            const productGrid = document.getElementById('product-grid');
            const categoryFilterContainer = document.getElementById('category-filter-container');
            const stockStatusFilter = document.getElementById('stock-status-filter');
            const orderItemsContainer = document.getElementById('order-items');
            
            console.log('DOM Elements loaded:');
            console.log('- productGrid:', productGrid);
            console.log('- productSearchInput:', productSearchInput);
            console.log('- categoryFilterContainer:', categoryFilterContainer);
            const subtotalElement = document.getElementById('subtotal');
            const discountSelector = document.getElementById('discount-selector');
            const discountAmountElement = document.getElementById('discount-amount');
            const totalElement = document.getElementById('total');
            const checkoutBtn = document.getElementById('checkout-btn');
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            const dateTimeEl = document.getElementById('date-time');

            // Modal DOM Elements
            const discountPaymentModal = document.getElementById('discount-payment-modal');
            const regularPaymentModal = document.getElementById('regular-payment-modal');
            const receiptModal = document.getElementById('receipt-modal');
            const discountPaymentForm = document.getElementById('discount-payment-form');
            const regularPaymentForm = document.getElementById('regular-payment-form');
            const cancelDiscountPaymentBtn = document.getElementById('cancel-discount-payment-btn');
            const cancelRegularPaymentBtn = document.getElementById('cancel-regular-payment-btn');
            const newTransactionBtn = document.getElementById('new-transaction-btn');
            const printReceiptBtn = document.getElementById('print-receipt-btn');
            const discountModalItemsCount = document.getElementById('discount-modal-items-count');
            const discountModalSubtotal = document.getElementById('discount-modal-subtotal');
            const discountModalDiscount = document.getElementById('discount-modal-discount');
            const discountModalTotalAmount = document.getElementById('discount-modal-total-amount');
            const discountPaymentMethodContainer = document.getElementById('discount-payment-method-container');
            const discountCustomerNameInput = document.getElementById('discount-customer-name');
            const discountIdNumberInput = document.getElementById('discount-id-number');
            const regularModalItemsCount = document.getElementById('regular-modal-items-count');
            const regularModalSubtotal = document.getElementById('regular-modal-subtotal');
            const regularModalTotalAmount = document.getElementById('regular-modal-total-amount');
            const regularPaymentMethodContainer = document.getElementById('regular-payment-method-container');
            const regularCustomerNameInput = document.getElementById('regular-customer-name');
            const receiptDate = document.getElementById('receipt-date');
            const receiptNo = document.getElementById('receipt-no');
            const receiptCustomer = document.getElementById('receipt-customer');
            const receiptId = document.getElementById('receipt-id');
            const receiptPaymentMethod = document.getElementById('receipt-payment-method');
            const receiptItems = document.getElementById('receipt-items');
            const receiptSubtotal = document.getElementById('receipt-subtotal');
            const receiptDiscount = document.getElementById('receipt-discount');
            const receiptTotal = document.getElementById('receipt-total');
            
            // Cash payment elements
            const discountCashAmount = document.getElementById('discount-cash-amount');
            const discountChangeAmount = document.getElementById('discount-change-amount');
            const discountCashDetails = document.getElementById('discount-cash-details');
            const discountGcashDetails = document.getElementById('discount-gcash-details');
            const regularCashAmount = document.getElementById('regular-cash-amount');
            const regularChangeAmount = document.getElementById('regular-change-amount');
            const regularCashDetails = document.getElementById('regular-cash-details');
            const regularGcashDetails = document.getElementById('regular-gcash-details');

            if(userMenuButton) {
                userMenuButton.addEventListener('click', () => userMenu.classList.toggle('hidden'));
            }
            window.addEventListener('click', (e) => {
                if (userMenuButton && !userMenuButton.contains(e.target) && userMenu && !userMenu.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });
            function updateDateTime() {
                const now = new Date();
                const options = { weekday: 'short', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                if (dateTimeEl) {
                   dateTimeEl.textContent = now.toLocaleDateString('en-US', options);
                }
            }
            updateDateTime();
            setInterval(updateDateTime, 60000);
            
            const placeholderSVG = `<i class="fas fa-pills text-gray-400" style="font-size: 3rem;"></i>`;

            async function fetchProducts(status = 'available') {
                console.log('fetchProducts called with status:', status);
                try {
                    const response = await fetch(`../api/get_products.php?status=${status}`);
                    console.log('Response received:', response.status);
                    allProducts = await response.json();
                    console.log('Products loaded:', allProducts.length, allProducts);
                    updateProductView();
                } catch (error) {
                    console.error('Error fetching products:', error);
                    productGrid.innerHTML = `<p class="col-span-full text-center text-red-500">Could not load products.</p>`;
                }
            }

            async function fetchCategories() {
                 try {
                    const response = await fetch('../api/get_categories.php');
                    allCategories = await response.json();
                    renderCategoryFilters(allCategories);
                } catch (error) {
                    console.error("Could not load categories", error);
                }
            }

            function updateProductView() {
                const searchTerm = productSearchInput.value.toLowerCase();
                const activeCategoryBtn = categoryFilterContainer.querySelector('.active');
                const activeCategoryName = activeCategoryBtn ? activeCategoryBtn.dataset.name : 'all';

                let filteredProducts = allProducts;
                
                if (activeCategoryName !== 'all') {
                    filteredProducts = filteredProducts.filter(p => p.category_name === activeCategoryName);
                }

                if (searchTerm) {
                    filteredProducts = filteredProducts.filter(p => p.name.toLowerCase().includes(searchTerm));
                }
                
                renderProducts(filteredProducts);
            }
            
            function getStockStatus(stock) {
                stock = parseInt(stock, 10);
                if (stock <= 0) return { text: 'Out of Stock', class: 'out-of-stock' };
                if (stock > 0 && stock <= 5) return { text: 'Low Stock', class: 'low-stock' };
                return { text: 'In Stock', class: 'in-stock' };
            }

            function renderProducts(productsToRender) {
                console.log('renderProducts called with', productsToRender.length, 'products');
                console.log('productGrid element:', productGrid);
                if (productsToRender.length === 0) {
                    productGrid.innerHTML = `<p class="col-span-full text-center text-gray-500 py-10">No products found for the current filter.</p>`;
                    return;
                }
                productGrid.innerHTML = productsToRender.map(p => {
                    const stockStatus = getStockStatus(p.stock);
                    const imageContent = p.image_path ? `<img src="../${p.image_path}" alt="${p.name}" class="product-image">` : placeholderSVG;
                    return `
                        <div class="product-card ${p.stock <= 0 ? 'opacity-60 grayscale cursor-not-allowed' : ''}" data-name="${p.product_identifier}">
                             <div class="product-image-container">
                                ${imageContent}
                                <div class="stock-badge ${stockStatus.class}">${stockStatus.text}</div>
                            </div>
                            <div class="product-info text-center">
                                <h4 class="product-name">${p.name}</h4>
                                <p class="text-sm text-gray-500 mb-2">${p.category_name}</p>
                                <p class="text-xs text-gray-400 mb-2">Stock: ${p.stock}</p>
                                <p class="product-price">₱${Number(p.price).toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                }).join('');
                console.log('Products rendered to DOM. Grid HTML length:', productGrid.innerHTML.length);
                console.log('First 200 chars:', productGrid.innerHTML.substring(0, 200));
            }

            function renderCategoryFilters(categories) {
                categoryFilterContainer.innerHTML = '<button class="category-btn active" data-name="all">All</button>' +
                categories.map(cat => `<button class="category-btn" data-name="${cat.name}">${cat.name}</button>`).join('');
            }
            
            function addToOrder(product) {
                if(product.stock <= 0) return;
                const existingItem = orderItems.find(item => item.name === product.name);
                if (existingItem) {
                    const newQuantity = existingItem.quantity + 1;
                    if (newQuantity > product.stock) { 
                        alert(`Sorry, only ${product.stock} items of ${product.name} available.`); 
                        return;
                    }
                    existingItem.quantity = newQuantity;
                } else {
                    orderItems.push({ ...product, quantity: 1 });
                }
                updateOrderSummary();
            }
            
            function updateOrderSummary() {
                if (orderItems.length === 0) {
                    orderItemsContainer.innerHTML = `<div class="text-center text-gray-400 py-16 px-4"><i class="fas fa-shopping-cart mx-auto h-12 w-12"></i><p class="mt-4 text-sm">Your cart is empty</p></div>`;
                } else {
                    orderItemsContainer.innerHTML = orderItems.map((item, index) =>
                        `<div class="flex items-center gap-4 p-3"><img src="${item.image_path ? `../${item.image_path}` : ''}" onerror="this.style.display='none'" class="w-12 h-12 rounded-md object-cover bg-gray-100"><div class="flex-grow"><p class="font-semibold text-sm">${item.name}</p><p class="text-xs text-gray-500">₱${Number(item.price).toFixed(2)}</p></div><div class="flex items-center gap-2 text-sm"><div class="quantity-selector flex items-center border border-gray-200 rounded-md"><button class="minus p-1.5 transition hover:bg-gray-100" data-index="${index}"><i data-lucide="minus" class="w-4 h-4 text-gray-500 pointer-events-none"></i></button><input type="text" class="w-8 text-center font-medium bg-transparent" value="${item.quantity}" readonly><button class="plus p-1.5 transition hover:bg-gray-100" data-index="${index}"><i data-lucide="plus" class="w-4 h-4 text-gray-500 pointer-events-none"></i></button></div></div><button class="remove-item-btn p-1.5 rounded-md text-gray-400 hover:text-red-500 hover:bg-red-50 transition" data-index="${index}"><i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i></button></div>`
                    ).join('');
                    
                    // Re-initialize Lucide icons for dynamically added content
                    lucide.createIcons();
                }
                
                const subtotal = orderItems.reduce((total, item) => total + (item.price * item.quantity), 0);
                const discountRate = parseFloat(discountSelector.value);
                const discountAmount = subtotal * discountRate;
                const total = subtotal - discountAmount;

                subtotalElement.textContent = `₱${subtotal.toFixed(2)}`;
                discountAmountElement.textContent = `-₱${discountAmount.toFixed(2)}`;
                totalElement.textContent = `₱${total.toFixed(2)}`;
                checkoutBtn.disabled = orderItems.length === 0;
            }

            productSearchInput.addEventListener('input', updateProductView);

            productGrid.addEventListener('click', (e) => {
                const card = e.target.closest('.product-card');
                if (card) {
                    const productName = card.dataset.name;
                    const product = allProducts.find(p => p.name === productName);
                    if (product) {
                        addToOrder(product);
                    }
                }
            });

            orderItemsContainer.addEventListener('click', (e) => {
                const button = e.target.closest('button');
                if (!button) return;

                const index = parseInt(button.dataset.index);
                const item = orderItems[index];

                if (button.classList.contains('remove-item-btn')) {
                    orderItems.splice(index, 1);
                } else if (button.classList.contains('minus')) {
                    if (item.quantity > 1) item.quantity--;
                    else orderItems.splice(index, 1);
                } else if (button.classList.contains('plus')) {
                    const product = allProducts.find(p => p.name == item.name);
                    if (product && item.quantity < product.stock) {
                        item.quantity++;
                    } else {
                        alert(`Maximum stock for ${item.name} reached.`);
                    }
                }
                updateOrderSummary();
            });


            stockStatusFilter.addEventListener('click', (e) => {
                if (e.target.matches('.category-btn')) {
                    stockStatusFilter.querySelector('.active').classList.remove('active');
                    e.target.classList.add('active');
                    const status = e.target.dataset.stockStatus;
                    fetchProducts(status);
                }
            });


            categoryFilterContainer.addEventListener('click', (e) => {
                if (e.target.matches('.category-btn')) {
                    categoryFilterContainer.querySelector('.active').classList.remove('active');
                    e.target.classList.add('active');
                    updateProductView();
                }
            });

            discountSelector.addEventListener('change', updateOrderSummary);

            checkoutBtn.addEventListener('click', () => {
                const subtotal = orderItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                const discountRate = parseFloat(discountSelector.value);
                const discountAmount = subtotal * discountRate;
                const total = subtotal - discountAmount;
                const totalItems = orderItems.reduce((sum, item) => sum + item.quantity, 0);
                
                if (discountRate > 0) {
                    discountModalItemsCount.textContent = totalItems;
                    discountModalSubtotal.textContent = `₱${subtotal.toFixed(2)}`;
                    discountModalDiscount.textContent = `-₱${discountAmount.toFixed(2)}`;
                    discountModalTotalAmount.textContent = `₱${total.toFixed(2)}`;
                    discountPaymentModal.classList.add('active');
                    updatePaymentMethodStyles(discountPaymentMethodContainer);

                } else {
                    regularModalItemsCount.textContent = totalItems;
                    regularModalSubtotal.textContent = `₱${subtotal.toFixed(2)}`;
                    regularModalTotalAmount.textContent = `₱${total.toFixed(2)}`;
                    regularPaymentModal.classList.add('active');
                    updatePaymentMethodStyles(regularPaymentMethodContainer);
                }
            });

            cancelDiscountPaymentBtn.addEventListener('click', () => {
                discountPaymentModal.classList.remove('active');
                discountPaymentForm.reset();
            });
            
            cancelRegularPaymentBtn.addEventListener('click', () => {
                regularPaymentModal.classList.remove('active');
                regularPaymentForm.reset();
            });
            
            // UNIFIED FUNCTION TO PROCESS SALE AND LOG CUSTOMER DATA
            async function completePurchase(customerData) {
                const discountRate = parseFloat(discountSelector.value);
                const subtotal = orderItems.reduce((total, item) => total + (item.price * item.quantity), 0);
                const discountAmount = subtotal * discountRate;
                const totalAmount = subtotal - discountAmount;
                
                // Get payment method and cash details
                const paymentMethodRadio = (discountRate > 0) ? 'discount-payment-method' : 'regular-payment-method';
                const paymentMethod = document.querySelector(`input[name="${paymentMethodRadio}"]:checked`).value;
                const cashAmount = (discountRate > 0) ? 
                    parseFloat(discountCashAmount.value) || 0 : 
                    parseFloat(regularCashAmount.value) || 0;
                const changeAmount = paymentMethod === 'cash' ? calculateChange(cashAmount, totalAmount) : 0;
                
                const saleData = {
                    ...customerData,
                    items: orderItems,
                    total_amount: totalAmount,
                    payment_method: paymentMethod,
                    cash_amount: paymentMethod === 'cash' ? cashAmount : null,
                    change_amount: paymentMethod === 'cash' ? changeAmount : null,
                    subtotal: subtotal,
                    discount_amount: discountAmount
                };

                // Process the complete sale (inventory + customer history) in one API call
                try {
                    const response = await fetch('../api/customer_api.php?action=complete_sale&v=' + Date.now(), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(saleData)
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        alert(`Error processing sale: ${result.message || 'Server error'}`);
                        return false;
                    }
                    return true;
                } catch (error) {
                    console.error('Sale processing error:', error);
                    alert('An error occurred while processing the sale.');
                    return false;
                }
            }
            
            discountPaymentForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = e.submitter;
                btn.disabled = true;
                btn.textContent = 'Processing...';

                const customerData = {
                    customer_name: discountCustomerNameInput.value,
                    customer_id: discountIdNumberInput.value,
                };

                if (await completePurchase(customerData)) {
                    showReceipt();
                    discountPaymentModal.classList.remove('active');
                }
                
                btn.disabled = false;
                btn.textContent = 'Complete Payment';
            });
            
            regularPaymentForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = e.submitter;
                btn.disabled = true;
                btn.textContent = 'Processing...';

                const customerData = {
                    customer_name: regularCustomerNameInput.value || 'Walk-in',
                    customer_id: '',
                };

                if (await completePurchase(customerData)) {
                    showReceipt();
                    regularPaymentModal.classList.remove('active');
                }

                btn.disabled = false;
                btn.textContent = 'Complete Payment';
            });
            
            function showReceipt() {
                const discountRate = parseFloat(discountSelector.value);
                const customerName = (discountRate > 0) ? discountCustomerNameInput.value : (regularCustomerNameInput.value || 'Walk-in');
                const idNumber = (discountRate > 0) ? discountIdNumberInput.value : '';
                const discountType = (discountRate > 0) ? document.querySelector('input[name="discount-type"]:checked')?.value : '';
                const paymentMethodRadio = (discountRate > 0) ? 'discount-payment-method' : 'regular-payment-method';
                const paymentMethod = document.querySelector(`input[name="${paymentMethodRadio}"]:checked`).value;
                
                const now = new Date();
                receiptDate.textContent = now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                receiptNo.textContent = `RX${Date.now().toString().slice(-6)}`;
                
                receiptCustomer.textContent = customerName;
                receiptId.textContent = `${idNumber || 'N/A'} ${discountType ? `(${discountType.charAt(0).toUpperCase() + discountType.slice(1)})` : ''}`;
                receiptPaymentMethod.textContent = paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1);

                receiptItems.innerHTML = `<div class="flex justify-between font-bold mb-2">
                        <span>Item</span>
                        <div class="grid grid-cols-3 gap-4 w-1/2 text-right">
                            <span>Qty</span>
                            <span>Price</span>
                            <span>Total</span>
                        </div>
                    </div>` + 
                    orderItems.map(item => `
                    <div class="flex justify-between items-center">
                        <span class="w-1/2 truncate">${item.name}</span>
                        <div class="grid grid-cols-3 gap-4 w-1/2 text-right">
                            <span>${item.quantity}</span>
                            <span>₱${Number(item.price).toFixed(2)}</span>
                            <span>₱${(item.quantity * item.price).toFixed(2)}</span>
                        </div>
                    </div>
                `).join('');

                const subtotal = orderItems.reduce((total, item) => total + (item.price * item.quantity), 0);
                const discountAmount = subtotal * discountRate;
                const total = subtotal - discountAmount;

                receiptSubtotal.textContent = `₱${subtotal.toFixed(2)}`;
                receiptDiscount.textContent = `-₱${discountAmount.toFixed(2)}`;
                receiptTotal.textContent = `₱${total.toFixed(2)}`;
                
                // Show cash details if payment method is cash
                const receiptCashDetails = document.getElementById('receipt-cash-details');
                const receiptCashAmount = document.getElementById('receipt-cash-amount');
                const receiptChangeAmount = document.getElementById('receipt-change-amount');
                
                if (paymentMethod === 'cash') {
                    const cashAmount = (discountRate > 0) ? 
                        parseFloat(discountCashAmount.value) || 0 : 
                        parseFloat(regularCashAmount.value) || 0;
                    const change = calculateChange(cashAmount, total);
                    
                    receiptCashAmount.textContent = `₱${cashAmount.toFixed(2)}`;
                    receiptChangeAmount.textContent = `₱${change.toFixed(2)}`;
                    receiptCashDetails.style.display = 'block';
                } else {
                    receiptCashDetails.style.display = 'none';
                }

                receiptModal.classList.add('active');
            }

            newTransactionBtn.addEventListener('click', () => {
                receiptModal.classList.remove('active');
                discountPaymentForm.reset();
                regularPaymentForm.reset();
                
                // Reset cash amount inputs and change displays
                if (discountCashAmount) {
                    discountCashAmount.value = '';
                    discountChangeAmount.textContent = '₱0.00';
                }
                if (regularCashAmount) {
                    regularCashAmount.value = '';
                    regularChangeAmount.textContent = '₱0.00';
                }
                
                orderItems = [];
                discountSelector.value = "0";
                updateOrderSummary();
                fetchProducts(); 
            });
            
            printReceiptBtn.addEventListener('click', () => {
                window.print();
            });
            
            // Change calculation functions
            function calculateChange(cashAmount, totalAmount) {
                const cash = parseFloat(cashAmount) || 0;
                const total = parseFloat(totalAmount) || 0;
                const change = cash - total;
                return change >= 0 ? change : 0;
            }
            
            function updateDiscountChange() {
                const totalAmount = parseFloat(discountModalTotalAmount.textContent.replace('₱', ''));
                const cashAmount = parseFloat(discountCashAmount.value) || 0;
                const change = calculateChange(cashAmount, totalAmount);
                discountChangeAmount.textContent = `₱${change.toFixed(2)}`;
                
                // Update change color based on whether sufficient cash is provided
                if (cashAmount >= totalAmount && cashAmount > 0) {
                    discountChangeAmount.classList.remove('text-red-500');
                    discountChangeAmount.classList.add('text-brand-green');
                } else if (cashAmount > 0 && cashAmount < totalAmount) {
                    discountChangeAmount.classList.remove('text-brand-green');
                    discountChangeAmount.classList.add('text-red-500');
                } else {
                    discountChangeAmount.classList.remove('text-red-500');
                    discountChangeAmount.classList.add('text-brand-green');
                }
            }
            
            function updateRegularChange() {
                const totalAmount = parseFloat(regularModalTotalAmount.textContent.replace('₱', ''));
                const cashAmount = parseFloat(regularCashAmount.value) || 0;
                const change = calculateChange(cashAmount, totalAmount);
                regularChangeAmount.textContent = `₱${change.toFixed(2)}`;
                
                // Update change color based on whether sufficient cash is provided
                if (cashAmount >= totalAmount && cashAmount > 0) {
                    regularChangeAmount.classList.remove('text-red-500');
                    regularChangeAmount.classList.add('text-brand-green');
                } else if (cashAmount > 0 && cashAmount < totalAmount) {
                    regularChangeAmount.classList.remove('text-brand-green');
                    regularChangeAmount.classList.add('text-red-500');
                } else {
                    regularChangeAmount.classList.remove('text-red-500');
                    regularChangeAmount.classList.add('text-brand-green');
                }
            }
            
            function togglePaymentDetails(container, isDiscount = false) {
                const cashRadio = container.querySelector('input[value="cash"]');
                const gcashRadio = container.querySelector('input[value="gcash"]');
                
                if (isDiscount) {
                    if (cashRadio && cashRadio.checked) {
                        discountCashDetails.classList.remove('hidden');
                        discountGcashDetails.classList.add('hidden');
                        updateDiscountChange();
                    } else if (gcashRadio && gcashRadio.checked) {
                        discountCashDetails.classList.add('hidden');
                        discountGcashDetails.classList.remove('hidden');
                    }
                } else {
                    if (cashRadio && cashRadio.checked) {
                        regularCashDetails.classList.remove('hidden');
                        regularGcashDetails.classList.add('hidden');
                        updateRegularChange();
                    } else if (gcashRadio && gcashRadio.checked) {
                        regularCashDetails.classList.add('hidden');
                        regularGcashDetails.classList.remove('hidden');
                    }
                }
            }

            function updatePaymentMethodStyles(container) {
                const allLabels = container.querySelectorAll('.payment-method-option');
                const isDiscount = container.id === 'discount-payment-method-container';
                
                allLabels.forEach(label => {
                    const radio = label.querySelector('input[type="radio"]');
                    const icon = label.querySelector('i');

                    label.classList.remove('border-brand-green', 'bg-green-50', 'border-blue-500', 'bg-blue-50');
                    label.classList.add('border-gray-200', 'bg-gray-50');
                    icon.classList.remove('text-brand-green', 'text-blue-500');
                    icon.classList.add('text-gray-600');

                    if (radio.checked) {
                        const activeIcon = label.querySelector('i');
                        if (radio.value === 'cash') {
                            label.classList.add('border-brand-green', 'bg-green-50');
                            label.classList.remove('border-gray-200', 'bg-gray-50');
                            activeIcon.classList.add('text-brand-green');
                            activeIcon.classList.remove('text-gray-600');
                        } else if (radio.value === 'gcash') {
                            label.classList.add('border-blue-500', 'bg-blue-50');
                            label.classList.remove('border-gray-200', 'bg-gray-50');
                            activeIcon.classList.add('text-blue-500');
                            activeIcon.classList.remove('text-gray-600');
                        }
                    }
                });
                
                // Toggle payment details based on selected method
                togglePaymentDetails(container, isDiscount);
            }

            if (discountPaymentMethodContainer) {
                discountPaymentMethodContainer.addEventListener('change', () => updatePaymentMethodStyles(discountPaymentMethodContainer));
            }
            if (regularPaymentMethodContainer) {
                regularPaymentMethodContainer.addEventListener('change', () => updatePaymentMethodStyles(regularPaymentMethodContainer));
            }
            
            // Add event listeners for cash amount inputs
            if (discountCashAmount) {
                discountCashAmount.addEventListener('input', updateDiscountChange);
                discountCashAmount.addEventListener('keyup', updateDiscountChange);
            }
            if (regularCashAmount) {
                regularCashAmount.addEventListener('input', updateRegularChange);
                regularCashAmount.addEventListener('keyup', updateRegularChange);
            }

            // Initial load
            fetchProducts();
            fetchCategories();
        });
    </script>
    
    <!-- Purchase History Modal -->
    <div id="purchase-history-modal" class="modal-overlay">
        <div class="modal-content !max-w-[85vw] !w-[65vw] !h-[55vh] !max-h-[95vh] overflow-y-auto">
            <div class="p-10">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-4xl font-bold text-gray-800">Purchase History</h3>
                    <button id="close-purchase-history-btn" class="p-3 rounded-full hover:bg-gray-100 text-3xl leading-none font-bold text-gray-600">&times;</button>
                </div>
                
                <div class="mb-6 flex gap-4">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="text" id="history-search" placeholder="Search by product name..." class="w-full pl-12 pr-4 py-3.5 text-lg rounded-lg border bg-white focus:outline-none focus:ring-2 focus:ring-brand-green">
                    </div>
                    <div class="relative">
                        <div class="flex gap-2">
                            <div class="relative">
                                <i class="fas fa-calendar-alt absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg pointer-events-none"></i>
                                <input type="text" readonly id="history-date-display" placeholder="Select date" class="px-4 pl-12 py-3.5 text-lg border-2 border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-brand-green cursor-pointer hover:border-brand-green transition-colors" style="min-width: 250px;">
                                <input type="hidden" id="history-date-filter">
                                
                                <!-- Custom Calendar Dropdown -->
                                <div id="calendar-dropdown-history" class="calendar-dropdown-history">
                                    <div class="calendar-header-history">
                                        <button type="button" class="nav-btn-history" id="history-prev-month">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <div class="flex items-center gap-2">
                                            <select id="history-month-select" class="px-2 py-1 rounded bg-gray-100 border-none font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-brand-green">
                                                <option value="0">January</option>
                                                <option value="1">February</option>
                                                <option value="2">March</option>
                                                <option value="3">April</option>
                                                <option value="4">May</option>
                                                <option value="5">June</option>
                                                <option value="6">July</option>
                                                <option value="7">August</option>
                                                <option value="8">September</option>
                                                <option value="9">October</option>
                                                <option value="10">November</option>
                                                <option value="11">December</option>
                                            </select>
                                            <select id="history-year-select" class="px-2 py-1 rounded bg-gray-100 border-none font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-brand-green"></select>
                                        </div>
                                        <button type="button" class="nav-btn-history" id="history-next-month">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="calendar-grid-history" id="history-calendar-days-header">
                                        <div class="calendar-day-name-history">Su</div>
                                        <div class="calendar-day-name-history">Mo</div>
                                        <div class="calendar-day-name-history">Tu</div>
                                        <div class="calendar-day-name-history">We</div>
                                        <div class="calendar-day-name-history">Th</div>
                                        <div class="calendar-day-name-history">Fr</div>
                                        <div class="calendar-day-name-history">Sa</div>
                                    </div>
                                    <div class="calendar-grid-history" id="history-calendar-days"></div>
                                    <div class="flex gap-2 mt-3 pt-3 border-t border-gray-200">
                                        <button type="button" id="history-today-btn" class="flex-1 px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-sm font-medium transition-colors">
                                            Today
                                        </button>
                                        <button type="button" id="history-clear-calendar-btn" class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                                            Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg border overflow-hidden">
                    <div class="overflow-x-auto max-h-[75vh]">
                        <table class="w-full text-lg">
                            <thead class="bg-gray-50 text-base font-semibold text-gray-600 uppercase tracking-wider sticky top-0">
                                <tr>
                                    <th class="px-8 py-4 text-left">#</th>
                                    <th class="px-8 py-4 text-left">Product Name</th>
                                    <th class="px-8 py-4 text-left">Items</th>
                                    <th class="px-8 py-4 text-right">Total Price</th>
                                    <th class="px-8 py-4 text-left">Date</th>
                                </tr>
                            </thead>
                            <tbody id="purchase-history-table" class="divide-y divide-gray-200 text-gray-700">
                                <!-- Data will be populated here -->
                            </tbody>
                        </table>
                    </div>
                    <div class="p-6 bg-gray-50 border-t flex justify-end gap-8 font-semibold text-xl">
                        <div>
                            <span class="text-gray-600">Total Sales:</span>
                            <span id="history-total-sales" class="text-brand-green ml-2">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Purchase History Modal Functionality
        document.addEventListener('DOMContentLoaded', () => {
            const purchaseHistoryBtn = document.getElementById('purchase-history-btn');
            const purchaseHistoryModal = document.getElementById('purchase-history-modal');
            const closePurchaseHistoryBtn = document.getElementById('close-purchase-history-btn');
            const historySearch = document.getElementById('history-search');
            const historyDateFilter = document.getElementById('history-date-filter');
            const historyDateDisplay = document.getElementById('history-date-display');
            const calendarDropdownHistory = document.getElementById('calendar-dropdown-history');
            const historyPrevMonthBtn = document.getElementById('history-prev-month');
            const historyNextMonthBtn = document.getElementById('history-next-month');
            const historyMonthSelect = document.getElementById('history-month-select');
            const historyYearSelect = document.getElementById('history-year-select');
            const historyCalendarDays = document.getElementById('history-calendar-days');
            const historyTodayBtn = document.getElementById('history-today-btn');
            const historyClearCalendarBtn = document.getElementById('history-clear-calendar-btn');
            const purchaseHistoryTable = document.getElementById('purchase-history-table');
            const historyTotalSales = document.getElementById('history-total-sales');
            
            let purchaseHistoryData = [];
            let currentDateHistory = new Date();
            let selectedDateHistory = null;
            
            // Open modal and fetch data
            if (purchaseHistoryBtn) {
                purchaseHistoryBtn.addEventListener('click', () => {
                    purchaseHistoryModal.classList.add('active');
                    fetchPurchaseHistory();
                });
            }
            
            // Close modal
            if (closePurchaseHistoryBtn) {
                closePurchaseHistoryBtn.addEventListener('click', () => {
                    purchaseHistoryModal.classList.remove('active');
                });
            }
            
            // Close on outside click
            purchaseHistoryModal.addEventListener('click', (e) => {
                if (e.target === purchaseHistoryModal) {
                    purchaseHistoryModal.classList.remove('active');
                }
            });
            
            // === CUSTOM CALENDAR FUNCTIONALITY ===
            
            // Initialize year select
            function initHistoryYearSelect() {
                const currentYear = new Date().getFullYear();
                for (let year = currentYear - 10; year <= currentYear + 10; year++) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    historyYearSelect.appendChild(option);
                }
            }
            initHistoryYearSelect();
            
            // Render calendar
            function renderHistoryCalendar() {
                const year = currentDateHistory.getFullYear();
                const month = currentDateHistory.getMonth();
                
                historyMonthSelect.value = month;
                historyYearSelect.value = year;
                
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const prevMonthDays = new Date(year, month, 0).getDate();
                
                let days = '';
                
                // Previous month days
                for (let i = firstDay - 1; i >= 0; i--) {
                    days += `<div class="calendar-day-history other-month">${prevMonthDays - i}</div>`;
                }
                
                // Current month days
                const today = new Date();
                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    // Format date as YYYY-MM-DD without timezone conversion
                    const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const isToday = date.toDateString() === today.toDateString();
                    const isSelected = selectedDateHistory === dateString;
                    
                    let classes = 'calendar-day-history';
                    if (isToday) classes += ' today';
                    if (isSelected) classes += ' selected';
                    
                    days += `<div class="${classes}" data-date="${dateString}">${day}</div>`;
                }
                
                // Next month days
                const totalCells = firstDay + daysInMonth;
                const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
                for (let i = 1; i <= remainingCells; i++) {
                    days += `<div class="calendar-day-history other-month">${i}</div>`;
                }
                
                historyCalendarDays.innerHTML = days;
                
                // Add click event to date cells
                document.querySelectorAll('.calendar-day-history:not(.other-month)').forEach(day => {
                    day.addEventListener('click', () => selectHistoryDate(day.dataset.date));
                });
            }
            
            // Select date
            function selectHistoryDate(dateString) {
                selectedDateHistory = dateString;
                // Parse date string manually to avoid timezone issues
                const [year, month, day] = dateString.split('-').map(Number);
                const date = new Date(year, month - 1, day);
                historyDateDisplay.value = date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                historyDateFilter.value = dateString;
                calendarDropdownHistory.classList.remove('show');
                filterPurchaseHistory();
            }
            
            // Toggle calendar
            historyDateDisplay.addEventListener('click', () => {
                calendarDropdownHistory.classList.toggle('show');
                if (calendarDropdownHistory.classList.contains('show')) {
                    renderHistoryCalendar();
                }
            });
            
            // Close calendar when clicking outside
            document.addEventListener('click', (e) => {
                if (!historyDateDisplay.contains(e.target) && !calendarDropdownHistory.contains(e.target)) {
                    calendarDropdownHistory.classList.remove('show');
                }
            });
            
            // Navigation buttons
            historyPrevMonthBtn.addEventListener('click', () => {
                currentDateHistory.setMonth(currentDateHistory.getMonth() - 1);
                renderHistoryCalendar();
            });
            
            historyNextMonthBtn.addEventListener('click', () => {
                currentDateHistory.setMonth(currentDateHistory.getMonth() + 1);
                renderHistoryCalendar();
            });
            
            historyMonthSelect.addEventListener('change', (e) => {
                currentDateHistory.setMonth(parseInt(e.target.value));
                renderHistoryCalendar();
            });
            
            historyYearSelect.addEventListener('change', (e) => {
                currentDateHistory.setFullYear(parseInt(e.target.value));
                renderHistoryCalendar();
            });
            
            // Today button
            historyTodayBtn.addEventListener('click', () => {
                const today = new Date();
                selectHistoryDate(today.toISOString().split('T')[0]);
            });
            
            // Clear calendar button
            historyClearCalendarBtn.addEventListener('click', () => {
                selectedDateHistory = null;
                historyDateDisplay.value = '';
                historyDateFilter.value = '';
                calendarDropdownHistory.classList.remove('show');
                renderHistoryCalendar();
                filterPurchaseHistory();
            });
            
            // === END CUSTOM CALENDAR FUNCTIONALITY ===
            
            // Fetch purchase history from database
            async function fetchPurchaseHistory() {
                try {
                    const response = await fetch('../api/get_purchase_history.php');
                    purchaseHistoryData = await response.json();
                    renderPurchaseHistory(purchaseHistoryData);
                } catch (error) {
                    console.error('Error fetching purchase history:', error);
                    purchaseHistoryTable.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-500">Error loading purchase history</td></tr>';
                }
            }
            
            // Render purchase history table
            function renderPurchaseHistory(data) {
                if (data.length === 0) {
                    purchaseHistoryTable.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-500">No purchase history found</td></tr>';
                    historyTotalSales.textContent = '₱0.00';
                    return;
                }
                
                let html = '';
                let totalSales = 0;
                
                data.forEach((item, index) => {
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">${index + 1}</td>
                            <td class="px-6 py-3 font-medium">${item.product_name}</td>
                            <td class="px-6 py-3">${item.quantity}</td>
                            <td class="px-6 py-3 text-right font-semibold">₱${parseFloat(item.total_price).toFixed(2)}</td>
                            <td class="px-6 py-3">${formatDate(item.transaction_date)}</td>
                        </tr>
                    `;
                    totalSales += parseFloat(item.total_price);
                });
                
                purchaseHistoryTable.innerHTML = html;
                historyTotalSales.textContent = `₱${totalSales.toFixed(2)}`;
            }
            
            // Combined filter functionality
            function filterPurchaseHistory() {
                const searchTerm = historySearch.value.toLowerCase();
                const selectedDate = historyDateFilter.value;
                
                let filtered = purchaseHistoryData;
                
                // Apply search filter
                if (searchTerm) {
                    filtered = filtered.filter(item => 
                        item.product_name.toLowerCase().includes(searchTerm)
                    );
                }
                
                // Apply date filter
                if (selectedDate) {
                    filtered = filtered.filter(item => {
                        const itemDate = item.transaction_date.split(' ')[0];
                        return itemDate === selectedDate;
                    });
                }
                
                renderPurchaseHistory(filtered);
            }
            
            // Search functionality
            historySearch.addEventListener('input', filterPurchaseHistory);
            
            // Format date helper
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            }
        });
    </script>
    
    <?php echo $posDarkMode['script']; ?>
</body>
</html>