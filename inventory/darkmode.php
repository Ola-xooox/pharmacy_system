<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize dark mode from session or default to light
if (!isset($_SESSION['inventory_dark_mode'])) {
    $_SESSION['inventory_dark_mode'] = false;
}

// Toggle dark mode if requested
if (isset($_GET['toggle_dark_mode']) || isset($_POST['toggle_dark_mode'])) {
    if (isset($_POST['toggle_dark_mode'])) {
        $_SESSION['inventory_dark_mode'] = ($_POST['toggle_dark_mode'] === '1');
    } else {
        $_SESSION['inventory_dark_mode'] = !$_SESSION['inventory_dark_mode'];
    }
    
    // If it's an AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'dark_mode' => $_SESSION['inventory_dark_mode']]);
        exit();
    }
    
    // For regular requests, redirect back
    $redirect_url = str_replace(['&toggle_dark_mode=1', '?toggle_dark_mode=1'], '', $_SERVER['REQUEST_URI']);
    $redirect_url = rtrim($redirect_url, '?&');
    header("Location: " . $redirect_url);
    exit();
}

// Dark mode assets function
function getInventoryDarkModeAssets() {
    $isDarkMode = isset($_SESSION['inventory_dark_mode']) && $_SESSION['inventory_dark_mode'];
    
    // Toggle button HTML
    $darkModeToggle = '<button id="inventory-dark-mode-toggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" title="' . ($isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode') . '">
        ' . ($isDarkMode ? 
            '<svg class="w-5 h-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
            </svg>' : 
            '<svg class="w-5 h-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
            </svg>') . '
    </button>';
    
    // Dark mode JavaScript
    $darkModeScript = '<script>
(function() {
    // Initialize immediately
    const isDarkMode = ' . ($isDarkMode ? 'true' : 'false') . ';
    console.log("Initial dark mode state:", isDarkMode);
    if (isDarkMode) {
        document.documentElement.classList.add("dark");
        console.log("Added dark class to html element");
    }
    
    // Wait for DOM to be ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initInventoryDarkMode);
    } else {
        initInventoryDarkMode();
    }
    
    function initInventoryDarkMode() {
        const darkModeToggle = document.getElementById("inventory-dark-mode-toggle");
        if (!darkModeToggle) {
            console.error("Inventory dark mode toggle button not found");
            return;
        }
        
        const html = document.documentElement;
        
        console.log("Inventory dark mode toggle initialized");
        
        // Toggle dark mode on button click
        darkModeToggle.addEventListener("click", function(e) {
            e.preventDefault();
            console.log("Inventory dark mode toggle clicked");
            
            const isDark = html.classList.toggle("dark");
            console.log("Dark mode is now:", isDark);
            
            // Update icon
            const icon = darkModeToggle.querySelector("svg");
            if (icon) {
                if (isDark) {
                    // Show sun icon
                    icon.outerHTML = `<svg class="w-5 h-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>`;
                    darkModeToggle.title = "Switch to Light Mode";
                } else {
                    // Show moon icon
                    icon.outerHTML = `<svg class="w-5 h-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>`;
                    darkModeToggle.title = "Switch to Dark Mode";
                }
            }
            
            // Save preference to session via GET request
            fetch(window.location.pathname + "?toggle_dark_mode=1", {
                method: "GET"
            }).catch(error => console.error("Error saving dark mode preference:", error));
        });
    }
})();
</script>';
    
    // Dark mode CSS styles
    $darkModeStyles = '<style>
    /* Dark mode configuration for Tailwind */
    .dark {
        --color-bg-primary: #374151;
        --color-bg-secondary: #1f2937;
        --color-bg-tertiary: #374151;
        --color-text-primary: #ffffff;
        --color-text-secondary: #e5e7eb;
        --color-border: #4b5563;
        --primary-green: #01A74F;
    }
    
    /* Body and main backgrounds */
    html.dark body {
        background-color: #374151 !important;
        color: var(--color-text-primary) !important;
    }
    
    /* Force background color with higher specificity */
    .dark body,
    html.dark body,
    body.dark {
        background-color: #374151 !important;
        background: #374151 !important;
    }
    
    /* Make all text white in dark mode */
    .dark,
    .dark * {
        color: white !important;
    }
    
    /* Background colors */
    .dark .bg-white {
        background-color: var(--color-bg-secondary) !important;
    }
    
    .dark .bg-gray-50,
    .dark .bg-gray-100 {
        background-color: var(--color-bg-tertiary) !important;
    }
    
    .dark .bg-gray-200 {
        background-color: var(--color-bg-secondary) !important;
    }
    
    /* Hover states */
    .dark .hover\:bg-gray-100:hover,
    .dark .hover\:bg-gray-50:hover {
        background-color: var(--color-bg-tertiary) !important;
    }
    
    .dark .hover\:bg-gray-200:hover {
        background-color: #4b5563 !important;
    }
    
    /* Borders */
    .dark .border,
    .dark .border-gray-200,
    .dark .border-gray-300 {
        border-color: var(--color-border) !important;
    }
    
    .dark .border-t,
    .dark .border-b,
    .dark .border-l,
    .dark .border-r {
        border-color: var(--color-border) !important;
    }
    
    /* Forms and inputs */
    .dark input[type="text"],
    .dark input[type="number"],
    .dark input[type="date"],
    .dark input[type="search"],
    .dark select,
    .dark textarea {
        background-color: var(--color-bg-tertiary) !important;
        border-color: var(--color-border) !important;
        color: var(--color-text-primary) !important;
    }
    
    .dark input::placeholder,
    .dark textarea::placeholder {
        color: #9ca3af !important;
        opacity: 0.7;
    }
    
    .dark input:focus,
    .dark select:focus,
    .dark textarea:focus {
        background-color: var(--color-bg-secondary) !important;
        border-color: var(--primary-green) !important;
        ring-color: var(--primary-green) !important;
    }
    
    /* Tables */
    .dark table {
        border-color: var(--color-border) !important;
        color: white !important;
    }
    
    .dark th,
    .dark td {
        border-color: var(--color-border) !important;
        color: white !important;
    }
    
    .dark thead,
    .dark .table-header {
        background-color: var(--color-bg-tertiary) !important;
        color: white !important;
    }
    
    .dark tbody {
        color: white !important;
    }
    
    .dark tr:hover {
        background-color: rgba(255, 255, 255, 0.05) !important;
    }
    
    /* Cards */
    .dark .summary-card {
        background-color: var(--color-bg-secondary) !important;
        border-color: var(--color-border) !important;
        color: white !important;
    }
    
    .dark .summary-card.active {
        background-color: var(--primary-green) !important;
        color: white !important;
    }
    
    .dark .product-card {
        background-color: var(--color-bg-secondary) !important;
        border-color: var(--color-border) !important;
    }
    
    /* Modals */
    .dark .modal-content {
        background-color: var(--color-bg-secondary) !important;
        border-color: var(--color-border) !important;
    }
    
    .dark .modal-header,
    .dark .modal-footer {
        border-color: var(--color-border) !important;
    }
    
    /* Buttons */
    .dark button {
        color: white !important;
    }
    
    .dark .btn-secondary {
        background-color: var(--color-bg-tertiary) !important;
        color: white !important;
    }
    
    .dark .btn-secondary:hover {
        background-color: #4b5563 !important;
    }
    
    .dark .category-btn {
        background-color: var(--color-bg-tertiary) !important;
        color: white !important;
    }
    
    .dark .category-btn:hover {
        background-color: #4b5563 !important;
    }
    
    .dark .category-btn.active {
        background-color: #1f2937 !important;
        color: white !important;
    }
    
    /* Add spacing to category buttons */
    .category-btn {
        margin: 0 0.5rem 0.5rem 0 !important;
        padding: 0.75rem 1.5rem !important;
    }
    
    #category-btn-container {
        gap: 0.75rem !important;
    }
    
    /* Keep important color indicators */
    .dark .text-red-500,
    .dark .text-red-600 {
        color: #ef4444 !important;
    }
    
    .dark .text-green-500,
    .dark .text-green-600 {
        color: #10b981 !important;
    }
    
    .dark .text-yellow-500 {
        color: #eab308 !important;
    }
    
    .dark .text-blue-500,
    .dark .text-blue-600 {
        color: #3b82f6 !important;
    }
    
    .dark .text-amber-500 {
        color: #f59e0b !important;
    }
    
    .dark .bg-green-100 {
        background-color: #166534 !important;
    }
    
    .dark .bg-red-100 {
        background-color: #7f1d1d !important;
    }
    
    .dark .bg-yellow-100,
    .dark .bg-amber-100 {
        background-color: #78350f !important;
    }
    
    .dark .bg-blue-100 {
        background-color: #1e40af !important;
    }
    
    /* Product cards specific */
    .dark .product-image-container {
        background-color: var(--color-bg-tertiary) !important;
    }
    
    .dark .product-info {
        background-color: var(--color-bg-secondary) !important;
    }
    
    .dark .stock-badge {
        color: white !important;
    }
    
    /* Enhanced shadows for dark mode */
    .dark .shadow,
    .dark .shadow-sm {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.6), 0 2px 4px -1px rgba(0, 0, 0, 0.4) !important;
    }
    
    .dark .shadow-md {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.7), 0 4px 6px -2px rgba(0, 0, 0, 0.5) !important;
    }
    
    .dark .shadow-lg {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.8), 0 10px 10px -5px rgba(0, 0, 0, 0.6) !important;
    }
    
    .dark .shadow-xl {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.9), 0 15px 20px -5px rgba(0, 0, 0, 0.7) !important;
    }
    
    /* Add shadows to cards and containers */
    .dark .bg-white,
    .dark .modal-content,
    .dark .product-card {
        box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.6), 0 4px 8px -2px rgba(0, 0, 0, 0.4) !important;
    }
    
    /* Add subtle shadows to buttons */
    .dark .category-btn,
    .dark .btn,
    .dark .summary-card {
        box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.5), 0 2px 4px -1px rgba(0, 0, 0, 0.3) !important;
    }
    
    /* Enhanced shadow for active/hover states */
    .dark .category-btn:hover,
    .dark .btn:hover,
    .dark .summary-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4) !important;
    }
    
    /* Stock badges visibility in inventory */
    .dark .stock-badge {
        color: white !important;
        font-weight: 700 !important;
        border: 1px solid !important;
    }
    
    .dark .in-stock {
        background-color: #166534 !important;
        color: #dcfce7 !important;
        border-color: #22c55e !important;
    }
    
    .dark .low-stock {
        background-color: #92400e !important;
        color: #fef3c7 !important;
        border-color: #f59e0b !important;
    }
    
    .dark .out-of-stock {
        background-color: #991b1b !important;
        color: #fee2e2 !important;
        border-color: #ef4444 !important;
    }
    
    /* Make stock text more visible */
    .dark .text-green-600,
    .dark .text-green-500 {
        color: #22c55e !important;
        font-weight: 600 !important;
    }
    
    .dark .text-yellow-600,
    .dark .text-yellow-500 {
        color: #f59e0b !important;
        font-weight: 600 !important;
    }
    
    .dark .text-red-600,
    .dark .text-red-500 {
        color: #ef4444 !important;
        font-weight: 600 !important;
    }
    
    /* Sidebar dark mode styles */
    .dark .sidebar {
        background-color: #111827 !important;
        border-right: 1px solid var(--color-border);
    }
    
    .dark .sidebar .nav-link {
        color: var(--color-text-secondary);
    }
    
    .dark .sidebar .nav-link:hover {
        color: #ffffff;
        background-color: rgba(59, 130, 246, 0.1);
    }
    
    .dark .sidebar .nav-link.active {
        background-color: var(--primary-green);
        color: white;
    }
    
    .dark .sidebar .nav-link.active svg {
        color: white !important;
    }
    
    .dark .sidebar .nav-link svg {
        color: var(--color-text-secondary);
    }
    
    .dark .sidebar .nav-link:hover svg {
        color: white;
    }
    
    .dark .sidebar h1 {
        color: white !important;
    }
    
    /* Header specific color */
    .dark header,
    html.dark header {
        background-color: #374151 !important;
        background: #374151 !important;
        border-bottom: 1px solid #4b5563 !important;
        color: #ffffff !important;
    }
    
    /* Main content area specific color */
    .dark .flex-1.overflow-y-auto.p-6,
    html.dark .flex-1.overflow-y-auto.p-6,
    .dark main.flex-1.overflow-y-auto.p-6,
    html.dark main.flex-1.overflow-y-auto.p-6 {
        background-color: #1f2937 !important;
        background: #1f2937 !important;
    }
    
    /* Target all main elements in dark mode */
    .dark main,
    html.dark main {
        background-color: #1f2937 !important;
        background: #1f2937 !important;
    }
    
    /* Catch all variations of flex-1 overflow-y-auto p-6 */
    .dark [class*="flex-1"][class*="overflow-y-auto"][class*="p-6"],
    html.dark [class*="flex-1"][class*="overflow-y-auto"][class*="p-6"],
    .dark .flex-1[class*="overflow-y-auto"][class*="p-6"],
    html.dark .flex-1[class*="overflow-y-auto"][class*="p-6"] {
        background-color: #1f2937 !important;
        background: #1f2937 !important;
    }
    
    /* Headings */
    .dark h1,
    .dark h2,
    .dark h3,
    .dark h4,
    .dark h5,
    .dark h6 {
        color: white !important;
    }
    
    /* Links */
    .dark a {
        color: #60a5fa !important;
    }
    
    .dark a:hover {
        color: #93c5fd !important;
    }
    
    /* Keep white text on green backgrounds */
    .dark .bg-green-600,
    .dark .bg-green-500,
    .dark [style*="background-color: var(--primary-green)"],
    .dark .btn-primary {
        color: white !important;
    }
</style>';
    
    return [
        'toggle' => $darkModeToggle,
        'styles' => $darkModeStyles,
        'script' => $darkModeScript,
        'is_dark' => $isDarkMode
    ];
}

// Get dark mode assets
$inventoryDarkMode = getInventoryDarkModeAssets();
?>
