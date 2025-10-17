<?php
session_start();
// Redirect if not logged in or not an inventory user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'inventory') {
    header("Location: ../index.php");
    exit();
}
    require '../db_connect.php';

    // This query correctly groups products by name and sums their totals.
    $products_result = $conn->query("
        SELECT
            p.name,
            SUM(p.stock) AS stock,
            SUM(p.item_total) AS item_total,
            c.name AS category_name,
            -- Get the price and image from the most recently added lot for this product name
            SUBSTRING_INDEX(GROUP_CONCAT(p.price ORDER BY p.id DESC), ',', 1) AS price,
            SUBSTRING_INDEX(GROUP_CONCAT(p.image_path ORDER BY p.id DESC), ',', 1) AS image_path,
            -- Use the name as a unique identifier for the card
            p.name as product_identifier
        FROM
            products p
        JOIN
            categories c ON p.category_id = c.id
        WHERE
            (p.expiration_date > CURDATE() OR p.expiration_date IS NULL)
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .stock-badge { position: absolute; top: 10px; right: 10px; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px; border: 1px solid rgba(0,0,0,0.05); text-transform: capitalize; }
        .in-stock { background-color: #dcfce7; color: #166534; } .low-stock { background-color: #fef3c7; color: #b45309; } .out-of-stock { background-color: #fee2e2; color: #b91c1c; }
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
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        
        <?php 
            $currentPage = 'products';
            include '../partials/sidebar.php'; 
        ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            
            <?php include '../partials/header.php'; ?>

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
                            <input type="text" name="lot_number" placeholder="Lot Number" class="form-input">
                            <input type="text" id="date-added-display" placeholder="Date Added" class="form-input bg-gray-200 cursor-not-allowed" readonly>
                            <select name="category" id="category-select" class="form-input sm:col-span-2" required></select>
                            <div id="new-category-wrapper" class="hidden sm:col-span-2">
                                <input type="text" name="new_category" id="new-category-input" placeholder="Enter new category name" class="form-input">
                            </div>
                            <input type="number" name="cost" placeholder="Cost (e.g., 15.00)" class="form-input" step="0.01">
                            <input type="number" name="price" placeholder="Price (e.g., 25.50)" class="form-input" step="0.01" required>
                            <input type="text" onfocus="(this.type='date')" onblur="(this.type='text')" name="expiration_date" placeholder="Expiration Date" class="form-input">
                            <input type="text" name="supplier" placeholder="Supplier" class="form-input">
                            
                            <div class="sm:col-span-2 grid grid-cols-3 gap-4">
                                <input type="text" name="batch_number" placeholder="Batch Number" class="form-input">
                                <input type="number" name="stock" placeholder="Stock to Add" class="form-input" required>
                                <input type="text" name="item_total" placeholder="Total Item to Add" class="form-input">
                            </div>
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

            function getStockStatus(stock) {
                stock = parseInt(stock, 10);
                if (stock <= 0) return { text: 'Out of Stock', class: 'out-of-stock' };
                if (stock > 0 && stock <= 5) return { text: 'Low Stock', class: 'low-stock' };
                return { text: 'In Stock', class: 'in-stock' };
            }

            function createProductCardHTML(product) {
                const stockStatus = getStockStatus(product.stock);
                const placeholderSVG = `<svg class="w-16 h-16 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor"><path d="M24 8h16v8H24z" opacity="0.3"/><path d="M40 6H24c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 10H24V8h16v8zm8 4H16c-2.2 0-4 1.8-4 4v32c0 2.2 1.8 4 4 4h32c2.2 0 4-1.8 4-4V24c0-2.2-1.8-4-4-4zm0 36H16V24h32v32z"/><path d="M32 28c-6.6 0-12 5.4-12 12s5.4 12 12 12 12-5.4 12-12-5.4-12-12-12zm0 20c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8z"/></svg>`;
                const imageContent = product.image_path ? `<img src="../${product.image_path}" alt="${product.name}" class="product-image">` : placeholderSVG;
                
                return `
                    <div class="product-card" data-product-id="${product.product_identifier}">
                        <div class="product-image-container">
                            ${imageContent}
                            <div class="stock-badge ${stockStatus.class}">${stockStatus.text}</div>
                        </div>
                        <div class="product-info text-center">
                            <h4 class="product-name">${product.name}</h4>
                            <p class="text-sm text-gray-500 mb-2">${product.category_name}</p>
                            <p class="text-xs text-gray-400 mb-2">Total Stock: ${product.stock} | Total Item: ${Number(product.item_total)}</p>
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
</body>
</html>