<?php
// Customer ledger page logic
require_once 'includes/header.php';

// Get customer ID from query parameter
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

// Validate customer ID
if ($customer_id <= 0) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Invalid Request!</strong>
            <span class="block sm:inline"> No customer ID provided.</span>
            <div class="mt-2">
                <a href="/customers" class="text-red-700 underline">Return to Customers</a>
            </div>
          </div>';
    require_once 'includes/footer.php';
    exit;
}

// Get customer details
$customer_query = "SELECT name, balance FROM customers WHERE id = $1";
$customer_result = pg_query_params($db_conn, $customer_query, [$customer_id]);

if (!$customer_result || pg_num_rows($customer_result) === 0) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Customer Not Found!</strong>
            <span class="block sm:inline"> The requested customer does not exist.</span>
            <div class="mt-2">
                <a href="/customers" class="text-red-700 underline">Return to Customers</a>
            </div>
          </div>';
    require_once 'includes/footer.php';
    exit;
}

$customer = pg_fetch_assoc($customer_result);
?>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">Ledger: <?= htmlspecialchars($customer['name']) ?></h1>
            <p class="text-gray-600 dark:text-gray-400">Current Balance: <span class="font-semibold"><?= formatCurrency($customer['balance']) ?></span></p>
        </div>
        <div class="flex space-x-2">
            <a href="/customers" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 py-2 px-4 rounded">
                Back to Customers
            </a>
            <?php if (hasPermission('add')): ?>
            <button id="add-payment-btn" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded">
                Add Payment
            </button>
            <button id="add-credit-btn" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                Add Credit Note
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div class="flex flex-col md:flex-row gap-4">
        <div>
            <label for="ledger-date-from" class="sr-only">From Date</label>
            <input type="date" id="ledger-date-from" placeholder="From Date" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        </div>
        
        <div>
            <label for="ledger-date-to" class="sr-only">To Date</label>
            <input type="date" id="ledger-date-to" placeholder="To Date" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        </div>
    </div>
    
    <button id="refresh-ledger" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
        Refresh
    </button>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Date
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Type
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Description
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Amount
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Balance
                </th>
                <?php if (isAdmin()): ?>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Actions
                </th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody id="ledger-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td colspan="<?= isAdmin() ? '6' : '5' ?>" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Loading transactions...
                </td>
            </tr>
        </tbody>
    </table>
    
    <div id="ledger-pagination" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
        <!-- Pagination will be inserted here -->
    </div>
</div>

<!-- Payment Modal -->
<div id="payment-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="payment-modal-title">Add Payment</h3>
            <form id="payment-form">
                <input type="hidden" name="customer_id" value="<?= $customer_id ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Customer: <span class="font-normal"><?= htmlspecialchars($customer['name']) ?></span></p>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-1">Current Balance: <span class="font-normal"><?= formatCurrency($customer['balance']) ?></span></p>
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
                <input type="hidden" name="customer_id" value="<?= $customer_id ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Customer: <span class="font-normal"><?= htmlspecialchars($customer['name']) ?></span></p>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-1">Current Balance: <span class="font-normal"><?= formatCurrency($customer['balance']) ?></span></p>
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
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="confirm-title">Confirm Delete</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-gray-700 dark:text-gray-300" id="confirm-message">Are you sure you want to delete this transaction?</p>
            </div>
            <div class="flex justify-center gap-3 mt-3">
                <button id="confirm-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                    Cancel
                </button>
                <button id="confirm-ok" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Set customer ID for JavaScript
    const customerId = <?= $customer_id ?>;
</script>

<?php require_once 'includes/footer.php'; ?>