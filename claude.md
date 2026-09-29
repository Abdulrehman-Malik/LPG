# Perfect LPG (Pvt.) LTD — Project Tracker (claude.md)

> Read this file FIRST at the start of every session. It tells you exactly what is
> done, what is in progress, and what is next. Do NOT regenerate completed code.
> If credit runs out, the user will say "Continue based on claude.md" — resume
> from the exact point marked `[/]` below.

Stack: CodeIgniter 4 (PHP 8.x) · MySQL · Bootstrap 5.3 · HTML5/CSS3 · jQuery/AJAX DataTables

---

## 1. System Module Architecture & Completed Features

- [x] `claude.md` tracker created
- [x] Database schema (`database/schema.sql`) — users, customers, cylinder_types,
      gas_rates, daily_filled_stock, daily_empty_stock, sales, sale_items,
      customer_payments, expenses, daily_summaries
- [x] Auth: `app/Controllers/Auth.php` (login/logout, session, CSRF, password hashing,
      loads the Form helper explicitly — see §4 Review Notes)
- [x] Auth: `app/Views/auth/login.php` (Bootstrap 5.3 modern login view)
- [x] Auth: `app/Controllers/Filters/AuthFilter.php` (route filter/middleware)
- [x] Auth: routes wired in `app/Config/Routes.php` (auth group + filter alias in
      `app/Config/Filters.php`)
- [x] Base layout: `app/Views/layouts/app.php` (dark left sidebar, topbar, content
      slot, Bootstrap 5.3 + Bootstrap Icons + jQuery + DataTables CDN includes)
- [x] `app/Controllers/Dashboard.php` — KPI queries are now LIVE (Today's Sales,
      Cylinder Stock units, Total Gas Stock KG), no more placeholders
- [x] Models — ALL created:
      `UserModel`, `CustomerModel`, `CylinderTypeModel`, `GasRateModel`,
      `SaleModel`, `SaleItemModel`, `CustomerPaymentModel`,
      `DailyFilledStockModel`, `DailyEmptyStockModel`, `DailySummaryModel`,
      `ExpenseModel`

## 2. Current Module In-Progress

- [/] **New Sale (POS) module** — NOT started yet. This is the next unit of work:
      - `app/Controllers/Sales.php::newSale()` + `app/Views/sales/new.php`
      - Cylinder-wise vs KG-wise toggle (front-end JS swaps the input group,
        back-end `SaleItemModel` already supports both `line_type` values)
      - On submit: create `sales` row + N `sale_items` rows in a DB transaction,
        decrement `daily_filled_stock.sold_stock` for the cylinder types sold,
        increment `daily_empty_stock.received_from_cust` by `empty_recv_count`
      - Cash/Online/Credit split: `credit_amount = total_amount - cash_received -
        online_received`; route the leftover into the customer's running balance
        (already computed on read via `CustomerModel::withLedgerTotals()`)
      - Route to wire: uncomment `sales/new` in `Routes.php` once the controller exists

## 3. Remaining Backlog & Pending Tasks

- [ ] New Sale (POS) — see §2, this is next
- [ ] Sales Search & Transactions module (filters, summary cards, data grid,
      edit/delete actions) — `SaleModel::search()` already built to support this
- [ ] Customers / Parties directory + Customer Ledger + "+ Record Payment" modal —
      `CustomerModel::withLedgerTotals()` and `CustomerPaymentModel` already built
- [ ] Cylinder Stock module (daily filled/empty grids, auto closing-stock logic,
      total weight summary) — `DailyFilledStockModel`/`DailyEmptyStockModel`
      already have `forDate()` + `ensureOpeningRowsForDate()` ("open the day"
      roll-forward logic) ready to call from a controller
- [ ] Daily Cash / Sale Report (opening cash, cash/credit/online sale, expenses,
      hand-over, balance) — `DailySummaryModel::upsertForDate()` already computes
      the balance formula; needs a controller + view
- [ ] Settings & Users (user management, cylinder capacities, gas rates) —
      `GasRateModel` already supports versioned effective-dated rates
- [ ] Seed data / sample fixtures for local testing (at least one admin user via
      `UserModel::createWithPassword()`, a few customers, a day-1 opening stock)
- [ ] `.env` / `app/Config/Database.php` wiring instructions for the user's MySQL

## 4. Review Notes (bugs caught and fixed this pass)

- **Fixed:** `login.php` calls `csrf_field()` and `old()`, which are **Form
  helper** functions. CI4 only autoloads the `url` helper by default — without
  `helper('form')`, hitting `/login` would throw "Call to undefined function
  csrf_field()". Added `helper('form');` to `Auth::__construct()`.
- Verified: all PHP files have balanced braces and correct opening tags (checked
  programmatically; no PHP interpreter available in this sandbox to run `php -l`
  or a real CI4 boot — recommend running `php -l` and hitting `/login` once this
  is dropped into a real CodeIgniter 4 install with Composer dependencies).
- Verified: generated `closing_stock` columns in `daily_filled_stock` /
  `daily_empty_stock` are never written to directly by their models (correct —
  MySQL computes them).
- `app/Config/Filters.php` is a full drop-in replacement of CI4's default file
  with only the `auth` alias added — merge it into an existing project's
  `Filters.php` rather than overwriting if other filters are already registered.

---
*Last updated: models complete + Dashboard KPIs wired live + review pass (Form
helper bug fixed). Next session should start directly on the New Sale (POS)
module per §2.*
