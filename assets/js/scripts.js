// General frontend logic for Supreme Rice Mills Management System

// Dark mode toggle
document.addEventListener('DOMContentLoaded', function() {
    // Check if dark mode is enabled in localStorage
    const darkMode = localStorage.getItem('darkMode') === 'true';
    if (darkMode) {
        document.documentElement.classList.add('dark');
    }

    // Dark mode toggle button
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        });
    }

    // User dropdown menu
    const userMenuButton = document.getElementById('user-menu-button');
    const userDropdown = document.getElementById('user-dropdown');
    if (userMenuButton && userDropdown) {
        userMenuButton.addEventListener('click', function() {
            userDropdown.classList.toggle('hidden');
        });

        // Close the dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                userDropdown.classList.add('hidden');
            }
        });
    }

    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    if (tabButtons.length > 0) {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Get the target tab content
                const tabId = this.id.replace('tab-', 'content-');
                const tabContent = document.getElementById(tabId);

                // Hide all tab contents and remove active class from all tab buttons
                document.querySelectorAll('.tab-pane').forEach(tab => tab.classList.add('hidden'));
                tabButtons.forEach(btn => {
                    btn.classList.remove('border-indigo-500', 'dark:border-indigo-400', 'text-indigo-600', 'dark:text-indigo-300');
                    btn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                });

                // Show the selected tab content and add active class to the clicked button
                if (tabContent) {
                    tabContent.classList.remove('hidden');
                    this.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                    this.classList.add('border-indigo-500', 'dark:border-indigo-400', 'text-indigo-600', 'dark:text-indigo-300');
                }
            });
        });
    }
});

// AJAX utility functions
const AJAX = {
    // Get JSON data from an API endpoint
    get: function(url, callback, errorCallback) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(response);
                    } catch (e) {
                        if (errorCallback) {
                            errorCallback('Invalid JSON response');
                        }
                    }
                } else {
                    if (errorCallback) {
                        errorCallback(xhr.statusText || 'Server error');
                    }
                }
            }
        };
        xhr.onerror = function() {
            if (errorCallback) {
                errorCallback('Network error');
            }
        };
        xhr.send();
    },

    // Send JSON data to an API endpoint with POST
    post: function(url, data, callback, errorCallback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(response);
                    } catch (e) {
                        if (errorCallback) {
                            errorCallback('Invalid JSON response');
                        }
                    }
                } else {
                    if (errorCallback) {
                        errorCallback(xhr.statusText || 'Server error');
                    }
                }
            }
        };
        xhr.onerror = function() {
            if (errorCallback) {
                errorCallback('Network error');
            }
        };
        xhr.send(JSON.stringify(data));
    },

    // Send form data to an API endpoint with POST
    postForm: function(url, formData, callback, errorCallback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(response);
                    } catch (e) {
                        if (errorCallback) {
                            errorCallback('Invalid JSON response');
                        }
                    }
                } else {
                    if (errorCallback) {
                        errorCallback(xhr.statusText || 'Server error');
                    }
                }
            }
        };
        xhr.onerror = function() {
            if (errorCallback) {
                errorCallback('Network error');
            }
        };
        xhr.send(formData);
    },

    // Update data with PUT
    put: function(url, data, callback, errorCallback) {
        const xhr = new XMLHttpRequest();
        xhr.open('PUT', url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(response);
                    } catch (e) {
                        if (errorCallback) {
                            errorCallback('Invalid JSON response');
                        }
                    }
                } else {
                    if (errorCallback) {
                        errorCallback(xhr.statusText || 'Server error');
                    }
                }
            }
        };
        xhr.onerror = function() {
            if (errorCallback) {
                errorCallback('Network error');
            }
        };
        xhr.send(JSON.stringify(data));
    },

    // Delete data
    delete: function(url, data, callback, errorCallback) {
        const xhr = new XMLHttpRequest();
        xhr.open('DELETE', url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(response);
                    } catch (e) {
                        if (errorCallback) {
                            errorCallback('Invalid JSON response');
                        }
                    }
                } else {
                    if (errorCallback) {
                        errorCallback(xhr.statusText || 'Server error');
                    }
                }
            }
        };
        xhr.onerror = function() {
            if (errorCallback) {
                errorCallback('Network error');
            }
        };
        xhr.send(JSON.stringify(data));
    }
};

// Utility functions
const Util = {
    // Format currency as Naira (₦)
    formatCurrency: function(amount) {
        return '₦' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    },

    // Format date as DD/MM/YYYY
    formatDate: function(dateString) {
        const date = new Date(dateString);
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    },

    // Get today's date in YYYY-MM-DD format
    getTodayDate: function() {
        const today = new Date();
        const day = today.getDate().toString().padStart(2, '0');
        const month = (today.getMonth() + 1).toString().padStart(2, '0');
        const year = today.getFullYear();
        return `${year}-${month}-${day}`;
    },

    // Show a notification
    showNotification: function(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg transform transition-transform duration-300 ease-in-out';

        // Set color based on type
        if (type === 'success') {
            notification.classList.add('bg-green-500', 'text-white');
        } else if (type === 'error') {
            notification.classList.add('bg-red-500', 'text-white');
        } else if (type === 'warning') {
            notification.classList.add('bg-yellow-500', 'text-white');
        } else {
            notification.classList.add('bg-blue-500', 'text-white');
        }

        // Add message
        notification.textContent = message;

        // Add to body
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.add('translate-y-4');
        }, 10);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('translate-y-4');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    },

    // Show loading indicator
    showLoading: function(element) {
        const loading = document.createElement('div');
        loading.className = 'loading';
        element.innerHTML = '';
        element.appendChild(loading);
    },

    // Create loading status badge
    createStatusBadge: function(status) {
        const badge = document.createElement('span');
        badge.className = 'status-badge';

        if (status === 'Pending') {
            badge.classList.add('status-pending');
        } else if (status === 'Partially Loaded') {
            badge.classList.add('status-partial');
        } else if (status === 'Fully Loaded') {
            badge.classList.add('status-complete');
        }

        badge.textContent = status;
        return badge;
    }
};

// Confirmation modal functionality
function initConfirmationModal() {
    const confirmModal = document.getElementById('confirm-modal');
    const confirmTitle = document.getElementById('confirm-title');
    const confirmMessage = document.getElementById('confirm-message');
    const confirmCancel = document.getElementById('confirm-cancel');
    const confirmOk = document.getElementById('confirm-ok');

    if (!confirmModal || !confirmTitle || !confirmMessage || !confirmCancel || !confirmOk) {
        return;
    }

    // Close modal when cancel is clicked
    confirmCancel.addEventListener('click', function() {
        confirmModal.classList.add('hidden');
    });

    // Public API for the confirmation modal
    window.confirmAction = function(title, message, callback) {
        confirmTitle.textContent = title;
        confirmMessage.textContent = message;

        // Set up the confirm button
        confirmOk.onclick = function() {
            confirmModal.classList.add('hidden');
            if (callback) {
                callback();
            }
        };

        // Show the modal
        confirmModal.classList.remove('hidden');
    };
}

// Initialize confirmation modal when the page is loaded
document.addEventListener('DOMContentLoaded', initConfirmationModal);