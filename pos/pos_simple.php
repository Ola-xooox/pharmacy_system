<?php
session_start();
// Redirect if not logged in or not a POS user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pos') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Test - No Dark Mode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .product-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            cursor: pointer;
        }
        .product-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100 p-4">
    <h1 class="text-2xl font-bold mb-4">Simple POS Test (No Dark Mode)</h1>
    
    <div id="product-grid" class="grid grid-cols-3 gap-4"></div>
    
    <script>
        async function loadProducts() {
            try {
                console.log('Fetching products...');
                const response = await fetch('../api/get_products.php?status=available');
                const products = await response.json();
                console.log('Products loaded:', products);
                
                const grid = document.getElementById('product-grid');
                
                if (products.length === 0) {
                    grid.innerHTML = '<p class="col-span-3 text-center text-gray-500">No products found</p>';
                    return;
                }
                
                grid.innerHTML = products.map(p => `
                    <div class="product-card">
                        <h3 class="font-bold text-lg">${p.name}</h3>
                        <p class="text-gray-600">${p.category_name}</p>
                        <p class="text-sm text-gray-500">Stock: ${p.stock}</p>
                        <p class="text-green-600 font-bold text-xl">₱${Number(p.price).toFixed(2)}</p>
                    </div>
                `).join('');
                
                console.log('Products rendered successfully');
            } catch (error) {
                console.error('Error loading products:', error);
                document.getElementById('product-grid').innerHTML = 
                    '<p class="col-span-3 text-center text-red-500">Error: ' + error.message + '</p>';
            }
        }
        
        // Load products on page load
        loadProducts();
    </script>
</body>
</html>
