# Prompt for Claude

Generate a complete web application for the Supreme Rice Mills Management System based on the attached files: `overview.md`, `database_schema.md`, and `features.md`. The app must:

1. Use HTML, Vanilla JS (AJAX), Tailwind CSS (v2.0.3 CDN), and Chart.js (3.9.1 CDN) for the frontend.
2. Use PHP to connect to PostgreSQL (credentials in `.env`) for the backend.
3. Implement all features in `features.md` with a minimalist, modern, mobile-responsive design.
4. Optimize performance with pagination, indexes, and efficient AJAX calls.
5. Include security: password hashing (PHP `password_hash`), CSRF protection, input validation, session management.
6. Create `setup_admin.php` to insert the initial admin user.
7. Support dark/light mode via Tailwind CSS.
8. Use `.env` for environment variables (e.g., `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
9. Use `.htaccess` for clean URLs (e.g., `/sales` instead of `/sales.php`).
10. Structure the code as follows:
    - `assets/js/scripts.js`: Frontend logic.
    - `assets/css/styles.css`: Custom CSS.
    - `includes/db_connect.php`: DB connection.
    - `includes/functions.php`: Shared functions.
    - `includes/session.php`: Session management.
    - `includes/header.php`: Top navigation bar with dropdowns.
    - `includes/footer.php`: Footer content.
    - `pages/*.php`: Page logic.
    - `templates/*.html`: HTML templates.
    - `api/*.php`: AJAX endpoints.
    - `index.php`: Main router.
    - `.env`: Environment variables.
    - `.htaccess`: URL rewriting.
11. Use no command-line dependencies; all resources via CDN or manual setup.
12. Include comments for clarity.

Output the full codebase with the exact file structure specified.