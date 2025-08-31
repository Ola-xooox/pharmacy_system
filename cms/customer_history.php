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
        .modal-overlay {
            position: fixed; inset: 0; background-color: rgba(0,0,0,0.5);
            display: flex; align-items: center; justify-content: center;
            z-index: 50; opacity: 0; transition: opacity 0.2s ease-in-out;
            pointer-events: none;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-content {
            background-color: white; border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%; transform: scale(0.95); transition: transform 0.2s ease-in-out;
            max-height: 90vh; overflow-y: auto;
        }
        .modal-overlay.active .modal-content { transform: scale(1); }
        @media print {
            body * { visibility: hidden; }
            #receipt-modal-content, #receipt-modal-content * { visibility: visible; }
            #receipt-modal-content { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border-radius: 0; }
            .no-print { display: none; }
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

    <div id="transaction-history-modal" class="modal-overlay">
        <div class="modal-content max-w-3xl">
            <div class="flex justify-between items-center p-4 border-b">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Transaction History</h3>
                    <p id="history-customer-name" class="text-sm text-gray-500"></p>
                </div>
                <button id="close-history-modal" class="p-2 rounded-full hover:bg-gray-100 text-2xl leading-none font-bold">&times;</button>
            </div>
            <div class="p-4 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left w-2/5">Product(s)</th>
                            <th class="px-4 py-3 text-left">Receipt #</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-right">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody id="transaction-list-body" class="divide-y divide-gray-200">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="receipt-modal" class="modal-overlay">
        <div id="receipt-modal-content" class="modal-content !max-w-sm">
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
                </div>
                <div class="my-6 border-t border-dashed"></div>
                <div id="receipt-items-container">
                    <div class="grid grid-cols-5 gap-2 text-sm font-bold mb-2">
                        <span class="col-span-2">Item</span>
                        <span class="text-center">Qty</span>
                        <span class="text-right">Price</span>
                        <span class="text-right">Total</span>
                    </div>
                    <div id="receipt-items" class="text-sm space-y-1">
                    </div>
                </div>
                 <div class="my-6 border-t border-dashed"></div>
                 <div class="text-sm space-y-1">
                    <div class="flex justify-between font-bold text-lg"><span class="text-gray-800">Total:</span><span id="receipt-total" class="text-brand-green">₱0.00</span></div>
                 </div>
                 <div class="text-center mt-8 text-xs text-gray-500">
                    <p>Thank you for your purchase!</p>
                 </div>
                 <div class="mt-8 flex gap-3 no-print">
                    <button id="print-receipt-btn" class="flex-1 flex items-center justify-center gap-2 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition-all duration-200"><i data-lucide="printer" class="w-4 h-4"></i><span>Print</span></button>
                    <button id="close-receipt-modal" class="flex-1 flex items-center justify-center gap-2 text-sm font-semibold text-white bg-brand-green hover:bg-opacity-90 px-4 py-2 rounded-lg transition-all duration-200">Close</button>
                 </div>
             </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            const searchInput = document.getElementById('customer-search');
            const tableBody = document.getElementById('customer-table-body');
            const paginationContainer = document.getElementById('customer-pagination');
            
            const historyModal = document.getElementById('transaction-history-modal');
            const receiptModal = document.getElementById('receipt-modal');
            const closeHistoryModalBtn = document.getElementById('close-history-modal');
            const closeReceiptModalBtn = document.getElementById('close-receipt-modal');
            const printReceiptBtn = document.getElementById('print-receipt-btn');
            const transactionListBody = document.getElementById('transaction-list-body');

            let currentPage = 1;
            let currentSearch = '';
            let debounceTimer;

            async function fetchCustomerHistory(page = 1, search = '') {
                try {
                    const response = await fetch(`../api/customer_api.php?action=get_history&page=${page}&search=${encodeURIComponent(search)}`);
                    if (!response.ok) throw new Error('Network response was not ok');
                    const data = await response.json();
                    renderTable(data.customers);
                    renderPagination(data);
                } catch (error) {
                    console.error('Fetch error:', error);
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-16 text-red-500">Could not load customer data.</td></tr>`;
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
                return parts.length > 1 ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase() : name.substring(0, 2).toUpperCase();
            }

            function renderTable(customers) {
                if (!customers || customers.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-16 text-gray-500"><div class="flex flex-col items-center gap-4"><i data-lucide="user-x" class="w-16 h-16 text-gray-300"></i><div><p class="font-semibold text-lg">No Customers Found</p><p class="text-sm mt-1">Try adjusting your search.</p></div></div></td></tr>`;
                } else {
                    tableBody.innerHTML = customers.map(customer => `
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-brand-green-light text-brand-green flex items-center justify-center font-bold text-sm">${getInitials(customer.customer_name)}</div>
                                    <div><div class="font-semibold text-gray-800" data-customer-name="${customer.customer_name}">${customer.customer_name}</div></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-mono text-xs">${customer.customer_id_no || 'N/A'}</td>
                            <td class="px-6 py-4 text-center"><span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">${customer.total_visits}</span></td>
                            <td class="px-6 py-4 font-semibold text-gray-900">${formatCurrency(customer.total_spent)}</td>
                            <td class="px-6 py-4 text-gray-500">${formatDate(customer.last_visit)}</td>
                            <td class="px-6 py-4 text-center">
                                <button data-customer-id="${customer.id}" class="view-history-btn flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-brand-green bg-gray-100 hover:bg-brand-green-light px-4 py-2 rounded-lg transition-all duration-200">
                                    <i data-lucide="history" class="w-4 h-4"></i><span>History</span>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
                lucide.createIcons();
            }
            
            function renderPagination({ totalPages, currentPage, totalResults, limit }) {
                 if (!totalPages || totalPages <= 1) {
                    paginationContainer.innerHTML = ''; return;
                }
                const startItem = (currentPage - 1) * limit + 1;
                const endItem = Math.min(startItem + limit - 1, totalResults);
                
                let paginationHTML = `<div class="text-sm text-gray-600">Showing <b>${startItem}</b> to <b>${endItem}</b> of <b>${totalResults}</b></div><div class="flex items-center gap-1">`;
                paginationHTML += `<button class="prev-btn p-2 rounded-lg ${currentPage === 1 ? 'text-gray-300 cursor-not-allowed' : 'hover:bg-gray-200'}" ${currentPage === 1 ? 'disabled' : ''}><i data-lucide="chevron-left" class="w-5 h-5"></i></button>`;
                for (let i = 1; i <= totalPages; i++) {
                     paginationHTML += `<button class="page-btn w-9 h-9 rounded-lg text-sm font-semibold ${i === currentPage ? 'bg-brand-green text-white' : 'hover:bg-gray-200'}" data-page="${i}">${i}</button>`;
                }
                paginationHTML += `<button class="next-btn p-2 rounded-lg ${currentPage === totalPages ? 'text-gray-300 cursor-not-allowed' : 'hover:bg-gray-200'}" ${currentPage === totalPages ? 'disabled' : ''}><i data-lucide="chevron-right" class="w-5 h-5"></i></button>`;
                paginationHTML += `</div>`;
                paginationContainer.innerHTML = paginationHTML;
                lucide.createIcons();
            }

            function changePage(newPage) {
                currentPage = newPage;
                fetchCustomerHistory(currentPage, currentSearch);
            }

            async function openHistoryModal(customerId, customerName) {
                document.getElementById('history-customer-name').textContent = customerName;
                transactionListBody.innerHTML = `<tr><td colspan="4" class="text-center p-8">Loading...</td></tr>`;
                historyModal.classList.add('active');

                try {
                    const response = await fetch(`../api/customer_api.php?action=get_customer_transactions&id=${customerId}`);
                    const transactions = await response.json();

                    if (transactions.length === 0) {
                        transactionListBody.innerHTML = `<tr><td colspan="4" class="text-center p-8 text-gray-500">No transactions found.</td></tr>`;
                        return;
                    }
                    
                    transactionListBody.innerHTML = transactions.map(tx => {
                        // UPDATED: Display product list correctly
                        const productList = tx.items.length > 0
                            ? tx.items.map(item => `<div class="truncate">${item.product_name} (x${item.quantity})</div>`).join('')
                            : 'N/A';
                        
                        return `
                        <tr class="hover:bg-gray-50 cursor-pointer" data-transaction-id="${tx.id}" data-total="${tx.total_amount}" data-date="${tx.transaction_date}" data-customer-name="${customerName}">
                            <td class="px-4 py-3 text-xs">${productList}</td>
                            <td class="px-4 py-3 font-mono text-xs">RX${tx.id}</td>
                            <td class="px-4 py-3 text-xs">${new Date(tx.transaction_date).toLocaleString()}</td>
                            <td class="px-4 py-3 text-right font-semibold">${formatCurrency(tx.total_amount)}</td>
                        </tr>
                    `}).join('');
                } catch (error) {
                    console.error("Failed to fetch transaction history:", error);
                    transactionListBody.innerHTML = `<tr><td colspan="4" class="text-center p-8 text-red-500">Could not load history.</td></tr>`;
                }
            }
            
            // UPDATED: Function to open receipt by fetching details using transaction ID
            async function openReceiptModal({ transactionId, total, date, customerName }) {
                document.getElementById('receipt-date').textContent = new Date(date).toLocaleString();
                document.getElementById('receipt-no').textContent = `RX${transactionId}`;
                document.getElementById('receipt-customer').textContent = customerName;
                document.getElementById('receipt-total').textContent = formatCurrency(total);
                document.getElementById('receipt-items').innerHTML = `<div class="text-center p-4">Loading items...</div>`;
                receiptModal.classList.add('active');

                try {
                    // Fetch receipt details using the reliable transaction ID
                    const response = await fetch(`../api/customer_api.php?action=get_receipt_details&id=${transactionId}`);
                    const result = await response.json();
                    
                    if (result.success && result.items.length > 0) {
                        const itemsHTML = result.items.map(item => `
                            <div class="grid grid-cols-5 gap-2">
                                <span class="col-span-2 truncate">${item.product_name}</span>
                                <span class="text-center">${item.quantity}</span>
                                <span class="text-right">${formatCurrency(item.total_price / item.quantity)}</span>
                                <span class="text-right font-medium">${formatCurrency(item.total_price)}</span>
                            </div>`).join('');
                        document.getElementById('receipt-items').innerHTML = itemsHTML;
                    } else {
                        throw new Error(result.message || 'Could not retrieve items.');
                    }
                } catch (error) {
                    console.error("Failed to fetch receipt details:", error);
                    document.getElementById('receipt-items').innerHTML = `<div class="text-center p-4 text-red-500">${error.message}</div>`;
                }
                 lucide.createIcons();
            }

            tableBody.addEventListener('click', e => {
                const historyBtn = e.target.closest('.view-history-btn');
                if (historyBtn) {
                    const customerId = historyBtn.dataset.customerId;
                    const customerName = historyBtn.closest('tr').querySelector('[data-customer-name]').dataset.customerName;
                    openHistoryModal(customerId, customerName);
                }
            });
            
            // UPDATED: Event listener for transaction rows to open receipt
            transactionListBody.addEventListener('click', e => {
                const transactionRow = e.target.closest('tr');
                if (transactionRow && transactionRow.dataset.transactionId) {
                    const data = {
                        transactionId: transactionRow.dataset.transactionId,
                        total: transactionRow.dataset.total,
                        date: transactionRow.dataset.date,
                        customerName: transactionRow.dataset.customerName
                    };
                    openReceiptModal(data);
                }
            });

            closeHistoryModalBtn.addEventListener('click', () => historyModal.classList.remove('active'));
            closeReceiptModalBtn.addEventListener('click', () => receiptModal.classList.remove('active'));
            printReceiptBtn.addEventListener('click', () => window.print());

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
                }, 300);
            });

            fetchCustomerHistory();
        });
    </script>
</body>
</html>