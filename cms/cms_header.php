<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Fetch notifications
$notifications_json = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../api/get_notifications.php');
$notifications_data = json_decode($notifications_json, true);
$total_notifications = $notifications_data['total_notifications'] ?? 0;
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
            <!-- Notification Bell -->
            <div class="relative">
                <button id="notification-bell-btn" class="relative p-2 rounded-full hover:bg-gray-100 text-gray-500 transition-colors">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    <?php if ($total_notifications > 0): ?>
                        <span class="absolute top-1.5 right-1.5 block h-4 w-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center ring-2 ring-white"><?php echo $total_notifications; ?></span>
                    <?php endif; ?>
                </button>
                <!-- Notification Dropdown -->
                <div id="notification-dropdown" class="origin-top-right absolute right-0 mt-2 w-80 sm:w-96 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden z-40">
                    <div class="flex justify-between items-center p-3 sm:p-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Notifications</h3>
                        <a href="/pharmacy_system/inventory/inventory-tracking.php" class="text-sm font-medium text-green-600 hover:text-green-800">View All</a>
                    </div>
                    <div class="py-1 max-h-80 overflow-y-auto">
                        <?php if ($total_notifications > 0): ?>
                            <?php if (!empty($notifications_data['expiring_soon'])): ?>
                                <?php foreach ($notifications_data['expiring_soon'] as $item): ?>
                                    <a href="/pharmacy_system/inventory/inventory-tracking.php?view=expiration-alert" class="flex items-start gap-3 px-3 sm:px-4 py-3 text-sm text-gray-700 hover:bg-gray-100">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($item['name']); ?></p>
                                            <p class="text-xs text-gray-500">Lot: <?php echo htmlspecialchars($item['lot_number']); ?> is expiring on <?php echo date("M d, Y", strtotime($item['expiration_date'])); ?>.</p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (!empty($notifications_data['expired'])): ?>
                                <?php foreach ($notifications_data['expired'] as $item): ?>
                                    <a href="/pharmacy_system/inventory/inventory-tracking.php?view=expired" class="flex items-start gap-3 px-3 sm:px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 border-t">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" /></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($item['name']); ?></p>
                                            <p class="text-xs text-red-600 font-medium">Lot: <?php echo htmlspecialchars($item['lot_number']); ?> expired on <?php echo date("M d, Y", strtotime($item['expiration_date'])); ?>.</p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center text-gray-500 py-10 px-4">
                                <svg class="w-12 h-12 mx-auto text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p class="mt-4 font-semibold">All caught up!</p>
                                <p class="text-sm">No new notifications.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- User Menu -->
            <div class="relative z-40">
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
                    <a href="#" id="profile-modal-btn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Profile</a>
                    <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Sign out</a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- New Profile Modal -->
<div id="profile-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
  <div class="flex items-center justify-center min-h-screen p-4 text-center">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
    <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all my-8 max-w-sm w-full">
        <div class="bg-white p-6">
            <div class="text-center">
                <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">
                    User Profile
                </h3>
                <div class="mt-6 flex justify-center">
                    <?php
                        if ($profileImage) {
                            echo '<img class="w-24 h-24 rounded-full object-cover ring-4 ring-green-200" src="../' . htmlspecialchars($profileImage) . '" alt="User profile">';
                        } else {
                            echo '<div class="w-24 h-24 rounded-full bg-green-500 flex items-center justify-center text-white font-bold text-4xl ring-4 ring-green-200">' . htmlspecialchars($userInitial) . '</div>';
                        }
                    ?>
                </div>
                <div class="mt-4">
                    <p class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                    <p class="text-sm text-gray-500">@<?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </div>
                <div class="mt-4 text-left bg-gray-50 p-3 rounded-lg">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-gray-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                        <span class="text-sm text-gray-700"><strong>Role:</strong> <span class="capitalize font-medium text-green-600"><?php echo htmlspecialchars($_SESSION['role']); ?></span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <button type="button" id="close-profile-modal-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:text-sm">
                Close
            </button>
        </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
        
        const profileModalBtn = document.getElementById('profile-modal-btn');
        const profileModal = document.getElementById('profile-modal');
        const closeProfileModalBtn = document.getElementById('close-profile-modal-btn');
        const notificationBellBtn = document.getElementById('notification-bell-btn');
        const notificationDropdown = document.getElementById('notification-dropdown');
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        const dateTimeEl = document.getElementById('date-time');

        if(userMenuButton) {
            userMenuButton.addEventListener('click', () => userMenu.classList.toggle('hidden'));
        }

        window.addEventListener('click', (e) => {
            if (userMenuButton && !userMenuButton.contains(e.target) && userMenu && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });

        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'short', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            if (dateTimeEl) {
               dateTimeEl.textContent = now.toLocaleDateString('en-US', options);
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        if (profileModalBtn) {
            profileModalBtn.addEventListener('click', (e) => {
                e.preventDefault();
                profileModal.classList.remove('hidden');
            });
        }
        if (closeProfileModalBtn) {
            closeProfileModalBtn.addEventListener('click', () => {
                profileModal.classList.add('hidden');
            });
        }
        if (notificationBellBtn) {
            notificationBellBtn.addEventListener('click', () => {
                notificationDropdown.classList.toggle('hidden');
            });
            window.addEventListener('click', (e) => {
                if (!notificationBellBtn.contains(e.target) && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.classList.add('hidden');
                }
            });
        }
    });
</script>

