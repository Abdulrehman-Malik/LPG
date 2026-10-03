# Perfect LPG POS & ERP

A branch-aware LPG retail POS/ERP built with CodeIgniter 4, PHP 8.2+ and MySQL 8+.

- **Permission-aware navigation** — sidebar items are shown only when the current user's role grants the permission used by that module.

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

1. **Sell Gas Only** — customer brings their own cylinder; gas stock is reduced by entered KG and the selected physical source cylinder is tracked.
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

On the Sales page, cylinder-type selectors show available filled/empty stock context and the physical gas KG represented by filled units. Line actions use compact icon buttons, Add Line is available from the line-grid header, and Security Deposit Amount is editable only for the Security Deposit / Issue Cylinder workflow. Switching transaction types rebuilds the applicable UI and clears incompatible line state.

## Installation

1. Create/configure a MySQL 8+ database.
2. Configure the local environment from `.env.example`.
3. Run `database/schema.sql` on the database.
4. Run `database/migrations/20260930_shop_settings.sql` once after the schema (fresh or existing database).
5. Run Composer dependencies: `composer install`.
6. Point Apache/Nginx to the project's `public` directory.
7. Open the application and log in with the configured user.

## Important Environment Rule

Do not commit a local `.env` file. Use `.env.example` as the template for local configuration.

## Shop Settings Migration

The branch settings table is intentionally delivered as a non-destructive migration so existing installations are not rebuilt.

Migration: `database/migrations/20260930_shop_settings.sql`

It creates one settings record per branch and synchronizes the branch-wide stock-validation flag with the existing inventory policy.

## QA / Testing

- `TESTING.md` — development status and release gate.
- `SQA.md` — detailed execution matrix with expected/actual/status fields.
- No browser execution is falsely marked PASS unless it has been executed in the user's local test environment.