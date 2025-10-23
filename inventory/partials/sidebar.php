<aside id="sidebar" class="sidebar flex flex-col md:relative">
    <div class="p-4 flex items-center gap-3 border-b border-white/20 h-[73px] flex-shrink-0">
        <img src="../mjpharmacy.logo.jpg" alt="Logo" class="w-10 h-10 rounded-full bg-white object-cover shadow-md flex-shrink-0">
        <h1 class="text-xl font-bold tracking-tight text-white nav-text">INVENTORY</h1>
    </div>
    <nav class="flex-1 p-4 space-y-2">
        <a href="products.php" class="nav-link <?php echo ($currentPage === 'products') ? 'active' : ''; ?> flex items-center px-4 py-3 rounded-lg">
            <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            <span class="nav-text">Products</span>
        </a>
        <a href="inventory-tracking.php" class="nav-link <?php echo ($currentPage === 'inventory') ? 'active' : ''; ?> flex items-center px-4 py-3 rounded-lg">
            <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span class="nav-text">Inventory Tracking</span>
        </a>
        <a href="purchase-history.php" class="nav-link <?php echo ($currentPage === 'history') ? 'active' : ''; ?> flex items-center px-4 py-3 rounded-lg">
            <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="nav-text">Purchase History</span>
        </a>
    </nav>
</aside>

