<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Purchase History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary-green: #01A74F;
            --light-gray: #f3f4f6;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--light-gray);
            color: #1f2937;
        }
        
        .sidebar {
            background-color: var(--primary-green);
            transition: width 0.3s ease-in-out, transform 0.3s ease-in-out;
        }

        @media (max-width: 767px) {
            .sidebar {
                width: 16rem;
                transform: translateX(-100%);
                position: fixed;
                height: 100%;
                z-index: 50;
            }
            .sidebar.open-mobile {
                transform: translateX(0);
            }
            .overlay {
                transition: opacity 0.3s ease-in-out;
            }
        }

        @media (min-width: 768px) {
            .sidebar {
                width: 5rem;
            }
            .sidebar.open-desktop {
                width: 16rem;
            }
            .sidebar .nav-text {
                opacity: 0;
                visibility: hidden;
                width: 0; 
                transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease;
                white-space: nowrap;
                overflow: hidden;
            }
            .sidebar.open-desktop .nav-text {
                opacity: 1;
                visibility: visible;
                width: auto; 
                transition: opacity 0.2s ease 0.1s; 
            }
            .sidebar .nav-link {
                justify-content: center;
                gap: 0;
            }
            .sidebar.open-desktop .nav-link {
                justify-content: flex-start;
                gap: 1rem;
            }
        }

        .nav-link { color: rgba(255, 255, 255, 0.8); }
        .nav-link svg { color: white; }
        .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); }
        .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; }
        .nav-link.active svg { color: var(--primary-green); }

        .category-btn { white-space: nowrap; padding: 0.5rem 1rem; border-radius: 9999px; background-color: #e5e7eb; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; }
        .category-btn:hover { background-color: #d1d5db; }
        .category-btn.active { background-color: var(--primary-green); color: white; border-color: var(--primary-green); }
        .table-header { background-color: var(--primary-green); color: white; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <aside id="sidebar" class="sidebar flex flex-col md:relative">
            <div class="p-4 flex items-center gap-3 border-b border-white/20 h-[73px] flex-shrink-0">
                <img src="https://i.imgur.com/uDbzYp0.png" alt="Logo" class="w-10 h-10 rounded-full bg-white object-cover shadow-md flex-shrink-0">
                <h1 class="text-xl font-bold tracking-tight text-white nav-text">INVENTORY</h1>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <a href="products.html" class="nav-link flex items-center px-4 py-3 rounded-lg">
                    <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    <span class="nav-text">Products</span>
                </a>
                <a href="inventory-tracking.html" class="nav-link flex items-center px-4 py-3 rounded-lg">
                    <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <span class="nav-text">Inventory Tracking</span>
                </a>
                <a href="purchase-history.html" class="nav-link active flex items-center px-4 py-3 rounded-lg">
                    <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="nav-text">Purchase History</span>
                </a>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center p-4">
                    <button id="sidebar-toggle-btn" class="p-2 rounded-full hover:bg-gray-100">
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div class="flex items-center gap-4 ml-auto">
                        <div class="hidden md:flex items-center gap-2 text-sm bg-gray-100 px-3 py-1.5 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <span id="date-time"></span>
                        </div>
                        <div class="p-2 rounded-full hover:bg-gray-100 cursor-pointer"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6 text-gray-600"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg></div>
                        <div class="p-2 rounded-full hover:bg-gray-100 cursor-pointer"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6 text-gray-600"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                <h2 class="text-3xl font-bold mb-4">Purchase History</h2>
                
                <div class="mb-4">
                    <div class="relative">
                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input type="text" placeholder="Search products by name..." class="w-full pl-10 pr-4 py-2.5 rounded-full border bg-white focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>

                <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2">
                    <button class="category-btn active">All Products</button>
                    <button class="category-btn bg-white">Pain Relief</button>
                    <button class="category-btn bg-white">Cold & Flu</button>
                    <button class="category-btn bg-white">Vitamins</button>
                    <button class="category-btn bg-white">Personal Care</button>
                </div>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 flex items-center gap-3 table-header">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <h3 class="font-bold text-lg">8/3/25</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">#</th>
                                    <th scope="col" class="px-6 py-3">Brand Name</th>
                                    <th scope="col" class="px-6 py-3">Quantity</th>
                                    <th scope="col" class="px-6 py-3">Total Price</th>
                                    <th scope="col" class="px-6 py-3">Date</th>
                                </tr>
                            </thead>
                            <tbody id="history-table-body">
                                </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const dateTimeEl = document.getElementById('date-time');
            const tableBody = document.getElementById('history-table-body');

            // --- Sidebar Toggle ---
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

            // --- Date & Time ---
            function updateDateTime() {
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                dateTimeEl.textContent = now.toLocaleDateString('en-US', options);
            }
            updateDateTime();
            setInterval(updateDateTime, 60000);

            // --- Purchase History Data ---
            const purchaseHistory = [
                { id: 1, brandName: "Ascorbic Acid 500mg TAB CEVIT", quantity: 2, totalPrice: 24.00, date: "8/3/25" },
                { id: 2, brandName: "Na Ascorbate 568.18mg CAP CEVITA", quantity: 7, totalPrice: 74.00, date: "8/3/25" },
                { id: 3, brandName: "NAC 200mg PWDR ACTEINSAPH", quantity: 13, totalPrice: 25.00, date: "8/3/25" },
                { id: 4, brandName: "NAC 600mg PWDR FLECHEM", quantity: 21, totalPrice: 27.00, date: "8/3/25" },
                { id: 5, brandName: "Calcium 500mg TAB AMBICAL", quantity: 7, totalPrice: 75.00, date: "8/3/25" },
                { id: 6, brandName: "Calcium + D3 TAB AMBICAL PLUS", quantity: 5, totalPrice: 24.00, date: "8/3/25" },
                { id: 7, brandName: "Caltrate Silver TAB 30s BOT", quantity: 23, totalPrice: 25.00, date: "8/3/25" }
            ];

            function renderTable() {
                tableBody.innerHTML = purchaseHistory.map((item, index) => `
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">${index + 1}</td>
                        <td class="px-6 py-4 font-semibold text-gray-700">${item.brandName}</td>
                        <td class="px-6 py-4">${item.quantity}</td>
                        <td class="px-6 py-4 font-semibold text-gray-700">
                           ₱${item.totalPrice.toFixed(2)}
                        </td>
                        <td class="px-6 py-4">${item.date}</td>
                    </tr>
                `).join('');
            }
            
            // Initial render
            renderTable();
        });
    </script>
</body>
</html>