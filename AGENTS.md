# Agent Instructions

This document provides guidance for AI agents working on the Open Source Point of Sale (OSPOS) codebase.

## Code Style

- Follow PHP CodeIgniter 4 coding standards
- Run PHP-CS-Fixer before committing: `vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.no-header.php`
- Write PHP 8.1+ compatible code with proper type declarations
- Use PSR-12 naming conventions: `camelCase` for variables and functions, `PascalCase` for classes, `UPPER_CASE` for constants

## Development

- Create a new git worktree for each issue, based on the latest state of `origin/master`
- Commit fixes to the worktree and push to the remote
- Open the MySQL shell with `docker compose exec mysql mysql -uadmin -ppointofsale ospos`

## Testing

- Run PHPUnit tests: `composer test`
- Tests must pass before submitting changes

## Build

- Install dependencies: `composer install && npm install`
- Build assets: `npm run build` or `gulp`

## Conventions

- Controllers go in `app/Controllers/`
- Models go in `app/Models/`
- Views go in `app/Views/`
- Database migrations in `app/Database/Migrations/`
- Use CodeIgniter 4 framework patterns and helpers
- Sanitize user input; escape output using `esc()` helper
- This fork should use `PHP` as the currency code and `₱` as the currency symbol
- After rebuilding the database from `app/Database/database.sql`, reapply the currency settings because the fresh-install default still uses `$`

## Feature History

- `4b6835e4d` (`2026-03-25`) added the `loan_adjustments` module and mirrors adjustment entries into `customer_loans`.
- `166098161` (`2026-04-04`) added supplier partnership and split receivings for landowner and tenant flows.
- `e8fc213e2` (`2026-04-07`) added `lunas`, luna-aware loan balances, receiving loan snapshots, and supplier loan detail views.
- `5daf5c321` (`2026-04-07`) fixed loan adjustment autocomplete so duplicate supplier names remain distinguishable as `Name - Land Owner` or `Name - Tenant`.

## Copra Workflow Notes

- For luna receivings, the selected supplier is expected to be the landowner. The tenant is derived from the selected `luna` and treated as the partner supplier.
- Receivings validates the selected luna against `landowner_id`. Sales and loan adjustments can validate against either landowner or tenant depending on the supplier role.
- Do not assume `suppliers/suggest` is role-aware. If duplicate supplier names exist across landowner and tenant records, use a workflow-specific autocomplete endpoint when disambiguation matters.
- Loan changes now exist in both general and luna-specific contexts. Check `customer_loans.luna_id`, `Loan_adjustments`, and `Receiving_loan_snapshot` before changing balance logic or reports.
- To find soft-deleted lunas that still have non-zero balances while both landowner and tenant supplier records are active, including separate landowner and tenant balances, use:

```sql
SELECT
    l.luna_id,
    l.area_name,
    l.barangay,

    l.landowner_id,
    TRIM(CONCAT(COALESCE(lp.first_name, ''), ' ', COALESCE(lp.last_name, ''))) AS landowner_name,
    ls.deleted AS landowner_deleted,
    COALESCE(SUM(CASE
        WHEN cl.customer_id = ls.customer_id THEN cl.loan_amount
        ELSE 0
    END), 0) AS landowner_balance,

    l.tenant_id,
    TRIM(CONCAT(COALESCE(tp.first_name, ''), ' ', COALESCE(tp.last_name, ''))) AS tenant_name,
    ts.deleted AS tenant_deleted,
    COALESCE(SUM(CASE
        WHEN cl.customer_id = ts.customer_id THEN cl.loan_amount
        ELSE 0
    END), 0) AS tenant_balance,

    l.deleted AS luna_deleted,
    SUM(cl.loan_amount) AS total_balance
FROM ospos_lunas l
JOIN ospos_customer_loans cl
    ON cl.luna_id = l.luna_id
JOIN ospos_suppliers ls
    ON ls.person_id = l.landowner_id
    AND ls.deleted = 0
JOIN ospos_suppliers ts
    ON ts.person_id = l.tenant_id
    AND ts.deleted = 0
LEFT JOIN ospos_people lp
    ON lp.person_id = l.landowner_id
LEFT JOIN ospos_people tp
    ON tp.person_id = l.tenant_id
WHERE l.deleted = 1
GROUP BY
    l.luna_id,
    l.area_name,
    l.barangay,
    l.landowner_id,
    lp.first_name,
    lp.last_name,
    ls.deleted,
    ls.customer_id,
    l.tenant_id,
    tp.first_name,
    tp.last_name,
    ts.deleted,
    ts.customer_id,
    l.deleted
HAVING ABS(SUM(cl.loan_amount)) > 0.00001
ORDER BY l.luna_id;
```
- On 2026-05-06, lunas `4`, `55`, `144`, `180`, and `198` matched this diagnostic and were undeleted proactively because the client often asks for these recoveries every day or every other day.

## Security

- Never commit secrets, credentials, or `.env` files
- Use parameterized queries to prevent SQL injection
- Validate and sanitize all user input
