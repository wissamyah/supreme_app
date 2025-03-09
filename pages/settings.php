<?php
// Settings page logic
require_once 'includes/header.php';
?>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Settings</h1>
    <p class="text-gray-600 dark:text-gray-400">Configure system settings and view audit logs.</p>
</div>

<!-- Tabs -->
<div class="mb-6">
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button id="tab-general" class="tab-button py-4 px-1 border-b-2 border-indigo-500 dark:border-indigo-400 font-medium text-sm text-indigo-600 dark:text-indigo-300">
                General Settings
            </button>
            <button id="tab-audit" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                Audit Logs
            </button>
            <?php if (isAdmin()): ?>
            <button id="tab-backup" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                Database Backup
            </button>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Tab content -->
<div class="tab-content">
    <!-- General Settings Tab -->
    <div id="content-general" class="tab-pane">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">General Settings</h2>
            
            <form id="settings-form">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <label for="session_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Session Timeout (minutes)</label>
                    <input type="number" id="session_timeout" name="settings[session_timeout]" min="1" class="w-full md:w-64 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">User will be logged out after this period of inactivity.</p>
                </div>
                
                <div class="mb-4">
                    <label for="rows_per_page" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rows Per Page</label>
                    <input type="number" id="rows_per_page" name="settings[rows_per_page]" min="5" class="w-full md:w-64 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Number of rows to display per page in tables.</p>
                </div>
                
                <div class="mt-6">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Audit Logs Tab -->
    <div id="content-audit" class="tab-pane hidden">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Audit Logs</h2>
            
            <div class="flex flex-col md:flex-row gap-4 mb-6">
                <div>
                    <label for="audit-user" class="sr-only">Filter by User</label>
                    <select id="audit-user" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">All Users</option>
                    </select>
                </div>
                
                <div>
                    <label for="audit-action" class="sr-only">Filter by Action</label>
                    <input type="text" id="audit-action" placeholder="Action..." class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
                
                <div>
                    <label for="audit-table" class="sr-only">Filter by Table</label>
                    <select id="audit-table" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">All Tables</option>
                        <option value="users">Users</option>
                        <option value="categories">Categories</option>
                        <option value="products">Products</option>
                        <option value="production">Production</option>
                        <option value="customers">Customers</option>
                        <option value="transactions">Transactions</option>
                        <option value="sales">Sales</option>
                        <option value="loadings">Loadings</option>
                    </select>
                </div>
                
                <div>
                    <label for="audit-date-from" class="sr-only">From Date</label>
                    <input type="date" id="audit-date-from" placeholder="From Date" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
                
                <div>
                    <label for="audit-date-to" class="sr-only">To Date</label>
                    <input type="date" id="audit-date-to" placeholder="To Date" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                User
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Action
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Table
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Record ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Details
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Date/Time
                            </th>
                        </tr>
                    </thead>
                    <tbody id="audit-logs-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Loading audit logs...
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div id="audit-logs-pagination" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <!-- Pagination will be inserted here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Database Backup Tab -->
    <?php if (isAdmin()): ?>
    <div id="content-backup" class="tab-pane hidden">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Database Backup</h2>
            
            <p class="mb-4 text-gray-600 dark:text-gray-400">Generate a SQL backup of your entire database. This will create a file that you can download and save for backup purposes.</p>
            
            <div class="bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-400 dark:border-yellow-600 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 dark:text-yellow-200">
                            Creating a backup may take some time depending on the size of your database. Do not close this page while the backup is being generated.
                        </p>
                    </div>
                </div>
            </div>
            
            <form id="backup-form" method="get" action="/api/settings.php">
                <input type="hidden" name="action" value="backup">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Generate Database Backup
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>