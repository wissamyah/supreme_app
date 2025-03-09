<?php
// Inventory page logic
require_once 'includes/header.php';
?>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Inventory Management</h1>
    <p class="text-gray-600 dark:text-gray-400">Manage your categories, products, and daily production.</p>
</div>

<!-- Tabs -->
<div class="mb-6">
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button id="tab-categories" class="tab-button py-4 px-1 border-b-2 border-indigo-500 dark:border-indigo-400 font-medium text-sm text-indigo-600 dark:text-indigo-300">
                Categories
            </button>
            <button id="tab-products" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                Products
            </button>
            <button id="tab-production" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                Daily Production
            </button>
            <button id="tab-stock" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                Current Stock
            </button>
        </nav>
    </div>
</div>

<!-- Tab content -->
<div class="tab-content">
    <!-- Categories Tab -->
    <div id="content-categories" class="tab-pane">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Categories</h2>
            <?php if (hasPermission('add')): ?>
            <button id="add-category-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                Add Category
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
                            Created At
                        </th>
                        <?php if (hasPermission('edit') || hasPermission('delete_own_records') || isAdmin()): ?>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Actions
                        </th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="categories-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            Loading categories...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Products Tab -->
    <div id="content-products" class="tab-pane hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Products</h2>
            
            <div class="flex flex-col md:flex-row gap-4">
                <div>
                    <label for="category-filter" class="sr-only">Filter by Category</label>
                    <select id="category-filter" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">All Categories</option>
                    </select>
                </div>
                
                <div>
                    <label for="product-search" class="sr-only">Search</label>
                    <input type="text" id="product-search" placeholder="Search products..." class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
                
                <?php if (hasPermission('add')): ?>
                <button id="add-product-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                    Add Product
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Category
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Physical Stock
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Booked Stock
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Available Stock
                        </th>
                        <?php if (hasPermission('edit') || hasPermission('delete_own_records') || isAdmin()): ?>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Actions
                        </th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="products-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            Loading products...
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div id="products-pagination" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <!-- Pagination will be inserted here -->
            </div>
        </div>
    </div>
    
    <!-- Daily Production Tab -->
    <div id="content-production" class="tab-pane hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Daily Production</h2>
            
            <div class="flex gap-4">
                <div>
                    <label for="production-date-filter" class="sr-only">Filter by Date</label>
                    <input type="date" id="production-date-filter" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
                
                <?php if (hasPermission('add')): ?>
                <button id="add-production-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                    Add Production
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Date
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Product
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Category
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Quantity
                        </th>
                        <?php if (isAdmin()): ?>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Actions
                        </th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="production-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            Loading production data...
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div id="production-pagination" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <!-- Pagination will be inserted here -->
            </div>
        </div>
    </div>
    
    <!-- Current Stock Tab -->
    <div id="content-stock" class="tab-pane hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Current Stock</h2>
            
            <div class="flex gap-4">
                <button id="refresh-stock-btn" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 py-2 px-4 rounded">
                    Refresh
                </button>
                <button id="export-stock-btn" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded">
                    Export
                </button>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Total Physical Stock -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Physical Stock</p>
                        <p class="text-lg font-semibold text-gray-700 dark:text-gray-200" id="total-physical">Loading...</p>
                    </div>
                </div>
            </div>
            
            <!-- Total Booked Stock -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Booked Stock</p>
                        <p class="text-lg font-semibold text-gray-700 dark:text-gray-200" id="total-booked">Loading...</p>
                    </div>
                </div>
            </div>
            
            <!-- Total Available Stock -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Available Stock</p>
                        <p class="text-lg font-semibold text-gray-700 dark:text-gray-200" id="total-available">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
        
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
                            Physical Stock
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Booked Stock
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Available Stock
                        </th>
                    </tr>
                </thead>
                <tbody id="stock-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            Loading stock data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Add/Edit Category Modal -->
<div id="category-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="category-modal-title">Add Category</h3>
            <form id="category-form">
                <input type="hidden" id="category-id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <label for="category-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                    <input type="text" id="category-name" name="name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="category-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="category-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div id="product-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="product-modal-title">Add Product</h3>
            <form id="product-form">
                <input type="hidden" id="product-id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <label for="product-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                    <input type="text" id="product-name" name="name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label for="product-category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                    <select id="product-category" name="category_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Select Category</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="product-stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Physical Stock</label>
                    <input type="number" id="product-stock" name="physical_stock" min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="product-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="product-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Production Modal -->
<div id="production-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="production-modal-title">Add Daily Production</h3>
            <form id="production-form">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <label for="production-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Production Date</label>
                    <input type="date" id="production-date" name="production_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div id="production-items">
                    <div class="production-item mb-4 p-4 border border-gray-200 dark:border-gray-700 rounded-md">
                        <div class="flex justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Item 1</h4>
                            <button type="button" class="production-remove-item text-red-500 hover:text-red-700 hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Product</label>
                            <select name="items[0][product_id]" class="production-product w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select Product</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quantity</label>
                            <input type="number" name="items[0][quantity]" min="1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <button type="button" id="production-add-item" class="flex items-center text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add Another Product
                    </button>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="production-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="production-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
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