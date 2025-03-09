# Project Structure
supreme-rice-mills/
├── assets/
│   ├── js/
│   │   ├── scripts.js           # General frontend logic (AJAX, utils)
│   │   ├── dashboard.js         # Dashboard-specific JS (charts)
│   │   ├── inventory.js         # Inventory-specific JS
│   │   ├── customers.js         # Customers-specific JS
│   │   ├── sales.js             # Sales-specific JS
│   │   ├── loading.js           # Loading-specific JS
│   │   ├── users.js             # Users-specific JS
│   │   ├── settings.js          # Settings-specific JS
│   │   ├── ledger.js            # Ledger-specific JS
│   │   └── reports.js           # Reports-specific JS
│   └── css/
│       └── styles.css          # Custom CSS (if needed beyond Tailwind)
├── includes/
│   ├── db_connect.php          # Database connection using .env variables
│   ├── functions.php           # Shared PHP functions (e.g., security, utilities)
│   ├── session.php             # Session management logic
│   ├── header.php              # Top navigation bar with dropdowns
│   └── footer.php              # Footer content
├── pages/
│   ├── login.php               # Login page logic
│   ├── dashboard.php           # Dashboard page logic
│   ├── inventory.php           # Inventory page logic
│   ├── customers.php           # Customers page logic
│   ├── sales.php               # Sales page logic
│   ├── loading.php             # Loading page logic
│   ├── users.php               # Users page logic
│   ├── settings.php            # Settings page logic
│   ├── ledger.php              # Customer ledger page logic
│   └── reports.php             # Reports page logic
├── templates/
│   ├── login.html              # Login page template
│   ├── dashboard.html          # Dashboard template
│   ├── inventory.html          # Inventory template
│   ├── customers.html          # Customers template
│   ├── sales.html              # Sales template
│   ├── loading.html            # Loading template
│   ├── users.html              # Users template
│   ├── settings.html           # Settings template
│   ├── ledger.html             # Ledger template
│   └── reports.html            # Reports template
├── api/
│   ├── auth.php                # Login/logout API
│   ├── inventory.php           # Inventory CRUD API
│   ├── production.php          # Production CRUD API
│   ├── customers.php           # Customers CRUD API
│   ├── transactions.php        # Ledger/transaction API
│   ├── sales.php               # Sales CRUD API
│   ├── loading.php             # Loading CRUD API
│   ├── users.php               # Users CRUD API
│   ├── settings.php            # Settings API
│   └── reports.php             # Reports generation API
├── .env                        # Environment variables (DB credentials, etc.)
├── .htaccess                   # URL rewriting for clean URLs
├── index.php                   # Main entry point (routes requests)
└── setup_admin.php             # Initial admin setup script



## Notes
- Separate JS files per page in `assets/js/` to avoid large files and mixing JS with PHP.
- `includes/header.php` and `includes/footer.php` are used across all pages.
- `index.php` routes requests to appropriate `pages/*.php` files based on clean URLs.


