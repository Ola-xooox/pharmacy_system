<?php
/**
 * DARK MODE INTEGRATION TEMPLATE
 * 
 * This template shows how to integrate the centralized dark mode system
 * into any admin portal PHP file.
 * 
 * STEP 1: Include the dark mode system in your HTML head section
 * STEP 2: Update your body tag and main containers
 * STEP 3: Replace existing Tailwind CDN with our configured version
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Page Title</title>
    
    <!-- STEP 1: Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- STEP 2: Include the centralized dark mode system -->
    <?php include 'assets/admin_darkmode.php'; ?>
    
    <!-- Your other head elements (Chart.js, icons, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="icon" type="image/x-icon" href="../mjpharmacy.logo.jpg">
    
    <!-- Your existing custom styles -->
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
        
        /* Your existing responsive styles */
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
        
        .nav-link { 
            color: rgba(255, 255, 255, 0.8); 
        } 
        .nav-link svg { 
            color: white; 
        } 
        .nav-link:hover { 
            color: white; 
            background-color: rgba(255, 255, 255, 0.2); 
        } 
        .nav-link.active { 
            background-color: white; 
            color: var(--primary-green); 
            font-weight: 600; 
        } 
        .nav-link.active svg { 
            color: var(--primary-green); 
        }
    </style>
</head>

<!-- STEP 3: Update body tag to include dark mode classes -->
<body class="bg-gray-100 min-h-screen flex">
    
    <!-- Include sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Include header (which now has the dark mode toggle) -->
        <?php include 'admin_header.php'; ?>

        <!-- STEP 4: Main content area -->
        <main class="flex-1 overflow-y-auto p-6">
            <div id="page-content">
                
                <!-- Your page content here -->
                <div class="space-y-8">
                    
                    <!-- Example: Cards that will automatically support dark mode -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Sample Card</h3>
                                <p class="text-gray-600">This card will automatically adapt to dark mode</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Example: Forms that will support dark mode -->
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Sample Form</h2>
                        <form class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sample Input</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sample Select</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option>Option 1</option>
                                    <option>Option 2</option>
                                </select>
                            </div>
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                Submit
                            </button>
                        </form>
                    </div>
                    
                    <!-- Example: Tables that will support dark mode -->
                    <div class="bg-white p-6 rounded-2xl shadow-md">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Sample Table</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Sample Item</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Active</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <button class="text-green-600 hover:text-green-800">Edit</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                </div>
                
            </div>
        </main>
    </div>

    <!-- STEP 5: Chart.js Dark Mode Integration (if using charts) -->
    <script>
        // Example of how to integrate Chart.js with dark mode
        document.addEventListener('DOMContentLoaded', () => {
            // Listen for dark mode changes
            if (window.darkModeManager) {
                window.darkModeManager.onModeChange((mode) => {
                    // Update charts when mode changes
                    updateChartsForMode(mode);
                });
            }
            
            // Function to update charts for dark mode
            function updateChartsForMode(mode) {
                // Example: Update existing charts
                if (window.myChart) {
                    const isDark = mode === 'dark';
                    
                    // Update chart options
                    window.myChart.options.plugins.legend.labels.color = isDark ? '#f9fafb' : '#374151';
                    window.myChart.options.scales.x.ticks.color = isDark ? '#d1d5db' : '#6b7280';
                    window.myChart.options.scales.y.ticks.color = isDark ? '#d1d5db' : '#6b7280';
                    window.myChart.options.scales.x.grid.color = isDark ? '#4b5563' : '#e5e7eb';
                    window.myChart.options.scales.y.grid.color = isDark ? '#4b5563' : '#e5e7eb';
                    
                    // Update the chart
                    window.myChart.update();
                }
            }
        });
    </script>

</body>
</html>

<?php
/**
 * INTEGRATION INSTRUCTIONS:
 * 
 * 1. Copy the head section structure to your existing files
 * 2. Make sure to include 'assets/admin_darkmode.php' after Tailwind CSS
 * 3. Update your body tag to include the dark mode classes
 * 4. Your existing Tailwind classes will automatically work with dark mode
 * 5. For charts, use the Chart.js integration example above
 * 
 * IMPORTANT NOTES:
 * - The dark mode toggle is already included in admin_header.php
 * - All existing Tailwind classes will automatically support dark mode
 * - The system remembers user preference in localStorage
 * - No need to modify individual components - they'll adapt automatically
 * 
 * FILES TO UPDATE:
 * - dashboard.php
 * - setup_account.php
 * - user_activity_log.php
 * - delete_account.php
 * - inventory_report.php
 * - sales_report.php
 * 
 * QUICK INTEGRATION STEPS:
 * 1. Add <?php include 'assets/admin_darkmode.php'; ?> after Tailwind CSS script
 * 2. That's it! The dark mode will work automatically.
 */
?>
