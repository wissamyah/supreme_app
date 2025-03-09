# Features

## General
- Mobile-responsive design using Tailwind CSS.
- AJAX for in-page updates, no full reloads.
- Search/filter across all pages.
- Pagination for large tables (default 25 rows, configurable).
- Audit trail logging for key actions.
- Top navigation bar with dropdown menus in `includes/header.php`.
- Footer content in `includes/footer.php`.
- Dark/light mode toggle using Tailwind CSS.
- Date format: `DD/MM/YYYY`.
- Currency: `₦` (Naira).
- Optimized for performance and usability; subtle, intuitive feature access.
- Clean URLs via `.htaccess` (no `.php` or `/index.php`).

## Login Page
- URL: `app.supremericemills.com/`.
- Login with username or email and password.
- Redirects to dashboard on success.
- Security: Password hashing, CSRF protection, input validation, session timeout (default 30 mins), logout button in header.

## Dashboard
- URL: `/dashboard`.
- Summary:
  - Last 5 sales.
  - Last 5 loadings.
  - Top 5 customers by balance.
  - Booked sales summary (products and balances).
- Visual charts via Chart.js (e.g., sales trends, stock levels).

## Inventory Page
- URL: `/inventory`.
- Categories: Create, edit, delete (if unused) `Head Rice`, `By Product`.
- Products: Create, edit, delete (if unused) with name, category, physical/booked stocks.
- Daily Production: Add multiple products (date, type, quantity), increments physical stock.
- Stock Table: Product Name, Category, Physical Stock, Booked Stock, Available Stock (Physical - Booked).
- Track stock movements per product.
- Product Movement Report: Date range filter.

## Customers Page
- URL: `/customers`.
- Add/edit/delete: Name, phone, state, balance.
- Balance links to ledger (URL: `/ledger?customer_id=X`).
- Ledger Page: Transactions (sale, payment, credit_note) with running balance; add payment button.
- Sales List Page per customer.

## Sales Page
- URL: `/sales`.
- Create Sale: Customer, multiple products (from inventory), quantities, rates, pre-selected today’s date.
- Display total, save updates booked stock.
- Edit/Delete Sale: Adjusts ledger and inventory.
- Loading Status: `Pending`, `Partially Loaded`, `Fully Loaded`.
- Filter booked sales by status.
- Payment tracking linked to sales (no ledger impact).
- Sales Report: Date range filter.

## Loading Page
- URL: `/loading`.
- Create Loading: Today’s date, customer (with bookings), multiple products, quantity per product, truck number, optional waybill/driver details.
- Decrements physical/booked stock.
- Edit/Delete Loading: Adjusts stocks.
- Loadings Report: Date range filter.

## Users Page
- URL: `/users`.
- Add/edit/delete: Username, email, password, role (`admin`, `moderator`, `operator`), status.
- View last session time.
- `setup_admin.php`: Inserts initial admin:
  - Username: `Wissam`
  - Email: `wissam.yahfoufi@gmail.com`
  - Password: `admin1` (hashed).

## Settings Page
- URL: `/settings`.
- Session timeout (default 30 mins).
- Rows per page (default 25).
- View audit logs (read-only).
- Manual database backup (export SQL).

## Reports
- URL: `/reports`.
- Manual generation with date range filter:
  - Production Report: Total production by product.
  - Sales Report: Sales totals/details.
  - Loadings Report: Loading history.
  - Product Movement Report: Stock changes.