<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include dark mode functionality
require_once 'darkmode.php';

// Fetch notifications
$notifications_json = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../api/get_notifications.php');
$notifications_data = json_decode($notifications_json, true);
$total_notifications = $notifications_data['total_notifications'] ?? 0;
?>
<header class="bg-white border-b border-gray-200 sticky top-0 z-30">
     <div class="flex items-center justify-between p-4 max-w-full mx-auto">
        <!-- Left Side: Logo + MJ Pharmacy Text -->
        <div class="flex items-center gap-3">
            <img src="../mjpharmacy.logo.jpg" alt="MJ Pharmacy Logo" class="h-10 w-10 rounded-full object-cover">
            <h1 class="text-xl font-semibold text-gray-800 tracking-tight">MJ Pharmacy</h1>
        </div>

        <!-- Right Side: Date/Time + Dark Mode Toggle + Notifications + Profile -->
        <div class="flex items-center gap-3">
            <!-- Purchase History Button -->
            <button id="purchase-history-btn" class="flex items-center gap-2 px-3 py-2 text-sm bg-brand-green text-white rounded-full hover:bg-opacity-90 transition-colors" title="Purchase History">
                <i class="fas fa-history w-4 h-4"></i>
                <span class="hidden sm:inline">History</span>
            </button>
            
            <!-- Date and Time -->
            <div class="flex items-center gap-2 text-sm text-gray-500 bg-gray-100 px-3 py-2 rounded-full">
                <i class="fas fa-calendar-alt w-4 h-4 text-gray-400"></i>
                <span id="date-time"></span>
            </div>
            
            <!-- Dark Mode Toggle -->
            <?php echo $posDarkMode['toggle']; ?>
            
            <!-- Notification Bell -->
            <div class="relative">
                <button id="notification-bell-btn" class="relative p-2 rounded-full hover:bg-gray-100 text-gray-500 transition-colors">
                    <i class="fas fa-bell w-5 h-5"></i>
                    <?php if ($total_notifications > 0): ?>
                        <span class="absolute top-1.5 right-1.5 block h-3 w-3 rounded-full bg-red-500 text-white text-xs flex items-center justify-center ring-1 ring-white" style="font-size: 10px;"><?php echo $total_notifications; ?></span>
                    <?php endif; ?>
                </button>
                <!-- Notification Dropdown -->
                <div id="notification-dropdown" class="origin-top-right absolute right-0 mt-2 w-80 sm:w-96 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 hidden z-40">
                    <div class="flex justify-between items-center p-3 sm:p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Notifications</h3>
                        <a href="" class="text-sm font-medium text-green-600 hover:text-green-800"></a>
                    </div>
                    <div class="py-1 max-h-80 overflow-y-auto">
                        <?php if ($total_notifications > 0): ?>
                            <?php if (!empty($notifications_data['expiring_soon'])): ?>
                                <?php foreach ($notifications_data['expiring_soon'] as $item): ?>
                                    <div class="notification-item flex items-start gap-3 px-3 sm:px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors"
                                         data-type="expiring"
                                         data-name="<?php echo htmlspecialchars($item['name'] ?? 'Unknown'); ?>"
                                         data-lot="<?php echo htmlspecialchars($item['lot_number'] ?? 'N/A'); ?>"
                                         data-batch="<?php echo htmlspecialchars($item['batch_number'] ?? 'N/A'); ?>"
                                         data-expiry="<?php echo date('M d, Y', strtotime($item['expiration_date'])); ?>"
                                         data-stock="<?php echo htmlspecialchars($item['stock'] ?? '0'); ?>"
                                         data-supplier="<?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?>">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($item['name'] ?? 'Unknown'); ?></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Lot: <?php echo htmlspecialchars($item['lot_number'] ?? 'N/A'); ?> is expiring on <?php echo date("M d, Y", strtotime($item['expiration_date'])); ?>.</p>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (!empty($notifications_data['expired'])): ?>
                                <?php foreach ($notifications_data['expired'] as $item): ?>
                                    <div class="notification-item flex items-start gap-3 px-3 sm:px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border-t border-gray-200 dark:border-gray-700 cursor-pointer transition-colors"
                                         data-type="expired"
                                         data-name="<?php echo htmlspecialchars($item['name'] ?? 'Unknown'); ?>"
                                         data-lot="<?php echo htmlspecialchars($item['lot_number'] ?? 'N/A'); ?>"
                                         data-batch="<?php echo htmlspecialchars($item['batch_number'] ?? 'N/A'); ?>"
                                         data-expiry="<?php echo date('M d, Y', strtotime($item['expiration_date'])); ?>"
                                         data-stock="<?php echo htmlspecialchars($item['stock'] ?? '0'); ?>"
                                         data-supplier="<?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?>">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" /></svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($item['name'] ?? 'Unknown'); ?></p>
                                            <p class="text-xs text-red-600 dark:text-red-400 font-medium">Lot: <?php echo htmlspecialchars($item['lot_number'] ?? 'N/A'); ?> expired on <?php echo date("M d, Y", strtotime($item['expiration_date'])); ?>.</p>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center text-gray-500 dark:text-gray-400 py-10 px-4">
                                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p class="mt-4 font-semibold">All caught up!</p>
                                <p class="text-sm">No new notifications.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- User Profile -->
            <div class="relative z-40">
                <button id="user-menu-button" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span class="sr-only">Open user menu</span>
                     <?php
                        $username = $_SESSION['username'] ?? 'User';
                        $userInitial = strtoupper(substr($username, 0, 1));
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
                    <a href="#" id="sign-out-btn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Sign out</a>
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

<!-- Sign Out Confirmation Modal -->
<div id="signout-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                    <svg class="h-6 w-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                    </svg>
                </div>
            </div>
            <div class="text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Sign Out</h3>
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to sign out? You will need to log in again to access the system.</p>
                <div class="flex justify-center gap-3">
                    <button id="cancel-signout-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button id="confirm-signout-btn" class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 border border-transparent rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                        Sign Out
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Detail Modal -->
<div id="notification-detail-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="notification-detail-title" role="dialog" aria-modal="true">
  <div class="flex items-center justify-center min-h-screen p-4">
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl transform transition-all my-8 max-w-2xl w-full overflow-hidden">
        <!-- Header Card -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div id="notification-icon" class="flex-shrink-0 w-12 h-12 rounded-full bg-white flex items-center justify-center shadow-lg">
                    <!-- Icon will be inserted dynamically -->
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white" id="notification-detail-title">Product Alert</h3>
                    <p class="text-sm text-green-100">Product Information Details</p>
                </div>
            </div>
            <button type="button" id="close-notification-detail-btn-x" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Content Cards -->
        <div class="p-6 bg-gray-50 dark:bg-gray-900">
            <div class="grid grid-cols-1 gap-4">
                <!-- Product Name Card -->
                <div class="bg-white dark:bg-gray-700 dark:border dark:border-gray-600 rounded-xl shadow-md p-4 border-l-4 border-green-500 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer">
                    <div class="flex flex-col items-center justify-center text-center gap-3">
                        <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">Product Name</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white" id="notif-product-name">-</p>
                    </div>
                </div>

                <!-- Lot Number Card -->
                <div class="bg-white dark:bg-gray-700 dark:border dark:border-gray-600 rounded-xl shadow-md p-4 border-l-4 border-blue-500 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer">
                    <div class="flex flex-col items-center justify-center text-center gap-3">
                        <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                        </svg>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">Lot Number</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white font-mono" id="notif-lot-number">-</p>
                    </div>
                </div>

                <!-- Expiration Date Card -->
                <div class="bg-white dark:bg-gray-700 dark:border dark:border-gray-600 rounded-xl shadow-md p-4 border-l-4 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer" id="expiry-card">
                    <div class="flex flex-col items-center justify-center text-center gap-3">
                        <svg class="w-6 h-6" id="expiry-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">Expiration Date</p>
                        <p class="text-lg font-bold" id="notif-expiry-date">-</p>
                    </div>
                </div>

                <!-- Stock Card -->
                <div class="bg-white dark:bg-gray-700 dark:border dark:border-gray-600 rounded-xl shadow-md p-4 border-l-4 border-indigo-500 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer">
                    <div class="flex flex-col items-center justify-center text-center gap-3">
                        <svg class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                        </svg>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">Current Stock</p>
                        <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400" id="notif-stock">-</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const profileModalBtn = document.getElementById('profile-modal-btn');
        const profileModal = document.getElementById('profile-modal');
        const closeProfileModalBtn = document.getElementById('close-profile-modal-btn');
        const notificationBellBtn = document.getElementById('notification-bell-btn');
        const notificationDropdown = document.getElementById('notification-dropdown');
        const signOutBtn = document.getElementById('sign-out-btn');
        const signOutModal = document.getElementById('signout-modal');
        const confirmSignOutBtn = document.getElementById('confirm-signout-btn');
        const cancelSignOutBtn = document.getElementById('cancel-signout-btn');

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
        
        // Notification bell functionality
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

        // Notification detail modal functionality
        const notificationDetailModal = document.getElementById('notification-detail-modal');
        const closeNotificationDetailBtnX = document.getElementById('close-notification-detail-btn-x');
        const notificationItems = document.querySelectorAll('.notification-item');

        // Handle clicking on notification items
        notificationItems.forEach(item => {
            item.addEventListener('click', () => {
                const type = item.dataset.type;
                const name = item.dataset.name;
                const lot = item.dataset.lot;
                const expiry = item.dataset.expiry;
                const stock = item.dataset.stock;

                // Populate modal with data
                document.getElementById('notif-product-name').textContent = name;
                document.getElementById('notif-lot-number').textContent = lot;
                document.getElementById('notif-expiry-date').textContent = expiry;
                document.getElementById('notif-stock').textContent = stock + ' units';

                // Update icon and title based on type
                const notificationIcon = document.getElementById('notification-icon');
                const notificationTitle = document.getElementById('notification-detail-title');
                const expiryDateEl = document.getElementById('notif-expiry-date');
                const expiryCard = document.getElementById('expiry-card');
                const expiryIcon = document.getElementById('expiry-icon');

                if (type === 'expiring') {
                    notificationIcon.className = 'flex-shrink-0 w-12 h-12 rounded-full bg-white flex items-center justify-center shadow-lg';
                    notificationIcon.innerHTML = '<svg class="w-6 h-6 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                    notificationTitle.textContent = 'Product Expiring Soon';
                    expiryDateEl.className = 'text-lg font-bold text-amber-600';
                    expiryCard.className = 'bg-white dark:bg-gray-700 dark:border dark:border-gray-600 rounded-xl shadow-md p-4 border-l-4 border-amber-500 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer';
                    expiryIcon.className = 'w-6 h-6 text-amber-600';
                } else if (type === 'expired') {
                    notificationIcon.className = 'flex-shrink-0 w-12 h-12 rounded-full bg-white flex items-center justify-center shadow-lg';
                    notificationIcon.innerHTML = '<svg class="w-6 h-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" /></svg>';
                    notificationTitle.textContent = 'Product Expired';
                    expiryDateEl.className = 'text-lg font-bold text-red-600';
                    expiryCard.className = 'bg-white dark:bg-gray-700 dark:border dark:border-gray-600 rounded-xl shadow-md p-4 border-l-4 border-red-500 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer';
                    expiryIcon.className = 'w-6 h-6 text-red-600';
                }

                // Hide notification dropdown and show detail modal
                notificationDropdown.classList.add('hidden');
                notificationDetailModal.classList.remove('hidden');
            });
        });

        // Close notification detail modal
        if (closeNotificationDetailBtnX) {
            closeNotificationDetailBtnX.addEventListener('click', () => {
                notificationDetailModal.classList.add('hidden');
            });
        }

        // Close modal when clicking outside
        notificationDetailModal?.addEventListener('click', (e) => {
            if (e.target === notificationDetailModal) {
                notificationDetailModal.classList.add('hidden');
            }
        });
        
        // Sign out modal functionality
        if (signOutBtn) {
            signOutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                signOutModal.classList.remove('hidden');
            });
        }
        
        if (confirmSignOutBtn) {
            confirmSignOutBtn.addEventListener('click', () => {
                window.location.href = '../logout.php';
            });
        }
        
        if (cancelSignOutBtn) {
            cancelSignOutBtn.addEventListener('click', () => {
                signOutModal.classList.add('hidden');
            });
        }
        
        // Close modal when clicking outside
        if (signOutModal) {
            signOutModal.addEventListener('click', (e) => {
                if (e.target === signOutModal) {
                    signOutModal.classList.add('hidden');
                }
            });
        }
    });
</script>

