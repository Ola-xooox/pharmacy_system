<?php
/**
 * POS Dark Mode System
 * Single file to manage dark mode functionality for all POS pages
 * Include this file in any POS page to add dark mode support
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle dark mode toggle request
if (isset($_POST['toggle_pos_dark_mode'])) {
    $currentMode = $_SESSION['pos_dark_mode'] ?? false;
    $_SESSION['pos_dark_mode'] = !$currentMode;
    
    // Return JSON response for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'dark_mode' => $_SESSION['pos_dark_mode']]);
        exit;
    }
}

// Get current dark mode state
$isDarkMode = $_SESSION['pos_dark_mode'] ?? false;

/**
 * Get all dark mode assets (toggle button, styles, script)
 * @return array
 */
function getPOSDarkModeAssets() {
    global $isDarkMode;
    
    // Dark mode toggle button
    $darkModeToggle = '<button class="pos-dark-mode-toggle p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" role="switch" aria-label="Toggle dark mode" title="Toggle dark mode">
        ' . ($isDarkMode ? 
            '<svg class="w-5 h-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"/>
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
    console.log("POS Initial dark mode state:", isDarkMode);
    if (isDarkMode) {
        document.documentElement.classList.add("dark");
        console.log("Added dark class to html element");
    }
    
    // Wait for DOM to be ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initPOSDarkMode);
    } else {
        initPOSDarkMode();
    }
    
    function initPOSDarkMode() {
        const toggleButton = document.querySelector(".pos-dark-mode-toggle");
        if (!toggleButton) {
            console.warn("POS Dark mode toggle button not found");
            return;
        }
        
        console.log("POS dark mode toggle initialized");
        
        toggleButton.addEventListener("click", function() {
            console.log("POS dark mode toggle clicked");
            
            fetch(window.location.href, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: "toggle_pos_dark_mode=1"
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("POS dark mode is now:", data.dark_mode);
                    
                    // Update the HTML class immediately
                    if (data.dark_mode) {
                        document.documentElement.classList.add("dark");
                        // Change to sun icon
                        toggleButton.innerHTML = `<svg class="w-5 h-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"/>
                        </svg>`;
                    } else {
                        document.documentElement.classList.remove("dark");
                        // Change to moon icon
                        toggleButton.innerHTML = `<svg class="w-5 h-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>`;
                    }
                }
            }).catch(error => console.error("Error saving POS dark mode preference:", error));
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
    html.dark,
    html.dark body {
        background-color: #1f2937 !important;
        color: var(--color-text-primary) !important;
        min-height: 100vh !important;
    }
    
    /* Force background color with higher specificity - use same color as main content */
    .dark,
    .dark body,
    html.dark,
    html.dark body,
    body.dark {
        background-color: #1f2937 !important;
        background: #1f2937 !important;
        min-height: 100vh !important;
    }
    
    /* Ensure full page coverage */
    .dark html,
    html.dark {
        background-color: #1f2937 !important;
        min-height: 100% !important;
    }
    
    /* Force background on body with Tailwind override - highest specificity */
    html.dark body.bg-gray-100,
    .dark body.bg-gray-100,
    body.bg-gray-100.dark,
    html.dark body[class*="bg-gray-100"] {
        background-color: #1f2937 !important;
        background: #1f2937 !important;
    }
    
    /* Override any Tailwind background classes */
    .dark [class*="bg-gray"]:not(.order-summary):not(.product-card):not(.modal-content),
    html.dark [class*="bg-gray"]:not(.order-summary):not(.product-card):not(.modal-content) {
        background-color: #1f2937 !important;
    }
    
    /* Specifically target bg-gray-100 class */
    .dark .bg-gray-100:not(.order-summary):not(.product-card):not(.modal-content),
    html.dark .bg-gray-100:not(.order-summary):not(.product-card):not(.modal-content) {
        background-color: #1f2937 !important;
    }
    
    /* Make all text white in dark mode */
    .dark,
    .dark * {
        color: white !important;
    }
    
    /* Background colors */
    .dark .bg-white {
        background-color: var(--color-bg-secondary) !important;
        color: white !important;
    }
    
    .dark .bg-gray-50,
    .dark .bg-gray-100 {
        background-color: var(--color-bg-tertiary) !important;
    }
    
    .dark .bg-gray-200 {
        background-color: #4b5563 !important;
    }
    
    /* Border colors */
    .dark .border-gray-200,
    .dark .border-gray-300 {
        border-color: var(--color-border) !important;
    }
    
    /* Text colors - preserve important colors */
    .dark .text-gray-500,
    .dark .text-gray-600,
    .dark .text-gray-700,
    .dark .text-gray-800,
    .dark .text-gray-900 {
        color: var(--color-text-secondary) !important;
    }
    
    /* Input fields */
    .dark input,
    .dark select,
    .dark textarea {
        background-color: var(--color-bg-tertiary) !important;
        border-color: var(--color-border) !important;
        color: white !important;
    }
    
    .dark input::placeholder {
        color: var(--color-text-secondary) !important;
    }
    
    /* Tables */
    .dark table,
    .dark .table {
        background-color: var(--color-bg-secondary) !important;
        color: white !important;
    }
    
    .dark th,
    .dark td {
        border-color: var(--color-border) !important;
        color: white !important;
    }
    
    .dark .table-header {
        background-color: var(--color-bg-tertiary) !important;
        color: white !important;
    }
    
    /* Buttons */
    .dark .btn-secondary {
        background-color: var(--color-bg-tertiary) !important;
        color: white !important;
        border-color: var(--color-border) !important;
    }
    
    .dark .btn-secondary:hover {
        background-color: #4b5563 !important;
    }
    
    /* Cards */
    .dark .card,
    .dark .product-card {
        background-color: var(--color-bg-secondary) !important;
        border-color: var(--color-border) !important;
        color: white !important;
    }
    
    /* Order Summary specific styling */
    .dark .order-summary {
        background-color: var(--color-bg-secondary) !important;
        border-color: var(--color-border) !important;
        color: white !important;
    }
    
    /* Order summary sections */
    .dark .order-summary .bg-gray-50 {
        background-color: var(--color-bg-tertiary) !important;
    }
    
    /* Product cards in POS */
    .dark .product-image-container {
        background-color: var(--color-bg-tertiary) !important;
    }
    
    /* Category buttons */
    .dark .category-btn {
        background-color: var(--color-bg-tertiary) !important;
        border-color: var(--color-border) !important;
        color: white !important;
    }
    
    .dark .category-btn:hover {
        background-color: #4b5563 !important;
    }
    
    .dark .category-btn.active {
        background-color: var(--primary-green) !important;
        color: white !important;
        border-color: var(--primary-green) !important;
    }
    
    /* Stock badges visibility */
    .dark .stock-badge {
        color: white !important;
        font-weight: 700 !important;
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
    
    /* Proceed to Payment button */
    .dark .btn-primary {
        background-color: var(--primary-green) !important;
        color: white !important;
        font-weight: 600 !important;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3) !important;
    }
    
    .dark .btn-primary:hover {
        background-color: #018d43 !important;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4) !important;
    }
    
    .dark .btn-primary:disabled {
        background-color: #4b5563 !important;
        color: #9ca3af !important;
        box-shadow: none !important;
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
        background-color: #1e3a8a !important;
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
    .dark .product-card,
    .dark .card {
        box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.6), 0 4px 8px -2px rgba(0, 0, 0, 0.4) !important;
    }
    
    /* Header specific color */
    .dark header,
    html.dark header {
        background-color: #374151 !important;
        background: #374151 !important;
        border-bottom: 1px solid #4b5563 !important;
        color: #ffffff !important;
    }
    
    /* Header date/time display */
    .dark header .bg-gray-100 {
        background-color: #4b5563 !important;
    }
    
    .dark header .text-gray-500 {
        color: #e5e7eb !important;
    }
    
    .dark header .text-gray-400 {
        color: #9ca3af !important;
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
        min-height: 100vh !important;
    }
    
    /* Main content container - keep layout but use #1f2937 bg color */
    .dark main.p-4,
    .dark main[class*="p-4"],
    .dark main[class*="sm:p-6"],
    .dark main[class*="max-w-screen-2xl"],
    html.dark main.p-4,
    html.dark main[class*="p-4"],
    html.dark main[class*="sm:p-6"],
    html.dark main[class*="max-w-screen-2xl"] {
        background-color: #1f2937 !important;
        background: #1f2937 !important;
        min-height: 100vh !important;
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
    
    /* Modals */
    .dark .modal-content {
        background-color: var(--color-bg-secondary) !important;
        color: white !important;
    }
    
    /* Dropdown menus */
    .dark .dropdown-menu {
        background-color: var(--color-bg-secondary) !important;
        border-color: var(--color-border) !important;
    }
    
    /* Hover states */
    .dark .hover\\:bg-gray-50:hover,
    .dark .hover\\:bg-gray-100:hover {
        background-color: #4b5563 !important;
    }
    
    .dark .hover\\:bg-gray-200:hover {
        background-color: #6b7280 !important;
    }
    
    /* Focus states */
    .dark input:focus,
    .dark select:focus,
    .dark textarea:focus {
        border-color: var(--primary-green) !important;
        box-shadow: 0 0 0 3px rgba(1, 167, 79, 0.2) !important;
    }
</style>';
    
    return [
        'toggle' => $darkModeToggle,
        'styles' => $darkModeStyles,
        'script' => $darkModeScript,
        'is_dark' => $isDarkMode
    ];
}

// Make dark mode assets available globally
$posDarkMode = getPOSDarkModeAssets();
?>
