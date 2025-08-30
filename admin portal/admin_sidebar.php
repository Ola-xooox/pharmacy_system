<aside id="sidebar" class="sidebar flex flex-col md:relative">
    <div class="p-4 flex items-center gap-3 border-b border-white/20 h-[73px] flex-shrink-0">
        <img src="../mjpharmacy.logo.jpg" alt="Logo" class="w-10 h-10 rounded-full bg-white object-cover shadow-md flex-shrink-0">
        <h1 class="text-xl font-bold tracking-tight text-white nav-text">ADMIN PORTAL</h1>
    </div>
    <nav class="flex-1 p-4 space-y-2">
        <a href="dashboard.php" class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?> flex items-center px-4 py-3 rounded-lg">
            <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M3 3.75A.75.75 0 013.75 3h6a.75.75 0 01.75.75v6a.75.75 0 01-.75.75h-6a.75.75 0 01-.75-.75v-6zm0 9.75A.75.75 0 013.75 13.5h6a.75.75 0 01.75.75v6a.75.75 0 01-.75.75h-6a.75.75 0 01-.75-.75v-6zm9.75-9.75A.75.75 0 0113.5 3h6a.75.75 0 01.75.75v6a.75.75 0 01-.75.75h-6a.75.75 0 01-.75-.75v-6zm9.75 9.75A.75.75 0 0113.5 13.5h6a.75.75 0 01.75.75v6a.75.75 0 01-.75.75h-6a.75.75 0 01-.75-.75v-6z" clip-rule="evenodd" />
            </svg>
            <span class="nav-text">Dashboard</span>
        </a>
        <a href="setup_account.php" class="nav-link <?php echo ($currentPage === 'setup_account') ? 'active' : ''; ?> flex items-center px-4 py-3 rounded-lg">
            <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.125 19.125a2.25 2.25 0 012.25-2.25h5.375a.75.75 0 010 1.5H5.375a.75.75 0 00-.75.75v.125a.75.75 0 00.75.75h9a.75.75 0 00.75-.75v-.125a.75.75 0 00-.75-.75h-1.5a.75.75 0 010-1.5h1.5a2.25 2.25 0 012.25 2.25v.125c0 .621-.504 1.125-1.125 1.125H4.25A1.125 1.125 0 013.125 19.125z" />
                <path d="M17.625 1.125a.75.75 0 01.75.75v2.625h2.625a.75.75 0 010 1.5h-2.625v2.625a.75.75 0 01-1.5 0V6h-2.625a.75.75 0 010-1.5h2.625V1.875a.75.75 0 01.75-.75z" />
            </svg>
            <span class="nav-text">Set Up New Account</span>
        </a>
        <a href="user_activity_log.php" class="nav-link <?php echo ($currentPage === 'user_activity_log') ? 'active' : ''; ?> flex items-center px-4 py-3 rounded-lg">
            <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.5 3.375c0-1.036.84-1.875 1.875-1.875h.375a3.75 3.75 0 013.75 3.75v1.875h-9.75V3.375z" />
                <path fill-rule="evenodd" d="M16.5 3.375h.375a1.875 1.875 0 011.875 1.875v13.5a1.875 1.875 0 01-1.875 1.875h-9.75A1.875 1.875 0 015.25 18.75V5.25a1.875 1.875 0 011.875-1.875h1.5v1.875c0 1.036.84 1.875 1.875 1.875h3.375V3.375zM15 6.75a.75.75 0 00-.75.75v3.375a.75.75 0 001.5 0V7.5a.75.75 0 00-.75-.75zM9.75 8.625a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zm.75 2.625a.75.75 0 000 1.5h3a.75.75 0 000-1.5h-3z" clip-rule="evenodd" />
            </svg>
            <span class="nav-text">User Activity Log</span>
        </a>
        <a href="inventory_report.php" class="nav-link <?php echo ($currentPage === 'inventory_report') ? 'active' : ''; ?> flex items-center px-4 py-3 rounded-lg">
            <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375z" />
                <path fill-rule="evenodd" d="M3.087 9l.54 9.176A3 3 0 006.62 21h10.757a3 3 0 002.995-2.824L20.913 9H3.087zm6.163 3.75A.75.75 0 0110 12h4a.75.75 0 010 1.5h-4a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
            </svg>
            <span class="nav-text">Inventory Report</span>
        </a>
        <a href="sales_report.php" class="nav-link <?php echo ($currentPage === 'sales_report') ? 'active' : ''; ?> flex items-center px-4 py-3 rounded-lg">
            <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c-1.036 0-1.875.84-1.875 1.875v9.375c0 1.036.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V10.5c0-1.036-.84-1.875-1.875-1.875h-.75zM3 13.5c-1.036 0-1.875.84-1.875 1.875v3.375c0 1.036.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V15.375c0-1.036-.84-1.875-1.875-1.875h-.75z" />
            </svg>
            <span class="nav-text">Sales Report</span>
        </a>
    </nav>
</aside>