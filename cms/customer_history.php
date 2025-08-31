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
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            transform: scale(0.95) translateY(10px);
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            opacity: 0;
        }
        .modal-overlay.active .modal-content {
            transform: scale(1) translateY(0);
            opacity: 1;
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

    <div id="history-modal" class="modal-overlay fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center p-4 z-50">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-3xl">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 id="history-modal-title" class="text-xl font-bold text-gray-800"></h3>
                <button id="close-history-modal" class="p-2 rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div id="history-modal-content" class="p-6 max-h-[60vh] overflow-y-auto space-y-4">
                </div>
             <div class="p-6 bg-gray-50 rounded-b-xl text-right">
                <button id="close-history-modal-footer" class="px-5 py-2.5 text-sm font-semibold bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">Close</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            const historyModal = document.getElementById('history-modal');
            const closeHistoryModalBtns = [document.getElementById('close-history-modal'), document.getElementById('close-history-modal-footer')];
            const historyModalTitle = document.getElementById('history-modal-title');
            const historyModalContent = document.getElementById('history-modal-content');
            const searchInput = document.getElementById('customer-search');

            const mockData = {
                customers: [ { id: 123456, name: 'Jairo Indoso', lastVisit: '2025-08-28', totalSpent: 12450, visits: 8, avatarInitial: 'JI' }, { id: 123457, name: 'Mark James Pisngot', lastVisit: '2025-08-30', totalSpent: 12450, visits: 8, avatarInitial: 'MP' }, { id: 123458, name: 'Edmalyn Cabales', lastVisit: '2025-08-25', totalSpent: 12450, visits: 8, avatarInitial: 'EC' }, { id: 123459, name: 'Mhae Micah', lastVisit: '2025-08-22', totalSpent: 12450, visits: 8, avatarInitial: 'MM' }, { id: 123450, name: 'Kim Elacion', lastVisit: '2025-08-19', totalSpent: 12450, visits: 8, avatarInitial: 'KE' }, { id: 123451, name: 'Karl Vincent', lastVisit: '2025-08-15', totalSpent: 12450, visits: 8, avatarInitial: 'KV' }, { id: 123452, name: 'Jane Doe', lastVisit: '2025-08-10', totalSpent: 8200, visits: 5, avatarInitial: 'JD' }, { id: 123453, name: 'John Smith', lastVisit: '2025-08-09', totalSpent: 5100, visits: 3, avatarInitial: 'JS' }, ],
                customerHistories: { 123456: [ { date: '2025-08-28', total: 5000, items: ['Amoxicillin 500mg', 'Biogesic'] }, { date: '2025-08-01', total: 4450, items: ['Diatabs', 'Neozep'] }, { date: '2025-07-15', total: 3000, items: ['Paracetamol'] }, ], 123457: [ { date: '2025-08-30', total: 1000, items: ['Solmux'] }, { date: '2025-08-20', total: 11450, items: ['Bioflu', 'Alaxan FR'] }, ], 123458: [ { date: '2025-08-25', total: 7000, items: ['Medicol', 'Tuseran'] }, { date: '2025-08-12', total: 5450, items: ['Kremil-S'] }, ], }
            };

            let currentPage = 1;
            const rowsPerPage = 6;

            function formatCurrency(amount) {
                return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
            }

            function formatDate(dateString) {
                return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            }

            function renderTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const filteredCustomers = mockData.customers.filter(customer =>
                    customer.name.toLowerCase().includes(searchTerm) ||
                    String(customer.id).includes(searchTerm)
                );

                const tableBody = document.getElementById('customer-table-body');
                const paginatedData = filteredCustomers.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);

                if (paginatedData.length === 0) {
                     tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-16 text-gray-500"><div class="flex flex-col items-center gap-4"><i data-lucide="user-x" class="w-16 h-16 text-gray-300"></i><div><p class="font-semibold text-lg">No Customers Found</p><p class="text-sm mt-1">Your search for "${searchTerm}" did not return any results.</p></div></div></td></tr>`;
                     lucide.createIcons();
                } else {
                    tableBody.innerHTML = paginatedData.map(customer => `
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-brand-green-light text-brand-green flex items-center justify-center font-bold text-sm">${customer.avatarInitial}</div>
                                    <div>
                                        <div class="font-semibold text-gray-800">${customer.name}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-mono text-xs">${customer.id}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">${customer.visits}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">${formatCurrency(customer.totalSpent)}</td>
                            <td class="px-6 py-4 text-gray-500">${formatDate(customer.lastVisit)}</td>
                            <td class="px-6 py-4 text-center">
                                <button class="view-history-btn flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-brand-green bg-gray-100 hover:bg-brand-green-light px-4 py-2 rounded-lg transition-all duration-200" data-customer-id="${customer.id}">
                                    <i data-lucide="history" class="w-4 h-4"></i>
                                    <span>History</span>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                     lucide.createIcons();
                }
                renderPagination(filteredCustomers.length);
            }
            
            function renderPagination(totalItems) {
                const paginationContainer = document.getElementById('customer-pagination');
                const totalPages = Math.ceil(totalItems / rowsPerPage);
                const startItem = (currentPage - 1) * rowsPerPage + 1;
                const endItem = Math.min(startItem + rowsPerPage - 1, totalItems);

                if (totalItems <= rowsPerPage) {
                    paginationContainer.innerHTML = '';
                    return;
                }

                let paginationHTML = `
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-semibold text-gray-800">${startItem}</span> to <span class="font-semibold text-gray-800">${endItem}</span> of <span class="font-semibold text-gray-800">${totalItems}</span> results
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
                renderTable();
            }
            
            document.getElementById('customer-pagination').addEventListener('click', e => {
                const target = e.target.closest('button');
                if (!target) return;
                
                if (target.classList.contains('page-btn')) changePage(Number(target.dataset.page));
                if (target.classList.contains('prev-btn')) changePage(currentPage - 1);
                if (target.classList.contains('next-btn')) changePage(currentPage + 1);
            });

            document.getElementById('customer-table-body').addEventListener('click', e => {
                const button = e.target.closest('.view-history-btn');
                if (button) {
                    const customerId = button.dataset.customerId;
                    const customer = mockData.customers.find(c => c.id == customerId);
                    const history = mockData.customerHistories[customerId] || [];

                    historyModalTitle.textContent = `Purchase History for ${customer.name}`;
                    if (history.length > 0) {
                        historyModalContent.innerHTML = history.map(h => `
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-2 gap-2">
                                    <div class="font-semibold text-gray-800 flex items-center gap-2">
                                        <i data-lucide="calendar" class="w-4 h-4 text-gray-500"></i> ${formatDate(h.date)}
                                    </div>
                                    <div class="font-bold text-brand-green text-lg">${formatCurrency(h.total)}</div>
                                </div>
                                <div class="text-sm text-gray-600 pt-2 border-t border-gray-200 mt-2">
                                    <span class="font-semibold text-gray-700">Items Purchased:</span> ${h.items.join(', ')}
                                </div>
                            </div>
                        `).join('');
                    } else {
                        historyModalContent.innerHTML = '<div class="text-center py-12 text-gray-500 flex flex-col items-center gap-4"><i data-lucide="shopping-basket" class="w-12 h-12 text-gray-300"></i><div><p class="font-semibold">No History Found</p><p class="text-sm">This customer has no recorded transactions.</p></div></div>';
                    }
                    historyModal.classList.add('active');
                    lucide.createIcons();
                }
            });

            closeHistoryModalBtns.forEach(btn => btn.addEventListener('click', () => historyModal.classList.remove('active')));
            searchInput.addEventListener('input', () => {
                currentPage = 1;
                renderTable();
            });

            renderTable();
        });
    </script>
</body>
</html>