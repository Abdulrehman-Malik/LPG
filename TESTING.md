# Perfect LPG — TESTING.md

## Test Gate

This file tracks the development state and the executable functional test gate.

**Application URL:** `http://localhost:180/LPG2/LPG/public/`

**Default test login:** `admin / admin123`

### Status convention

- `[x]` = verified PASS by the person who executed the test
- `[!]` = FAIL; include evidence/reproduction
- `[ ]` = READY / not yet executed
- `[-]` = N/A

**Important:** Code review or static inspection is not a substitute for browser/DB execution. SQA must execute the browser flows and enter the actual result in **SQA.md**.

---

## Current Development Status

The requested functional scope is implemented and the repository has undergone a second code review.

### Completed development

- Authentication, sessions and role/permission protection.
- Dashboard.
- Customers/parties and customer ledger.
- Suppliers and supplier ledger.
- Cylinder types and capacity validation.
- LPG gas/kg and cylinder-price rates with history.
- Users, roles and permissions.
- Opening inventory.
- Physical cylinder-unit inventory with per-cylinder actual gas weight.
- POS five-mode transaction model: Sell Gas Only; Same-Capacity Replacement; Filled + Gas; Different-Capacity Replacement; Empty Only.
- Cash/cheque/online/credit payments.
- Customer credit-limit enforcement.
- Customer receipts.
- Purchases and supplier credit-limit enforcement.
- Supplier payments.
- Counter cash opening, movement, history and close/reconciliation.
- Expenses.
- Inventory stock adjustments.
- Configurable stock validation with explicit override confirmation when disabled.
- Shop-default and cylinder-type inventory policy overrides.
- Configurable wastage allowance using percentage or fixed KG.
- Partial physical-cylinder wastage and full conversion to empty stock.
- Wastage history/report filtering.
- Sale void/reversal with inventory and cash reversal.
- Daily reports, ledgers and audit logging.
- POS numeric rate inputs use 2-decimal increments.
- Local environment file removed from source control; `.env.example` is provided.

### Important inventory behavior

A filled physical cylinder has:
- a cylinder type/capacity;
- a physical unit code;
- an actual gas weight between 0 and capacity;
- a status: filled, empty or sold.

POS transaction behavior is explicit:
- **Sell Gas Only:** gas stock decreases by entered KG; the user selects the filled-cylinder type used as the source. Full consumption converts that physical source cylinder to empty; partial consumption leaves the source cylinder filled with its remaining actual gas.
- **Same-Capacity Replacement:** customer returns an empty cylinder and receives a filled cylinder of the same type; gas is charged by actual gas weight and the returned empty is added to physical stock.
- **Filled + Gas:** gas is charged by actual gas weight plus cylinder price; gas and filled-cylinder stock decrease.
- **Different-Capacity Replacement:** same as Filled + Gas, but the returned empty cylinder is a different type/capacity.
- **Empty Only:** only empty-cylinder stock is reduced; gas stock is unchanged.
- Users can save their preferred POS transaction type as their default in the browser for their logged-in user.

Selling a filled cylinder deducts its **actual gas weight**, not automatically its rated capacity.

Partial wastage:
- deducts only the gas reported as wasted;
- reduces the physical unit's gas weight;
- keeps the unit filled if gas remains.

Full wastage:
- deducts the remaining gas;
- moves the physical unit from filled to empty;
- increases empty-cylinder stock.

Wastage cannot exceed current gas or the configured percentage/fixed-KG allowance.

---

## Code Review Fixes Applied

1. Fixed duplicate POS JavaScript declaration that could prevent POS submission.
2. Fixed POS empty-cylinder stock display so it uses real controller stock.
3. Changed POS and purchase rate number inputs to 2-decimal increments.
4. Made inventory cylinder adjustments create/remove physical cylinder units instead of changing aggregate counts only.
5. Added actual-gas input for filled-cylinder inventory adjustments.
6. Changed wastage from an all-or-nothing operation to correct partial-loss behavior.
7. Enforced configured wastage allowance and cylinder-type policy override.
8. Moved wastage physical-unit locking inside the DB transaction.
9. Applied cylinder-type stock-validation overrides during POS gas validation.
10. Enforced customer receipt amount against outstanding customer balance.
11. Enforced supplier payment amount against outstanding supplier balance.
12. Corrected supplier credit-limit validation to consider existing outstanding credit, not only opening balance.
13. Removed tracked local `.env`; added `.env.example` and Git ignore rules.

---

## Phase 1 — Foundation

Previously user-tested / recorded as PASS:

- [x] Database schema installation.
- [x] Database seed data.
- [x] Application configuration.
- [x] Authentication.
- [x] Dashboard.

**Note:** Re-run these as part of the complete SQA regression if the environment/database has been rebuilt.

---

## Phase 2 — Master Data

Previously user-tested / recorded as PASS:

- [x] Customers / Parties.
- [x] Suppliers.
- [x] Cylinder Types.
- [x] LPG Rates.
- [x] Users / Roles / Permissions.
- [x] Opening Inventory.
- [x] Customer ledger.
- [x] Supplier ledger.

**Regression requirement:** SQA must repeat the master-data create/edit/inactive/duplicate/validation cases in SQA.md.

---

## Phase 3/4/5/6 — Full Functional SQA Gate

The following are now **READY FOR SQA** after development completion:

- [ ] Counter Cash open / IN / OUT / history / close.
- [ ] Physical cylinder opening stock and actual gas weight.
- [ ] POS filled-cylinder sale.
- [ ] POS KG refill.
- [ ] POS cylinder exchange.
- [ ] Empty-cylinder intake.
- [ ] Empty-cylinder sale.
- [ ] Custom rates and rate history.
- [ ] Customer credit sales and credit-limit enforcement.
- [ ] Customer receipts and outstanding-balance enforcement.
- [ ] Purchases.
- [ ] Supplier credit-limit enforcement.
- [ ] Supplier payments and outstanding-balance enforcement.
- [ ] Expenses.
- [ ] Physical inventory adjustments.
- [ ] Configurable stock validation.
- [ ] Stock-validation OFF override confirmation.
- [ ] Cylinder-type stock-policy overrides.
- [ ] Percentage wastage.
- [ ] Fixed-KG wastage.
- [ ] Partial cylinder wastage.
- [ ] Full cylinder-to-empty wastage.
- [ ] Wastage report/date/type filtering.
- [ ] Sale void/reversal.
- [ ] Inventory reconciliation.
- [ ] Customer/supplier ledger reconciliation.
- [ ] Daily reports.
- [ ] Audit logging.
- [ ] Browser regression and JavaScript error check.
- [ ] End-to-end retail-day flow.

Execute the complete matrix in **SQA.md** and record Actual Result, Comments and Status for every case.

---

## Required SQA Execution Order

1. Fresh installation/configuration.
2. Login/permissions.
3. Master data.
4. Opening gas and physical-cylinder inventory.
5. Counter Cash opening.
6. Purchase/inventory receipt.
7. POS filled-cylinder sale.
8. KG refill.
9. Cylinder exchange/intake/empty sale.
10. Credit sale and customer receipt.
11. Supplier credit purchase and supplier payment.
12. Expense and manual cash movements.
13. Inventory controls and wastage.
14. Sale void/reversal.
15. Reports and audit.
16. Counter Cash close.
17. End-to-end reconciliation.

Do not manually correct database balances during testing. If a result is wrong, record the defect and reproduction steps.

---

## Environment Notes

- PHP 8.2+ with intl, mbstring and MySQLi.
- MySQL 8+ / InnoDB.
- Run `database/schema.sql` on a fresh database.
- For an existing database, preserve the current physical-cylinder tables and data; the five POS modes do not require a schema migration because they use the existing sales, sale_items and inventory movement structures.
- Local configuration should be created from `.env.example`.
- The repository intentionally does not contain a local `.env`.

## Automated Tests

The repository contains CodeIgniter/PHPUnit framework example tests, but the business-specific functional test suite is primarily represented by the browser/DB SQA matrix in **SQA.md**.

The current development environment used for this review did not provide the user's local Apache/MySQL/browser runtime, so no local browser test is being falsely marked PASS here.

## Release Gate

Development is considered functionally complete for the defined scope when:
- all required implementation items above remain present;
- SQA executes SQA.md;
- every required case is PASS or has an explicitly accepted documented exception;
- all FAIL cases are fixed and re-tested;
- final SQA sign-off is completed.
