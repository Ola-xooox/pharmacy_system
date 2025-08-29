<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy POS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .header { background-color: #01A74F; color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); position: sticky; top: 0; z-index: 30; }
        .container { max-width: 1400px; margin: 0 auto; padding: 1rem; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
        @media (min-width: 640px) { .product-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.25rem; } }
        .product-card { background-color: white; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; display: flex; flex-direction: column; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); }
        .product-image { height: 130px; background-color: #f9fafb; display: flex; align-items: center; justify-content: center; position: relative; }
        .product-image img { height: 100%; width: 100%; object-fit: cover; }
        @media (min-width: 640px) { .product-image { height: 150px; } }
        .product-image svg { width: 60px; height: 60px; }
        @media (min-width: 640px) { .product-image svg { width: 70px; height: 70px; } }
        .stock-badge { position: absolute; top: 10px; right: 10px; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px; border: 1px solid rgba(0,0,0,0.05); }
        .in-stock { background-color: #dcfce7; color: #166534; } .low-stock { background-color: #fef3c7; color: #b45309; } .out-of-stock { background-color: #fee2e2; color: #b91c1c; }
        .product-info { padding: 1rem; flex-grow: 1; display: flex; flex-direction: column; } .product-name { font-weight: 600; margin-bottom: 0.25rem; font-size: 1rem; }
        .product-price { font-weight: 700; color: #01A74F; font-size: 1.1rem; margin-top: auto; }
        .order-summary { background-color: white; border-radius: 0.75rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); padding: 1.5rem; }
        @media (min-width: 768px) { .order-summary { position: sticky; top: 6rem; } }
        .summary-header { font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; } .summary-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid #f3f4f6; }
        .summary-item:last-child { border-bottom: none; }
        .summary-totals-section { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; display: flex; flex-direction: column; gap: 0.5rem; }
        .summary-total { font-weight: 700; margin-top: 0.5rem; padding-top: 0.75rem; border-top: 2px solid #e5e7eb; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; text-align: center; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background-color: #01A74F; color: white; box-shadow: 0 2px 4px rgba(1, 167, 79, 0.2); } .btn-primary:hover { background-color: #018d43; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(1, 167, 79, 0.3); }
        .btn-primary:disabled { background-color: #a3e6be; cursor: not-allowed; transform: none; box-shadow: none; }
        .quantity-selector { display: flex; align-items: center; background-color: #f3f4f6; border-radius: 9999px; border: 1px solid #e5e7eb; }
        .quantity-btn { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; user-select: none; font-size: 1.2rem; line-height: 1; color: #4b5563; transition: background-color 0.15s; }
        .quantity-btn:hover { background-color: #e5e7eb; }
        .quantity-input { width: 35px; text-align: center; border: none; font-size: 0.9rem; font-weight: 500; background: transparent; color: #1f2937; padding: 0; }
        .remove-item-btn { color: #9ca3af; transition: color 0.15s; } .remove-item-btn:hover { color: #ef4444; }
        .category-filter { display: flex; overflow-x: auto; gap: 0.5rem; padding-bottom: 0.75rem; margin-bottom: 1rem; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
        .category-filter::-webkit-scrollbar { display: none; }
        .category-btn { white-space: nowrap; padding: 0.5rem 1rem; border-radius: 9999px; background-color: #e5e7eb; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; }
        .category-btn:hover { background-color: #d1d5db; } .category-btn.active { background-color: #01A74F; color: white; border-color: #01A74F; }
    </style>
</head>
<body class="antialiased">
    <header class="header">
        <div class="container flex justify-between items-center">
             <a href="inventory/products.php" class="flex items-center gap-3">
                <img src="https://i.imgur.com/uDbzYp0.png" alt="MJ Pharmacy Logo" class="w-10 h-10 rounded-full bg-white object-cover shadow-md">
                <h1 class="text-xl md:text-2xl font-bold tracking-tight">MJ Pharmacy POS</h1>
            </a>
            <div class="hidden md:flex items-center gap-2 text-sm bg-black bg-opacity-10 px-3 py-1.5 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <span id="current-date"></span>
            </div>
        </div>
    </header>
    
    <main class="container py-4 md:py-6">
        <div class="md:grid md:grid-cols-3 lg:grid-cols-5 md:gap-6">
            <div class="md:col-span-2 lg:col-span-3">
                <div class="mb-4 md:mb-6">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">Products</h2>
                    <div class="category-filter mt-4">
                        </div>
                </div>
                
                <div class="product-grid" id="product-grid">
                    </div>
            </div>
            
            <div class="md:col-span-1 lg:col-span-2 mt-8 md:mt-0">
                <div class="order-summary">
                    <div class="summary-header">Order Summary</div>
                    <div id="order-items" class="mb-4 divide-y divide-gray-100">
                        <div class="text-center text-gray-500 py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            <p class="mt-2 text-sm">Your cart is empty</p>
                        </div>
                    </div>
                    
                    <div class="summary-totals-section">
                        <div class="flex justify-between items-center text-gray-600">
                            <span>Subtotal:</span>
                            <span id="subtotal" class="font-medium">₱0.00</span>
                        </div>
                        <div class="summary-total flex justify-between items-center">
                            <span class="text-lg">Total:</span>
                            <span id="total" class="text-2xl font-bold text-green-600">₱0.00</span>
                        </div>
                    </div>
                    
                    <button id="checkout-btn" class="btn btn-primary w-full mt-6" disabled>
                        Proceed to Payment
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let allProducts = [];
            let orderItems = [];

            const productGrid = document.getElementById('product-grid');
            const categoryFilterContainer = document.querySelector('.category-filter');
            const orderItemsContainer = document.getElementById('order-items');
            const subtotalElement = document.getElementById('subtotal');
            const totalElement = document.getElementById('total');
            const checkoutBtn = document.getElementById('checkout-btn');

            // --- Date and Time ---
            function updateDateTime() {
                const now = new Date();
                const dateOptions = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
                document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
            }
            updateDateTime();
            
            // --- Placeholder SVG for products without an image ---
            const placeholderSVG = `<svg class="w-16 h-16 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor"><path d="M24 8h16v8H24z" opacity="0.3"/><path d="M40 6H24c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 10H24V8h16v8zm8 4H16c-2.2 0-4 1.8-4 4v32c0 2.2 1.8 4 4 4h32c2.2 0 4-1.8 4-4V24c0-2.2-1.8-4-4-4zm0 36H16V24h32v32z"/><path d="M32 28c-6.6 0-12 5.4-12 12s5.4 12 12 12 12-5.4 12-12-5.4-12-12-12zm0 20c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8z"/></svg>`;


            // --- Data Fetching and Rendering ---
            async function fetchAndRenderData() {
                try {
                    // Fetch both products and categories from your new API endpoints
                    const [productsRes, categoriesRes] = await Promise.all([
                        fetch('api/get_products.php'),
                        fetch('api/get_categories.php')
                    ]);
                    allProducts = await productsRes.json();
                    const allCategories = await categoriesRes.json();
                    
                    renderProducts(allProducts);
                    renderCategoryFilters(allCategories);
                } catch (error) {
                    console.error('Failed to fetch data:', error);
                    productGrid.innerHTML = `<p class="col-span-full text-center text-red-500">Could not load products. Please check the connection.</p>`;
                }
            }

            function renderProducts(productsToRender) {
                productGrid.innerHTML = '';
                 if (productsToRender.length === 0) {
                    productGrid.innerHTML = `<p class="col-span-full text-center text-gray-500">No products found.</p>`;
                    return;
                }
                productsToRender.forEach(p => {
                    let stockBadgeClass = 'in-stock', stockStatus = `In Stock: ${p.stock}`;
                    if (p.stock <= 0) { stockBadgeClass = 'out-of-stock'; stockStatus = 'Out of Stock'; }
                    else if (p.stock <= 20) { stockBadgeClass = 'low-stock'; stockStatus = `Low Stock: ${p.stock}`; }

                    // Use database image if available, otherwise show placeholder
                    const imageContent = p.image_path ? `<img src="${p.image_path}" alt="${p.name}">` : placeholderSVG;
                    
                    const card = document.createElement('div');
                    card.className = `product-card ${p.stock <= 0 ? 'opacity-60 grayscale' : ''}`;
                    card.dataset.id = p.id;
                    card.innerHTML = `
                        <div class="product-image">${imageContent}<div class="stock-badge ${stockBadgeClass}">${stockStatus}</div></div>
                        <div class="product-info">
                            <div class="product-name">${p.name}</div>
                            <div class="product-price">₱${Number(p.price).toFixed(2)}</div>
                        </div>
                    `;
                    productGrid.appendChild(card);
                });

                productGrid.querySelectorAll('.product-card').forEach(card => {
                    card.addEventListener('click', () => {
                        const product = allProducts.find(p => p.id === parseInt(card.dataset.id));
                        if (product && product.stock > 0) {
                            addToOrder(product, 1);
                        }
                    });
                });
            }

            function renderCategoryFilters(categories) {
                categoryFilterContainer.innerHTML = '';
                const allBtn = document.createElement('div');
                allBtn.className = 'category-btn active';
                allBtn.textContent = 'All Products';
                allBtn.dataset.categoryName = 'all';
                categoryFilterContainer.appendChild(allBtn);

                categories.forEach(cat => {
                    const btn = document.createElement('div');
                    btn.className = 'category-btn';
                    btn.textContent = cat.name;
                    btn.dataset.categoryName = cat.name;
                    categoryFilterContainer.appendChild(btn);
                });

                categoryFilterContainer.querySelectorAll('.category-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        categoryFilterContainer.querySelector('.active').classList.remove('active');
                        btn.classList.add('active');
                        const categoryName = btn.dataset.categoryName;
                        const filtered = categoryName === 'all'
                            ? allProducts
                            : allProducts.filter(p => p.category_name === categoryName);
                        renderProducts(filtered);
                    });
                });
            }
            
            // --- Cart Logic (Remains the same as your original file) ---
            function addToOrder(product, quantity) {
                const existingItem = orderItems.find(item => item.id === product.id);
                if (existingItem) {
                    const newQuantity = existingItem.quantity + quantity;
                    if (newQuantity > product.stock) { alert(`Sorry, only ${product.stock} units available.`); return; }
                    existingItem.quantity = newQuantity;
                } else {
                    orderItems.push({ ...product, quantity: 1 });
                }
                updateOrderSummary();
            }
            
            function updateOrderSummary() {
                if (orderItems.length === 0) {
                    orderItemsContainer.innerHTML = `<div class="text-center text-gray-500 py-8"><svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg><p class="mt-2 text-sm">Your cart is empty</p></div>`;
                } else {
                    orderItemsContainer.innerHTML = orderItems.map((item, index) =>
                        `<div class="summary-item"><div class="flex-grow"><div class="font-semibold text-sm">${item.name}</div><div class="text-xs text-gray-500">₱${item.price.toFixed(2)}</div></div><div class="flex items-center gap-4"><div class="quantity-selector"><div class="quantity-btn minus" data-index="${index}">-</div><input type="text" class="quantity-input" value="${item.quantity}" readonly><div class="quantity-btn plus" data-index="${index}">+</div></div><span class="font-bold w-20 text-right text-sm">₱${(item.price * item.quantity).toFixed(2)}</span><button class="remove-item-btn" data-index="${index}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button></div></div>`
                    ).join('');
                
                    orderItemsContainer.querySelectorAll('.remove-item-btn, .minus, .plus').forEach(el => {
                        el.addEventListener('click', () => {
                            const index = parseInt(el.dataset.index);
                            const item = orderItems[index];
                            if (el.classList.contains('remove-item-btn')) {
                                orderItems.splice(index, 1);
                            } else if (el.classList.contains('minus')) {
                                if (item.quantity > 1) item.quantity--;
                                else orderItems.splice(index, 1); // Remove if quantity becomes 0
                            } else if (el.classList.contains('plus') && item.quantity < item.stock) {
                                item.quantity++;
                            }
                            updateOrderSummary();
                        });
                    });
                }
                
                const subtotal = orderItems.reduce((total, item) => total + (item.price * item.quantity), 0);
                subtotalElement.textContent = `₱${subtotal.toFixed(2)}`;
                totalElement.textContent = `₱${subtotal.toFixed(2)}`;
                checkoutBtn.disabled = orderItems.length === 0;
            }

            // Initial load
            fetchAndRenderData();
        });
    </script>
</body>
</html>