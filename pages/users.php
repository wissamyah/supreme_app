<?php
// Users page logic
require_once 'includes/header.php';

// Check if user has admin or moderator role
if (!isAdminOrModerator()) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Access Denied!</strong>
            <span class="block sm:inline"> You do not have permission to access this page.</span>
          </div>';
    require_once 'includes/footer.php';
    exit;
}
?>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Users Management</h1>
    <p class="text-gray-600 dark:text-gray-400">Manage system users and their permissions.</p>
</div>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div>
        <label for="user-search" class="sr-only">Search</label>
        <input type="text" id="user-search" placeholder="Search users..." class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
    </div>
    
    <?php if (isAdmin()): ?>
    <button id="add-user-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
        Add User
    </button>
    <?php endif; ?>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Username
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Email
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Role
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Status
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Last Session
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody id="users-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Loading users...
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Add/Edit User Modal -->
<div id="user-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="user-modal-title">Add User</h3>
            <form id="user-form">
                <input type="hidden" id="user-id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                    <input type="text" id="username" name="username" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                    <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div id="password-field" class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                    <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Role</label>
                    <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                        <?php if (isAdmin()): ?>
                        <option value="admin">Admin</option>
                        <option value="moderator">Moderator</option>
                        <?php endif; ?>
                        <option value="operator">Operator</option>
                    </select>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="user-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="user-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="password-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Change Password</h3>
            <form id="password-form">
                <input type="hidden" id="password-user-id" name="id">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Username: <span id="password-username" class="font-normal"></span></p>
                </div>
                
                <div class="mb-4">
                    <label for="new-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Password</label>
                    <input type="password" id="new-password" name="password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" id="password-cancel" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="password-submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        Change Password
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