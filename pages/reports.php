<?php
// Reports page logic
require_once 'includes/header.php';
?>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Reports</h1>
    <p class="text-gray-600 dark:text-gray-400">Generate and view various reports for your business.</p>
</div>

<!-- Tabs -->
<div class="mb-6">
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button id="tab-production" class="tab-button py-4 px-1 border-b-2 border-indigo-500 dark:border-indigo-400 font-medium text-sm text-indigo-600 dark:text-indigo-300">
                Production Report
            </button>
            <button id="tab-sales" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                Sales Report
            </button>
            <button id="tab-loadings" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                Loadings Report
            </button>
            <button id="tab-product-movement" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                Product Movement
            </button>
        </nav>
    </div>
</div>

<!-- Tab content -->
<div class="tab-content">
    <!-- Production Report Tab -->
    <div id="content-production" class="tab-pane">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Production Report</h2>
            
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="production-category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                        <select id="production-category" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="production-date-from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                        <input type="date" id="production-date-from" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="production-date-to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                        <input type="date" id="production-date-to" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="mt-4 flex space-x-2">
                    <button id="generate-production-report" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                        Generate Report
                    </button>
                    <button id="export-production-report" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded hidden">
                        Export CSV
                    </button>
                </div>
            </div>
            
            <div id="production-report-results" class="hidden">
                <div class="mb-4">
                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Production Summary by Product</h3>
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
                                        Total Quantity
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="production-summary-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Production Details</h3>
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
                                </tr>
                            </thead>
                            <tbody id="production-details-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sales Report Tab -->
    <div id="content-sales" class="tab-pane hidden">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Sales Report</h2>
            
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="sales-customer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</label>
                        <select id="sales-customer" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">All Customers</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="sales-loading-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Loading Status</label>
                        <select id="sales-loading-status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Partially Loaded">Partially Loaded</option>
                            <option value="Fully Loaded">Fully Loaded</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="sales-date-from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                        <input type="date" id="sales-date-from" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="sales-date-to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                        <input type="date" id="sales-date-to" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="mt-4 flex space-x-2">
                    <button id="generate-sales-report" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                        Generate Report
                    </button>
                    <button id="export-sales-report" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded hidden">
                        Export CSV
                    </button>
                </div>
            </div>
            
            <div id="sales-report-results" class="hidden">
                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex justify-between items-center">
                        <h3 class="text-md font-medium text-gray-700 dark:text-gray-300">Sales Summary</h3>
                        <p class="text-gray-600 dark:text-gray-400">Total: <span id="sales-total-amount" class="font-bold"></span></p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">By Customer</h3>
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Customer
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        State
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Sales Count
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Total Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="sales-by-customer-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">By Product</h3>
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
                                        Total Quantity
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Total Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="sales-by-product-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">By Loading Status</h3>
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Loading Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Sales Count
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Total Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="sales-by-status-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Sales Details</h3>
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
                                </tr>
                            </thead>
                            <tbody id="sales-details-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loadings Report Tab -->
    <div id="content-loadings" class="tab-pane hidden">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Loadings Report</h2>
            
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="loadings-customer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</label>
                        <select id="loadings-customer" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">All Customers</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="loadings-date-from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                        <input type="date" id="loadings-date-from" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="loadings-date-to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                        <input type="date" id="loadings-date-to" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="mt-4 flex space-x-2">
                    <button id="generate-loadings-report" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                        Generate Report
                    </button>
                    <button id="export-loadings-report" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded hidden">
                        Export CSV
                    </button>
                </div>
            </div>
            
            <div id="loadings-report-results" class="hidden">
                <div class="mb-4">
                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">By Customer</h3>
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Customer
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        State
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Loading Count
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="loadings-by-customer-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">By Product</h3>
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
                                        Total Quantity
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="loadings-by-product-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Loading Details</h3>
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
                                </tr>
                            </thead>
                            <tbody id="loadings-details-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Movement Tab -->
    <div id="content-product-movement" class="tab-pane hidden">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Product Movement Report</h2>
            
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="movement-category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                        <select id="movement-category" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="movement-product" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Product</label>
                        <select id="movement-product" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">All Products</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="movement-date-from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                        <input type="date" id="movement-date-from" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="movement-date-to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                        <input type="date" id="movement-date-to" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="mt-4 flex space-x-2">
                    <button id="generate-movement-report" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                        Generate Report
                    </button>
                    <button id="export-movement-report" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded hidden">
                        Export CSV
                    </button>
                </div>
            </div>
            
            <div id="movement-report-results" class="hidden">
                <div id="product-movement-details" class="space-y-6">
                    <!-- Product movement details will be inserted here dynamically -->
                    <div class="text-center text-gray-500 dark:text-gray-400">
                        No data available
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>