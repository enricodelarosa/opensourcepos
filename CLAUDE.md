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

## Copra Feature History

- `4b6835e4d` (`2026-03-25`): added the `loan_adjustments` module. This feature creates manual cash-in or cash-out loan balance changes for a supplier and mirrors them into `customer_loans` so balances and cashups stay aligned.
- `166098161` (`2026-04-04`): added supplier partnership and split receivings. This introduced the landowner and tenant supplier relationship plus split cash handling in receivings and receipts.
- `e8fc213e2` (`2026-04-07`): added `lunas` and connected them to landowners and tenants. This made loan balances and receivings luna-aware, added receiving loan snapshots for reports, and added supplier loan detail screens.
- `5daf5c321` (`2026-04-07`): fixed loan adjustment autocomplete for duplicate names where the same real person exists as separate supplier records for landowner and tenant roles. Loan adjustments now use role-aware labels like `Name - Land Owner` and `Name - Tenant`.

## Current Workflow Rules

- In receivings, the selected supplier is still the primary supplier record. For luna-based receivings, that primary supplier is expected to be the landowner.
- In receivings, the tenant is derived from the selected `luna` as the partner supplier. Do not treat the tenant as the main supplier selector for luna purchases.
- `luna` validation is not uniform across all modules. Receivings validates `luna.landowner_id === selected supplier`. Sales and loan adjustments allow access based on the selected supplier role for that luna.
- Generic `suppliers/suggest` autocomplete is not role-aware. If a workflow must distinguish duplicate supplier names across landowner and tenant records, use or add a feature-specific suggestion endpoint instead of assuming the generic endpoint is sufficient.
- Loan balances can be general or luna-specific. Receivings, reports, and loan adjustments now rely on `customer_loans.luna_id` and `receiving_loan_snapshots` for accurate before, deduction, and after values.
