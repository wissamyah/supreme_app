# Supreme Rice Mills Management System - Overview

## Purpose
A web-based management system for a rice mill business, handling inventory, customers, sales, loadings, and users.

## Tech Stack
- **Frontend**: HTML, Vanilla JS (AJAX for in-page updates), Tailwind CSS (v2.0.3 via CDN: `https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css`).
- **Backend**: PHP (for PostgreSQL communication).
- **Database**: PostgreSQL hosted on PlanetHoster.
- **External Resources**: Chart.js (via CDN: `https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js`) for dashboard charts.

## Configuration
- **`.env`**: Stores environment variables (e.g., DB credentials).
- **`.htaccess`**: Enables clean URLs (no `.php` or `/index.php` in URLs).

## Design Goals
- Minimalist, modern, mobile-responsive.
- Optimized for performance with AJAX, pagination, and PostgreSQL indexes.
- User-friendly with subtle, intuitive access to features (e.g., payments).
- Dark/light mode support via Tailwind CSS.
- Hosted at `app.supremericemills.com/`.
- No command-line dependencies; all resources via CDN or manual setup.

## File Structure
See `project_structure.md` for the detailed structure.