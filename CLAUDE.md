# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

OpenSourcePOS is a web-based Point of Sale system built on **CodeIgniter 4** (PHP MVC framework). It handles sales, inventory, receivings, supplier/customer management, reporting, and more.

## Development Commands

### Setup
```bash
composer install          # Install PHP dependencies
npm install               # Install Node dependencies
npm run build             # Build frontend assets (runs gulp default)
```

### Docker Development
```bash
export USERID=$(id -u) && export GROUPID=$(id -g)
docker-compose -f docker-compose.dev.yml up
```
- App at `http://localhost`
- DB: MariaDB 10.5, user `admin` / password `pointofsale`, database `ospos`

### Testing
```bash
composer test                                                    # Run all tests
phpunit tests/helpers                                            # Run helpers suite
phpunit tests/Controllers/EmployeesControllerTest.php            # Run single test file
```

### Linting
```bash
php-cs-fixer fix                                                 # Fix code style (CodeIgniter 4 standard)
```

## Architecture

### MVC Structure (CodeIgniter 4)
- `app/Controllers/` — Request handling; one controller per feature (Sales, Receivings, Suppliers, Items, Reports, etc.)
- `app/Models/` — Database layer with table prefix `ospos_`
- `app/Views/` — PHP templates organized by feature; `app/Views/partial/` for shared components (header, footer, nav)
- `app/Config/` — App configuration (database, encryption, routes, filters)
- `app/Filters/` — Middleware for auth and CSRF
- `app/Helpers/` — Utility functions (e.g., `tabular_helper.php` for table rendering)
- `app/Language/en/` — i18n strings; add keys here when introducing new UI text
- `app/Database/Migrations/` — Versioned schema migrations (filename: `YYYYMMDDHHMMSS_Description.php`)
- `app/Database/database.sql` — Full schema for fresh installs

### Frontend
- Bootstrap 3/5 (dual support) + jQuery 3.7
- Gulp pipeline for asset minification/bundling
- jsPDF for client-side PDF generation; dompdf for server-side PDFs
- Barcode generation via `picqer/php-barcode-generator`

### Key Libraries
- `ezyang/htmlpurifier` + `laminas/laminas-escaper` — XSS protection; always escape user output
- `dompdf/dompdf` — Server-side PDF receipts/invoices
- CodeIgniter 4 built-in validation, sessions, CSRF (honeypot mode)

### Security Model
- Role-based access control enforced via filters
- Host header whitelist in `app/Config/App.php` (`$allowedHostnames`)
- CSRF honeypot configured; XSS escaping required on all user-facing output

### Testing Approach
Tests use real database connections (integration testing), not mocks. Test config lives in `tests/phpunit.xml`. Suites: `Helpers`, `Libraries`. Seed data is required for controller/model tests to pass.

### Database Migrations
- All schema changes must have a migration file in `app/Database/Migrations/`
- Companion SQL scripts go in `app/Database/Migrations/sqlscripts/`
- Naming: `YYYYMMDDHHMMSS_DescriptiveName.php`

### CI/CD
GitHub Actions runs PHPUnit across PHP 8.1–8.4 with MariaDB, PHP syntax linting, and CodeQL security scanning on every push.

### Manage View Pattern (Bootstrap Table + Daterangepicker)
Every `manage.php` view that has a daterangepicker and a filters dropdown **must** include these two event listeners inside `$(document).ready`, after the daterangepicker partial is loaded:

```js
$('#filters').on('hidden.bs.select', function(e) {
    table_support.refresh();
});

<?= view('partial/daterangepicker') ?>

$("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
    table_support.refresh();
});
```

Without these, changing the date range or toggling filters will not refresh the table. See `app/Views/sales/manage.php` as the canonical reference.

## Fork Additions
This ospos fork was specifically adapted for copra dealer. So the business is selling goods like rice, equipment etc but these are purchased via loans. These loans are then paid when the customers (which are now suppliers) sell copra to the business, and the business buys the copra using the loan balance that they had, plus cash if the loan is lower than the copra being soled to the business.