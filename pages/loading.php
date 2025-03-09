<?php
// Loading page logic
require_once 'includes/header.php';
?>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Loading</h1>
    <p class="text-gray-600 dark:text-gray-400">Create and manage loadings for customer orders.</p>
</div>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div class="flex flex-col md:flex-row gap-4">
        <div>
            <label for="loading-customer-filter" class="sr-only">Filter by Customer</label>
            <select id="loading-customer-filter" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">All Customers</option>
            </select>
        </div>
        
        <div>
            <label for="loading-date-from" class="sr-only">From Date</label>
            <input type="date" id="loading-date-from" placeholder="From Date" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        </div>
        
        <div>
            <label for="loading-date-to" class="sr-only">To Date</label>
            <input type="date" id="loading-date-to" placeholder="To Date" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        </div>
    </div>
    
    <?php if (hasPermission('add')): ?>
    <button id="add-loading-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
        Create Loading
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
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Truck Number
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Waybill
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Items
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody id="loadings-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Loading data...
                </td>
            </tr>
        </tbody>
    </table>
    
    <div id="loadings-pagination" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
        <!-- Pagination will be inserted here -->
    </div>
</div>

<!-- Loading Form Modal -->
<div id="loading-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border max-w-4xl w-full shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="loading-modal-title">Create Loading</h3>
            <form id="loading-form">
                <input type="hidden" id="loading-id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="loading-customer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</label>
                        <select id="loading-customer" name="customer_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Customer</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="loading-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Loading Date</label>
                        <input type="date" id="loading-date" name="loading_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="loading-truck" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Truck Number</label>
                        <input type="text" id="loading-truck" name="truck_number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="loading-waybill" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Waybill (Optional)</label>
                        <input type="text" id="loading-waybill" name="waybill" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="loading-driver" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Driver Name (Optional)</label>
                        <input type="text" id="loading-driver" name="driver_name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="loading-driver-phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Driver Phone (Optional)</label>
                        <input type="text" id="loading-driver-phone" name="driver_phone" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300">Items</h4>
                        <div id="customer-bookings-info" class="text-sm text-gray-500 dark:text-gray-400 hidden">
                            Customer has pending bookings
                        </div>
                    </div>
                    
                    <div id="loading-items" class="border border-gray-200 dark:border-gray-700 rounded-md p-4">
                        <div class="loading-item mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product</label>
                                    <select name="items[0][product_id]" class="loading-product w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">Select Product</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                                    <input type="number" name="items[0][quantity]" min="1" class="loading-quantity w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                    <span class="available-stock text-sm text-gray-500 dark:text-gray-400 mt-1 block"></span>
                                </div>
                            </div>
                            
                            <div class="flex justify-end mt-3">
                                <button type="button" class="loading-remove-item text-red-500 hover:text-red-700 hidden">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <button type="button" id="loading-add-item" class="flex items-center text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Add Another Item
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="loading-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="loading-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading Details Modal -->
<div id="loading-details-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border max-w-4xl w-full shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Loading Details</h3>
                <button id="loading-details-close" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer</p>
                    <p id="details-loading-customer" class="text-lg text-gray-800 dark:text-white"></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Loading Date</p>
                    <p id="details-loading-date" class="text-lg text-gray-800 dark:text-white"></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Truck Number</p>
                    <p id="details-loading-truck" class="text-lg text-gray-800 dark:text-white"></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Waybill</p>
                    <p id="details-loading-waybill" class="text-lg text-gray-800 dark:text-white">-</p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Driver Name</p>
                    <p id="details-loading-driver" class="text-lg text-gray-800 dark:text-white">-</p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Driver Phone</p>
                    <p id="details-loading-driver-phone" class="text-lg text-gray-800 dark:text-white">-</p>
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
                            </tr>
                        </thead>
                        <tbody id="details-loading-items" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
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
                    <button id="edit-loading" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Edit Loading
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if (isAdmin()): ?>
                <button id="delete-loading" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                    Delete Loading
                </button>
                <?php endif; ?>
            </div>
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