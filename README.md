# Perfect LPG POS & ERP

A branch-aware LPG retail POS/ERP built with CodeIgniter 4, PHP 8.2+ and MySQL 8+.

## Core Modules

- Authentication and role-based access
- Customers and supplier ledgers
- Cylinder types and LPG pricing
- Physical cylinder inventory with actual gas weight
- Five-mode LPG POS
- Purchases and supplier payments
- Counter Cash
- Expenses
- Inventory controls and gas wastage
- Reports and audit logging
- Branch-level Shop Settings

## POS Transaction Modes

1. **Sell Gas Only** — customer brings their own cylinder; the cashier selects exactly one filled physical source cylinder, gas stock is reduced only from that unit, and the same unit becomes empty when its gas reaches zero.
2. **Sell Gas by Replacing Same-Capacity Cylinder** — customer returns an empty cylinder and receives the same capacity filled cylinder.
3. **Sell Filled Cylinder with Gas + Cylinder Price** — gas is charged by actual gas weight plus the cylinder price.
4. **Sell Filled Cylinder with Gas + Replace Different-Capacity Cylinder** — same as mode 3, with a different empty cylinder type received.
5. **Sell Empty Cylinder Only** — only empty-cylinder inventory is affected.

## Shop Settings

Open **Shop Settings** from the left navigation.

The branch-level settings are organized into tabs:

- General — branch name, address, city, phone
- POS & Sales — default POS transaction mode
- Inventory Control — sale stock validation and stock override policy
- Cash & Payments — default payment mode
- Receipt & Printing — receipt title, footer and branch address
- Backup & Maintenance — database backup URL/endpoint and maintenance notes

The POS no longer contains a separate default-transaction preference control. It reads the branch default from Shop Settings.

## Installation

1. Create/configure a MySQL 8+ database.
2. Configure the local environment from `.env.example`.
3. Run `database/schema.sql` on the database.
4. Run the required non-destructive migrations after the schema, including `013_20261004_customer_credit_sale_control.sql` for the customer credit-sale rule.
5. On an existing database, also run `database/migrations/009_20261003_permissions_sync.sql` to add any newer permissions and restore the default ADMIN/MANAGER/CASHIER permission mappings without removing custom permissions.
6. Run Composer dependencies: `composer install`.
7. Point Apache/Nginx to the project's `public` directory.
8. Open the application and log in with the configured user.

## Important Environment Rule

Do not commit a local `.env` file. Use `.env.example` as the template for local configuration.

## Shop Settings Migration

The branch settings table is intentionally delivered as a non-destructive migration so existing installations are not rebuilt.

Migration: `database/migrations/001_20260930_shop_settings.sql`

Permissions migration: `database/migrations/009_20261003_permissions_sync.sql`

It adds missing standard permissions and backfills the default role mappings so Reports (`REPORT_VIEW`) and Shop Settings access are available to the standard roles according to the application's permission model. Custom role permissions are not removed.

It creates one settings record per branch and synchronizes the branch-wide stock-validation flag with the existing inventory policy.

## Database Deployment Migrations

All manual SQL deployment scripts use a numeric sequence prefix. Run only migrations that are not already applied to the target database, in this order:

1. `001_20260930_shop_settings.sql`
2. `002_20260930_inventory_controls.sql`
3. `003_20260930_per_cylinder_inventory.sql`
4. `004_20261002_credit_limit_validation.sql`
5. `005_20261002_opening_inventory_comments.sql`
6. `006_20261002_security_deposits.sql`
7. `007_20261003_pos_transaction_type.sql`
8. `008_20261003_individual_cylinder_tracking.sql`
9. `009_20261003_permissions_sync.sql`
10. `010_20261004_purchase_inventory_integrity.sql`
11. `011_20261003_refresh_transactional_data.sql` — **manual staging/test-data refresh only; never run automatically in production**
12. `012_20261004_physical_cylinder_code.sql` — physical cylinder code migration
13. `013_20261004_customer_credit_sale_control.sql` — per-customer credit-sale permission

See `database/migrations/README.md` for the deployment procedure and safety rules.

## QA / Testing

- `TESTING.md` — development status and release gate.
- `SQA.md` — detailed execution matrix with expected/actual/status fields.
- No browser execution is falsely marked PASS unless it has been executed in the user's local test environment.

## POS Customer Credit Business Rules

These rules are mandatory and are enforced in both the POS UI and SalesService server-side posting logic:

1. **Walk-in / Cash customer:** Credit sale is never allowed. A walk-in sale must be fully paid at posting time; **received amount must equal the sale total**. Walk-in payments are cash only.
2. **Actual customer:** Credit sale is allowed only when **Allow Credit Sale** is enabled on that customer's record.
3. **Credit limit:** For an actual customer allowed to buy on credit, the resulting customer outstanding balance must never exceed that customer's configured **Credit Limit**. A zero credit limit therefore permits no credit.
4. **Server-side enforcement:** UI restrictions are not trusted by themselves; direct POST requests are validated by the sales service as well.
5. **Customer configuration:** The Customers / Parties screen shows the credit-sale status and provides the **Allow Credit Sale** option alongside the customer's Credit Limit.


## POS Source Filled Cylinder Selection

Shop Settings → POS & Sales includes **Allow user to select the source filled cylinder on POS**.

- **ON:** Gas Sale / Refill shows the Source Filled Cylinder dropdown. One source physical cylinder can be used on only one POS line, the entered KG cannot exceed that cylinder's available gas, and stock is deducted only from the selected cylinder.
- **OFF:** The source dropdown is hidden. The system automatically allocates gas from filled physical cylinders in ascending global sequence (cylinder unit ID), consuming one cylinder's available quantity before moving to the next.
- The same cylinder type cannot appear on multiple gas-sale lines. Available gas stock is shown in the Cylinder Type dropdown as the sum of gas across its filled physical cylinders.
- Server-side validation and transaction locking enforce the same rules and prevent concurrent overselling.

Migration: `database/migrations/014_20261004_pos_source_filled_cylinder_selection.sql`.
