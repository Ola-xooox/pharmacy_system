<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// IP Access Control Function
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Check IP authorization for CMS access
$userIP = getUserIP();

// Check if IP is authorized (WiFi or ISP range)
$isAuthorized = ($userIP === '192.168.100.142') || preg_match('/^112\.203\.\d+\.\d+$/', $userIP);

if (!$isAuthorized) {
    header("Location: ../access_denied.php?module=cms");
    exit();
}

// Redirect if not logged in or not a CMS user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cms') {
    header("Location: ../index.php");
    exit();
}

// Include dark mode functionality
require_once 'darkmode.php';
$darkMode = getDarkModeAssets();
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $darkMode['is_dark'] ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - MJ Pharmacy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            green: '#01A74F',
                            'green-light': '#E6F6EC',
                            'gray': '#F3F4F6',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            },
            variants: {
                extend: {
                    backgroundColor: ['dark'],
                    textColor: ['dark'],
                    borderColor: ['dark'],
                    ringColor: ['dark'],
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
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
            backdrop-filter: blur(4px);
        }
        .modal-overlay.active { 
            opacity: 1; 
            pointer-events: auto; 
        }
        .modal-content {
            background-color: white; 
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%; 
            transform: scale(0.95); 
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            max-height: 90vh; 
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .modal-overlay.active .modal-content { 
            transform: scale(1); 
        }
        .pagination-button {
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        .pagination-button:hover {
            background: #F3F4F6;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .pagination-button.active {
            background: linear-gradient(135deg, #01A74F, #059669);
            color: white;
            border-color: #01A74F;
            box-shadow: 0 4px 12px rgba(1, 167, 79, 0.3);
        }
        .pagination-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .dark .pagination-button {
            background-color: #374151;
            border-color: #4B5563;
            color: #E5E7EB;
        }
        .dark .pagination-button:hover {
            background-color: #4B5563;
        }
        
        /* Custom Calendar Dropdown */
        .calendar-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
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
        .dark .calendar-dropdown {
            background: #1f2937;
            border-color: #01A74F;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        .calendar-dropdown.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
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
            color: #374151;
        }
        .dark .nav-btn {
            background: #374151;
            color: #d1d5db;
        }
        .nav-btn:hover {
            background: #01A74F;
            color: white;
            transform: scale(1.1);
        }
        .dark .nav-btn:hover {
            background: #01A74F;
            color: white;
        }
        
        /* Enhanced card styles */
        .customer-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .customer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1);
        }
        
        /* Gradient backgrounds */
        .header-gradient {
            background: linear-gradient(135deg, #01A74F 0%, #059669 100%);
        }
        
        /* Filter section styling */
        .filter-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Full width layout improvements */
        .full-width-container {
            width: 100%;
            max-width: none;
        }
        
        /* Responsive scaling for different zoom levels */
        @media screen and (min-width: 1024px) {
            .main-content {
                padding-left: 2rem;
                padding-right: 2rem;
            }
        }
        
        @media screen and (min-width: 1280px) {
            .main-content {
                padding-left: 3rem;
                padding-right: 3rem;
            }
        }
        
        @media screen and (min-width: 1536px) {
            .main-content {
                padding-left: 4rem;
                padding-right: 4rem;
            }
        }
        
        /* Ensure tables expand properly */
        .table-container {
            width: 100%;
            overflow-x: auto;
        }
        
        .table-container table {
            width: 100%;
            min-width: 100%;
        }
        
        /* Customer type filter buttons */
        .customer-type-btn {
            position: relative;
            overflow: hidden;
        }
        
        .customer-type-btn.active {
            background: linear-gradient(135deg, #10B981, #059669) !important;
            color: white !important;
            border-color: #10B981 !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            transform: translateY(-1px);
        }
        
        .customer-type-btn:not(.active):hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .dark .customer-type-btn.active {
            background: linear-gradient(135deg, #10B981, #059669) !important;
            color: white !important;
        }
        
        /* Custom Calendar Dropdown Styling */
        .calendar-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 0.5rem;
            background: white;
            border: 2px solid #10B981;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            z-index: 99999;
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
            border-color: #10B981;
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
            color: #10B981;
            transform: scale(1.05);
        }
        .dark .calendar-day:hover {
            background-color: #065f46;
            color: white;
        }
        .calendar-day.today {
            border: 2px solid #10B981;
            font-weight: 600;
        }
        .calendar-day.selected {
            background-color: #10B981;
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
            background: #10B981;
            color: white;
            transform: scale(1.1);
        }
        .dark .nav-btn {
            background: #374151;
            color: #d1d5db;
        }
        .dark .nav-btn:hover {
            background: #10B981;
            color: white;
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
            .no-print, .no-print * {
                display: none !important;
                visibility: hidden !important;
            }
        }
    </style>
    <?php echo $darkMode['styles']; ?>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <div class="flex flex-col min-h-screen">
        <?php include 'cms_header.php'; ?>

        <main class="flex-1 main-content p-4 sm:p-6 lg:p-8">
            <div class="full-width-container">

                <div class="customer-card overflow-hidden">
                    <div class="p-6 header-gradient text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-users text-2xl"></i>
                                    <h1 class="text-2xl font-bold">Customer Relations</h1>
                                </div>
                                <div class="flex items-center gap-3 mt-2 ml-1">
                                    <p class="text-white/80">View and manage customer information with advanced filtering.</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-white/80">Total Customers</div>
                                <div id="total-customers" class="text-2xl font-bold">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 filter-section border-b border-gray-200 dark:border-gray-600">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Search Filter -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search Customers</label>
                                <div class="relative">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-300 pointer-events-none"></i>
                                    <input type="text" id="customer-search" placeholder="Search by name or ID..." class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:border-green-500 transition-all duration-200">
                                </div>
                            </div>
                            
                            <!-- Customer Type Filter -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer Type</label>
                                <div class="relative">
                                    <i class="fas fa-users absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-300 pointer-events-none"></i>
                                    <select id="customer-type-select" class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500/50 focus:border-green-500 transition-all duration-200 cursor-pointer">
                                        <option value="all">All Customers</option>
                                        <option value="discounted">Discounted</option>
                                        <option value="walk-in">Walk-in</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Date Range Filter -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filter by Date</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="ph ph-calendar-blank text-gray-400 dark:text-gray-500"></i>
                                    </div>
                                    <input type="text" readonly id="date-display" placeholder="Select a date" class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 cursor-pointer transition-all duration-200 hover:border-green-500">
                                    <input type="hidden" id="date-filter">
                                    
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
                            </div>
                            
                        </div>
                        
                        <!-- Customer Table -->
                        <div class="table-container mt-10">
                            <table class="w-full text-sm">
                                <thead class="bg-white dark:bg-gray-800 border-b-2 border-gray-200 dark:border-gray-600">
                                    <tr>
                                        <th class="py-4 px-6 font-semibold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider text-left">Customer</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider text-left">ID No.</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider text-center">Total Visits</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider text-left">Total Spent</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider text-left">Last Visit</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="customer-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                </tbody>
                            </table>
                        </div>
                        <div id="customer-pagination" class="p-6 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex flex-col sm:flex-row justify-between items-center gap-4">
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>
        </main>
    </div>

    <div id="transaction-history-modal" class="modal-overlay">
        <div class="modal-content max-w-6xl bg-white dark:bg-gray-800">
            <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-700 header-gradient text-white">
                <div>
                    <h3 class="text-xl font-bold">Transaction History</h3>
                    <p id="history-customer-name" class="text-sm text-white/80 mt-1"></p>
                </div>
                <button id="close-history-modal" class="p-2 rounded-full hover:bg-white/20 text-2xl leading-none font-bold text-white transition-colors">&times;</button>
            </div>
            
            <!-- Transaction Modal Date Filter -->
            <div id="transaction-date-filter-section" class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filter Transactions by Date</label>
                        <div class="relative">
                            <i class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                            <input type="text" readonly id="transaction-date-display" placeholder="Select date" class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 cursor-pointer hover:border-brand-green transition-colors">
                            <input type="hidden" id="transaction-date-filter">
                            
                            <!-- Transaction Calendar Dropdown -->
                            <div id="transaction-calendar-dropdown" class="calendar-dropdown">
                                <div class="calendar-header">
                                    <button type="button" class="nav-btn" id="transaction-prev-month">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <select id="transaction-month-select" class="px-2 py-1 rounded bg-gray-100 border-none font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-brand-green">
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
                                        <select id="transaction-year-select" class="px-2 py-1 rounded bg-gray-100 border-none font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-brand-green"></select>
                                    </div>
                                    <button type="button" class="nav-btn" id="transaction-next-month">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                                <div class="calendar-grid">
                                    <div class="calendar-day-name">Su</div>
                                    <div class="calendar-day-name">Mo</div>
                                    <div class="calendar-day-name">Tu</div>
                                    <div class="calendar-day-name">We</div>
                                    <div class="calendar-day-name">Th</div>
                                    <div class="calendar-day-name">Fr</div>
                                    <div class="calendar-day-name">Sa</div>
                                </div>
                                <div class="calendar-grid" id="transaction-calendar-days"></div>
                                <div class="flex gap-2 mt-3 pt-3 border-t border-gray-200">
                                    <button type="button" id="transaction-today-btn" class="flex-1 px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-sm font-medium transition-colors">
                                        Today
                                    </button>
                                    <button type="button" id="transaction-clear-btn" class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button id="transaction-filter-today" class="px-3 py-2 text-xs font-medium bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">Today</button>
                        <button id="transaction-filter-week" class="px-3 py-2 text-xs font-medium bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors">This Week</button>
                        <button id="transaction-filter-month" class="px-3 py-2 text-xs font-medium bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors">This Month</button>
                        <button id="transaction-filter-all" class="px-3 py-2 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">All</button>
                    </div>
                </div>
            </div>
            
            <div class="p-4 overflow-y-auto max-h-96 table-container">
                <table class="w-full text-base">
                    <thead class="bg-white dark:bg-gray-800 border-b-2 border-gray-200 dark:border-gray-600 text-sm font-semibold uppercase sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-gray-700 dark:text-gray-200 text-left w-2/5">Product(s)</th>
                            <th class="px-4 py-3 text-gray-700 dark:text-gray-200 text-left">Receipt #</th>
                            <th class="px-4 py-3 text-gray-700 dark:text-gray-200 text-left">Date</th>
                            <th class="px-4 py-3 text-gray-700 dark:text-gray-200 text-right">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody id="transaction-list-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                    </tbody>
                </table>
            </div>
            
            <!-- Transaction Summary -->
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span id="transaction-count">0</span> transactions found
                    </div>
                    <div class="text-lg font-bold text-brand-green">
                        Total: <span id="transaction-total">₱0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="receipt-modal" class="modal-overlay">
        <div id="receipt-modal-content" class="modal-content !max-w-sm bg-white dark:bg-gray-800">
             <div class="p-6">
                <div class="text-center">
                    <img src="../mjpharmacy.logo.jpg" alt="MJ Pharmacy Logo" class="w-16 h-16 mx-auto mb-2 rounded-full">
                    <h2 class="text-xl font-bold mt-2 text-gray-900 dark:text-gray-100">MJ PHARMACY</h2>
                </div>
                <div class="my-6 border-t border-dashed border-gray-300 dark:border-gray-600"></div>
                <div class="text-sm space-y-2 text-gray-600 dark:text-gray-300">
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
                    <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Subtotal:</span><span id="receipt-subtotal" class="font-medium">₱0.00</span></div>
                    <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Discount:</span><span id="receipt-discount" class="font-medium text-red-500">-₱0.00</span></div>
                    <div class="flex justify-between font-bold text-lg"><span class="text-gray-800 dark:text-gray-100">Total:</span><span id="receipt-total" class="text-brand-green">₱0.00</span></div>
                    <div id="receipt-cash-details" class="mt-3 pt-2 border-t border-dashed border-gray-300 dark:border-gray-600" style="display: none;">
                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Cash Received:</span><span id="receipt-cash-amount" class="font-medium">₱0.00</span></div>
                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Change:</span><span id="receipt-change-amount" class="font-medium text-brand-green">₱0.00</span></div>
                    </div>
                 </div>
                 <div class="text-center mt-8 text-xs text-gray-500">
                    <p>Thank you for your purchase!</p>
                 </div>
                 <div class="mt-8 flex gap-3 no-print">
                    <button id="print-receipt-btn" class="flex-1 flex items-center justify-center gap-2 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition-all duration-200"><i class="fas fa-print w-4 h-4"></i><span>Print</span></button>
                    <button id="close-receipt-modal" class="flex-1 flex items-center justify-center gap-2 text-sm font-semibold text-white bg-brand-green hover:bg-opacity-90 px-4 py-2 rounded-lg transition-all duration-200">Close</button>
                 </div>
             </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // DOM Elements
            const searchInput = document.getElementById('customer-search');
            const tableBody = document.getElementById('customer-table-body');
            const paginationContainer = document.getElementById('customer-pagination');
            const totalCustomersEl = document.getElementById('total-customers');
            
            // Modal elements
            const historyModal = document.getElementById('transaction-history-modal');
            const receiptModal = document.getElementById('receipt-modal');
            const closeHistoryModalBtn = document.getElementById('close-history-modal');
            const closeReceiptModalBtn = document.getElementById('close-receipt-modal');
            const printReceiptBtn = document.getElementById('print-receipt-btn');
            const transactionListBody = document.getElementById('transaction-list-body');
            const transactionCount = document.getElementById('transaction-count');
            const transactionTotal = document.getElementById('transaction-total');
            
            // Date filter elements
            const dateDisplay = document.getElementById('date-display');
            const dateFilter = document.getElementById('date-filter');
            const calendarDropdown = document.getElementById('calendar-dropdown');
            const prevMonthBtn = document.getElementById('prev-month');
            const nextMonthBtn = document.getElementById('next-month');
            const monthSelect = document.getElementById('month-select');
            const yearSelect = document.getElementById('year-select');
            const calendarDays = document.getElementById('calendar-days');
            const todayBtn = document.getElementById('today-btn');
            const clearCalendarBtn = document.getElementById('clear-calendar-btn');
            
            // Transaction modal date filter elements
            const transactionDateDisplay = document.getElementById('transaction-date-display');
            const transactionDateFilter = document.getElementById('transaction-date-filter');
            const transactionCalendarDropdown = document.getElementById('transaction-calendar-dropdown');
            const transactionPrevMonthBtn = document.getElementById('transaction-prev-month');
            const transactionNextMonthBtn = document.getElementById('transaction-next-month');
            const transactionMonthSelect = document.getElementById('transaction-month-select');
            const transactionYearSelect = document.getElementById('transaction-year-select');
            const transactionCalendarDays = document.getElementById('transaction-calendar-days');
            const transactionTodayBtn = document.getElementById('transaction-today-btn');
            const transactionClearBtn = document.getElementById('transaction-clear-btn');
            
            
            // Customer type filter dropdown
            const customerTypeSelect = document.getElementById('customer-type-select');
            
            // Transaction quick filter buttons
            const transactionFilterTodayBtn = document.getElementById('transaction-filter-today');
            const transactionFilterWeekBtn = document.getElementById('transaction-filter-week');
            const transactionFilterMonthBtn = document.getElementById('transaction-filter-month');
            const transactionFilterAllBtn = document.getElementById('transaction-filter-all');

            // State variables
            let currentPage = 1;
            let currentSearch = '';
            let currentDateFilter = '';
            let currentCustomerTypeFilter = 'all'; // 'all', 'discounted', 'walk-in'
            let currentTransactionDateFilter = '';
            let currentCustomerId = null;
            let allTransactions = [];
            let debounceTimer;
            let currentDate = new Date();
            let selectedDate = null;
            let transactionCurrentDate = new Date();
            let transactionSelectedDate = null;

            // === CALENDAR FUNCTIONALITY ===
            
            // Initialize year selects
            function initYearSelects() {
                const currentYear = new Date().getFullYear();
                [yearSelect, transactionYearSelect].forEach(select => {
                    if (select) {
                        for (let year = currentYear - 10; year <= currentYear + 10; year++) {
                            const option = document.createElement('option');
                            option.value = year;
                            option.textContent = year;
                            select.appendChild(option);
                        }
                    }
                });
            }
            
            // Render calendar
            function renderCalendar(isTransaction = false) {
                const targetDate = isTransaction ? transactionCurrentDate : currentDate;
                const targetCalendarDays = isTransaction ? transactionCalendarDays : calendarDays;
                const targetMonthSelect = isTransaction ? transactionMonthSelect : monthSelect;
                const targetYearSelect = isTransaction ? transactionYearSelect : yearSelect;
                const targetSelectedDate = isTransaction ? transactionSelectedDate : selectedDate;
                
                const year = targetDate.getFullYear();
                const month = targetDate.getMonth();
                
                targetMonthSelect.value = month;
                targetYearSelect.value = year;
                
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
                    const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const isToday = date.toDateString() === today.toDateString();
                    const isSelected = targetSelectedDate === dateString;
                    
                    let classes = 'calendar-day';
                    if (isToday) classes += ' today';
                    if (isSelected) classes += ' selected';
                    
                    days += `<div class="${classes}" data-date="${dateString}" data-transaction="${isTransaction}">${day}</div>`;
                }
                
                // Next month days
                const totalCells = firstDay + daysInMonth;
                const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
                for (let i = 1; i <= remainingCells; i++) {
                    days += `<div class="calendar-day other-month">${i}</div>`;
                }
                
                targetCalendarDays.innerHTML = days;
                
                // Add click events to date cells
                document.querySelectorAll('.calendar-day:not(.other-month)').forEach(day => {
                    day.addEventListener('click', () => {
                        const isTransactionCalendar = day.dataset.transaction === 'true';
                        selectDate(day.dataset.date, isTransactionCalendar);
                    });
                });
            }
            
            // Select date
            function selectDate(dateString, isTransaction = false) {
                if (isTransaction) {
                    transactionSelectedDate = dateString;
                    currentTransactionDateFilter = dateString;
                    const [year, month, day] = dateString.split('-').map(Number);
                    const date = new Date(year, month - 1, day);
                    transactionDateDisplay.value = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    transactionDateFilter.value = dateString;
                    transactionCalendarDropdown.classList.remove('show');
                    filterTransactions();
                } else {
                    selectedDate = dateString;
                    const [year, month, day] = dateString.split('-').map(Number);
                    const date = new Date(year, month - 1, day);
                    dateDisplay.value = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    dateFilter.value = dateString;
                    calendarDropdown.classList.remove('show');
                    currentDateFilter = dateString;
                    currentPage = 1;
                    fetchCustomerHistory(currentPage, currentSearch, currentDateFilter, currentCustomerTypeFilter);
                }
            }
            
            // Quick date filters
            function setQuickFilter(type, isTransaction = false) {
                const today = new Date();
                let targetDate = null;
                
                switch(type) {
                    case 'today':
                        targetDate = today.toISOString().split('T')[0];
                        break;
                    case 'week':
                        const weekStart = new Date(today);
                        weekStart.setDate(today.getDate() - today.getDay());
                        targetDate = weekStart.toISOString().split('T')[0];
                        break;
                    case 'month':
                        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                        targetDate = monthStart.toISOString().split('T')[0];
                        break;
                    case 'all':
                        if (isTransaction) {
                            transactionSelectedDate = null;
                            transactionDateDisplay.value = '';
                            transactionDateFilter.value = '';
                            currentTransactionDateFilter = '';
                            filterTransactions();
                        } else {
                            selectedDate = null;
                            dateDisplay.value = '';
                            dateFilter.value = '';
                            currentDateFilter = '';
                            currentPage = 1;
                            fetchCustomerHistory(currentPage, currentSearch, currentDateFilter, currentCustomerTypeFilter);
                        }
                        return;
                }
                
                if (targetDate) {
                    selectDate(targetDate, isTransaction);
                }
            }

            async function fetchCustomerHistory(page = 1, search = '', dateFilter = '', customerTypeFilter = 'all') {
                try {
                    let url = `../api/customer_api.php?action=get_history&page=${page}&search=${encodeURIComponent(search)}`;
                    if (dateFilter) {
                        url += `&date=${dateFilter}`;
                    }
                    if (customerTypeFilter && customerTypeFilter !== 'all') {
                        url += `&customer_type=${customerTypeFilter}`;
                    }
                    
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('Network response was not ok');
                    const data = await response.json();
                    renderTable(data.customers);
                    renderPagination(data);
                    
                    // Update total customers count
                    if (totalCustomersEl) {
                        totalCustomersEl.textContent = data.totalResults || data.customers.length;
                    }
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
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-16 text-gray-500"><div class="flex flex-col items-center gap-4"><i class="fas fa-user-slash w-16 h-16 text-gray-300" style="font-size: 4rem;"></i><div><p class="font-semibold text-lg">No Customers Found</p><p class="text-sm mt-1">Try adjusting your search.</p></div></div></td></tr>`;
                } else {
                    tableBody.innerHTML = customers.map(customer => `
                        <tr class="hover:bg-gray-50 dark:hover:from-gray-700 dark:hover:to-gray-600 transition-all duration-300 border-b border-gray-100 dark:border-gray-700">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md">${getInitials(customer.customer_name)}</div>
                                    <div><div class="font-semibold text-gray-800 dark:text-gray-100" data-customer-name="${customer.customer_name}">${customer.customer_name}</div></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300 font-mono text-xs">${customer.customer_id_no || 'N/A'}</td>
                            <td class="px-6 py-4 text-center"><span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-500 text-white shadow-sm">${customer.total_visits}</span></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">${formatCurrency(customer.total_spent)}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">${formatDate(customer.last_visit)}</td>
                            <td class="px-6 py-4 text-center">
                                <button data-customer-id="${customer.id}" class="view-history-btn w-10 h-10 flex items-center justify-center text-white bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 rounded-full transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 mx-auto">
                                    <i class="fas fa-history w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            }
            
            function renderPagination({ totalPages, currentPage, totalResults, limit }) {
                 if (!totalPages || totalPages <= 1) {
                    paginationContainer.innerHTML = ''; return;
                }
                const startItem = (currentPage - 1) * limit + 1;
                const endItem = Math.min(startItem + limit - 1, totalResults);
                
                let paginationHTML = `<div class="text-sm text-gray-600">Showing <b>${startItem}</b> to <b>${endItem}</b> of <b>${totalResults}</b></div><div class="flex items-center gap-1">`;
                paginationHTML += `<button class="prev-btn p-2 rounded-lg ${currentPage === 1 ? 'text-gray-300 cursor-not-allowed' : 'hover:bg-gray-200'}" ${currentPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left w-5 h-5"></i></button>`;
                for (let i = 1; i <= totalPages; i++) {
                     paginationHTML += `<button class="page-btn w-9 h-9 rounded-lg text-sm font-semibold ${i === currentPage ? 'bg-brand-green text-white' : 'hover:bg-gray-200'}" data-page="${i}">${i}</button>`;
                }
                paginationHTML += `<button class="next-btn p-2 rounded-lg ${currentPage === totalPages ? 'text-gray-300 cursor-not-allowed' : 'hover:bg-gray-200'}" ${currentPage === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right w-5 h-5"></i></button>`;
                paginationHTML += `</div>`;
                paginationContainer.innerHTML = paginationHTML;
            }

            function changePage(newPage) {
                currentPage = newPage;
                fetchCustomerHistory(currentPage, currentSearch, currentDateFilter, currentCustomerTypeFilter);
            }

            // Filter transactions based on date
            function filterTransactions() {
                if (!allTransactions.length) return;
                
                let filtered = allTransactions;
                
                if (currentTransactionDateFilter) {
                    filtered = filtered.filter(tx => {
                        const txDate = tx.transaction_date.split(' ')[0]; // Get YYYY-MM-DD part
                        return txDate === currentTransactionDateFilter;
                    });
                }
                
                renderTransactions(filtered);
            }
            
            // Render transactions in the modal
            function renderTransactions(transactions) {
                if (transactions.length === 0) {
                    transactionListBody.innerHTML = `<tr><td colspan="4" class="text-center p-8 text-gray-500">No transactions found for the selected criteria.</td></tr>`;
                    transactionCount.textContent = '0';
                    transactionTotal.textContent = '₱0.00';
                    return;
                }
                
                const customerName = document.getElementById('history-customer-name').textContent;
                
                transactionListBody.innerHTML = transactions.map(tx => {
                    const productList = tx.items.length > 0
                        ? tx.items.map(item => `<div class="truncate text-xs">${item.product_name} (x${item.quantity})</div>`).join('')
                        : 'N/A';
                    
                    return `
                    <tr class="hover:bg-gray-50 dark:hover:from-gray-700 dark:hover:to-gray-600 cursor-pointer transition-all duration-300 border-b border-gray-100 dark:border-gray-700" data-transaction-id="${tx.id}" data-total="${tx.total_amount}" data-date="${tx.transaction_date}" data-customer-name="${customerName}">
                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">${productList}</td>
                        <td class="px-4 py-3 font-mono text-xs"><span class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-2 py-1 rounded-md text-xs font-semibold">RX${tx.id}</span></td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">${new Date(tx.transaction_date).toLocaleString()}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">${formatCurrency(tx.total_amount)}</td>
                    </tr>
                `}).join('');
                
                // Update summary
                const totalAmount = transactions.reduce((sum, tx) => sum + parseFloat(tx.total_amount), 0);
                transactionCount.textContent = transactions.length;
                transactionTotal.textContent = formatCurrency(totalAmount);
            }

            async function openHistoryModal(customerId, customerName) {
                currentCustomerId = customerId;
                document.getElementById('history-customer-name').textContent = customerName;
                transactionListBody.innerHTML = `<tr><td colspan="4" class="text-center p-8">Loading...</td></tr>`;
                
                // Reset transaction filters
                transactionSelectedDate = null;
                transactionDateDisplay.value = '';
                transactionDateFilter.value = '';
                currentTransactionDateFilter = '';
                
                // Hide date filter section for Walk-in customers
                const dateFilterSection = document.getElementById('transaction-date-filter-section');
                if (customerName === 'Walk-in') {
                    dateFilterSection.style.display = 'none';
                } else {
                    dateFilterSection.style.display = 'block';
                }
                
                historyModal.classList.add('active');

                try {
                    const response = await fetch(`../api/customer_api.php?action=get_customer_transactions&id=${customerId}`);
                    allTransactions = await response.json();

                    if (allTransactions.length === 0) {
                        transactionListBody.innerHTML = `<tr><td colspan="4" class="text-center p-8 text-gray-500">No transactions found.</td></tr>`;
                        transactionCount.textContent = '0';
                        transactionTotal.textContent = '₱0.00';
                        return;
                    }
                    
                    renderTransactions(allTransactions);
                } catch (error) {
                    console.error("Failed to fetch transaction history:", error);
                    transactionListBody.innerHTML = `<tr><td colspan="4" class="text-center p-8 text-red-500">Could not load history.</td></tr>`;
                    transactionCount.textContent = '0';
                    transactionTotal.textContent = '₱0.00';
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
                        
                        // Update receipt totals and payment information
                        const subtotal = result.subtotal || total;
                        const discount = result.discount || 0;
                        const paymentMethod = result.payment_method || 'N/A';
                        const cashAmount = result.cash_amount || 0;
                        const changeAmount = result.change_amount || 0;
                        
                        document.getElementById('receipt-subtotal').textContent = formatCurrency(subtotal);
                        document.getElementById('receipt-discount').textContent = `-${formatCurrency(discount)}`;
                        
                        // Show cash details if payment method is cash and cash amount exists
                        const receiptCashDetails = document.getElementById('receipt-cash-details');
                        if (paymentMethod.toLowerCase() === 'cash' && cashAmount > 0) {
                            document.getElementById('receipt-cash-amount').textContent = formatCurrency(cashAmount);
                            document.getElementById('receipt-change-amount').textContent = formatCurrency(changeAmount);
                            receiptCashDetails.style.display = 'block';
                        } else {
                            receiptCashDetails.style.display = 'none';
                        }
                    } else {
                        throw new Error(result.message || 'Could not retrieve items.');
                    }
                } catch (error) {
                    console.error("Failed to fetch receipt details:", error);
                    document.getElementById('receipt-items').innerHTML = `<div class="text-center p-4 text-red-500">${error.message}</div>`;
                }
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

            // === EVENT LISTENERS ===
            
            // Search input
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentSearch = searchInput.value;
                    currentPage = 1;
                    fetchCustomerHistory(currentPage, currentSearch, currentDateFilter, currentCustomerTypeFilter);
                }, 300);
            });
            
            // Main calendar controls
            if (dateDisplay) {
                dateDisplay.addEventListener('click', () => {
                    calendarDropdown.classList.toggle('show');
                    if (calendarDropdown.classList.contains('show')) {
                        renderCalendar(false);
                    }
                });
            }
            
            // Close calendar when clicking outside
            document.addEventListener('click', (e) => {
                if (calendarDropdown && !calendarDropdown.contains(e.target) && !dateDisplay.contains(e.target)) {
                    calendarDropdown.classList.remove('show');
                }
            });
            
            if (prevMonthBtn) {
                prevMonthBtn.addEventListener('click', () => {
                    currentDate.setMonth(currentDate.getMonth() - 1);
                    renderCalendar(false);
                });
            }
            
            if (nextMonthBtn) {
                nextMonthBtn.addEventListener('click', () => {
                    currentDate.setMonth(currentDate.getMonth() + 1);
                    renderCalendar(false);
                });
            }
            
            if (monthSelect) {
                monthSelect.addEventListener('change', (e) => {
                    currentDate.setMonth(parseInt(e.target.value));
                    renderCalendar(false);
                });
            }
            
            if (yearSelect) {
                yearSelect.addEventListener('change', (e) => {
                    currentDate.setFullYear(parseInt(e.target.value));
                    renderCalendar(false);
                });
            }
            
            if (todayBtn) {
                todayBtn.addEventListener('click', () => {
                    const today = new Date();
                    selectDate(today.toISOString().split('T')[0], false);
                });
            }
            
            if (clearCalendarBtn) {
                clearCalendarBtn.addEventListener('click', () => {
                    selectedDate = null;
                    dateDisplay.value = '';
                    dateFilter.value = '';
                    calendarDropdown.classList.remove('show');
                    currentDateFilter = '';
                    currentPage = 1;
                    renderCalendar(false);
                    fetchCustomerHistory(currentPage, currentSearch, currentDateFilter, currentCustomerTypeFilter);
                });
            }
            
            // Transaction modal calendar controls
            if (transactionDateDisplay) {
                transactionDateDisplay.addEventListener('click', () => {
                    transactionCalendarDropdown.classList.toggle('show');
                    if (transactionCalendarDropdown.classList.contains('show')) {
                        renderCalendar(true);
                    }
                });
            }
            
            if (transactionPrevMonthBtn) {
                transactionPrevMonthBtn.addEventListener('click', () => {
                    transactionCurrentDate.setMonth(transactionCurrentDate.getMonth() - 1);
                    renderCalendar(true);
                });
            }
            
            if (transactionNextMonthBtn) {
                transactionNextMonthBtn.addEventListener('click', () => {
                    transactionCurrentDate.setMonth(transactionCurrentDate.getMonth() + 1);
                    renderCalendar(true);
                });
            }
            
            if (transactionMonthSelect) {
                transactionMonthSelect.addEventListener('change', (e) => {
                    transactionCurrentDate.setMonth(parseInt(e.target.value));
                    renderCalendar(true);
                });
            }
            
            if (transactionYearSelect) {
                transactionYearSelect.addEventListener('change', (e) => {
                    transactionCurrentDate.setFullYear(parseInt(e.target.value));
                    renderCalendar(true);
                });
            }
            
            if (transactionTodayBtn) {
                transactionTodayBtn.addEventListener('click', () => {
                    const today = new Date();
                    selectDate(today.toISOString().split('T')[0], true);
                });
            }
            
            if (transactionClearBtn) {
                transactionClearBtn.addEventListener('click', () => {
                    transactionSelectedDate = null;
                    transactionDateDisplay.value = '';
                    transactionDateFilter.value = '';
                    transactionCalendarDropdown.classList.remove('show');
                    currentTransactionDateFilter = '';
                    renderCalendar(true);
                    filterTransactions();
                });
            }
            
            
            // Customer type filter dropdown
            if (customerTypeSelect) {
                customerTypeSelect.addEventListener('change', () => {
                    currentCustomerTypeFilter = customerTypeSelect.value;
                    currentPage = 1;
                    fetchCustomerHistory(currentPage, currentSearch, currentDateFilter, currentCustomerTypeFilter);
                });
            }
            
            // Transaction quick filter buttons
            if (transactionFilterTodayBtn) transactionFilterTodayBtn.addEventListener('click', () => setQuickFilter('today', true));
            if (transactionFilterWeekBtn) transactionFilterWeekBtn.addEventListener('click', () => setQuickFilter('week', true));
            if (transactionFilterMonthBtn) transactionFilterMonthBtn.addEventListener('click', () => setQuickFilter('month', true));
            if (transactionFilterAllBtn) transactionFilterAllBtn.addEventListener('click', () => setQuickFilter('all', true));
            
            // Close calendars when clicking outside
            document.addEventListener('click', (e) => {
                if (dateDisplay && calendarDropdown && 
                    !dateDisplay.contains(e.target) && !calendarDropdown.contains(e.target)) {
                    calendarDropdown.classList.remove('show');
                }
                
                if (transactionDateDisplay && transactionCalendarDropdown && 
                    !transactionDateDisplay.contains(e.target) && !transactionCalendarDropdown.contains(e.target)) {
                    transactionCalendarDropdown.classList.remove('show');
                }
            });

            // Initialize
            initYearSelects();
            fetchCustomerHistory();
        });
    </script>
    <?php echo $darkMode['script']; ?>
</body>
</html>