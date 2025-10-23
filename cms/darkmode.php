<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize dark mode from session or default to light
if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}

// Toggle dark mode if requested (handles both GET and POST)
if (isset($_GET['toggle_dark_mode']) || isset($_POST['toggle_dark_mode'])) {
    if (isset($_POST['toggle_dark_mode'])) {
        $_SESSION['dark_mode'] = ($_POST['toggle_dark_mode'] === '1');
    } else {
        $_SESSION['dark_mode'] = !$_SESSION['dark_mode'];
    }
    
    // If it's an AJAX request, just return success
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'dark_mode' => $_SESSION['dark_mode']]);
        exit();
    }
    
    // For regular requests, redirect back
    $redirect_url = str_replace(['&toggle_dark_mode=1', '?toggle_dark_mode=1'], '', $_SERVER['REQUEST_URI']);
    $redirect_url = rtrim($redirect_url, '?&');
    header("Location: " . $redirect_url);
    exit();
}

// Dark mode CSS and JS
function getDarkModeAssets() {
    $isDarkMode = isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'];
    
    $darkModeToggle = '<button id="dark-mode-toggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" title="' . ($isDarkMode ? 'Light Mode' : 'Dark Mode') . '">
        ' . ($isDarkMode ? 
            '<i data-lucide="sun" class="w-5 h-5 text-yellow-500"></i>' : 
            '<i data-lucide="moon" class="w-5 h-5 text-gray-600"></i>') . '
    </button>';
    
    $darkModeScript = '<script>
(function() {
    // Initialize immediately
    const isDarkMode = ' . ($isDarkMode ? 'true' : 'false') . ';
    if (isDarkMode) {
        document.documentElement.classList.add("dark");
    }
    
    // Wait for DOM to be ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initDarkMode);
    } else {
        initDarkMode();
    }
    
    function initDarkMode() {
        const darkModeToggle = document.getElementById("dark-mode-toggle");
        if (!darkModeToggle) {
            console.error("Dark mode toggle button not found");
            return;
        }
        
        const html = document.documentElement;
        
        console.log("Dark mode toggle initialized");
        
        // Toggle dark mode on button click
        darkModeToggle.addEventListener("click", function(e) {
            e.preventDefault();
            console.log("Dark mode toggle clicked");
            
            const isDark = html.classList.toggle("dark");
            console.log("Dark mode is now:", isDark);
            
            // Update icon
            const icon = darkModeToggle.querySelector("i");
            if (icon) {
                if (isDark) {
                    icon.setAttribute("data-lucide", "sun");
                    icon.classList.add("text-yellow-500");
                    icon.classList.remove("text-gray-600");
                    darkModeToggle.title = "Switch to Light Mode";
                } else {
                    icon.setAttribute("data-lucide", "moon");
                    icon.classList.add("text-gray-600");
                    icon.classList.remove("text-yellow-500");
                    darkModeToggle.title = "Switch to Dark Mode";
                }
                // Recreate icons
                if (typeof lucide !== "undefined" && lucide.createIcons) {
                    lucide.createIcons();
                }
            }
            
            // Save preference to session via AJAX
            fetch(window.location.pathname + "?toggle_dark_mode=1", {
                method: "GET"
            }).catch(error => console.error("Error saving dark mode preference:", error));
        });
    }
})();
</script>';
    
    $darkModeStyles = '<style>
    /* Dark mode styles */
    .dark {
        --color-bg-primary: #1a202c;
        --color-bg-secondary: #2d3748;
        --color-text-primary: #ffffff;
        --color-text-secondary: #e2e8f0;
        --color-border: #4a5568;
    }
    
    .dark body {
        background-color: var(--color-bg-primary);
        color: var(--color-text-primary) !important;
    }
    
    .dark .bg-white {
        background-color: var(--color-bg-secondary);
        color: var(--color-text-primary) !important;
    }
    
    /* Make all text white in dark mode */
    .dark,
    .dark * {
        color: white !important;
    }
    
    /* Override specific grays */
    .dark .text-gray-800,
    .dark .text-gray-700,
    .dark .text-gray-900,
    .dark .text-gray-600 {
        color: white !important;
    }
    
    .dark .text-gray-500,
    .dark .text-gray-400 {
        color: #e2e8f0 !important;
    }
    
    /* Keep important colors */
    .dark .text-red-500,
    .dark .text-red-600 {
        color: #ef4444 !important;
    }
    
    .dark .text-green-500,
    .dark .text-green-600,
    .dark .text-brand-green {
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
    
    .dark .border-gray-200,
    .dark .border-t,
    .dark .border-b,
    .dark .border-l,
    .dark .border-r,
    .dark .border {
        border-color: var(--color-border) !important;
    }
    
    .dark .bg-gray-100,
    .dark .hover\:bg-gray-100:hover {
        background-color: var(--color-bg-secondary) !important;
    }
    
    .dark .ring-1 {
        --tw-ring-color: var(--color-border) !important;
    }
    
    /* Custom dark mode styles for specific elements */
    .dark .modal-overlay {
        background-color: rgba(0, 0, 0, 0.7);
    }
    
    .dark .modal-content {
        background-color: var(--color-bg-secondary);
        border-color: var(--color-border);
    }
    
    .dark input[type="text"],
    .dark input[type="search"],
    .dark select,
    .dark textarea {
        background-color: var(--color-bg-secondary);
        border-color: var(--color-border);
        color: var(--color-text-primary);
    }
    
    .dark input::placeholder,
    .dark textarea::placeholder {
        color: var(--color-text-secondary);
        opacity: 0.7;
    }
    
    /* Table styles */
    .dark table {
        border-color: var(--color-border);
        color: white !important;
    }
    
    .dark th,
    .dark td {
        border-color: var(--color-border);
        color: white !important;
    }
    
    .dark thead {
        background-color: #374151 !important;
        color: white !important;
    }
    
    .dark tbody {
        color: white !important;
    }
    
    .dark tr:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    
    /* Ensure all headings are white */
    .dark h1,
    .dark h2,
    .dark h3,
    .dark h4,
    .dark h5,
    .dark h6 {
        color: white !important;
    }
    
    /* Ensure all paragraphs are white */
    .dark p,
    .dark span,
    .dark div,
    .dark label {
        color: white !important;
    }
    
    /* Buttons in dark mode */
    .dark button {
        color: white !important;
    }
    
    /* Links in dark mode */
    .dark a {
        color: #60a5fa !important;
    }
    
    .dark a:hover {
        color: #93c5fd !important;
    }
    
    /* Pagination */
    .dark .pagination-button {
        color: white !important;
        border-color: var(--color-border);
        background-color: #374151;
    }
    
    .dark .pagination-button:hover:not(.active) {
        background-color: #4b5563;
    }
    
    .dark .pagination-button.active {
        background-color: #01A74F !important;
        border-color: #01A74F;
        color: white !important;
    }
    
    /* Keep white text exceptions */
    .dark .text-white {
        color: white !important;
    }
    
    /* SVG and icon colors */
    .dark svg {
        color: currentColor;
    }
    
    /* Keep badge and notification colors visible */
    .dark .bg-red-500 {
        background-color: #ef4444 !important;
        color: white !important;
    }
    
    .dark .bg-blue-100 {
        background-color: #1e40af !important;
    }
    
    .dark .text-blue-800 {
        color: #93c5fd !important;
    }
    </style>';
    
    return [
        'toggle' => $darkModeToggle,
        'styles' => $darkModeStyles,
        'script' => $darkModeScript,
        'is_dark' => $isDarkMode
    ];
}
?>
