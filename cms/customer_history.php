<?php
session_start();
// Redirect if not logged in or not a CMS user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cms') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - MJ Pharmacy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            green: '#01A74F',
                            'green-light': '#E6F6EC',
                            'gray': '#F3F4F6',
                        },
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
    </style>
  <link rel="icon" type="image/x-icon" href="../mjpharmacy.logo.jpg">
</head>
<body class="bg-brand-gray">
    <div class="flex flex-col min-h-screen">
        <?php include 'cms_header.php'; ?>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">

                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="p-6 bg-brand-green text-white">
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold">Customer Relations</h1>
                        </div>
                        <div class="flex items-center gap-3 mt-2 ml-1">
                            <p class="text-white/80">View and manage customer information.</p>
                        </div>
                    </div>

                    <div class="p-4 bg-gray-50 border-y border-gray-200">
                        <div class="relative w-full">
                             <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
                             <input type="text" id="customer-search" placeholder="Search by name or ID..." class="w-full pl-11 pr-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green">
                        </div>
                    </div>


                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50/75 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4 text-left">Customer</th>
                                    <th class="px-6 py-4 text-left">ID No.</th>
                                    <th class="px-6 py-4 text-center">Total Visits</th>
                                    <th class="px-6 py-4 text-left">Total Spent</th>
                                    <th class="px-6 py-4 text-left">Last Visit</th>
                                    <th class="px-6 py-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="customer-table-body" class="text-gray-700 divide-y divide-gray-200">
                                </tbody>
                        </table>
                    </div>
                    <div id="customer-pagination" class="p-6 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                        </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            const searchInput = document.getElementById('customer-search');
            const tableBody = document.getElementById('customer-table-body');
            const paginationContainer = document.getElementById('customer-pagination');

            let currentPage = 1;
            let currentSearch = '';
            let debounceTimer;

            async function fetchCustomerHistory(page = 1, search = '') {
                try {
                    // **MODIFIED URL to point to the new single API file**
                    const response = await fetch(`../api/customer_api.php?action=get_history&page=${page}&search=${encodeURIComponent(search)}`);
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`Network response was not ok: ${response.statusText}. Server says: ${errorText}`);
                    }
                    const data = await response.json();
                    renderTable(data.customers);
                    renderPagination(data);
                } catch (error) {
                    console.error('Fetch error:', error);
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-16 text-red-500">Could not load customer data. Please check the browser console (F12) for more details.</td></tr>`;
                }
            }

            function formatCurrency(amount) {
                return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
            }

            function formatDate(dateString) {
                return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            }

            function getInitials(name) {
                if (!name) return '';
                const parts = name.split(' ').filter(p => p);
                if (parts.length > 1) {
                    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
                }
                return name.substring(0, 2).toUpperCase();
            }

            function renderTable(customers) {
                if (!customers || customers.length === 0) {
                     tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-16 text-gray-500"><div class="flex flex-col items-center gap-4"><i data-lucide="user-x" class="w-16 h-16 text-gray-300"></i><div><p class="font-semibold text-lg">No Customers Found</p><p class="text-sm mt-1">Try adjusting your search or complete a new transaction in the POS.</p></div></div></td></tr>`;
                } else {
                    tableBody.innerHTML = customers.map(customer => `
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-brand-green-light text-brand-green flex items-center justify-center font-bold text-sm">${getInitials(customer.customer_name)}</div>
                                    <div>
                                        <div class="font-semibold text-gray-800">${customer.customer_name}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-mono text-xs">${customer.customer_id_no || 'N/A'}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">${customer.total_visits}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">${formatCurrency(customer.total_spent)}</td>
                            <td class="px-6 py-4 text-gray-500">${formatDate(customer.last_visit)}</td>
                            <td class="px-6 py-4 text-center">
                                <button class="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-brand-green bg-gray-100 hover:bg-brand-green-light px-4 py-2 rounded-lg transition-all duration-200">
                                    <i data-lucide="history" class="w-4 h-4"></i>
                                    <span>History</span>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
                lucide.createIcons();
            }
            
            function renderPagination({ totalPages, currentPage, totalResults, limit }) {
                if (!totalResults || totalResults <= limit) {
                    paginationContainer.innerHTML = '';
                    return;
                }
                const startItem = (currentPage - 1) * limit + 1;
                const endItem = Math.min(startItem + limit - 1, totalResults);
                
                let paginationHTML = `
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-semibold text-gray-800">${startItem}</span> to <span class="font-semibold text-gray-800">${endItem}</span> of <span class="font-semibold text-gray-800">${totalResults}</span> results
                    </div>
                    <div class="flex items-center gap-1">
                `;
                
                paginationHTML += `<button class="prev-btn p-2 rounded-lg ${currentPage === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-200 transition-colors'}" ${currentPage === 1 ? 'disabled' : ''}><i data-lucide="chevron-left" class="w-5 h-5"></i></button>`;

                for (let i = 1; i <= totalPages; i++) {
                     paginationHTML += `<button class="page-btn w-9 h-9 rounded-lg text-sm font-semibold ${i === currentPage ? 'bg-brand-green text-white' : 'text-gray-600 hover:bg-gray-200 transition-colors'}" data-page="${i}">${i}</button>`;
                }
                
                paginationHTML += `<button class="next-btn p-2 rounded-lg ${currentPage === totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-200 transition-colors'}" ${currentPage === totalPages ? 'disabled' : ''}><i data-lucide="chevron-right" class="w-5 h-5"></i></button>`;

                paginationHTML += `</div>`;
                paginationContainer.innerHTML = paginationHTML;
                lucide.createIcons();
            }

            function changePage(newPage) {
                currentPage = newPage;
                fetchCustomerHistory(currentPage, currentSearch);
            }
            
            paginationContainer.addEventListener('click', e => {
                const target = e.target.closest('button');
                if (!target) return;
                
                const totalPages = document.querySelectorAll('.page-btn').length;
                if (target.classList.contains('page-btn')) changePage(Number(target.dataset.page));
                if (target.classList.contains('prev-btn') && currentPage > 1) changePage(currentPage - 1);
                if (target.classList.contains('next-btn') && currentPage < totalPages) changePage(currentPage + 1);
            });

            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentSearch = searchInput.value;
                    currentPage = 1;
                    fetchCustomerHistory(currentPage, currentSearch);
                }, 300); // 300ms delay
            });

            // Initial load
            fetchCustomerHistory();
        });
    </script>
</body>
</html>