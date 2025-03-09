// Sales page specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let salesData = [];
    let customersData = [];
    let productsData = [];
    let salesPage = 1;
    let salesTotalPages = 1;
    let salesPerPage = 25;

    // DOM elements
    const salesTable = document.getElementById('sales-table');
    const salesPagination = document.getElementById('sales-pagination');
    const customerFilter = document.getElementById('customer-filter');
    const loadingStatusFilter = document.getElementById('loading-status-filter');
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    const addSaleBtn = document.getElementById('add-sale-btn');

    // Modal elements
    const saleModal = document.getElementById('sale-modal');
    const saleForm = document.getElementById('sale-form');
    const saleModalTitle = document.getElementById('sale-modal-title');
    const saleId = document.getElementById('sale-id');
    const saleCustomer = document.getElementById('sale-customer');
    const saleDate = document.getElementById('sale-date');
    const saleItems = document.getElementById('sale-items');
    const saleAddItem = document.getElementById('sale-add-item');
    const saleTotal = document.getElementById('sale-total');
    const saleCancel = document.getElementById('sale-cancel');

    // Sale details modal elements
    const saleDetailsModal = document.getElementById('sale-details-modal');
    const saleDetailsClose = document.getElementById('sale-details-close');
    const detailsCustomer = document.getElementById('details-customer');
    const detailsDate = document.getElementById('details-date');
    const detailsAmount = document.getElementById('details-amount');
    const detailsStatus = document.getElementById('details-status');
    const detailsItems = document.getElementById('details-items');
    const changeLoadingStatus = document.getElementById('change-loading-status');
    const editSaleDate = document.getElementById('edit-sale-date');
    const deleteSale = document.getElementById('delete-sale');

    // Loading status modal elements
    const loadingStatusModal = document.getElementById('loading-status-modal');
    const loadingStatusForm = document.getElementById('loading-status-form');
    const loadingStatusSaleId = document.getElementById('loading-status-sale-id');
    const loadingStatus = document.getElementById('loading-status');
    const loadingStatusCancel = document.getElementById('loading-status-cancel');

    // Edit date modal elements
    const editDateModal = document.getElementById('edit-date-modal');
    const editDateForm = document.getElementById('edit-date-form');
    const editDateSaleId = document.getElementById('edit-date-sale-id');
    const editSaleDateInput = document.getElementById('edit-sale-date');
    const editDateCancel = document.getElementById('edit-date-cancel');

    // Initial data load and setup
    loadCustomers();
    loadSales();

    // Event listeners for filters
    if (customerFilter) {
        customerFilter.addEventListener('change', function() {
            salesPage = 1;
            loadSales();
        });
    }

    if (loadingStatusFilter) {
        loadingStatusFilter.addEventListener('change', function() {
            salesPage = 1;
            loadSales();
        });
    }

    if (dateFrom) {
        dateFrom.addEventListener('change', function() {
            salesPage = 1;
            loadSales();
        });
    }

    if (dateTo) {
        dateTo.addEventListener('change', function() {
            salesPage = 1;
            loadSales();
        });
    }

    // Add sale button click
    if (addSaleBtn) {
        addSaleBtn.addEventListener('click', function() {
            // Reset form
            saleModalTitle.textContent = 'Create Sale';
            saleId.value = '';
            saleCustomer.value = '';
            saleDate.value = Util.getTodayDate();

            // Reset items
            resetSaleItems();

            // Show modal
            saleModal.classList.remove('hidden');

            // Update total
            updateSaleTotal();
        });
    }

    // Cancel button click
    if (saleCancel) {
        saleCancel.addEventListener('click', function() {
            saleModal.classList.add('hidden');
        });
    }

    // Sale details close button click
    if (saleDetailsClose) {
        saleDetailsClose.addEventListener('click', function() {
            saleDetailsModal.classList.add('hidden');
        });
    }

    // Loading status cancel button click
    if (loadingStatusCancel) {
        loadingStatusCancel.addEventListener('click', function() {
            loadingStatusModal.classList.add('hidden');
        });
    }

    // Edit date cancel button click
    if (editDateCancel) {
        editDateCancel.addEventListener('click', function() {
            editDateModal.classList.add('hidden');
        });
    }

    // Add item button click
    if (saleAddItem) {
        saleAddItem.addEventListener('click', function() {
            addSaleItem();
        });
    }

    // Submit sale form
    if (saleForm) {
        saleForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Validate form
            if (!validateSaleForm()) {
                return;
            }

            // Prepare form data
            const formData = new FormData(saleForm);

            // Submit form
            AJAX.postForm('/api/sales.php?action=add', formData, function(response) {
                if (response.success) {
                    Util.showNotification('Sale created successfully', 'success');
                    saleModal.classList.add('hidden');
                    loadSales();
                } else {
                    Util.showNotification(response.message || 'Failed to create sale', 'error');
                }
            }, function(error) {
                Util.showNotification('An error occurred: ' + error, 'error');
            });
        });
    }

    // Submit loading status form
    if (loadingStatusForm) {
        loadingStatusForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Get form data
            const id = loadingStatusSaleId.value;
            const status = loadingStatus.value;
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;

            // Update status
            AJAX.put('/api/sales.php?action=loading_status', {
                id: parseInt(id),
                status: status,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    Util.showNotification('Loading status updated successfully', 'success');
                    loadingStatusModal.classList.add('hidden');
                    loadSales();

                    // Update details modal if open
                    if (!saleDetailsModal.classList.contains('hidden')) {
                        detailsStatus.innerHTML = Util.createStatusBadge(status).outerHTML;
                    }
                } else {
                    Util.showNotification(response.message || 'Failed to update loading status', 'error');
                }
            }, function(error) {
                Util.showNotification('An error occurred: ' + error, 'error');
            });
        });
    }

    // Submit edit date form
    if (editDateForm) {
        editDateForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Get form data
            const id = editDateSaleId.value;
            const saleDate = editSaleDateInput.value;
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;

            if (!saleDate) {
                alert('Sale date is required');
                return;
            }

            // Update date
            AJAX.put('/api/sales.php?action=update', {
                id: parseInt(id),
                sale_date: saleDate,
                csrf_token: csrfToken
            }, function(response) {
                if (response.success) {
                    Util.showNotification('Sale date updated successfully', 'success');
                    editDateModal.classList.add('hidden');
                    loadSales();

                    // Update details modal if open
                    if (!saleDetailsModal.classList.contains('hidden')) {
                        detailsDate.textContent = Util.formatDate(saleDate);
                    }
                } else {
                    Util.showNotification(response.message || 'Failed to update sale date', 'error');
                }
            }, function(error) {
                Util.showNotification('An error occurred: ' + error, 'error');
            });
        });
    }

    // Change loading status button click
    if (changeLoadingStatus) {
        changeLoadingStatus.addEventListener('click', function() {
            const saleId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');

            // Set current status in dropdown
            loadingStatusSaleId.value = saleId;
            loadingStatus.value = currentStatus;

            // Show modal
            loadingStatusModal.classList.remove('hidden');
        });
    }

    // Edit sale date button click
    if (editSaleDate) {
        editSaleDate.addEventListener('click', function() {
            const saleId = this.getAttribute('data-id');
            const currentDate = this.getAttribute('data-date');

            // Set current values
            editDateSaleId.value = saleId;
            editSaleDateInput.value = currentDate;

            // Show modal
            editDateModal.classList.remove('hidden');
        });
    }

    // Delete sale button click
    if (deleteSale) {
        deleteSale.addEventListener('click', function() {
            const saleId = this.getAttribute('data-id');

            // Confirm deletion
            confirmAction('Delete Sale', 'Are you sure you want to delete this sale? This action cannot be undone.', function() {
                deleteSaleRecord(saleId);
            });
        });
    }

    // Validate sale form
    function validateSaleForm() {
        const customerId = saleCustomer.value;
        const date = saleDate.value;
        const items = saleItems.querySelectorAll('.sale-item');

        if (!customerId) {
            alert('Please select a customer');
            return false;
        }

        if (!date) {
            alert('Please select a sale date');
            return false;
        }

        // Check if at least one item is added
        let validItems = 0;

        items.forEach(item => {
            const productId = item.querySelector('.sale-product').value;
            const quantity = item.querySelector('.sale-quantity').value;
            const rate = item.querySelector('.sale-rate').value;

            if (productId && quantity > 0 && rate > 0) {
                validItems++;
            }
        });

        if (validItems === 0) {
            alert('Please add at least one valid item to the sale');
            return false;
        }

        return true;
    }

    // Reset sale items to a single empty item
    function resetSaleItems() {
        const itemsContainer = document.getElementById('sale-items');

        // Keep only the first item
        while (itemsContainer.children.length > 1) {
            itemsContainer.removeChild(itemsContainer.lastChild);
        }

        // Reset the first item
        const firstItem = itemsContainer.querySelector('.sale-item');
        firstItem.querySelector('.sale-product').innerHTML = '<option value="">Select Product</option>';
        firstItem.querySelector('.sale-quantity').value = '';
        firstItem.querySelector('.sale-rate').value = '';
        firstItem.querySelector('.item-subtotal').textContent = '';

        // Hide remove button for the first item
        firstItem.querySelector('.sale-remove-item').classList.add('hidden');

        // Load products for dropdown
        loadProductsForDropdown(firstItem.querySelector('.sale-product'));

        // Add event listeners
        addSaleItemEventListeners(firstItem);
    }

    // Add a new item to sale
    function addSaleItem() {
        const itemsContainer = document.getElementById('sale-items');
        const itemCount = itemsContainer.children.length;

        // Clone the first item
        const firstItem = itemsContainer.querySelector('.sale-item');
        const newItem = firstItem.cloneNode(true);

        // Update input names
        newItem.querySelector('.sale-product').name = `items[${itemCount}][product_id]`;
        newItem.querySelector('.sale-quantity').name = `items[${itemCount}][quantity]`;
        newItem.querySelector('.sale-rate').name = `items[${itemCount}][rate]`;

        // Reset values
        newItem.querySelector('.sale-product').value = '';
        newItem.querySelector('.sale-quantity').value = '';
        newItem.querySelector('.sale-rate').value = '';
        newItem.querySelector('.item-subtotal').textContent = '';
        newItem.querySelector('.available-stock').textContent = '';

        // Show remove button
        const removeButton = newItem.querySelector('.sale-remove-item');
        removeButton.classList.remove('hidden');

        // Add event listener to remove button
        removeButton.addEventListener('click', function() {
            itemsContainer.removeChild(newItem);

            // Renumber items
            const items = itemsContainer.querySelectorAll('.sale-item');
            items.forEach((item, index) => {
                item.querySelector('.sale-product').name = `items[${index}][product_id]`;
                item.querySelector('.sale-quantity').name = `items[${index}][quantity]`;
                item.querySelector('.sale-rate').name = `items[${index}][rate]`;
            });

            // Update total
            updateSaleTotal();
        });

        // Load products for dropdown
        loadProductsForDropdown(newItem.querySelector('.sale-product'));

        // Add event listeners
        addSaleItemEventListeners(newItem);

        // Add the new item to the container
        itemsContainer.appendChild(newItem);
    }

    // Add event listeners to sale item inputs
    function addSaleItemEventListeners(item) {
        const productSelect = item.querySelector('.sale-product');
        const quantityInput = item.querySelector('.sale-quantity');
        const rateInput = item.querySelector('.sale-rate');
        const availableStockSpan = item.querySelector('.available-stock');

        // Product change
        productSelect.addEventListener('change', function() {
            const productId = this.value;

            if (productId) {
                // Get product details
                const product = productsData.find(p => p.id == productId);

                if (product) {
                    // Update available stock display
                    const available = parseInt(product.physical_stock) - parseInt(product.booked_stock);
                    availableStockSpan.textContent = `Available: ${available}`;

                    // Update last rate if available and not already set
                    if (!rateInput.value) {
                        // In a real system, we might fetch the last rate from the API
                        // For now, set a default or leave empty
                    }
                }
            } else {
                availableStockSpan.textContent = '';
            }

            // Update subtotal and total
            updateItemSubtotal(item);
            updateSaleTotal();
        });

        // Quantity change
        quantityInput.addEventListener('input', function() {
            updateItemSubtotal(item);
            updateSaleTotal();
        });

        // Rate change
        rateInput.addEventListener('input', function() {
            updateItemSubtotal(item);
            updateSaleTotal();
        });
    }

    // Update item subtotal
    function updateItemSubtotal(item) {
        const quantity = parseFloat(item.querySelector('.sale-quantity').value) || 0;
        const rate = parseFloat(item.querySelector('.sale-rate').value) || 0;
        const subtotal = quantity * rate;

        if (subtotal > 0) {
            item.querySelector('.item-subtotal').textContent = `Subtotal: ${Util.formatCurrency(subtotal)}`;
        } else {
            item.querySelector('.item-subtotal').textContent = '';
        }
    }

    // Update sale total
    function updateSaleTotal() {
        const items = document.querySelectorAll('.sale-item');
        let total = 0;

        items.forEach(item => {
            const quantity = parseFloat(item.querySelector('.sale-quantity').value) || 0;
            const rate = parseFloat(item.querySelector('.sale-rate').value) || 0;
            total += quantity * rate;
        });

        saleTotal.textContent = Util.formatCurrency(total);
    }

    // Load customers from API
    function loadCustomers() {
        AJAX.get('/api/customers.php?action=list&per_page=1000', function(response) {
            if (response.success && response.data) {
                customersData = response.data;
                populateCustomerDropdowns();
            }
        }, function(error) {
            console.error('Error loading customers:', error);
        });
    }

    // Load products from API
    function loadProducts() {
        AJAX.get('/api/inventory.php?action=products&per_page=1000', function(response) {
            if (response.success && response.data) {
                productsData = response.data;
            }
        }, function(error) {
            console.error('Error loading products:', error);
        });
    }

    // Populate customer dropdowns
    function populateCustomerDropdowns() {
        // For filter
        if (customerFilter) {
            let html = '<option value="">All Customers</option>';

            customersData.forEach(customer => {
                html += `<option value="${customer.id}">${customer.name}</option>`;
            });

            customerFilter.innerHTML = html;
        }

        // For sale form
        if (saleCustomer) {
            let html = '<option value="">Select Customer</option>';

            customersData.forEach(customer => {
                html += `<option value="${customer.id}">${customer.name}</option>`;
            });

            saleCustomer.innerHTML = html;
        }
    }

    // Load products for dropdown
    function loadProductsForDropdown(selectElement) {
        // If products data is not loaded yet, load it
        if (productsData.length === 0) {
            AJAX.get('/api/inventory.php?action=products&per_page=1000', function(response) {
                if (response.success && response.data) {
                    productsData = response.data;
                    populateProductDropdown(selectElement);
                }
            }, function(error) {
                console.error('Error loading products:', error);
            });
        } else {
            populateProductDropdown(selectElement);
        }
    }

    // Populate product dropdown
    function populateProductDropdown(selectElement) {
        let html = '<option value="">Select Product</option>';

        // Group products by category
        const categories = {};

        productsData.forEach(product => {
            if (!categories[product.category_name]) {
                categories[product.category_name] = [];
            }
            categories[product.category_name].push(product);
        });

        // Create optgroups for each category
        Object.keys(categories).sort().forEach(category => {
            html += `<optgroup label="${category}">`;

            categories[category].sort((a, b) => a.name.localeCompare(b.name)).forEach(product => {
                const available = parseInt(product.physical_stock) - parseInt(product.booked_stock);
                const disabled = available <= 0 ? 'disabled' : '';

                html += `<option value="${product.id}" ${disabled}>${product.name} (${available} available)</option>`;
            });

            html += `</optgroup>`;
        });

        selectElement.innerHTML = html;
    }

    // Load sales from API
    function loadSales() {
        // Show loading indicator
        salesTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading sales...</td></tr>';

        // Build query parameters
        const customer = customerFilter ? customerFilter.value : '';
        const status = loadingStatusFilter ? loadingStatusFilter.value : '';
        const from = dateFrom ? dateFrom.value : '';
        const to = dateTo ? dateTo.value : '';

        const url = `/api/sales.php?action=list&page=${salesPage}&per_page=${salesPerPage}` +
            (customer ? `&customer_id=${customer}` : '') +
            (status ? `&loading_status=${encodeURIComponent(status)}` : '') +
            (from ? `&start_date=${from}` : '') +
            (to ? `&end_date=${to}` : '');

        AJAX.get(url, function(response) {
            if (response.success && response.data) {
                salesData = response.data;
                salesTotalPages = response.pagination.last_page;
                salesPerPage = response.pagination.per_page;
                renderSales();
                renderPagination();
            } else {
                salesTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Failed to load sales</td></tr>';
                salesPagination.innerHTML = '';
            }
        }, function(error) {
            console.error('Error loading sales:', error);
            salesTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Error loading sales</td></tr>';
            salesPagination.innerHTML = '';
        });
    }

    // Render sales table
    function renderSales() {
        if (salesData.length === 0) {
            salesTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No sales found</td></tr>';
            return;
        }

        let html = '';
        salesData.forEach(sale => {
            html += `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${sale.formatted_date}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${sale.customer_name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${sale.formatted_amount}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        ${Util.createStatusBadge(sale.loading_status).outerHTML}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" data-action="view" data-id="${sale.id}">View</button>
                    </td>
                </tr>
            `;
        });

        salesTable.innerHTML = html;

        // Add event listeners for action buttons
        salesTable.querySelectorAll('[data-action="view"]').forEach(button => {
            button.addEventListener('click', function() {
                const id = parseInt(this.getAttribute('data-id'));
                viewSaleDetails(id);
            });
        });
    }

    // Render pagination
    function renderPagination() {
        if (salesTotalPages <= 1) {
            salesPagination.innerHTML = '';
            return;
        }

        let html = `
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button ${salesPage === 1 ? 'disabled' : ''} class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 ${salesPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${salesPage - 1}">
                        Previous
                    </button>
                    <button ${salesPage === salesTotalPages ? 'disabled' : ''} class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 ${salesPage === salesTotalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${salesPage + 1}">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Showing page <span class="font-medium">${salesPage}</span> of <span class="font-medium">${salesTotalPages}</span>
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button ${salesPage === 1 ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 ${salesPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${salesPage - 1}">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
        `;

        // Page numbers
        const maxPagesToShow = 5;
        let startPage = Math.max(1, salesPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(salesTotalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === salesPage;
            html += `
                <button ${isActive ? 'disabled' : ''} aria-current="${isActive ? 'page' : 'false'}" class="relative inline-flex items-center px-4 py-2 border ${isActive ? 'border-indigo-500 dark:border-indigo-400 z-10 bg-indigo-50 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'}" data-page="${i}">
                    ${i}
                </button>
            `;
        }

        html += `
                            <button ${salesPage === salesTotalPages ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 ${salesPage === salesTotalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${salesPage + 1}">
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

        salesPagination.innerHTML = html;

        // Add event listeners for pagination buttons
        salesPagination.querySelectorAll('button[data-page]').forEach(button => {
            button.addEventListener('click', function() {
                if (this.hasAttribute('disabled')) return;

                const page = parseInt(this.getAttribute('data-page'));
                if (page >= 1 && page <= salesTotalPages) {
                    salesPage = page;
                    loadSales();
                }
            });
        });
    }

    // View sale details
    function viewSaleDetails(id) {
        AJAX.get(`/api/sales.php?action=get&id=${id}`, function(response) {
            if (response.success && response.data) {
                const sale = response.data;

                // Fill in details
                detailsCustomer.textContent = sale.customer_name;
                detailsDate.textContent = sale.formatted_date;
                detailsAmount.textContent = sale.formatted_amount;
                detailsStatus.innerHTML = Util.createStatusBadge(sale.loading_status).outerHTML;

                // Set data attributes for action buttons
                if (changeLoadingStatus) {
                    changeLoadingStatus.setAttribute('data-id', sale.id);
                    changeLoadingStatus.setAttribute('data-status', sale.loading_status);
                }

                if (editSaleDate) {
                    editSaleDate.setAttribute('data-id', sale.id);
                    editSaleDate.setAttribute('data-date', sale.sale_date);
                }

                if (deleteSale) {
                    deleteSale.setAttribute('data-id', sale.id);
                }

                // Fill in items table
                let itemsHtml = '';

                sale.items.forEach(item => {
                    itemsHtml += `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.product_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.category_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${item.quantity}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${item.formatted_rate}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${item.formatted_subtotal}</td>
                        </tr>
                    `;
                });

                detailsItems.innerHTML = itemsHtml;

                // Show the modal
                saleDetailsModal.classList.remove('hidden');
            } else {
                Util.showNotification('Failed to load sale details', 'error');
            }
        }, function(error) {
            console.error('Error loading sale details:', error);
            Util.showNotification('Error loading sale details', 'error');
        });
    }

    // Delete sale
    function deleteSaleRecord(id) {
        AJAX.delete('/api/sales.php?action=delete', {
            id: parseInt(id),
            csrf_token: document.querySelector('input[name="csrf_token"]').value
        }, function(response) {
            if (response.success) {
                Util.showNotification('Sale deleted successfully', 'success');
                saleDetailsModal.classList.add('hidden');
                loadSales();
            } else {
                Util.showNotification(response.message || 'Failed to delete sale', 'error');
            }
        }, function(error) {
            Util.showNotification('An error occurred: ' + error, 'error');
        });
    }

    // Initialize
    function init() {
        // Set today's date as the default date for sale form
        if (saleDate) {
            saleDate.value = Util.getTodayDate();
        }

        // Load products
        loadProducts();
    }

    // Call initialize
    init();
});