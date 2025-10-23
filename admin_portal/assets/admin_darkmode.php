<?php
/**
 * Centralized Dark Mode System for Admin Portal
 * Include this file in all admin portal pages for consistent dark mode functionality
 */
?>

<!-- Dark Mode CSS and JavaScript -->
<script>
    // Dark mode configuration and Tailwind CSS setup
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    primary: {
                        50: '#f0fdf4',
                        100: '#dcfce7',
                        200: '#bbf7d0',
                        300: '#86efac',
                        400: '#4ade80',
                        500: '#01A74F',
                        600: '#16a34a',
                        700: '#15803d',
                        800: '#166534',
                        900: '#14532d',
                    }
                }
            }
        }
    }
</script>

<style>
    /* Dark mode CSS variables and styles */
    :root {
        --primary-green: #01A74F;
        --light-gray: #f3f4f6;
        --dark-bg: #1f2937;
        --dark-surface: #374151;
        --dark-border: #4b5563;
        --dark-text: #f9fafb;
        --dark-text-secondary: #d1d5db;
    }

    /* Dark mode styles */
    .dark {
        color-scheme: dark;
        color: #ffffff !important;
    }

    /* Text color overrides for dark mode */
    .dark,
    .dark p,
    .dark h1,
    .dark h2,
    .dark h3,
    .dark h4,
    .dark h5,
    .dark h6,
    .dark span,
    .dark div,
    .dark td,
    .dark th,
    .dark label,
    .dark .text-gray-700,
    .dark .text-gray-800,
    .dark .text-gray-900 {
        color: #ffffff !important;
    }

    /* Sidebar dark mode styles */
    .dark .sidebar {
        background-color: #111827;
        border-right: 1px solid var(--dark-border);
    }

    .dark .sidebar .nav-link {
        color: var(--dark-text-secondary);
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
        color: var(--dark-text-secondary);
    }
    
    .dark .sidebar .nav-link:hover svg {
        color: white;
    }

    /* Header dark mode styles */
    .dark header {
        background-color: var(--dark-surface);
        border-bottom: 1px solid var(--dark-border);
        color: #ffffff;
    }

    /* Main content dark mode styles */
    .dark main {
        background-color: var(--dark-bg);
        color: #ffffff;
    }

    /* Card/Panel dark mode styles */
    .dark .bg-white {
        background-color: var(--dark-surface) !important;
        color: #ffffff !important;
        border-color: var(--dark-border) !important;
    }

    /* Tables */
    .dark table {
        color: #ffffff;
    }

    .dark th,
    .dark td {
        border-color: var(--dark-border);
        color: #ffffff;
    }

    /* Input and form dark mode styles */
    .dark input[type="text"],
    .dark input[type="email"],
    .dark input[type="password"],
    .dark input[type="number"],
    .dark input[type="date"],
    .dark select,
    .dark textarea {
        background-color: #4b5563;
        border-color: var(--dark-border);
        color: #ffffff;
    }

    /* Placeholder text */
    .dark ::placeholder {
        color: #d1d5db !important;
        opacity: 1;
    }

    /* Links */
    .dark a {
        color: #93c5fd;
    }

    .dark a:hover {
        color: #60a5fa;
    }

    /* Buttons */
    .dark .btn-primary {
        background-color: var(--primary-green);
        color: white;
    }

    /* Dropdowns */
    .dark .dropdown-content {
        background-color: var(--dark-surface);
        border: 1px solid var(--dark-border);
    }

    /* Modal */
    .dark .modal-content {
        background-color: var(--dark-surface);
        color: #ffffff;
    }

    /* Alerts and notifications */
    .dark .alert {
        color: #ffffff;
    }
        color: var(--dark-text);
    }

    .dark input[type="text"]:focus,
    .dark input[type="email"]:focus,
    .dark input[type="password"]:focus,
    .dark input[type="number"]:focus,
    .dark input[type="date"]:focus,
    .dark select:focus,
    .dark textarea:focus {
        border-color: var(--primary-green);
        ring-color: var(--primary-green);
    }

    /* Button dark mode styles */
    .dark .btn-secondary {
        background-color: #4b5563;
        color: var(--dark-text);
        border-color: var(--dark-border);
    }

    .dark .btn-secondary:hover {
        background-color: #6b7280;
    }

    /* Table dark mode styles */
    .dark table {
        background-color: var(--dark-surface);
        color: var(--dark-text);
    }

    .dark table th {
        background-color: #374151;
        color: var(--dark-text);
        border-color: var(--dark-border);
    }

    .dark table td {
        border-color: var(--dark-border);
    }

    .dark table tbody tr:hover {
        background-color: #4b5563;
    }

    /* Modal dark mode styles */
    .dark .modal-content {
        background-color: var(--dark-surface);
        color: var(--dark-text);
        border-color: var(--dark-border);
    }

    /* Notification dropdown dark mode styles */
    .dark #notification-dropdown {
        background-color: var(--dark-surface);
        border-color: var(--dark-border);
        color: var(--dark-text);
    }

    .dark #notification-dropdown a:hover {
        background-color: #4b5563;
    }

    /* User menu dark mode styles */
    .dark #user-menu {
        background-color: var(--dark-surface);
        border-color: var(--dark-border);
        color: var(--dark-text);
    }

    .dark #user-menu a:hover {
        background-color: #4b5563;
    }

    /* Chart dark mode styles */
    .dark .chart-container {
        background-color: var(--dark-surface);
    }

    /* Text color overrides for dark mode */
    .dark .text-gray-600 {
        color: var(--dark-text-secondary) !important;
    }

    .dark .text-gray-700 {
        color: var(--dark-text-secondary) !important;
    }

    .dark .text-gray-800 {
        color: var(--dark-text) !important;
    }

    .dark .text-gray-900 {
        color: var(--dark-text) !important;
    }

    /* Background color overrides for dark mode */
    .dark .bg-gray-50 {
        background-color: #374151 !important;
    }

    .dark .bg-gray-100 {
        background-color: var(--dark-bg) !important;
    }

    .dark .bg-gray-200 {
        background-color: #4b5563 !important;
    }

    /* Border color overrides for dark mode */
    .dark .border-gray-200 {
        border-color: var(--dark-border) !important;
    }

    .dark .border-gray-300 {
        border-color: var(--dark-border) !important;
    }

    /* Ring color overrides for dark mode */
    .dark .ring-gray-200 {
        --tw-ring-color: var(--dark-border) !important;
    }

    /* Dark mode toggle button styles */
    .dark-mode-toggle {
        position: relative;
        cursor: pointer;
        border: none;
        outline: none;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dark-mode-toggle:focus {
        outline: 2px solid var(--primary-green);
        outline-offset: 2px;
    }

    .dark-mode-icon-light,
    .dark-mode-icon-dark {
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .dark-mode-toggle:hover .dark-mode-icon-light,
    .dark-mode-toggle:hover .dark-mode-icon-dark {
        transform: scale(1.1);
    }

    /* Smooth transitions for dark mode */
    * {
        transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }
</style>

<script>
    // Dark Mode JavaScript functionality
    class DarkModeManager {
        constructor() {
            this.darkModeKey = 'admin_dark_mode';
            this.init();
        }

        init() {
            // Check for saved dark mode preference or default to light mode
            const savedMode = localStorage.getItem(this.darkModeKey);
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedMode === 'dark' || (!savedMode && prefersDark)) {
                this.enableDarkMode();
            } else {
                this.disableDarkMode();
            }

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem(this.darkModeKey)) {
                    if (e.matches) {
                        this.enableDarkMode();
                    } else {
                        this.disableDarkMode();
                    }
                }
            });

            this.setupToggleListeners();
        }

        enableDarkMode() {
            document.documentElement.classList.add('dark');
            localStorage.setItem(this.darkModeKey, 'dark');
            this.updateToggleButtons(true);
        }

        disableDarkMode() {
            document.documentElement.classList.remove('dark');
            localStorage.setItem(this.darkModeKey, 'light');
            this.updateToggleButtons(false);
        }

        toggle() {
            if (document.documentElement.classList.contains('dark')) {
                this.disableDarkMode();
            } else {
                this.enableDarkMode();
            }
        }

        updateToggleButtons(isDark) {
            const toggleButtons = document.querySelectorAll('.dark-mode-toggle');
            toggleButtons.forEach(button => {
                button.setAttribute('aria-checked', isDark);
                button.title = isDark ? 'Switch to light mode' : 'Switch to dark mode';
                
                // Toggle icon visibility
                const lightIcon = button.querySelector('.dark-mode-icon-light');
                const darkIcon = button.querySelector('.dark-mode-icon-dark');
                
                if (lightIcon && darkIcon) {
                    if (isDark) {
                        lightIcon.classList.add('hidden');
                        darkIcon.classList.remove('hidden');
                    } else {
                        lightIcon.classList.remove('hidden');
                        darkIcon.classList.add('hidden');
                    }
                }
            });

            // Update any text indicators
            const modeTexts = document.querySelectorAll('.dark-mode-text');
            modeTexts.forEach(text => {
                text.textContent = isDark ? 'Dark' : 'Light';
            });
        }

        setupToggleListeners() {
            // Setup toggle button listeners
            document.addEventListener('click', (e) => {
                if (e.target.matches('.dark-mode-toggle') || e.target.closest('.dark-mode-toggle')) {
                    e.preventDefault();
                    this.toggle();
                }
            });

            // Keyboard support for toggle
            document.addEventListener('keydown', (e) => {
                if ((e.target.matches('.dark-mode-toggle') || e.target.closest('.dark-mode-toggle')) && 
                    (e.key === 'Enter' || e.key === ' ')) {
                    e.preventDefault();
                    this.toggle();
                }
            });
        }

        // Method to get current mode
        getCurrentMode() {
            return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        }

        // Method for external components to listen to mode changes
        onModeChange(callback) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const isDark = document.documentElement.classList.contains('dark');
                        callback(isDark ? 'dark' : 'light');
                    }
                });
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });

            return observer;
        }
    }

    // Initialize dark mode when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        window.darkModeManager = new DarkModeManager();
        
        // Make it globally accessible for debugging
        window.toggleDarkMode = () => window.darkModeManager.toggle();
    });

    // Chart.js dark mode configuration
    window.chartDarkModeConfig = {
        plugins: {
            legend: {
                labels: {
                    color: function(context) {
                        return document.documentElement.classList.contains('dark') ? '#f9fafb' : '#374151';
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: function(context) {
                        return document.documentElement.classList.contains('dark') ? '#d1d5db' : '#6b7280';
                    }
                },
                grid: {
                    color: function(context) {
                        return document.documentElement.classList.contains('dark') ? '#4b5563' : '#e5e7eb';
                    }
                }
            },
            y: {
                ticks: {
                    color: function(context) {
                        return document.documentElement.classList.contains('dark') ? '#d1d5db' : '#6b7280';
                    }
                },
                grid: {
                    color: function(context) {
                        return document.documentElement.classList.contains('dark') ? '#4b5563' : '#e5e7eb';
                    }
                }
            }
        }
    };
</script>
