// Dashboard specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Get dashboard elements
    const totalPhysicalStock = document.getElementById('total-physical-stock');
    const totalBookedStock = document.getElementById('total-booked-stock');
    const totalCustomers = document.getElementById('total-customers');
    const totalSalesMonth = document.getElementById('total-sales-month');
    const recentSalesTable = document.getElementById('recent-sales-table');
    const topCustomersTable = document.getElementById('top-customers-table');

    // Load dashboard data
    loadDashboardData();

    // Load charts
    loadSalesChart();
    loadStockChart();

    // Load dashboard summary data
    function loadDashboardData() {
        // Get stock totals
        AJAX.get('/api/inventory.php?action=stock', function(response) {
            if (response.success && response.data.totals) {
                const totals = response.data.totals;
                totalPhysicalStock.textContent = parseInt(totals.total_physical).toLocaleString();
                totalBookedStock.textContent = parseInt(totals.total_booked).toLocaleString();

                // Calculate available stock
                const available = parseInt(totals.total_physical) - parseInt(totals.total_booked);
                document.getElementById('total-available').textContent = available.toLocaleString();
            } else {
                totalPhysicalStock.textContent = 'N/A';
                totalBookedStock.textContent = 'N/A';
                document.getElementById('total-available').textContent = 'N/A';
            }
        }, function(error) {
            console.error('Error loading stock data:', error);
            totalPhysicalStock.textContent = 'Error';
            totalBookedStock.textContent = 'Error';
            document.getElementById('total-available').textContent = 'Error';
        });

        // Get total customers
        AJAX.get('/api/customers.php?action=list&per_page=1', function(response) {
            if (response.success && response.pagination) {
                totalCustomers.textContent = response.pagination.total.toLocaleString();
            } else {
                totalCustomers.textContent = 'N/A';
            }
        }, function(error) {
            console.error('Error loading customer count:', error);
            totalCustomers.textContent = 'Error';
        });

        // Get sales for current month
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];

        AJAX.get(`/api/sales.php?action=monthly&months=1`, function(response) {
            if (response.success && response.data && response.data.length > 0) {
                totalSalesMonth.textContent = Util.formatCurrency(response.data[0].total);
            } else {
                totalSalesMonth.textContent = Util.formatCurrency(0);
            }
        }, function(error) {
            console.error('Error loading monthly sales:', error);
            totalSalesMonth.textContent = 'Error';
        });

        // Load recent sales
        AJAX.get('/api/sales.php?action=recent&limit=5', function(response) {
            if (response.success && response.data) {
                const sales = response.data;

                if (sales.length === 0) {
                    recentSalesTable.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No sales found</td></tr>';
                    return;
                }

                let html = '';
                sales.forEach(sale => {
                    html += `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${sale.customer_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${sale.formatted_date}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${sale.formatted_amount}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                ${Util.createStatusBadge(sale.loading_status).outerHTML}
                            </td>
                        </tr>
                    `;
                });

                recentSalesTable.innerHTML = html;
            } else {
                recentSalesTable.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Failed to load sales</td></tr>';
            }
        }, function(error) {
            console.error('Error loading recent sales:', error);
            recentSalesTable.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Error loading sales</td></tr>';
        });

        // Load top customers by balance
        AJAX.get('/api/customers.php?action=top&limit=5', function(response) {
            if (response.success && response.data) {
                const customers = response.data;

                if (customers.length === 0) {
                    topCustomersTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No customers found</td></tr>';
                    return;
                }

                let html = '';
                customers.forEach(customer => {
                    html += `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                <a href="/ledger?customer_id=${customer.id}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    ${customer.name}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${customer.phone || '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${customer.formatted_balance}</td>
                        </tr>
                    `;
                });

                topCustomersTable.innerHTML = html;
            } else {
                topCustomersTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Failed to load customers</td></tr>';
            }
        }, function(error) {
            console.error('Error loading top customers:', error);
            topCustomersTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Error loading customers</td></tr>';
        });
    }

    // Load sales chart (last 6 months)
    function loadSalesChart() {
        AJAX.get('/api/sales.php?action=monthly&months=6', function(response) {
            if (!response.success || !response.data) {
                console.error('Failed to load sales data for chart');
                return;
            }

            const salesData = response.data;

            // Format data for Chart.js
            const labels = [];
            const data = [];

            // Process data (ensuring all months are included)
            const today = new Date();
            for (let i = 5; i >= 0; i--) {
                const date = new Date(today.getFullYear(), today.getMonth() - i, 1);
                const monthYear = date.toISOString().slice(0, 7); // Format: YYYY-MM

                labels.push(new Date(date).toLocaleDateString('default', { month: 'short', year: 'numeric' }));

                // Find if we have data for this month
                const monthData = salesData.find(item => item.month === monthYear);
                data.push(monthData ? parseFloat(monthData.total) : 0);
            }

            // Create chart
            const ctx = document.getElementById('sales-chart').getContext('2d');

            // Check if chart already exists and destroy it
            if (window.salesChart) {
                window.salesChart.destroy();
            }

            window.salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales',
                        data: data,
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderColor: 'rgb(99, 102, 241)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return Util.formatCurrency(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₦' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }, function(error) {
            console.error('Error loading sales chart data:', error);
        });
    }

    // Load stock chart
    function loadStockChart() {
        AJAX.get('/api/inventory.php?action=stock', function(response) {
            if (!response.success || !response.data || !response.data.stock) {
                console.error('Failed to load stock data for chart');
                return;
            }

            const stockData = response.data.stock;

            // Get top products (by physical stock)
            const topProducts = [...stockData]
                .sort((a, b) => b.physical_stock - a.physical_stock)
                .slice(0, 5);

            // Format data for Chart.js
            const labels = topProducts.map(product => product.name);
            const physicalData = topProducts.map(product => parseInt(product.physical_stock));
            const bookedData = topProducts.map(product => parseInt(product.booked_stock));
            const availableData = topProducts.map(product => parseInt(product.available_stock));

            // Create chart
            const ctx = document.getElementById('stock-chart').getContext('2d');

            // Check if chart already exists and destroy it
            if (window.stockChart) {
                window.stockChart.destroy();
            }

            window.stockChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Physical Stock',
                            data: physicalData,
                            backgroundColor: 'rgba(16, 185, 129, 0.6)',
                            borderColor: 'rgb(16, 185, 129)',
                            borderWidth: 1
                        },
                        {
                            label: 'Booked Stock',
                            data: bookedData,
                            backgroundColor: 'rgba(245, 158, 11, 0.6)',
                            borderColor: 'rgb(245, 158, 11)',
                            borderWidth: 1
                        },
                        {
                            label: 'Available Stock',
                            data: availableData,
                            backgroundColor: 'rgba(59, 130, 246, 0.6)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }, function(error) {
            console.error('Error loading stock chart data:', error);
        });
    }
});