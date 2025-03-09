// Customers page specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let customersData = [];
    let customersPage = 1;
    let customersTotalPages = 1;
    let customersPerPage = 25;
    let statesList = [];

    // DOM elements
    const customersTable = document.getElementById('customers-table');
    const customersPagination = document.getElementById('customers-pagination');
    const stateFilter = document.getElementById('state-filter');
    const balanceFilter = document.getElementById('balance-filter');
    const customerSearch = document.getElementById('customer-search');
    const addCustomerBtn = document.getElementById('add-customer-btn');
    const customerModal = document.getElementById('customer-modal');
    const customerForm = document.getElementById('customer-form');
    const customerModalTitle = document.getElementById('customer-modal-title');
    const customerId = document.getElementById('customer-id');
    const customerName = document.getElementById('customer-name');
    const customerPhone = document.getElementById('customer-phone');
    const customerState = document.getElementById('customer-state');
    const customerBalance = document.getElementById('customer-balance');
    const initialBalanceDiv = document.getElementById('initial-balance-div');
    const customerCancel = document.getElementById('customer-cancel');

    // Payment and Credit Note modal elements
    const paymentModal = document.getElementById('payment-modal');
    const paymentForm = document.getElementById('payment-form');
    const paymentCustomerId = document.getElementById('payment-customer-id');
    const paymentCustomerName = document.getElementById('payment-customer-name');
    const paymentCurrentBalance = document.getElementById('payment-current-balance');
    const paymentDate = document.getElementById('payment-date');
    const paymentAmount = document.getElementById('payment-amount');
    const paymentDescription = document.getElementById('payment-description');
    const paymentCancel = document.getElementById('payment-cancel');

    const creditModal = document.getElementById('credit-modal');
    const creditForm = document.getElementById('credit-form');
    const creditCustomerId = document.getElementById('credit-customer-id');
    const creditCustomerName = document.getElementById('credit-customer-name');
    const creditCurrentBalance = document.getElementById('credit-current-balance');
    const creditDate = document.getElementById('credit-date');
    const creditAmount = document.getElementById('credit-amount');
    const creditDescription = document.getElementById('credit-description');
    const creditCancel = document.getElementById('credit-cancel');

    // Initial data load
    loadCustomers();

    // Event listeners for filters
    if (stateFilter) {
        stateFilter.addEventListener('change', function() {
            customersPage = 1;
            loadCustomers();
        });
    }

    if (balanceFilter) {
        balanceFilter.addEventListener('change', function() {
            customersPage = 1;
            loadCustomers();
        });
    }

    if (customerSearch) {
        customerSearch.addEventListener('input', debounce(function() {
            customersPage = 1;
            loadCustomers();
        }, 300));
    }

    // Add customer button click
    if (addCustomerBtn) {
        addCustomerBtn.addEventListener('click', function() {
            customerModalTitle.textContent = 'Add Customer';
            customerId.value = '';
            customerName.value = '';
            customerPhone.value = '';
            customerState.value = '';
            customerBalance.value = '0';

            // Show initial balance field for new customers
            initialBalanceDiv.classList.remove('hidden');

            customerModal.classList.remove('hidden');
        });
    }

    // Cancel button click
    if (customerCancel) {
        customerCancel.addEventListener('click', function() {
            customerModal.classList.add('hidden');
        });
    }

    // Payment Cancel button click
    if (paymentCancel) {
        paymentCancel.addEventListener('click', function() {
            paymentModal.classList.add('hidden');
        });
    }

    // Credit Note Cancel button click
    if (creditCancel) {
        creditCancel.addEventListener('click', function() {
            creditModal.classList.add('hidden');
        });
    }

    // Submit customer form
    if (customerForm) {
        customerForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(customerForm);
            const name = formData.get('name').trim();

            if (!name) {
                alert('Customer name is required');
                return;
            }

            const isEdit = customerId.value !== '';

            if (isEdit) {
                // Update customer
                AJAX.put('/api/customers.php?action=update', {
                    id: parseInt(customerId.value),
                    name: name,
                    phone: formData.get('phone'),
                    state: formData.get('state'),
                    csrf_token: formData.get('csrf_token')
                }, function(response) {
                    if (response.success) {
                        Util.showNotification('Customer updated successfully', 'success');
                        customerModal.classList.add('hidden');
                        loadCustomers();
                    } else {
                        Util.showNotification(response.message || 'Failed to update customer', 'error');
                    }
                }, function(error) {
                    Util.showNotification('An error occurred: ' + error, 'error');
                });
            } else {
                // Add new customer
                AJAX.postForm('/api/customers.php?action=add', formData, function(response) {
                    if (response.success) {
                        Util.showNotification('Customer added successfully', 'success');
                        customerModal.classList.add('hidden');
                        loadCustomers();
                    } else {
                        Util.showNotification(response.message || 'Failed to add customer', 'error');
                    }
                }, function(error) {
                    Util.showNotification('An error occurred: ' + error, 'error');
                });
            }
        });
    }

    // Submit payment form
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(paymentForm);
            const amount = parseFloat(formData.get('amount'));

            if (!amount || amount <= 0) {
                alert('Payment amount must be greater than 0');
                return;
            }

            AJAX.postForm('/api/transactions.php?action=payment', formData, function(response) {
                if (response.success) {
                    Util.showNotification('Payment added successfully', 'success');
                    paymentModal.classList.add('hidden');
                    loadCustomers();
                } else {
                    Util.showNotification(response.message || 'Failed to add payment', 'error');
                }
            }, function(error) {
                Util.showNotification('An error occurred: ' + error, 'error');
            });
        });
    }

    // Submit credit note form
    if (creditForm) {
        creditForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(creditForm);
            const amount = parseFloat(formData.get('amount'));
            const description = formData.get('description').trim();

            if (!amount || amount <= 0) {
                alert('Credit note amount must be greater than 0');
                return;
            }

            if (!description) {
                alert('Description is required for credit notes');
                return;
            }

            AJAX.postForm('/api/transactions.php?action=credit_note', formData, function(response) {
                if (response.success) {
                    Util.showNotification('Credit note added successfully', 'success');
                    creditModal.classList.add('hidden');
                    loadCustomers();
                } else {
                    Util.showNotification(response.message || 'Failed to add credit note', 'error');
                }
            }, function(error) {
                Util.showNotification('An error occurred: ' + error, 'error');
            });
        });
    }

    // Load customers from API
    function loadCustomers() {
        // Show loading indicator
        customersTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading customers...</td></tr>';

        // Build query parameters
        const state = stateFilter ? stateFilter.value : '';
        const hasBalance = balanceFilter ? balanceFilter.value : '';
        const search = customerSearch ? customerSearch.value : '';

        const url = `/api/customers.php?action=list&page=${customersPage}&per_page=${customersPerPage}` +
            (state ? `&state=${encodeURIComponent(state)}` : '') +
            (hasBalance ? `&has_balance=${hasBalance === 'has_balance'}` : '') +
            (search ? `&search=${encodeURIComponent(search)}` : '');

        AJAX.get(url, function(response) {
            if (response.success && response.data) {
                customersData = response.data;
                customersTotalPages = response.pagination.last_page;
                customersPerPage = response.pagination.per_page;
                renderCustomers();
                renderPagination();

                // Extract unique states for filter
                extractStates();
            } else {
                customersTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Failed to load customers</td></tr>';
                customersPagination.innerHTML = '';
            }
        }, function(error) {
            console.error('Error loading customers:', error);
            customersTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Error loading customers</td></tr>';
            customersPagination.innerHTML = '';
        });
    }

    // Render customers table
    function renderCustomers() {
        if (customersData.length === 0) {
            customersTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No customers found</td></tr>';
            return;
        }

        let html = '';
        customersData.forEach(customer => {
            const balanceValue = parseFloat(customer.balance);
            const balanceClass = balanceValue > 0 ? 'text-red-600 dark:text-red-400' : (balanceValue < 0 ? 'text-green-600 dark:text-green-400' : '');

            html += `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${customer.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${customer.phone || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${customer.state || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right ${balanceClass}">${customer.formatted_balance}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="/ledger?customer_id=${customer.id}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-2">Ledger</a>
                        <button class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-2" data-action="payment" data-id="${customer.id}">Payment</button>
                        <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-2" data-action="credit" data-id="${customer.id}">Credit Note</button>
                        <button class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-2" data-action="edit" data-id="${customer.id}">Edit</button>
                        <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" data-action="delete" data-id="${customer.id}">Delete</button>
                    </td>
                </tr>
            `;
        });

        customersTable.innerHTML = html;

        // Add event listeners for action buttons
        customersTable.querySelectorAll('[data-action]').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.getAttribute('data-action');
                const id = parseInt(this.getAttribute('data-id'));
                const customer = customersData.find(c => c.id == id);

                if (action === 'edit') {
                    // Show edit modal
                    customerModalTitle.textContent = 'Edit Customer';
                    customerId.value = customer.id;
                    customerName.value = customer.name;
                    customerPhone.value = customer.phone || '';
                    customerState.value = customer.state || '';

                    // Hide initial balance field for editing
                    initialBalanceDiv.classList.add('hidden');

                    customerModal.classList.remove('hidden');
                } else if (action === 'delete') {
                    // Confirm deletion
                    confirmAction('Delete Customer', `Are you sure you want to delete the customer "${customer.name}"?`, function() {
                        deleteCustomer(id);
                    });
                } else if (action === 'payment') {
                    // Show payment modal
                    paymentCustomerId.value = customer.id;
                    paymentCustomerName.textContent = customer.name;
                    paymentCurrentBalance.textContent = customer.formatted_balance;
                    paymentDate.value = Util.getTodayDate();
                    paymentAmount.value = '';
                    paymentDescription.value = '';

                    paymentModal.classList.remove('hidden');
                } else if (action === 'credit') {
                    // Show credit note modal
                    creditCustomerId.value = customer.id;
                    creditCustomerName.textContent = customer.name;
                    creditCurrentBalance.textContent = customer.formatted_balance;
                    creditDate.value = Util.getTodayDate();
                    creditAmount.value = '';
                    creditDescription.value = '';

                    creditModal.classList.remove('hidden');
                }
            });
        });
    }

    // Render pagination
    function renderPagination() {
        if (customersTotalPages <= 1) {
            customersPagination.innerHTML = '';
            return;
        }

        let html = `
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button ${customersPage === 1 ? 'disabled' : ''} class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 ${customersPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${customersPage - 1}">
                        Previous
                    </button>
                    <button ${customersPage === customersTotalPages ? 'disabled' : ''} class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 ${customersPage === customersTotalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${customersPage + 1}">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Showing page <span class="font-medium">${customersPage}</span> of <span class="font-medium">${customersTotalPages}</span>
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button ${customersPage === 1 ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 ${customersPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${customersPage - 1}">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
        `;

        // Page numbers
        const maxPagesToShow = 5;
        let startPage = Math.max(1, customersPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(customersTotalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === customersPage;
            html += `
                <button ${isActive ? 'disabled' : ''} aria-current="${isActive ? 'page' : 'false'}" class="relative inline-flex items-center px-4 py-2 border ${isActive ? 'border-indigo-500 dark:border-indigo-400 z-10 bg-indigo-50 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'}" data-page="${i}">
                    ${i}
                </button>
            `;
        }

        html += `
                            <button ${customersPage === customersTotalPages ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 ${customersPage === customersTotalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${customersPage + 1}">
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        `;

        customersPagination.innerHTML = html;

        // Add event listeners for pagination buttons
        customersPagination.querySelectorAll('button[data-page]').forEach(button => {
            button.addEventListener('click', function() {
                if (this.hasAttribute('disabled')) return;

                const page = parseInt(this.getAttribute('data-page'));
                if (page >= 1 && page <= customersTotalPages) {
                    customersPage = page;
                    loadCustomers();
                }
            });
        });
    }

    // Delete customer
    function deleteCustomer(id) {
        AJAX.delete('/api/customers.php?action=delete', {
            id: id,
            csrf_token: document.querySelector('input[name="csrf_token"]').value
        }, function(response) {
            if (response.success) {
                Util.showNotification('Customer deleted successfully', 'success');
                loadCustomers();
            } else {
                Util.showNotification(response.message || 'Failed to delete customer', 'error');
            }
        }, function(error) {
            Util.showNotification('An error occurred: ' + error, 'error');
        });
    }

    // Extract unique states from customers for filter
    function extractStates() {
        if (!stateFilter) return;

        // Get unique states
        const states = new Set();
        customersData.forEach(customer => {
            if (customer.state) {
                states.add(customer.state);
            }
        });

        // Convert to array and sort
        statesList = Array.from(states).sort();

        // Only update if list has changed
        if (JSON.stringify(statesList) !== JSON.stringify(Array.from(stateFilter.options).slice(1).map(opt => opt.value))) {
            // Save current selection
            const currentSelection = stateFilter.value;

            // Build options
            let html = '<option value="">All States</option>';
            statesList.forEach(state => {
                html += `<option value="${state}">${state}</option>`;
            });

            stateFilter.innerHTML = html;

            // Restore selection if possible
            if (statesList.includes(currentSelection)) {
                stateFilter.value = currentSelection;
            }
        }
    }

    // Debounce function for search input
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }
});