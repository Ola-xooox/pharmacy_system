<?php
    session_start();
        // Redirect if not logged in or not a POS user
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pos') {
        header("Location: ../login.php");
        exit();
    }
    $currentPage = 'pos';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy POS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
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
            height: 130px; 
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
            padding: 1rem; 
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
             .no-print {
                display: none;
            }
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
</head>
<body class="bg-gray-100">

    <?php include 'pos_header.php'; ?>

    <main class="p-4 sm:p-6 max-w-screen-2xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-5 gap-6">
            <div class="lg:col-span-2 xl:col-span-3">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Point of Sale</h1>
                    <p class="text-gray-500 mt-1">Select products to add them to the order.</p>
                </div>
                
                <div class="relative mb-6">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
                    <input type="text" id="product-search" placeholder="Search by product name..." class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green transition-shadow">
                </div>

                <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2">
                    <div id="stock-status-filter" class="flex items-center gap-2">
                        <button class="category-btn active" data-stock-status="available">Available Products</button>
                        <button class="category-btn" data-stock-status="outOfStock">Out of Stock</button>
                    </div>
                    <div class="border-l border-gray-300 h-6 mx-2"></div>
                    <div id="category-filter-container" class="flex items-center gap-2 overflow-x-auto">
                        </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6" id="product-grid"></div>
            </div>
            
            <div class="lg:col-span-1 xl:col-span-2 mt-8 lg:mt-0">
                <div class="order-summary-wrapper">
                    <div class="order-summary">
                        <div class="p-5 border-b border-gray-200">
                           <h2 class="text-lg font-semibold">Order Summary</h2>
                        </div>
                        <div id="order-items" class="p-2 max-h-[45vh] overflow-y-auto">
                            <div class="text-center text-gray-400 py-16 px-4">
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
                                <i data-lucide="credit-card" class="w-5 h-5"></i>
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
                                <i data-lucide="wallet" class="w-5 h-5 sm:w-6 sm:h-6 mx-3 text-gray-600"></i>
                                <span class="font-semibold text-sm sm:text-base">Cash</span>
                            </label>
                             <label class="payment-method-option flex items-center p-3 border-2 border-gray-200 rounded-lg bg-gray-50 cursor-pointer">
                                <input type="radio" name="discount-payment-method" value="gcash" class="form-radio text-brand-green">
                                <i data-lucide="smartphone" class="w-5 h-5 sm:w-6 sm:h-6 mx-3 text-gray-600"></i>
                                <span class="font-semibold text-sm sm:text-base">GCash</span>
                            </label>
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
                                <i data-lucide="wallet" class="w-5 h-5 sm:w-6 sm:h-6 mx-3 text-gray-600"></i>
                                <span class="font-semibold text-sm sm:text-base">Cash</span>
                            </label>
                             <label class="payment-method-option flex items-center p-3 border-2 border-gray-200 rounded-lg bg-gray-50 cursor-pointer">
                                <input type="radio" name="regular-payment-method" value="gcash" class="form-radio text-brand-green">
                                <i data-lucide="smartphone" class="w-5 h-5 sm:w-6 sm:h-6 mx-3 text-gray-600"></i>
                                <span class="font-semibold text-sm sm:text-base">GCash</span>
                            </label>
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
                 </div>
                 
                 <div class="text-center mt-8 text-xs text-gray-500">
                    <p>Thank you for your purchase!</p>
                    <p>Please keep this receipt for your records</p>
                 </div>

                 <div class="mt-8 flex gap-3 no-print">
                    <button id="print-receipt-btn" class="btn btn-secondary w-full"><i data-lucide="printer" class="w-4 h-4"></i>Print Receipt</button>
                    <button id="new-transaction-btn" class="btn btn-primary w-full">New Transaction</button>
                 </div>
             </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
            
            const placeholderSVG = `<svg class="w-16 h-16 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>`;

            async function fetchProducts(status = 'available') {
                try {
                    const response = await fetch(`../api/get_products.php?status=${status}`);
                    allProducts = await response.json();
                    updateProductView();
                } catch (error) {
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
                if (productsToRender.length === 0) {
                    productGrid.innerHTML = `<p class="col-span-full text-center text-gray-500 py-10">No products found for the current filter.</p>`;
                    return;
                }
                productGrid.innerHTML = productsToRender.map(p => {
                    const stockStatus = getStockStatus(p.stock);
                    const imageContent = p.image_path ? `<img src="../${p.image_path}" alt="${p.name}" class="product-image">` : placeholderSVG;
                    return `
                        <div class="product-card ${p.item_total <= 0 ? 'opacity-60 grayscale cursor-not-allowed' : ''}" data-name="${p.product_identifier}">
                             <div class="product-image-container">
                                ${imageContent}
                                <div class="stock-badge ${stockStatus.class}">${stockStatus.text}</div>
                            </div>
                            <div class="product-info text-center">
                                <h4 class="product-name">${p.name}</h4>
                                <p class="text-sm text-gray-500 mb-2">${p.category_name}</p>
                                <p class="text-xs text-gray-400 mb-2">Stock: ${p.stock} | Items: ${p.item_total}</p>
                                <p class="product-price">₱${Number(p.price).toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function renderCategoryFilters(categories) {
                categoryFilterContainer.innerHTML = '<button class="category-btn active" data-name="all">All</button>' +
                categories.map(cat => `<button class="category-btn" data-name="${cat.name}">${cat.name}</button>`).join('');
            }
            
            function addToOrder(product) {
                if(product.item_total <= 0) return;
                const existingItem = orderItems.find(item => item.name === product.name);
                if (existingItem) {
                    const newQuantity = existingItem.quantity + 1;
                    if (newQuantity > product.item_total) { 
                        alert(`Sorry, only ${product.item_total} items of ${product.name} available.`); 
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
                    orderItemsContainer.innerHTML = `<div class="text-center text-gray-400 py-16 px-4"><i data-lucide="shopping-cart" class="mx-auto h-12 w-12"></i><p class="mt-4 text-sm">Your cart is empty</p></div>`;
                } else {
                    orderItemsContainer.innerHTML = orderItems.map((item, index) =>
                        `<div class="flex items-center gap-4 p-3"><img src="${item.image_path ? `../${item.image_path}` : ''}" onerror="this.style.display='none'" class="w-12 h-12 rounded-md object-cover bg-gray-100"><div class="flex-grow"><p class="font-semibold text-sm">${item.name}</p><p class="text-xs text-gray-500">₱${Number(item.price).toFixed(2)}</p></div><div class="flex items-center gap-2 text-sm"><div class="quantity-selector flex items-center border border-gray-200 rounded-md"><button class="minus p-1.5 transition" data-index="${index}"><i data-lucide="minus" class="w-4 h-4 text-gray-500 pointer-events-none"></i></button><input type="text" class="w-8 text-center font-medium bg-transparent" value="${item.quantity}" readonly><button class="plus p-1.5 transition" data-index="${index}"><i data-lucide="plus" class="w-4 h-4 text-gray-500 pointer-events-none"></i></button></div></div><button class="remove-item-btn p-1.5 rounded-md text-gray-400 transition" data-index="${index}"><i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i></button></div>`
                    ).join('');
                }
                
                const subtotal = orderItems.reduce((total, item) => total + (item.price * item.quantity), 0);
                const discountRate = parseFloat(discountSelector.value);
                const discountAmount = subtotal * discountRate;
                const total = subtotal - discountAmount;

                subtotalElement.textContent = `₱${subtotal.toFixed(2)}`;
                discountAmountElement.textContent = `-₱${discountAmount.toFixed(2)}`;
                totalElement.textContent = `₱${total.toFixed(2)}`;
                checkoutBtn.disabled = orderItems.length === 0;
                lucide.createIcons();
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
                    if (product && item.quantity < product.item_total) {
                        item.quantity++;
                    } else {
                        alert(`Maximum items for ${item.name} reached.`);
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
                
                lucide.createIcons();
            });

            cancelDiscountPaymentBtn.addEventListener('click', () => {
                discountPaymentModal.classList.remove('active');
                discountPaymentForm.reset();
            });
            
            cancelRegularPaymentBtn.addEventListener('click', () => {
                regularPaymentModal.classList.remove('active');
                regularPaymentForm.reset();
            });
            
            // NEW UNIFIED FUNCTION TO PROCESS SALE AND LOG CUSTOMER DATA
            async function completePurchase(customerData) {
                const saleData = {
                    ...customerData,
                    items: orderItems,
                    total_amount: parseFloat(totalElement.textContent.replace('₱', ''))
                };

                // First, process the inventory reduction
                try {
                    const stockResponse = await fetch('../api.php?action=process_sale', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(orderItems)
                    });
                    const stockResult = await stockResponse.json();
                    if (!stockResponse.ok) {
                        alert(`Error processing sale: ${stockResult.message || 'Server error'}`);
                        return false;
                    }
                } catch (error) {
                    console.error('Stock update error:', error);
                    alert('An error occurred while connecting to the server for stock update.');
                    return false;
                }

                // Second, log the complete sale with customer history
                try {
                    const historyResponse = await fetch('../api/customer_api.php?action=complete_sale', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(saleData)
                    });
                    const historyResult = await historyResponse.json();
                     if (!historyResponse.ok) {
                        alert(`Error logging customer history: ${historyResult.message || 'Server error'}`);
                        return false;
                    }
                    return true;
                } catch (error) {
                    console.error('Customer history logging error:', error);
                    alert('An error occurred while logging the sale.');
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

                receiptModal.classList.add('active');
                lucide.createIcons();
            }

            newTransactionBtn.addEventListener('click', () => {
                receiptModal.classList.remove('active');
                discountPaymentForm.reset();
                regularPaymentForm.reset();
                orderItems = [];
                discountSelector.value = "0";
                updateOrderSummary();
                fetchProducts(); 
            });
            
            printReceiptBtn.addEventListener('click', () => {
                window.print();
            });
            
            function updatePaymentMethodStyles(container) {
                const allLabels = container.querySelectorAll('.payment-method-option');
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
            }

            if (discountPaymentMethodContainer) {
                discountPaymentMethodContainer.addEventListener('change', () => updatePaymentMethodStyles(discountPaymentMethodContainer));
            }
            if (regularPaymentMethodContainer) {
                regularPaymentMethodContainer.addEventListener('change', () => updatePaymentMethodStyles(regularPaymentMethodContainer));
            }

            // Initial load
            fetchProducts();
            fetchCategories();
        });
    </script>
</body>
</html>