<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center p-4">
        <button id="sidebar-toggle-btn" class="p-2 rounded-full hover:bg-gray-100">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <div class="flex items-center gap-4 ml-auto">
            <div class="hidden md:flex items-center gap-2 text-sm bg-gray-100 px-3 py-1.5 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <span id="date-time"></span>
            </div>
            <button class="relative p-2 rounded-full hover:bg-gray-100">
                <svg class="w-6 h-6 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                <span class="absolute top-1 right-1 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
            </button>
            <div class="relative">
                <button id="user-menu-button" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span class="sr-only">Open user menu</span>
                    <?php
                        $userName = $_SESSION['name'] ?? 'Admin';
                        $userInitial = strtoupper(substr($userName, 0, 1));
                        $profileImage = $_SESSION['profile_image'] ?? null;

                        if ($profileImage) {
                            echo '<img class="w-8 h-8 rounded-full object-cover" src="../' . htmlspecialchars($profileImage) . '" alt="User profile">';
                        } else {
                            echo '<div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white font-bold">' . htmlspecialchars($userInitial) . '</div>';
                        }
                    ?>
                </button>
                <div id="user-menu" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 hidden" role="menu">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Profile</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Settings</a>
                    <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Sign out</a>
                </div>
            </div>
        </div>
    </div>
</header>