<?php
// Customers page logic
require_once 'includes/header.php';
?>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Customers</h1>
    <p class="text-gray-600 dark:text-gray-400">Manage your customer information and view their transaction history.</p>
</div>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div class="flex flex-col md:flex-row gap-4">
        <div>
            <label for="state-filter" class="sr-only">Filter by State</label>
            <select id="state-filter" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">All States</option>
            </select>
        </div>
        
        <div>
            <label for="balance-filter" class="sr-only">Filter by Balance</label>
            <select id="balance-filter" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">All Balances</option>
                <option value="has_balance">Has Balance</option>
                <option value="no_balance">No Balance</option>
            </select>
        </div>
        
        <div>
            <label for="customer-search" class="sr-only">Search</label>
            <input type="text" id="customer-search" placeholder="Search customers..." class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        </div>
    </div>
    
    <?php if (hasPermission('add')): ?>
    <button id="add-customer-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
        Add Customer
    </button>
    <?php endif; ?>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Name
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Phone
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    State
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Balance
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody id="customers-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Loading customers...
                </td>
            </tr>
        </tbody>
    </table>
    
    <div id="customers-pagination" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
        <!-- Pagination will be inserted here -->
    </div>
</div>

<!-- Add/Edit Customer Modal -->
<div id="customer-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="customer-modal-title">Add Customer</h3>
            <form id="customer-form">
                <input type="hidden" id="customer-id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <label for="customer-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                    <input type="text" id="customer-name" name="name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label for="customer-phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone</label>
                    <input type="text" id="customer-phone" name="phone" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label for="customer-state" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">State</label>
                    <input type="text" id="customer-state" name="state" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div id="initial-balance-div" class="mb-4">
                    <label for="customer-balance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Initial Balance (₦)</label>
                    <input type="number" id="customer-balance" name="balance" step="0.01" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional: Use positive value for customer's debt, negative for customer's credit</p>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="customer-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="customer-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="payment-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="payment-modal-title">Add Payment</h3>
            <form id="payment-form">
                <input type="hidden" id="payment-customer-id" name="customer_id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Customer: <span id="payment-customer-name" class="font-normal"></span></p>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-1">Current Balance: <span id="payment-current-balance" class="font-normal"></span></p>
                </div>
                
                <div class="mb-4">
                    <label for="payment-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Date</label>
                    <input type="date" id="payment-date" name="transaction_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label for="payment-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount (₦)</label>
                    <input type="number" id="payment-amount" name="amount" min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label for="payment-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <textarea id="payment-description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="payment-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="payment-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Credit Note Modal -->
<div id="credit-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="credit-modal-title">Add Credit Note</h3>
            <form id="credit-form">
                <input type="hidden" id="credit-customer-id" name="customer_id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Customer: <span id="credit-customer-name" class="font-normal"></span></p>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-1">Current Balance: <span id="credit-current-balance" class="font-normal"></span></p>
                </div>
                
                <div class="mb-4">
                    <label for="credit-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Credit Note Date</label>
                    <input type="date" id="credit-date" name="transaction_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label for="credit-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount (₦)</label>
                    <input type="number" id="credit-amount" name="amount" min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label for="credit-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <textarea id="credit-description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white" required></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="credit-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="credit-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirm-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3 text-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="confirm-title">Confirm Action</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-gray-700 dark:text-gray-300" id="confirm-message">Are you sure you want to proceed?</p>
            </div>
            <div class="flex justify-center gap-3 mt-3">
                <button id="confirm-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                    Cancel
                </button>
                <button id="confirm-ok" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>