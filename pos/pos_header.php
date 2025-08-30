<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="bg-white border-b border-gray-200 sticky top-0 z-30">
     <div class="flex items-center justify-between p-4 max-w-screen-2xl mx-auto">
        <div class="flex items-center gap-3">
            <img src="../mjpharmacy.logo.jpg" alt="MJ Pharmacy Logo" class="h-10 w-10 rounded-full object-cover">
            <h1 class="text-xl font-semibold text-gray-800 hidden sm:block tracking-tight">MJ Pharmacy</h1>
        </div>

        <div class="flex items-center gap-2 sm:gap-4">
            <div class="hidden md:flex items-center gap-2 text-sm text-gray-500 bg-gray-100 px-4 py-2 rounded-full">
                <i data-lucide="calendar-days" class="w-4 h-4 text-gray-400"></i>
                <span id="date-time"></span>
            </div>
            <button class="relative p-2 rounded-full hover:bg-gray-100 text-gray-500 transition-colors">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
            </button>
            <div class="relative">
                <button id="user-menu-button" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span class="sr-only">Open user menu</span>
                     <?php
                        $userName = $_SESSION['name'] ?? 'User';
                        $userInitial = strtoupper(substr($userName, 0, 1));
                        $profileImage = $_SESSION['profile_image'] ?? null;

                        if ($profileImage) {
                            echo '<img class="w-9 h-9 rounded-full object-cover" src="../' . htmlspecialchars($profileImage) . '" alt="User profile">';
                        } else {
                            echo '<div class="w-9 h-9 rounded-full bg-green-500 flex items-center justify-center text-white font-bold text-sm">' . htmlspecialchars($userInitial) . '</div>';
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