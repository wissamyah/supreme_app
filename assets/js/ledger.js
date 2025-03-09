// Ledger page specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
            // Global variables
            let ledgerData = [];
            let ledgerPage = 1;
            let ledgerTotalPages = 1;
            let ledgerPerPage = 25;

            // DOM elements
            const ledgerTable = document.getElementById('ledger-table');
            const ledgerPagination = document.getElementById('ledger-pagination');
            const ledgerDateFrom = document.getElementById('ledger-date-from');
            const ledgerDateTo = document.getElementById('ledger-date-to');
            const refreshLedger = document.getElementById('refresh-ledger');
            const addPaymentBtn = document.getElementById('add-payment-btn');
            const addCreditBtn = document.getElementById('add-credit-btn');

            // Payment modal elements
            const paymentModal = document.getElementById('payment-modal');
            const paymentForm = document.getElementById('payment-form');
            const paymentDate = document.getElementById('payment-date');
            const paymentAmount = document.getElementById('payment-amount');
            const paymentDescription = document.getElementById('payment-description');
            const paymentCancel = document.getElementById('payment-cancel');

            // Credit note modal elements
            const creditModal = document.getElementById('credit-modal');
            const creditForm = document.getElementById('credit-form');
            const creditDate = document.getElementById('credit-date');
            const creditAmount = document.getElementById('credit-amount');
            const creditDescription = document.getElementById('credit-description');
            const creditCancel = document.getElementById('credit-cancel');

            // Confirmation modal elements
            const confirmModal = document.getElementById('confirm-modal');
            const confirmTitle = document.getElementById('confirm-title');
            const confirmMessage = document.getElementById('confirm-message');
            const confirmCancel = document.getElementById('confirm-cancel');
            const confirmOk = document.getElementById('confirm-ok');

            // Initial data load
            loadLedger();

            // Event listeners
            if (refreshLedger) {
                refreshLedger.addEventListener('click', function() {
                    loadLedger();
                });
            }

            if (ledgerDateFrom) {
                ledgerDateFrom.addEventListener('change', function() {
                    ledgerPage = 1;
                    loadLedger();
                });
            }

            if (ledgerDateTo) {
                ledgerDateTo.addEventListener('change', function() {
                    ledgerPage = 1;
                    loadLedger();
                });
            }

            // Add payment button click
            if (addPaymentBtn) {
                addPaymentBtn.addEventListener('click', function() {
                    // Reset form fields
                    paymentDate.value = Util.getTodayDate();
                    paymentAmount.value = '';
                    paymentDescription.value = '';

                    // Show modal
                    paymentModal.classList.remove('hidden');
                });
            }

            // Add credit note button click
            if (addCreditBtn) {
                addCreditBtn.addEventListener('click', function() {
                    // Reset form fields
                    creditDate.value = Util.getTodayDate();
                    creditAmount.value = '';
                    creditDescription.value = '';

                    // Show modal
                    creditModal.classList.remove('hidden');
                });
            }

            // Payment cancel button click
            if (paymentCancel) {
                paymentCancel.addEventListener('click', function() {
                    paymentModal.classList.add('hidden');
                });
            }

            // Credit note cancel button click
            if (creditCancel) {
                creditCancel.addEventListener('click', function() {
                    creditModal.classList.add('hidden');
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
                            loadLedger();
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
                            loadLedger();
                        } else {
                            Util.showNotification(response.message || 'Failed to add credit note', 'error');
                        }
                    }, function(error) {
                        Util.showNotification('An error occurred: ' + error, 'error');
                    });
                });
            }

            // Load ledger data
            function loadLedger() {
                // Show loading indicator
                ledgerTable.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading transactions...</td></tr>';

                // Build query parameters
                const startDate = ledgerDateFrom ? ledgerDateFrom.value : '';
                const endDate = ledgerDateTo ? ledgerDateTo.value : '';

                const url = `/api/transactions.php?action=ledger&customer_id=${customerId}&page=${ledgerPage}&per_page=${ledgerPerPage}` +
                    (startDate ? `&start_date=${startDate}` : '') +
                    (endDate ? `&end_date=${endDate}` : '');

                AJAX.get(url, function(response) {
                    if (response.success && response.data) {
                        ledgerData = response.data;
                        ledgerTotalPages = response.pagination.last_page;
                        ledgerPerPage = response.pagination.per_page;

                        renderLedger();
                        renderPagination();
                    } else {
                        ledgerTable.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Failed to load transactions</td></tr>';
                        ledgerPagination.innerHTML = '';
                    }
                }, function(error) {
                    console.error('Error loading ledger:', error);
                    ledgerTable.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Error loading transactions</td></tr>';
                    ledgerPagination.innerHTML = '';
                });
            }

            // Render ledger table
            function renderLedger() {
                if (!ledgerData.transactions || ledgerData.transactions.length === 0) {
                    ledgerTable.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No transactions found</td></tr>';
                    return;
                }

                // Update customer info
                document.querySelector('h1').textContent = `Ledger: ${ledgerData.customer.name}`;
                document.querySelector('p.text-gray-600').innerHTML = `Current Balance: <span class="font-semibold">${ledgerData.customer.formatted_balance}</span>`;

                let html = '';
                ledgerData.transactions.forEach(transaction => {
                            const isSale = transaction.type === 'sale';
                            const amountClass = isSale ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
                            const balanceValue = parseFloat(transaction.running_balance);
                            const balanceClass = balanceValue > 0 ? 'text-red-600 dark:text-red-400' : (balanceValue < 0 ? 'text-green-600 dark:text-green-400' : '');

                            html += `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${transaction.formatted_date}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${transaction.type_name}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        ${transaction.description || '-'}
                        ${transaction.sale_id ? `<a href="/sales?id=${transaction.sale_id}" class="text-indigo-600 dark:text-indigo-400 hover:underline ml-1">(View Sale)</a>` : ''}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right ${amountClass}">${transaction.formatted_amount}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right ${balanceClass}">${transaction.formatted_balance}</td>
                    ${document.getElementById('ledger-table').querySelector('th:last-child') ? `
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            ${!transaction.sale_id ? `
                                <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" data-action="delete" data-id="${transaction.id}">Delete</button>
                            ` : ''}
                        </td>
                    ` : ''}
                </tr>
            `;
        });
        
        ledgerTable.innerHTML = html;
        
        // Add event listeners for delete buttons
        ledgerTable.querySelectorAll('[data-action="delete"]').forEach(button => {
            button.addEventListener('click', function() {
                const id = parseInt(this.getAttribute('data-id'));
                
                // Confirm deletion
                confirmAction('Delete Transaction', 'Are you sure you want to delete this transaction? This will affect the customer balance.', function() {
                    deleteTransaction(id);
                });
            });
        });
    }
    
    // Render pagination
    function renderPagination() {
        if (ledgerTotalPages <= 1) {
            ledgerPagination.innerHTML = '';
            return;
        }
        
        let html = `
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button ${ledgerPage === 1 ? 'disabled' : ''} class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 ${ledgerPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${ledgerPage - 1}">
                        Previous
                    </button>
                    <button ${ledgerPage === ledgerTotalPages ? 'disabled' : ''} class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 ${ledgerPage === ledgerTotalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${ledgerPage + 1}">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Showing page <span class="font-medium">${ledgerPage}</span> of <span class="font-medium">${ledgerTotalPages}</span>
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button ${ledgerPage === 1 ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 ${ledgerPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${ledgerPage - 1}">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
        `;
        
        // Page numbers
        const maxPagesToShow = 5;
        let startPage = Math.max(1, ledgerPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(ledgerTotalPages, startPage + maxPagesToShow - 1);
        
        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === ledgerPage;
            html += `
                <button ${isActive ? 'disabled' : ''} aria-current="${isActive ? 'page' : 'false'}" class="relative inline-flex items-center px-4 py-2 border ${isActive ? 'border-indigo-500 dark:border-indigo-400 z-10 bg-indigo-50 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'}" data-page="${i}">
                    ${i}
                </button>
            `;
        }
        
        html += `
                            <button ${ledgerPage === ledgerTotalPages ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 ${ledgerPage === ledgerTotalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${ledgerPage + 1}">
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
        
        ledgerPagination.innerHTML = html;
        
        // Add event listeners for pagination buttons
        ledgerPagination.querySelectorAll('button[data-page]').forEach(button => {
            button.addEventListener('click', function() {
                if (this.hasAttribute('disabled')) return;
                
                const page = parseInt(this.getAttribute('data-page'));
                if (page >= 1 && page <= ledgerTotalPages) {
                    ledgerPage = page;
                    loadLedger();
                }
            });
        });
    }
    
    // Delete transaction
    function deleteTransaction(id) {
        AJAX.delete('/api/transactions.php?action=delete', {
            id: id,
            csrf_token: document.querySelector('input[name="csrf_token"]').value
        }, function(response) {
            if (response.success) {
                Util.showNotification('Transaction deleted successfully', 'success');
                loadLedger();
            } else {
                Util.showNotification(response.message || 'Failed to delete transaction', 'error');
            }
        }, function(error) {
            Util.showNotification('An error occurred: ' + error, 'error');
        });
    }
    
    // Confirm action
    function confirmAction(title, message, callback) {
        confirmTitle.textContent = title;
        confirmMessage.textContent = message;
        
        // Remove previous event listeners
        const newConfirmOk = confirmOk.cloneNode(true);
        confirmOk.parentNode.replaceChild(newConfirmOk, confirmOk);
        confirmOk = newConfirmOk;
        
        // Add new event listener
        confirmOk.addEventListener('click', function() {
            confirmModal.classList.add('hidden');
            if (callback) callback();
        });
        
        // Show modal
        confirmModal.classList.remove('hidden');
        
        // Cancel button
        confirmCancel.onclick = function() {
            confirmModal.classList.add('hidden');
        };
    }
});