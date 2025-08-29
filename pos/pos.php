<?php
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

        /* Redesigned Components */
        .product-card { 
            background-color: white; 
            border-radius: 0.75rem; 
            border: 1px solid var(--border-gray); 
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            overflow: hidden; 
            transition: transform 0.2s, box-shadow 0.2s; 
            cursor: pointer; 
            display: flex; 
            flex-direction: column; 
        }
        .product-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .product-image-container { 
            height: 140px; 
            background-color: #f8f9fa; 
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
        .stock-badge { 
            position: absolute; 
            top: 12px; 
            right: 12px; 
            font-size: 0.75rem; 
            font-weight: 500; 
            padding: 0.2rem 0.6rem; 
            border-radius: 9999px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .stock-dot { width: 6px; height: 6px; border-radius: 50%; }
        .in-stock { background-color: #e6fffa; color: #2c7a7b; } .in-stock .stock-dot { background-color: #38b2ac; }
        .low-stock { background-color: #fffaf0; color: #c05621; } .low-stock .stock-dot { background-color: #ed8936; }
        .out-of-stock { background-color: #fef2f2; color: #c53030; } .out-of-stock .stock-dot { background-color: #f56565; }

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
        }
        .modal-overlay.active .modal-content {
            transform: scale(1);
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
                
                <!-- Search Bar -->
                <div class="relative mb-6">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
                    <input type="text" id="product-search" placeholder="Search by product name..." class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green transition-shadow">
                </div>

                <div class="category-filter flex items-center gap-2 mb-6 overflow-x-auto pb-2"></div>
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4" id="product-grid"></div>
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
                                    <span>Discount</span>
                                    <span class="font-medium text-red-500">-₱0.00</span>
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

    <!-- Payment Modal -->
    <div id="payment-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="p-5 border-b">
                <h3 class="text-lg font-semibold">Payment</h3>
            </div>
            <div class="p-5">
                <div class="text-center mb-6">
                    <p class="text-gray-500 text-sm">Total Amount Due</p>
                    <p id="modal-total-amount" class="text-4xl font-bold text-brand-green">₱0.00</p>
                </div>
                <form id="payment-form">
                    <div>
                        <label for="amount-paid" class="text-sm font-medium text-gray-700">Amount Paid</label>
                        <input type="number" id="amount-paid" name="amount-paid" step="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-green focus:border-brand-green text-lg" placeholder="0.00" required>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button type="button" id="cancel-payment-btn" class="btn btn-secondary w-full">Cancel</button>
                        <button type="submit" class="btn btn-primary w-full">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="success-modal" class="modal-overlay">
        <div class="modal-content text-center p-8">
            <div class="w-16 h-16 bg-brand-green/10 text-brand-green rounded-full mx-auto flex items-center justify-center">
                <i data-lucide="check" class="w-10 h-10"></i>
            </div>
            <h3 class="text-2xl font-bold mt-6">Payment Successful</h3>
            <p class="text-gray-500 mt-2">Change:</p>
            <p id="change-amount" class="text-4xl font-bold text-gray-800 mt-1">₱0.00</p>
            <button id="new-transaction-btn" class="btn btn-primary w-full mt-8">New Transaction</button>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            let allProducts = [];
            let orderItems = [];

            // DOM Elements
            const productSearchInput = document.getElementById('product-search');
            const productGrid = document.getElementById('product-grid');
            const categoryFilterContainer = document.querySelector('.category-filter');
            const orderItemsContainer = document.getElementById('order-items');
            const subtotalElement = document.getElementById('subtotal');
            const totalElement = document.getElementById('total');
            const checkoutBtn = document.getElementById('checkout-btn');
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            const dateTimeEl = document.getElementById('date-time');
            const modalTotalAmount = document.getElementById('modal-total-amount');
            const amountPaidInput = document.getElementById('amount-paid');
            const paymentForm = document.getElementById('payment-form');
            const cancelPaymentBtn = document.getElementById('cancel-payment-btn');
            const changeAmountEl = document.getElementById('change-amount');
            const newTransactionBtn = document.getElementById('new-transaction-btn');
            const paymentModal = document.getElementById('payment-modal');
            const successModal = document.getElementById('success-modal');

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
            
            const placeholderSVG = `<svg class="w-12 h-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4Z"/></svg>`;

            async function fetchAndRenderData() {
                try {
                    const [productsRes, categoriesRes] = await Promise.all([
                        fetch('../api/get_products.php'),
                        fetch('../api/get_categories.php')
                    ]);
                    allProducts = await productsRes.json();
                    const allCategories = await categoriesRes.json();
                    renderCategoryFilters(allCategories);
                    updateProductView(); // Initial render
                } catch (error) {
                    productGrid.innerHTML = `<p class="col-span-full text-center text-red-500">Could not load products.</p>`;
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

            function renderProducts(productsToRender) {
                if (productsToRender.length === 0) {
                    productGrid.innerHTML = `<p class="col-span-full text-center text-gray-500 py-10">No products found for the current filter.</p>`;
                    return;
                }
                productGrid.innerHTML = productsToRender.map(p => {
                    let stockBadgeClass = 'in-stock', stockStatusText = `In Stock`;
                    if (p.stock <= 0) { stockBadgeClass = 'out-of-stock'; stockStatusText = 'Out of Stock'; }
                    else if (p.stock <= 20) { stockBadgeClass = 'low-stock'; stockStatusText = `Low Stock`; }
                    const imageContent = p.image_path ? `<img src="../${p.image_path}" alt="${p.name}">` : placeholderSVG;
                    return `
                        <div class="product-card ${p.stock <= 0 ? 'opacity-60 grayscale cursor-not-allowed' : ''}" data-id="${p.id}">
                            <div class="product-image-container">
                                ${imageContent}
                                <div class="stock-badge ${stockBadgeClass}"><span class="stock-dot"></span>${stockStatusText}</div>
                            </div>
                            <div class="p-4 flex flex-col flex-grow">
                                <h3 class="font-semibold text-gray-800 text-sm flex-grow">${p.name}</h3>
                                <p class="font-bold text-gray-900 mt-2">₱${Number(p.price).toFixed(2)}</p>
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
                const existingItem = orderItems.find(item => item.id === product.id);
                if (existingItem) {
                    const newQuantity = existingItem.quantity + 1;
                    if (newQuantity > product.stock) { 
                        alert(`Sorry, only ${product.stock} units of ${product.name} available.`); 
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
                        `<div class="flex items-center gap-4 p-3"><img src="${item.image_path ? `../${item.image_path}` : ''}" onerror="this.style.display='none'" class="w-12 h-12 rounded-md object-cover bg-gray-100"><div class="flex-grow"><p class="font-semibold text-sm">${item.name}</p><p class="text-xs text-gray-500">₱${Number(item.price).toFixed(2)}</p></div><div class="flex items-center gap-2 text-sm"><div class="quantity-selector flex items-center border border-gray-200 rounded-md"><button class="minus p-1.5 transition" data-index="${index}"><i data-lucide="minus" class="w-4 h-4 text-gray-500"></i></button><input type="text" class="w-8 text-center font-medium bg-transparent" value="${item.quantity}" readonly><button class="plus p-1.5 transition" data-index="${index}"><i data-lucide="plus" class="w-4 h-4 text-gray-500"></i></button></div></div><button class="remove-item-btn p-1.5 rounded-md text-gray-400 transition" data-index="${index}"><i data-lucide="trash-2" class="w-4 h-4"></i></button></div>`
                    ).join('');
                }
                
                const subtotal = orderItems.reduce((total, item) => total + (item.price * item.quantity), 0);
                subtotalElement.textContent = `₱${subtotal.toFixed(2)}`;
                totalElement.textContent = `₱${subtotal.toFixed(2)}`;
                checkoutBtn.disabled = orderItems.length === 0;
                lucide.createIcons();
            }

            productSearchInput.addEventListener('input', updateProductView);

            productGrid.addEventListener('click', (e) => {
                const card = e.target.closest('.product-card');
                if (card) {
                    const productId = parseInt(card.dataset.id);
                    const product = allProducts.find(p => p.id === productId);
                    if (product && product.stock > 0) {
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
                    const product = allProducts.find(p => p.id === item.id);
                    if (item.quantity < product.stock) {
                        item.quantity++;
                    } else {
                        alert(`Maximum stock for ${item.name} reached.`);
                    }
                }
                updateOrderSummary();
            });

            categoryFilterContainer.addEventListener('click', (e) => {
                if (e.target.matches('.category-btn')) {
                    categoryFilterContainer.querySelector('.active').classList.remove('active');
                    e.target.classList.add('active');
                    updateProductView();
                }
            });

            checkoutBtn.addEventListener('click', () => {
                const total = orderItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                modalTotalAmount.textContent = `₱${total.toFixed(2)}`;
                paymentModal.classList.add('active');
                amountPaidInput.focus();
            });

            cancelPaymentBtn.addEventListener('click', () => {
                paymentModal.classList.remove('active');
                paymentForm.reset();
            });
            
            paymentForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const total = orderItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                const amountPaid = parseFloat(amountPaidInput.value);

                if (amountPaid < total) {
                    alert('Amount paid is less than the total amount.');
                    return;
                }
                try {
                    const response = await fetch('../api.php?action=process_sale', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(orderItems)
                    });
                    const result = await response.json();

                    if (!result.success) {
                        alert(`Error processing sale: ${result.message}`);
                        return;
                    }

                    const change = amountPaid - total;
                    changeAmountEl.textContent = `₱${change.toFixed(2)}`;
                    
                    paymentModal.classList.remove('active');
                    paymentForm.reset();
                    successModal.classList.add('active');
                    lucide.createIcons();

                } catch (error) {
                    alert('An error occurred while connecting to the server.');
                }
            });

            newTransactionBtn.addEventListener('click', () => {
                successModal.classList.remove('active');
                orderItems = [];
                updateOrderSummary();
                fetchAndRenderData(); // Refresh products to show updated stock
            });

            // Initial load
            fetchAndRenderData();
        });
    </script>
</body>
</html>

