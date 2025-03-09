<?php
// Top navigation bar with dropdowns

// Don't display header on login page
$current_page = basename($_SERVER['SCRIPT_NAME']);
if (basename($_SERVER['SCRIPT_NAME']) === 'login.php') {
    return;
}

// Get current user information
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supreme Rice Mills</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css" rel="stylesheet">
    <link href="/assets/css/styles.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 min-h-screen">
    <div class="flex flex-col min-h-screen">
        <header class="bg-white dark:bg-gray-800 shadow">
            <nav class="container mx-auto px-4 py-2">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <a href="/dashboard" class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                            Supreme Rice Mills
                        </a>
                        <div class="hidden md:flex ml-10 space-x-4">
                            <a href="/dashboard" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 <?= $url[0] === 'dashboard' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300' ?>">
                                Dashboard
                            </a>
                            <a href="/inventory" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 <?= $url[0] === 'inventory' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300' ?>">
                                Inventory
                            </a>
                            <a href="/customers" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 <?= $url[0] === 'customers' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300' ?>">
                                Customers
                            </a>
                            <a href="/sales" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 <?= $url[0] === 'sales' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300' ?>">
                                Sales
                            </a>
                            <a href="/loading" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 <?= $url[0] === 'loading' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300' ?>">
                                Loading
                            </a>
                            <a href="/reports" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 <?= $url[0] === 'reports' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300' ?>">
                                Reports
                            </a>
                            <?php if (isAdminOrModerator()): ?>
                            <a href="/users" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 <?= $url[0] === 'users' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300' ?>">
                                Users
                            </a>
                            <?php endif; ?>
                            <a href="/settings" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 <?= $url[0] === 'settings' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300' ?>">
                                Settings
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <!-- Dark mode toggle -->
                        <button id="dark-mode-toggle" class="mr-4 p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 dark:hidden" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        
                        <!-- User menu -->
                        <div class="ml-3 relative">
                            <div>
                                <button id="user-menu-button" class="flex text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Open user menu</span>
                                    <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-white">
                                        <?= substr($username, 0, 1) ?>
                                    </div>
                                </button>
                            </div>
                            
                            <!-- User dropdown menu -->
                            <div id="user-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-10" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                                <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                                    Logged in as <?= htmlspecialchars($username) ?>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700"></div>
                                <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                    Settings
                                </a>
                                <form method="post" action="/api/auth.php">
                                    <input type="hidden" name="action" value="logout">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                        Log out
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Mobile menu button -->
                        <div class="md:hidden ml-2">
                            <button id="mobile-menu-button" class="p-2 rounded-md inline-flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                                <span class="sr-only">Open main menu</span>
                                <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile menu -->
                <div id="mobile-menu" class="hidden md:hidden mt-2">
                    <div class="px-2 pt-2 pb-3 space-y-1">
                        <a href="/dashboard" class="block px-3 py-2 rounded-md text-base font-medium <?= $url[0] === 'dashboard' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            Dashboard
                        </a>
                        <a href="/inventory" class="block px-3 py-2 rounded-md text-base font-medium <?= $url[0] === 'inventory' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            Inventory
                        </a>
                        <a href="/customers" class="block px-3 py-2 rounded-md text-base font-medium <?= $url[0] === 'customers' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            Customers
                        </a>
                        <a href="/sales" class="block px-3 py-2 rounded-md text-base font-medium <?= $url[0] === 'sales' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            Sales
                        </a>
                        <a href="/loading" class="block px-3 py-2 rounded-md text-base font-medium <?= $url[0] === 'loading' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            Loading
                        </a>
                        <a href="/reports" class="block px-3 py-2 rounded-md text-base font-medium <?= $url[0] === 'reports' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            Reports
                        </a>
                        <?php if (isAdminOrModerator()): ?>
                        <a href="/users" class="block px-3 py-2 rounded-md text-base font-medium <?= $url[0] === 'users' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            Users
                        </a>
                        <?php endif; ?>
                        <a href="/settings" class="block px-3 py-2 rounded-md text-base font-medium <?= $url[0] === 'settings' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                            Settings
                        </a>
                    </div>
                </div>
            </nav>
        </header>
        
        <!-- Main content container -->
        <main class="flex-grow container mx-auto px-4 py-6">