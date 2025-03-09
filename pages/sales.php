<?php
// Sales page logic
require_once 'includes/header.php';
?>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Sales</h1>
    <p class="text-gray-600 dark:text-gray-400">Create and manage sales to customers.</p>
</div>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div class="flex flex-col md:flex-row gap-4">
        <div>
            <label for="customer-filter" class="sr-only">Filter by Customer</label>
            <select id="customer-filter" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">All Customers</option>
            </select>
        </div>
        
        <div>
            <label for="loading-status-filter" class="sr-only">Filter by Loading Status</label>
            <select id="loading-status-filter" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">All Loading Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Partially Loaded">Partially Loaded</option>
                <option value="Fully Loaded">Fully Loaded</option>
            </select>
        </div>
        
        <div>
            <label for="date-from" class="sr-only">From Date</label>
            <input type="date" id="date-from" placeholder="From Date" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        </div>
        
        <div>
            <label for="date-to" class="sr-only">To Date</label>
            <input type="date" id="date-to" placeholder="To Date" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        </div>
    </div>
    
    <?php if (hasPermission('add')): ?>
    <button id="add-sale-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
        Create Sale
    </button>
    <?php endif; ?>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Date
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Customer
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Amount
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Loading Status
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody id="sales-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Loading sales...
                </td>
            </tr>
        </tbody>
    </table>
    
    <div id="sales-pagination" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
        <!-- Pagination will be inserted here -->
    </div>
</div>

<!-- Sale Form Modal -->
<div id="sale-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border max-w-4xl w-full shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="sale-modal-title">Create Sale</h3>
            <form id="sale-form">
                <input type="hidden" id="sale-id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="sale-customer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</label>
                        <select id="sale-customer" name="customer_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Customer</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="sale-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sale Date</label>
                        <input type="date" id="sale-date" name="sale_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="mb-4">
                    <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Items</h4>
                    <div id="sale-items" class="border border-gray-200 dark:border-gray-700 rounded-md p-4">
                        <div class="sale-item mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product</label>
                                    <select name="items[0][product_id]" class="sale-product w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">Select Product</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                                    <input type="number" name="items[0][quantity]" min="1" class="sale-quantity w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                    <span class="available-stock text-sm text-gray-500 dark:text-gray-400 mt-1 block"></span>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rate (₦)</label>
                                    <input type="number" name="items[0][rate]" min="0.01" step="0.01" class="sale-rate w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center mt-3">
                                <span class="item-subtotal text-sm font-medium text-gray-700 dark:text-gray-300"></span>
                                <button type="button" class="sale-remove-item text-red-500 hover:text-red-700 hidden">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <button type="button" id="sale-add-item" class="flex items-center text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Add Another Item
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-3 rounded-md mb-4">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total:</span>
                    <span id="sale-total" class="text-lg font-bold text-gray-800 dark:text-white">₦0.00</span>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="sale-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="sale-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sale Details Modal -->
<div id="sale-details-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border max-w-4xl w-full shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sale Details</h3>
                <button id="sale-details-close" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer</p>
                    <p id="details-customer" class="text-lg text-gray-800 dark:text-white"></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sale Date</p>
                    <p id="details-date" class="text-lg text-gray-800 dark:text-white"></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Amount</p>
                    <p id="details-amount" class="text-lg text-gray-800 dark:text-white"></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Loading Status</p>
                    <p id="details-status" class="text-lg text-gray-800 dark:text-white"></p>
                </div>
            </div>
            
            <div class="mb-6">
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Items</h4>
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Product
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Category
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Rate
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody id="details-items" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    Loading items...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="flex justify-between items-center">
                <div>
                    <?php if (hasPermission('edit')): ?>
                    <button id="change-loading-status" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md mr-2">
                        Change Loading Status
                    </button>
                    <?php endif; ?>
                    <?php if (hasPermission('edit')): ?>
                    <button id="edit-sale-date" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Edit Date
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if (hasPermission('delete_own_records') || isAdmin()): ?>
                <button id="delete-sale" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                    Delete Sale
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Change Loading Status Modal -->
<div id="loading-status-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Change Loading Status</h3>
            <form id="loading-status-form">
                <input type="hidden" id="loading-status-sale-id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <label for="loading-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Loading Status</label>
                    <select id="loading-status" name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="Pending">Pending</option>
                        <option value="Partially Loaded">Partially Loaded</option>
                        <option value="Fully Loaded">Fully Loaded</option>
                    </select>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="loading-status-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="loading-status-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sale Date Modal -->
<div id="edit-date-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Edit Sale Date</h3>
            <form id="edit-date-form">
                <input type="hidden" id="edit-date-sale-id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <label for="edit-sale-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sale Date</label>
                    <input type="date" id="edit-sale-date" name="sale_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="edit-date-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="edit-date-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
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