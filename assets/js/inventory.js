// Inventory page specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let categoriesData = [];
    let productsData = [];
    let productsPage = 1;
    let productsTotalPages = 1;
    let productsPerPage = 25;

    // Tab management
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.id.replace('tab-', '');

            // Hide all tabs and remove active class
            tabPanes.forEach(pane => pane.classList.add('hidden'));
            tabButtons.forEach(btn => {
                btn.classList.remove('border-indigo-500', 'text-indigo-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });

            // Show selected tab
            document.getElementById(`content-${tabId}`).classList.remove('hidden');
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-indigo-500', 'text-indigo-600');

            // Load data based on active tab
            if (tabId === 'categories') {
                loadCategories();
            } else if (tabId === 'products') {
                loadProducts();
            } else if (tabId === 'production') {
                loadProduction();
            } else if (tabId === 'stock') {
                loadStock();
            }
        });
    });

    // Initial data load
    loadCategories();

    // Categories tab functionality
    const categoriesTable = document.getElementById('categories-table');
    const addCategoryBtn = document.getElementById('add-category-btn');
    const categoryModal = document.getElementById('category-modal');
    const categoryForm = document.getElementById('category-form');
    const categoryModalTitle = document.getElementById('category-modal-title');
    const categoryId = document.getElementById('category-id');
    const categoryName = document.getElementById('category-name');
    const categoryCancel = document.getElementById('category-cancel');
    const categorySubmit = document.getElementById('category-submit');

    // Add category button click
    if (addCategoryBtn) {
        addCategoryBtn.addEventListener('click', function() {
            categoryModalTitle.textContent = 'Add Category';
            categoryId.value = '';
            categoryName.value = '';
            categoryModal.classList.remove('hidden');
        });
    }

    // Cancel button click
    if (categoryCancel) {
        categoryCancel.addEventListener('click', function() {
            categoryModal.classList.add('hidden');
        });
    }

    // Submit category form
    if (categoryForm) {
        categoryForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(categoryForm);
            const name = formData.get('name').trim();

            if (!name) {
                alert('Category name is required');
                return;
            }

            const isEdit = categoryId.value !== '';

            if (isEdit) {
                // Update category
                AJAX.put('/api/inventory.php?action=category', {
                    id: parseInt(categoryId.value),
                    name: name,
                    csrf_token: formData.get('csrf_token')
                }, function(response) {
                    if (response.success) {
                        Util.showNotification('Category updated successfully', 'success');
                        categoryModal.classList.add('hidden');
                        loadCategories();
                    } else {
                        Util.showNotification(response.message || 'Failed to update category', 'error');
                    }
                }, function(error) {
                    Util.showNotification('An error occurred: ' + error, 'error');
                });
            } else {
                // Add new category
                AJAX.postForm('/api/inventory.php?action=category', formData, function(response) {
                    if (response.success) {
                        Util.showNotification('Category added successfully', 'success');
                        categoryModal.classList.add('hidden');
                        loadCategories();
                    } else {
                        Util.showNotification(response.message || 'Failed to add category', 'error');
                    }
                }, function(error) {
                    Util.showNotification('An error occurred: ' + error, 'error');
                });
            }
        });
    }

    // Load categories from API
    function loadCategories() {
        // Show loading indicator
        categoriesTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading categories...</td></tr>';

        AJAX.get('/api/inventory.php?action=categories', function(response) {
            if (response.success && response.data) {
                categoriesData = response.data;
                renderCategories();
            } else {
                categoriesTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Failed to load categories</td></tr>';
            }
        }, function(error) {
            console.error('Error loading categories:', error);
            categoriesTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Error loading categories</td></tr>';
        });
    }

    // Render categories table
    function renderCategories() {
        if (categoriesData.length === 0) {
            categoriesTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No categories found</td></tr>';
            return;
        }

        let html = '';
        categoriesData.forEach(category => {
            html += `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${category.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${category.created_at ? new Date(category.created_at).toLocaleDateString() : '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3" data-action="edit" data-id="${category.id}">Edit</button>
                        <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" data-action="delete" data-id="${category.id}">Delete</button>
                    </td>
                </tr>
            `;
        });

        categoriesTable.innerHTML = html;

        // Add event listeners for action buttons
        categoriesTable.querySelectorAll('[data-action]').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.getAttribute('data-action');
                const id = parseInt(this.getAttribute('data-id'));
                const category = categoriesData.find(c => c.id == id);

                if (action === 'edit') {
                    // Show edit modal
                    categoryModalTitle.textContent = 'Edit Category';
                    categoryId.value = category.id;
                    categoryName.value = category.name;
                    categoryModal.classList.remove('hidden');
                } else if (action === 'delete') {
                    // Confirm deletion
                    confirmAction('Delete Category', `Are you sure you want to delete the category "${category.name}"?`, function() {
                        deleteCategory(id);
                    });
                }
            });
        });
    }

    // Delete category
    function deleteCategory(id) {
        AJAX.delete('/api/inventory.php?action=category', {
            id: id,
            csrf_token: document.querySelector('input[name="csrf_token"]').value
        }, function(response) {
            if (response.success) {
                Util.showNotification('Category deleted successfully', 'success');
                loadCategories();
            } else {
                Util.showNotification(response.message || 'Failed to delete category', 'error');
            }
        }, function(error) {
            Util.showNotification('An error occurred: ' + error, 'error');
        });
    }

    // Products tab functionality
    const productsTable = document.getElementById('products-table');
    const productsPagination = document.getElementById('products-pagination');
    const categoryFilter = document.getElementById('category-filter');
    const productSearch = document.getElementById('product-search');
    const addProductBtn = document.getElementById('add-product-btn');
    const productModal = document.getElementById('product-modal');
    const productForm = document.getElementById('product-form');
    const productModalTitle = document.getElementById('product-modal-title');
    const productId = document.getElementById('product-id');
    const productName = document.getElementById('product-name');
    const productCategory = document.getElementById('product-category');
    const productStock = document.getElementById('product-stock');
    const productCancel = document.getElementById('product-cancel');

    // Add product button click
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function() {
            productModalTitle.textContent = 'Add Product';
            productId.value = '';
            productName.value = '';
            productCategory.value = '';
            productStock.value = '0';

            // Populate categories dropdown
            populateCategoriesDropdown();

            productModal.classList.remove('hidden');
        });
    }

    // Cancel button click
    if (productCancel) {
        productCancel.addEventListener('click', function() {
            productModal.classList.add('hidden');
        });
    }

    // Category filter change
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            productsPage = 1;
            loadProducts();
        });

        // Populate category filter dropdown
        populateCategoryFilter();
    }

    // Product search input
    if (productSearch) {
        productSearch.addEventListener('input', debounce(function() {
            productsPage = 1;
            loadProducts();
        }, 300));
    }

    // Submit product form
    if (productForm) {
        productForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(productForm);
            const name = formData.get('name').trim();
            const categoryId = formData.get('category_id');
            const physicalStock = formData.get('physical_stock');

            if (!name) {
                alert('Product name is required');
                return;
            }

            if (!categoryId) {
                alert('Category is required');
                return;
            }

            const isEdit = productId.value !== '';

            if (isEdit) {
                // Update product
                AJAX.put('/api/inventory.php?action=product', {
                    id: parseInt(productId.value),
                    name: name,
                    category_id: parseInt(categoryId),
                    physical_stock: parseInt(physicalStock),
                    csrf_token: formData.get('csrf_token')
                }, function(response) {
                    if (response.success) {
                        Util.showNotification('Product updated successfully', 'success');
                        productModal.classList.add('hidden');
                        loadProducts();
                    } else {
                        Util.showNotification(response.message || 'Failed to update product', 'error');
                    }
                }, function(error) {
                    Util.showNotification('An error occurred: ' + error, 'error');
                });
            } else {
                // Add new product
                AJAX.postForm('/api/inventory.php?action=product', formData, function(response) {
                    if (response.success) {
                        Util.showNotification('Product added successfully', 'success');
                        productModal.classList.add('hidden');
                        loadProducts();
                    } else {
                        Util.showNotification(response.message || 'Failed to add product', 'error');
                    }
                }, function(error) {
                    Util.showNotification('An error occurred: ' + error, 'error');
                });
            }
        });
    }

    // Load products from API
    function loadProducts() {
        // Show loading indicator
        productsTable.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading products...</td></tr>';

        // Build query parameters
        const categoryId = categoryFilter ? categoryFilter.value : '';
        const search = productSearch ? productSearch.value : '';

        const url = `/api/inventory.php?action=products&page=${productsPage}&per_page=${productsPerPage}` +
            (categoryId ? `&category_id=${categoryId}` : '') +
            (search ? `&search=${encodeURIComponent(search)}` : '');

        AJAX.get(url, function(response) {
            if (response.success && response.data) {
                productsData = response.data;
                productsTotalPages = response.pagination.last_page;
                productsPerPage = response.pagination.per_page;
                renderProducts();
                renderProductsPagination();
            } else {
                productsTable.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Failed to load products</td></tr>';
                productsPagination.innerHTML = '';
            }
        }, function(error) {
            console.error('Error loading products:', error);
            productsTable.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Error loading products</td></tr>';
            productsPagination.innerHTML = '';
        });
    }

    // Render products table
    function renderProducts() {
        if (productsData.length === 0) {
            productsTable.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No products found</td></tr>';
            return;
        }

        let html = '';
        productsData.forEach(product => {
            const availableStock = parseInt(product.physical_stock) - parseInt(product.booked_stock);
            const availableClass = availableStock <= 0 ? 'text-red-600 dark:text-red-400' : '';

            html += `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${product.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${product.category_name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${parseInt(product.physical_stock).toLocaleString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${parseInt(product.booked_stock).toLocaleString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right ${availableClass}">${availableStock.toLocaleString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3" data-action="edit" data-id="${product.id}">Edit</button>
                        <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" data-action="delete" data-id="${product.id}">Delete</button>
                    </td>
                </tr>
            `;
        });

        productsTable.innerHTML = html;

        // Add event listeners for action buttons
        productsTable.querySelectorAll('[data-action]').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.getAttribute('data-action');
                const id = parseInt(this.getAttribute('data-id'));
                const product = productsData.find(p => p.id == id);

                if (action === 'edit') {
                    // Show edit modal
                    productModalTitle.textContent = 'Edit Product';
                    productId.value = product.id;
                    productName.value = product.name;

                    // Populate categories dropdown
                    populateCategoriesDropdown(product.category_id);

                    productStock.value = product.physical_stock;
                    productModal.classList.remove('hidden');
                } else if (action === 'delete') {
                    // Confirm deletion
                    confirmAction('Delete Product', `Are you sure you want to delete the product "${product.name}"?`, function() {
                        deleteProduct(id);
                    });
                }
            });
        });
    }

    // Render products pagination
    function renderProductsPagination() {
        if (productsTotalPages <= 1) {
            productsPagination.innerHTML = '';
            return;
        }

        let html = `
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button ${productsPage === 1 ? 'disabled' : ''} class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 ${productsPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${productsPage - 1}">
                        Previous
                    </button>
                    <button ${productsPage === productsTotalPages ? 'disabled' : ''} class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 ${productsPage === productsTotalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${productsPage + 1}">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Showing page <span class="font-medium">${productsPage}</span> of <span class="font-medium">${productsTotalPages}</span>
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button ${productsPage === 1 ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 ${productsPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${productsPage - 1}">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
        `;

        // Page numbers
        const maxPagesToShow = 5;
        let startPage = Math.max(1, productsPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(productsTotalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === productsPage;
            html += `
                <button ${isActive ? 'disabled' : ''} aria-current="${isActive ? 'page' : 'false'}" class="relative inline-flex items-center px-4 py-2 border ${isActive ? 'border-indigo-500 dark:border-indigo-400 z-10 bg-indigo-50 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'}" data-page="${i}">
                    ${i}
                </button>
            `;
        }

        html += `
                            <button ${productsPage === productsTotalPages ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 ${productsPage === productsTotalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${productsPage + 1}">
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

        productsPagination.innerHTML = html;

        // Add event listeners for pagination buttons
        productsPagination.querySelectorAll('button[data-page]').forEach(button => {
            button.addEventListener('click', function() {
                if (this.hasAttribute('disabled')) return;

                const page = parseInt(this.getAttribute('data-page'));
                if (page >= 1 && page <= productsTotalPages) {
                    productsPage = page;
                    loadProducts();
                }
            });
        });
    }

    // Delete product
    function deleteProduct(id) {
        AJAX.delete('/api/inventory.php?action=product', {
            id: id,
            csrf_token: document.querySelector('input[name="csrf_token"]').value
        }, function(response) {
            if (response.success) {
                Util.showNotification('Product deleted successfully', 'success');
                loadProducts();
            } else {
                Util.showNotification(response.message || 'Failed to delete product', 'error');
            }
        }, function(error) {
            Util.showNotification('An error occurred: ' + error, 'error');
        });
    }

    // Populate categories dropdown for product form
    function populateCategoriesDropdown(selectedId = null) {
        // First, load categories if not already loaded
        if (categoriesData.length === 0) {
            AJAX.get('/api/inventory.php?action=categories', function(response) {
                if (response.success && response.data) {
                    categoriesData = response.data;
                    populateCategoriesDropdownFromData(selectedId);
                }
            });
        } else {
            populateCategoriesDropdownFromData(selectedId);
        }
    }

    function populateCategoriesDropdownFromData(selectedId = null) {
        let html = '<option value="">Select Category</option>';

        categoriesData.forEach(category => {
            const selected = selectedId && parseInt(selectedId) === parseInt(category.id) ? 'selected' : '';
            html += `<option value="${category.id}" ${selected}>${category.name}</option>`;
        });

        productCategory.innerHTML = html;
    }

    // Populate category filter dropdown
    function populateCategoryFilter() {
        // First, load categories if not already loaded
        if (categoriesData.length === 0) {
            AJAX.get('/api/inventory.php?action=categories', function(response) {
                if (response.success && response.data) {
                    categoriesData = response.data;
                    populateCategoryFilterFromData();
                }
            });
        } else {
            populateCategoryFilterFromData();
        }
    }

    function populateCategoryFilterFromData() {
        let html = '<option value="">All Categories</option>';

        categoriesData.forEach(category => {
            html += `<option value="${category.id}">${category.name}</option>`;
        });

        categoryFilter.innerHTML = html;
    }

    // Production tab functionality
    const productionTable = document.getElementById('production-table');
    const productionPagination = document.getElementById('production-pagination');
    const productionDateFilter = document.getElementById('production-date-filter');
    const addProductionBtn = document.getElementById('add-production-btn');
    const productionModal = document.getElementById('production-modal');
    const productionForm = document.getElementById('production-form');
    const productionDate = document.getElementById('production-date');
    const productionItems = document.getElementById('production-items');
    const productionAddItem = document.getElementById('production-add-item');
    const productionCancel = document.getElementById('production-cancel');
    let productionPage = 1;
    let productionTotalPages = 1;

    // Add production button click
    if (addProductionBtn) {
        addProductionBtn.addEventListener('click', function() {
            // Reset form
            productionDate.value = Util.getTodayDate();

            // Reset items
            const itemsContainer = document.getElementById('production-items');
            const firstItem = itemsContainer.querySelector('.production-item');

            // Clear all items except the first one
            while (itemsContainer.children.length > 1) {
                itemsContainer.removeChild(itemsContainer.lastChild);
            }

            // Reset the first item
            const productSelect = firstItem.querySelector('.production-product');
            productSelect.innerHTML = '<option value="">Select Product</option>';

            // Populate product dropdown
            loadProductsForDropdown(productSelect);

            // Reset quantity
            firstItem.querySelector('input[type="number"]').value = '';

            // Show modal
            productionModal.classList.remove('hidden');
        });
    }

    // Cancel button click
    if (productionCancel) {
        productionCancel.addEventListener('click', function() {
            productionModal.classList.add('hidden');
        });
    }

    // Production date filter change
    if (productionDateFilter) {
        productionDateFilter.addEventListener('change', function() {
            productionPage = 1;
            loadProduction();
        });
    }

    // Add production item button click
    if (productionAddItem) {
        productionAddItem.addEventListener('click', function() {
            addProductionItem();
        });
    }

    // Submit production form
    if (productionForm) {
        productionForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Validate form
            const date = productionDate.value;
            if (!date) {
                alert('Production date is required');
                return;
            }

            // Check if at least one product is selected
            const productSelects = productionForm.querySelectorAll('.production-product');
            let isValid = false;

            productSelects.forEach(select => {
                if (select.value) {
                    isValid = true;
                }
            });

            if (!isValid) {
                alert('At least one product must be selected');
                return;
            }

            // Prepare form data
            const formData = new FormData(productionForm);

            // Submit form
            AJAX.postForm('/api/production.php?action=add', formData, function(response) {
                if (response.success) {
                    Util.showNotification('Production added successfully', 'success');
                    productionModal.classList.add('hidden');
                    loadProduction();
                } else {
                    Util.showNotification(response.message || 'Failed to add production', 'error');
                }
            }, function(error) {
                Util.showNotification('An error occurred: ' + error, 'error');
            });
        });
    }

    // Load production data
    function loadProduction() {
        // Show loading indicator
        productionTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading production data...</td></tr>';

        // Build query parameters
        const date = productionDateFilter ? productionDateFilter.value : '';

        const url = `/api/production.php?action=list&page=${productionPage}&per_page=25` +
            (date ? `&start_date=${date}&end_date=${date}` : '');

        AJAX.get(url, function(response) {
            if (response.success && response.data) {
                const productionData = response.data;
                productionTotalPages = response.pagination.last_page;
                renderProduction(productionData);
                renderProductionPagination();
            } else {
                productionTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Failed to load production data</td></tr>';
                productionPagination.innerHTML = '';
            }
        }, function(error) {
            console.error('Error loading production data:', error);
            productionTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Error loading production data</td></tr>';
            productionPagination.innerHTML = '';
        });
    }

    // Render production table
    function renderProduction(data) {
        if (data.length === 0) {
            productionTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No production data found</td></tr>';
            return;
        }

        let html = '';
        data.forEach(item => {
            html += `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.formatted_date}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.product_name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.category_name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${parseInt(item.quantity).toLocaleString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" data-action="delete-production" data-id="${item.id}">Delete</button>
                    </td>
                </tr>
            `;
        });

        productionTable.innerHTML = html;

        // Add event listeners for delete buttons
        productionTable.querySelectorAll('[data-action="delete-production"]').forEach(button => {
            button.addEventListener('click', function() {
                const id = parseInt(this.getAttribute('data-id'));

                // Confirm deletion
                confirmAction('Delete Production', 'Are you sure you want to delete this production record?', function() {
                    deleteProduction(id);
                });
            });
        });
    }

    // Render production pagination
    function renderProductionPagination() {
        // Similar to renderProductsPagination but for production data
        // Simplified version for brevity
        if (productionTotalPages <= 1) {
            productionPagination.innerHTML = '';
            return;
        }

        let html = `
            <div class="flex justify-between items-center">
                <button ${productionPage === 1 ? 'disabled' : ''} class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded ${productionPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${productionPage - 1}">Previous</button>
                <span class="text-sm text-gray-700 dark:text-gray-300">Page ${productionPage} of ${productionTotalPages}</span>
                <button ${productionPage === productionTotalPages ? 'disabled' : ''} class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded ${productionPage === productionTotalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${productionPage + 1}">Next</button>
            </div>
        `;

        productionPagination.innerHTML = html;

        // Add event listeners for pagination buttons
        productionPagination.querySelectorAll('button[data-page]').forEach(button => {
            button.addEventListener('click', function() {
                if (this.hasAttribute('disabled')) return;

                const page = parseInt(this.getAttribute('data-page'));
                if (page >= 1 && page <= productionTotalPages) {
                    productionPage = page;
                    loadProduction();
                }
            });
        });
    }

    // Add production item
    function addProductionItem() {
        const itemsContainer = document.getElementById('production-items');
        const itemCount = itemsContainer.children.length;

        // Clone the first item
        const firstItem = itemsContainer.querySelector('.production-item');
        const newItem = firstItem.cloneNode(true);

        // Update item number
        newItem.querySelector('h4').textContent = `Item ${itemCount + 1}`;

        // Update input names
        newItem.querySelector('select').name = `items[${itemCount}][product_id]`;
        newItem.querySelector('input').name = `items[${itemCount}][quantity]`;

        // Reset values
        newItem.querySelector('select').value = '';
        newItem.querySelector('input').value = '';

        // Show remove button
        const removeButton = newItem.querySelector('.production-remove-item');
        removeButton.classList.remove('hidden');

        // Add event listener to remove button
        removeButton.addEventListener('click', function() {
            itemsContainer.removeChild(newItem);

            // Renumber items
            const items = itemsContainer.querySelectorAll('.production-item');
            items.forEach((item, index) => {
                item.querySelector('h4').textContent = `Item ${index + 1}`;
                item.querySelector('select').name = `items[${index}][product_id]`;
                item.querySelector('input').name = `items[${index}][quantity]`;
            });
        });

        // Populate product dropdown
        const productSelect = newItem.querySelector('.production-product');
        loadProductsForDropdown(productSelect);

        // Add the new item to the container
        itemsContainer.appendChild(newItem);
    }

    // Load products for dropdown
    function loadProductsForDropdown(selectElement) {
        AJAX.get('/api/inventory.php?action=products&per_page=100', function(response) {
            if (response.success && response.data) {
                let html = '<option value="">Select Product</option>';

                response.data.forEach(product => {
                    html += `<option value="${product.id}">${product.name} (${product.category_name})</option>`;
                });

                selectElement.innerHTML = html;
            }
        });
    }

    // Delete production record
    function deleteProduction(id) {
        AJAX.delete('/api/production.php?action=delete', {
            id: id,
            csrf_token: document.querySelector('input[name="csrf_token"]').value
        }, function(response) {
            if (response.success) {
                Util.showNotification('Production record deleted successfully', 'success');
                loadProduction();
            } else {
                Util.showNotification(response.message || 'Failed to delete production record', 'error');
            }
        }, function(error) {
            Util.showNotification('An error occurred: ' + error, 'error');
        });
    }

    // Stock tab functionality
    const stockTable = document.getElementById('stock-table');
    const refreshStockBtn = document.getElementById('refresh-stock-btn');
    const exportStockBtn = document.getElementById('export-stock-btn');

    // Refresh stock button click
    if (refreshStockBtn) {
        refreshStockBtn.addEventListener('click', function() {
            loadStock();
        });
    }

    // Export stock button click
    if (exportStockBtn) {
        exportStockBtn.addEventListener('click', function() {
            exportStockToCSV();
        });
    }

    // Load stock data
    function loadStock() {
        // Show loading indicator
        stockTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading stock data...</td></tr>';
        document.getElementById('total-physical').textContent = 'Loading...';
        document.getElementById('total-booked').textContent = 'Loading...';
        document.getElementById('total-available').textContent = 'Loading...';

        AJAX.get('/api/inventory.php?action=stock', function(response) {
            if (response.success && response.data) {
                const stockData = response.data.stock;
                const totals = response.data.totals;

                // Update totals
                document.getElementById('total-physical').textContent = parseInt(totals.total_physical).toLocaleString();
                document.getElementById('total-booked').textContent = parseInt(totals.total_booked).toLocaleString();
                document.getElementById('total-available').textContent = parseInt(totals.total_available).toLocaleString();

                renderStock(stockData);
            } else {
                stockTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Failed to load stock data</td></tr>';
                document.getElementById('total-physical').textContent = 'N/A';
                document.getElementById('total-booked').textContent = 'N/A';
                document.getElementById('total-available').textContent = 'N/A';
            }
        }, function(error) {
            console.error('Error loading stock data:', error);
            stockTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Error loading stock data</td></tr>';
            document.getElementById('total-physical').textContent = 'Error';
            document.getElementById('total-booked').textContent = 'Error';
            document.getElementById('total-available').textContent = 'Error';
        });
    }

    // Render stock table
    function renderStock(data) {
        if (data.length === 0) {
            stockTable.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No stock data found</td></tr>';
            return;
        }

        let html = '';
        data.forEach(item => {
            const availableStock = parseInt(item.available_stock);
            const availableClass = availableStock <= 0 ? 'text-red-600 dark:text-red-400' : '';

            html += `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.category}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${parseInt(item.physical_stock).toLocaleString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${parseInt(item.booked_stock).toLocaleString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right ${availableClass}">${availableStock.toLocaleString()}</td>
                </tr>
            `;
        });

        stockTable.innerHTML = html;
    }

    // Export stock to CSV
    function exportStockToCSV() {
        AJAX.get('/api/inventory.php?action=stock', function(response) {
            if (response.success && response.data) {
                const stockData = response.data.stock;
                const totals = response.data.totals;

                // Create CSV content
                let csv = 'Product,Category,Physical Stock,Booked Stock,Available Stock\n';

                // Add stock data
                stockData.forEach(item => {
                    csv += `"${item.name}","${item.category}",${item.physical_stock},${item.booked_stock},${item.available_stock}\n`;
                });

                // Add totals
                csv += `\n"TOTAL","",${totals.total_physical},${totals.total_booked},${totals.total_available}\n`;

                // Create download link
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'stock_report_' + new Date().toISOString().slice(0, 10) + '.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                Util.showNotification('Failed to export stock data', 'error');
            }
        }, function(error) {
            console.error('Error exporting stock data:', error);
            Util.showNotification('Error exporting stock data', 'error');
        });
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