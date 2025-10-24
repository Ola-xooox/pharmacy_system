<?php
session_start();
// Redirect if not logged in or not an inventory user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'inventory') {
    header("Location: ../index.php");
    exit();
}
require '../db_connect.php';
require_once 'darkmode.php';
$currentPage = 'history';

// Fetch all purchase history records for movement calculation
$all_history_result = $conn->query("SELECT * FROM purchase_history ORDER BY transaction_date DESC");
$all_purchase_history = [];
while($row = $all_history_result->fetch_assoc()) {
    $all_purchase_history[] = $row;
}

// Calculate product movement speed
function calculateProductMovement($product_name, $purchase_history) {
    $product_sales = array_filter($purchase_history, function($item) use ($product_name) {
        return $item['product_name'] === $product_name;
    });
    
    if (empty($product_sales)) {
        return 'slow';
    }
    
    $total_quantity = array_sum(array_column($product_sales, 'quantity'));
    $sales_count = count($product_sales);
    
    // Get date range for sales
    $dates = array_column($product_sales, 'transaction_date');
    $earliest_date = min($dates);
    $latest_date = max($dates);
    
    $earliest_timestamp = strtotime($earliest_date);
    $current_timestamp = time();
    
    // Calculate days since first sale
    $days_active = max(1, ceil(($current_timestamp - $earliest_timestamp) / (60 * 60 * 24)));
    
    // Calculate average sales per day
    $avg_sales_per_day = $total_quantity / $days_active;
    
    // Determine movement speed based on total quantity sold
    // Fast: High volume sales (10+ units sold)
    if ($total_quantity >= 10) {
        return 'fast';
    } 
    // Medium: Moderate volume sales (5-9 units sold)
    elseif ($total_quantity >= 5) {
        return 'medium';
    } 
    // Slow: Low volume sales (1-4 units sold)
    else {
        return 'slow';
    }
}

// Group purchase history by product name and aggregate data
$grouped_history_result = $conn->query("
    SELECT 
        product_name,
        SUM(quantity) as total_quantity,
        SUM(total_price) as total_sales,
        COUNT(*) as transaction_count,
        MIN(transaction_date) as first_sale_date,
        MAX(transaction_date) as last_sale_date
    FROM purchase_history 
    GROUP BY product_name 
    ORDER BY MAX(transaction_date) DESC
");

$purchase_history = [];
while($row = $grouped_history_result->fetch_assoc()) {
    $row['movement_status'] = calculateProductMovement($row['product_name'], $all_purchase_history);
    $purchase_history[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $inventoryDarkMode['is_dark'] ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase History - Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <style>
        :root { --primary-green: #01A74F; --light-gray: #f3f4f6; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: #1f2937; }
        .sidebar { background-color: var(--primary-green); transition: width 0.3s ease-in-out, transform 0.3s ease-in-out; }
        @media (max-width: 767px) { .sidebar { width: 16rem; transform: translateX(-100%); position: fixed; height: 100%; z-index: 50; } .sidebar.open-mobile { transform: translateX(0); } .overlay { transition: opacity 0.3s ease-in-out; } }
        @media (min-width: 768px) { .sidebar { width: 5rem; } .sidebar.open-desktop { width: 16rem; } .sidebar .nav-text { opacity: 0; visibility: hidden; width: 0; transition: opacity 0.1s ease, visibility 0.1s ease, width 0.1s ease; white-space: nowrap; overflow: hidden; } .sidebar.open-desktop .nav-text { opacity: 1; visibility: visible; width: auto; transition: opacity 0.2s ease 0.1s; } .sidebar .nav-link { justify-content: center; gap: 0; } .sidebar.open-desktop .nav-link { justify-content: flex-start; gap: 1rem; } }
        .nav-link { color: rgba(255, 255, 255, 0.8); } .nav-link svg { color: white; } .nav-link:hover { color: white; background-color: rgba(255, 255, 255, 0.2); } .nav-link.active { background-color: white; color: var(--primary-green); font-weight: 600; } .nav-link.active svg { color: var(--primary-green); }
        .table-header { background-color: #f9fafb; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; }
        
        /* Custom Date Picker Styling */
        #date-input-display {
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        #date-input-display:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            border-color: #01A74F;
        }
        .dark #date-input-display {
            background-color: #374151;
            border-color: #4b5563;
            color: white;
        }
        #clear-date-btn {
            transition: all 0.2s ease;
        }
        #clear-date-btn:hover {
            background-color: #ef4444;
        }
        
        /* Custom Calendar Dropdown */
        .calendar-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
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
        .calendar-dropdown.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
        }
        .dark .calendar-dropdown {
            background: #1f2937;
            border-color: #01A74F;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .dark .calendar-header {
            border-bottom-color: #374151;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.25rem;
        }
        .calendar-day-name {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            padding: 0.5rem 0;
        }
        .dark .calendar-day-name {
            color: #9ca3af;
        }
        .calendar-day {
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
        .dark .calendar-day {
            color: #d1d5db;
        }
        .calendar-day:hover {
            background-color: #e0f2e9;
            color: #01A74F;
            transform: scale(1.05);
        }
        .dark .calendar-day:hover {
            background-color: #065f46;
            color: white;
        }
        .calendar-day.today {
            border: 2px solid #01A74F;
            font-weight: 600;
        }
        .calendar-day.selected {
            background-color: #01A74F;
            color: white;
            font-weight: 600;
        }
        .calendar-day.other-month {
            color: #d1d5db;
        }
        .dark .calendar-day.other-month {
            color: #4b5563;
        }
        .nav-btn {
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
        }
        .nav-btn:hover {
            background: #01A74F;
            color: white;
            transform: scale(1.1);
        }
        .dark .nav-btn {
            background: #374151;
            color: #d1d5db;
        }
        .dark .nav-btn:hover {
            background: #01A74F;
            color: white;
        }
        
        /* Movement Status Badges */
        .movement-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .movement-fast {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .movement-medium {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .movement-slow {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .dark .movement-fast {
            background-color: #14532d;
            color: #bbf7d0;
            border-color: #166534;
        }
        .dark .movement-medium {
            background-color: #451a03;
            color: #fde68a;
            border-color: #92400e;
        }
        .dark .movement-slow {
            background-color: #7f1d1d;
            color: #fecaca;
            border-color: #991b1b;
        }
    </style>
    <?php echo $inventoryDarkMode['styles']; ?>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'partials/sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            
            <?php include 'partials/header.php'; ?>

            <main class="flex-1 overflow-y-auto p-6">
                <h2 class="text-3xl font-bold mb-6 flex items-center gap-3">
                    <i class="ph ph-clock-counter-clockwise text-green-600"></i>
                    Purchase History
                </h2>
                
                <div class="mb-4 flex flex-col md:flex-row gap-4">
                    <div class="relative flex-1">
                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input type="text" id="search-input" placeholder="Search by product name..." class="w-full pl-10 pr-4 py-2.5 rounded-lg border bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ph ph-trend-up text-gray-400"></i>
                        </div>
                        <select id="movement-filter" class="pl-10 pr-8 py-2.5 rounded-lg border bg-white focus:outline-none focus:ring-2 focus:ring-green-500 appearance-none cursor-pointer">
                            <option value="">All Movement Types</option>
                            <option value="fast">Fast Moving</option>
                            <option value="medium">Medium Moving</option>
                            <option value="slow">Slow Moving</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="ph ph-caret-down text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="ph ph-funnel text-green-600"></i>
                        Filter by Date
                    </label>
                    <div class="flex gap-2 items-center">
                        <div class="relative flex-1 md:flex-initial">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ph ph-calendar-blank text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <input type="text" readonly id="date-input-display" placeholder="Select a date" class="w-full md:w-64 pl-10 pr-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 cursor-pointer">
                            <input type="hidden" id="date-picker">
                            
                            <!-- Custom Calendar Dropdown -->
                            <div id="calendar-dropdown" class="calendar-dropdown">
                                <div class="calendar-header">
                                    <button type="button" class="nav-btn" id="prev-month">
                                        <i class="ph ph-caret-left"></i>
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <select id="month-select" class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 border-none font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
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
                                        <select id="year-select" class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 border-none font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></select>
                                    </div>
                                    <button type="button" class="nav-btn" id="next-month">
                                        <i class="ph ph-caret-right"></i>
                                    </button>
                                </div>
                                <div class="calendar-grid" id="calendar-days-header">
                                    <div class="calendar-day-name">Su</div>
                                    <div class="calendar-day-name">Mo</div>
                                    <div class="calendar-day-name">Tu</div>
                                    <div class="calendar-day-name">We</div>
                                    <div class="calendar-day-name">Th</div>
                                    <div class="calendar-day-name">Fr</div>
                                    <div class="calendar-day-name">Sa</div>
                                </div>
                                <div class="calendar-grid" id="calendar-days"></div>
                                <div class="flex gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                    <button type="button" id="today-btn" class="flex-1 px-3 py-2 bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800 text-green-700 dark:text-green-200 rounded-lg text-sm font-medium transition-colors">
                                        Today
                                    </button>
                                    <button type="button" id="clear-calendar-btn" class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-colors">
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button id="clear-date-btn" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-600 hover:bg-red-500 dark:hover:bg-red-600 text-gray-700 dark:text-gray-200 hover:text-white rounded-lg font-medium transition-all flex items-center gap-2 shadow-sm" title="Clear date filter">
                            <i class="ph ph-x-circle"></i>
                            <span class="hidden sm:inline">Clear</span>
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs table-header">
                                <tr>
                                    <th scope="col" class="px-6 py-3">#</th>
                                    <th scope="col" class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-package"></i>
                                            Product Name
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-stack"></i>
                                            Total Sold
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-currency-circle-dollar"></i>
                                            Total Sales
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-shopping-cart"></i>
                                            Transactions
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-calendar"></i>
                                            Last Sale
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-trend-up"></i>
                                            Movement
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="history-table-body">
                                <!-- Data will be injected here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 bg-gray-50 border-t flex justify-end gap-8 font-semibold">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-coins text-green-600"></i>
                            <span>Total Sales:</span>
                            <span id="total-price" class="text-green-600">₱0.00</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

    <script>
        const purchaseHistory = <?php echo json_encode($purchase_history); ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const dateTimeEl = document.getElementById('date-time');
            const tableBody = document.getElementById('history-table-body');
            const searchInput = document.getElementById('search-input');
            const movementFilter = document.getElementById('movement-filter');
            const datePicker = document.getElementById('date-picker');
            const dateInputDisplay = document.getElementById('date-input-display');
            const calendarDropdown = document.getElementById('calendar-dropdown');
            const prevMonthBtn = document.getElementById('prev-month');
            const nextMonthBtn = document.getElementById('next-month');
            const monthSelect = document.getElementById('month-select');
            const yearSelect = document.getElementById('year-select');
            const calendarDays = document.getElementById('calendar-days');
            const todayBtn = document.getElementById('today-btn');
            const clearCalendarBtn = document.getElementById('clear-calendar-btn');
            const clearDateBtn = document.getElementById('clear-date-btn');
            const totalPriceEl = document.getElementById('total-price');
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            
            let currentDate = new Date();
            let selectedDate = null;

            // --- Sidebar & Header Logic ---
            if (userMenuButton) {
                userMenuButton.addEventListener('click', () => userMenu.classList.toggle('hidden'));
                window.addEventListener('click', (e) => {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
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
            
            // === CUSTOM CALENDAR FUNCTIONALITY ===
            
            // Initialize year select
            function initYearSelect() {
                const currentYear = new Date().getFullYear();
                for (let year = currentYear - 10; year <= currentYear + 10; year++) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    yearSelect.appendChild(option);
                }
            }
            initYearSelect();
            
            // Render calendar
            function renderCalendar() {
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                
                monthSelect.value = month;
                yearSelect.value = year;
                
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const prevMonthDays = new Date(year, month, 0).getDate();
                
                let days = '';
                
                // Previous month days
                for (let i = firstDay - 1; i >= 0; i--) {
                    days += `<div class="calendar-day other-month">${prevMonthDays - i}</div>`;
                }
                
                // Current month days
                const today = new Date();
                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const dateString = date.toISOString().split('T')[0];
                    const isToday = date.toDateString() === today.toDateString();
                    const isSelected = selectedDate === dateString;
                    
                    let classes = 'calendar-day';
                    if (isToday) classes += ' today';
                    if (isSelected) classes += ' selected';
                    
                    days += `<div class="${classes}" data-date="${dateString}">${day}</div>`;
                }
                
                // Next month days
                const totalCells = firstDay + daysInMonth;
                const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
                for (let i = 1; i <= remainingCells; i++) {
                    days += `<div class="calendar-day other-month">${i}</div>`;
                }
                
                calendarDays.innerHTML = days;
                
                // Add click event to date cells
                document.querySelectorAll('.calendar-day:not(.other-month)').forEach(day => {
                    day.addEventListener('click', () => selectDate(day.dataset.date));
                });
            }
            
            // Select date
            function selectDate(dateString) {
                selectedDate = dateString;
                const date = new Date(dateString);
                dateInputDisplay.value = date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                datePicker.value = dateString;
                calendarDropdown.classList.remove('show');
                updateHistoryView();
            }
            
            // Toggle calendar
            dateInputDisplay.addEventListener('click', () => {
                calendarDropdown.classList.toggle('show');
                if (calendarDropdown.classList.contains('show')) {
                    renderCalendar();
                }
            });
            
            // Close calendar when clicking outside
            document.addEventListener('click', (e) => {
                if (!dateInputDisplay.contains(e.target) && !calendarDropdown.contains(e.target)) {
                    calendarDropdown.classList.remove('show');
                }
            });
            
            // Navigation buttons
            prevMonthBtn.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            });
            
            nextMonthBtn.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            });
            
            monthSelect.addEventListener('change', (e) => {
                currentDate.setMonth(parseInt(e.target.value));
                renderCalendar();
            });
            
            yearSelect.addEventListener('change', (e) => {
                currentDate.setFullYear(parseInt(e.target.value));
                renderCalendar();
            });
            
            // Today button
            todayBtn.addEventListener('click', () => {
                const today = new Date();
                selectDate(today.toISOString().split('T')[0]);
            });
            
            // Clear calendar button
            clearCalendarBtn.addEventListener('click', () => {
                selectedDate = null;
                dateInputDisplay.value = '';
                datePicker.value = '';
                calendarDropdown.classList.remove('show');
                renderCalendar();
                updateHistoryView();
            });
            
            // === END CUSTOM CALENDAR FUNCTIONALITY ==="

            function renderTable(dataToRender) {
                if(dataToRender.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-gray-500">No purchase history found for the selected criteria.</td></tr>`;
                    return;
                }
                tableBody.innerHTML = dataToRender.map((item, index) => {
                    const lastSaleDate = new Date(item.last_sale_date);
                    const formattedDate = lastSaleDate.toLocaleDateString('en-US');
                    
                    // Create movement badge
                    const movementStatus = item.movement_status || 'slow';
                    const movementIcon = movementStatus === 'fast' ? 'ph-trend-up' : 
                                       movementStatus === 'medium' ? 'ph-minus' : 'ph-trend-down';
                    const movementBadge = `
                        <span class="movement-badge movement-${movementStatus}">
                            <i class="ph ${movementIcon}"></i>
                            ${movementStatus}
                        </span>
                    `;
                    
                    return `
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">${index + 1}</td>
                            <td class="px-6 py-4 font-semibold text-gray-700">${item.product_name}</td>
                            <td class="px-6 py-4 font-semibold text-blue-600">${item.total_quantity}</td>
                            <td class="px-6 py-4 font-semibold text-green-600">
                               ₱${Number(item.total_sales).toFixed(2)}
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-600">${item.transaction_count}</td>
                            <td class="px-6 py-4">${formattedDate}</td>
                            <td class="px-6 py-4">${movementBadge}</td>
                        </tr>
                    `
                }).join('');
            }
            
            function calculateAndRenderTotals(data) {
                const totalPrice = data.reduce((sum, item) => sum + parseFloat(item.total_sales), 0);
                totalPriceEl.textContent = `₱${totalPrice.toFixed(2)}`;
            }

            function updateHistoryView() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedDate = datePicker.value;
                const selectedMovement = movementFilter.value;
                
                let filteredHistory = purchaseHistory;

                if (selectedDate) {
                    filteredHistory = filteredHistory.filter(item => {
                        const lastSaleDate = item.last_sale_date.split(' ')[0]; // Get only the YYYY-MM-DD part
                        return lastSaleDate === selectedDate;
                    });
                }
                
                if (searchTerm) {
                    filteredHistory = filteredHistory.filter(item => 
                        item.product_name.toLowerCase().includes(searchTerm)
                    );
                }
                
                if (selectedMovement) {
                    filteredHistory = filteredHistory.filter(item => 
                        item.movement_status === selectedMovement
                    );
                }

                renderTable(filteredHistory);
                calculateAndRenderTotals(filteredHistory);
            }

            // --- Event Listeners ---
            searchInput.addEventListener('input', updateHistoryView);
            movementFilter.addEventListener('change', updateHistoryView);
            
            // Clear date button functionality (external clear button)
            clearDateBtn.addEventListener('click', () => {
                selectedDate = null;
                dateInputDisplay.value = '';
                datePicker.value = '';
                renderCalendar();
                updateHistoryView();
                // Add visual feedback
                clearDateBtn.classList.add('scale-95');
                setTimeout(() => clearDateBtn.classList.remove('scale-95'), 100);
            });
            
            // --- Initial Page Load ---
            updateHistoryView();
        });
    </script>
    <?php echo $inventoryDarkMode['script']; ?>
</body>
</html>

