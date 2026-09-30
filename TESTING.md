# Perfect LPG — TESTING.md

Use this file as the test gate between development phases.

## Application Starting URL

**Starting URL:** http://localhost:180/LPG2/LPG/public/

The application base URL is configured in .env as:

    app.baseURL = 'http://localhost:180/LPG2/LPG/public/'

After starting the application, open the URL above in your browser. The login page is available at:

    http://localhost:180/LPG2/LPG/public/login

## How to use
1. Pull the latest main branch.
2. Install PHP 8.2+ with intl, mbstring and MySQLi enabled.
3. The repository currently contains the CodeIgniter/vendor tree. If rebuilding dependencies locally, use the repository's composer.lock and verify the installed framework version before testing.
4. Copy 'env' to '.env' if required and set your local MySQL credentials/database settings. The repository configuration uses 'perfect_lpg' as the database.
5. Execute 'database/schema.sql' once on a fresh MySQL server.
6. Start the application using the configured local web server and open **http://localhost:180/LPG2/LPG/public/**.
7. If using CodeIgniter's development server instead, run 'php spark serve' and open the URL reported by Spark (normally http://localhost:8080/).
8. Test only items marked READY FOR TEST.
9. Change '[ ]' to '[x]' for PASS or '[!]' for FAIL.
10. For every FAIL, add the exact error/message and reproduction steps.
11. Commit/push your updated TESTING.md to GitHub.
12. I will read the results, fix failures, and only then start the next phase.

Status: [ ] Not tested · [x] Pass · [!] Fail · [-] N/A

## Phase 1 — Database Installation
- [x] Execute database/schema.sql successfully on a fresh MySQL server.
- [x] Database perfect_lpg is created.
- [x] All required tables are created without SQL errors.
- [x] users contains seeded admin account.
- [x] cylinder_types contains 6 standard cylinder types.
- [x] cash_registers contains REG-01.
- [x] roles, permissions and role_permissions contain seed data.
Result: [x] PASS / [ ] FAIL
Database error / notes:
> Write here.

## Phase 1 — Application Configuration
- [x] .env database connection points to perfect_lpg.
- [x] Application starts without PHP fatal error.
- [x] /login opens correctly.
- [x] Bootstrap/CSS/JS load correctly.
- [x] Application starting URL is http://localhost:180/LPG2/LPG/public/.
Result: [x] PASS / [ ] FAIL
Error / notes:
> Write here.

## Phase 1 — Authentication
- [x] Username: admin
- [x] Password: admin123
- [x] Login succeeds.
- [x] Redirect to /dashboard works.
- [x] Top-right user shows System Administrator.
- [x] Role badge shows ADMIN.
- [x] Invalid password is rejected.
- [x] Logout returns to /login.
- [x] Direct /dashboard while logged out redirects to /login.
Result: [x] PASS / [ ] FAIL
Error / reproduction steps:
> Write here.

## Phase 1 — Dashboard
- [x] Dashboard opens after login.
- [x] Today's Sales shows Rs. 0.00 on fresh database.
- [x] Cylinder Stock shows 0 on fresh database.
- [x] Total Gas Stock shows 0.00 kg on fresh database.
- [x] No SQL/PHP errors appear in page or server log.
Result: [x] PASS / [ ] FAIL
Error / notes:
> Write here.

## Phase 2 — Master Data (READY FOR TEST)
- [x] Customers / Parties: create, edit, active/inactive, required fields, duplicate-code handling, ledger link. Previous edit-modal defect fixed and retested.
  - SQA Comments / Improvement Notes: 
  - Evidence / Test Data: 
- [x] Suppliers: create, edit, active/inactive, required fields, duplicate-code handling, ledger link. Previous edit-modal defect fixed and retested.
  - SQA Comments / Improvement Notes: 
  - Evidence / Test Data: 
- [x] Cylinder Types: create, edit, capacity/tare validation, active/inactive, duplicate-code handling.
  - SQA Comments / Improvement Notes: 
  - Evidence / Test Data: 
- [x] LPG Rates: gas/kg and cylinder-package rates, effective date/time, rate history, old/new change log.
  - SQA Comments / Improvement Notes: 
  - Evidence / Test Data: 
- [x] Users / Roles / Permissions: create/update user, password hashing, role assignment, permission matrix, unauthorized access blocked. Previous edit-modal defect fixed and retested.
  - SQA Comments / Improvement Notes: 
  - Evidence / Test Data: 
- [x] Opening Inventory: gas KG, filled cylinders and empty cylinders, date/type uniqueness, non-negative validation.
  - SQA Comments / Improvement Notes: 
  - Evidence / Test Data: 
- [x] Customer ledger: opening balance + posted credit sales - posted receipts.
  - SQA Comments / Improvement Notes: 
  - Evidence / Test Data: 
- [x] Supplier ledger: opening balance + posted credit purchases - posted supplier payments.
  - SQA Comments / Improvement Notes: 
  - Evidence / Test Data: 
Phase 2 status: PASS — USER RETEST COMPLETE

### Phase 2 Regression / Improvement Notes
- Edit actions must open the correct edit modal and preload the selected record; saving an edit must persist the changed values.
- Edit forms must preserve existing values, including role selection for users; user password must remain unchanged when the password field is left blank during an edit.
- Use only `[x]` for PASS and `[!]` for FAIL; do not use uppercase `[X]`, because the documented status convention is case-sensitive for this test gate.
- Phase 2 regression completed after the edit retests. Any future SQA observation should be recorded in the per-function comment space above; implementation-impacting items should also be added to requirements.md before scheduling them.

## Phase 3 — POS Sales (READY FOR USER TEST)
- [ ] POS screen: customer selection, line grid, totals, payments, validation.
  - SQA Comments / Improvement Notes: Verify mixed-line behavior, client/server total agreement, empty-state validation, and that walk-in mode prevents non-cash payment selection. Confirm displayed OS is clearly distinguished from the resulting New OS.
  - Evidence / Test Data:
- [ ] Filled cylinder sale: capacity-based gas/cylinder inventory OUT and applied package rate.
  - SQA Comments / Improvement Notes: Confirm one package reduces both gas KG and filled-cylinder stock by the correct quantities and stores the applied rate on the sale line.
  - Evidence / Test Data:
- [ ] KG refill: KG-based gas inventory OUT and gas/kg rate.
  - SQA Comments / Improvement Notes: Verify fractional KG precision and that the displayed rate is per KG, not per cylinder.
  - Evidence / Test Data:
- [ ] Cylinder exchange: filled cylinder + gas OUT and empty cylinder IN.
  - SQA Comments / Improvement Notes: Verify exchange defaults empty return quantity to the sold quantity and all three inventory effects post together.
  - Evidence / Test Data:
- [ ] Empty cylinder intake: empty-cylinder inventory IN.
  - SQA Comments / Improvement Notes: Confirm intake is not allowed through the filled-cylinder scenario and that the empty-cylinder stock increases only once.
  - Evidence / Test Data:
- [ ] Empty cylinder sale: empty-cylinder inventory OUT.
  - SQA Comments / Improvement Notes: Verify negative empty stock is rejected and the sale stores the actual applied amount.
  - Evidence / Test Data:
- [ ] Cash / cheque / online / credit: payment validation and walk-in restrictions.
  - SQA Comments / Improvement Notes: SQA improvement to confirm reference-number rules for cheque/online once the open business decision is confirmed. Cash payments now require an open Counter Cash session and post to that session atomically; cheque/online do not enter Counter Cash.
  - Evidence / Test Data:
- [ ] Credit-limit enforcement: Previous OS + Current Credit must not exceed Credit Limit.
  - SQA Comments / Improvement Notes: Test exact-limit acceptance and one-cent-over-limit rejection; include opening balance and posted receipts in the OS calculation.
  - Evidence / Test Data:
- [ ] Previous OS + Current Credit = New OS: customer balance calculation and display.
  - SQA Comments / Improvement Notes: Verify the formula against the customer ledger after posting and ensure the POS display uses the same posted-balance source.
  - Evidence / Test Data:
- [ ] Custom-rate detection: applied rate differs from standard rate and is flagged.
  - SQA Comments / Improvement Notes: Verify both higher and lower custom rates, and confirm the standard rate and applied rate remain visible on the stored sale line.
  - Evidence / Test Data:
- [ ] Atomic inventory + financial posting: sale, items, payments, inventory movements and cash movement commit or roll back together.
  - SQA Comments / Improvement Notes: POS posting now includes cash-session sale_cash movement when cash is used. A missing open session must roll back the entire sale. Verify forced failures leave no partial sale/payment/inventory/cash rows.
  - Evidence / Test Data:
- [ ] Receipt / print: print-friendly receipt route implemented and ready for user test with browser print preview/thermal-width layout.
  - SQA Comments / Improvement Notes: Verify sale number, customer/walk-in identity, line quantities/rates, totals, payment modes and print layout. Confirm voided sales are visibly distinguishable if a receipt is opened after void.
  - Evidence / Test Data:
- [ ] Sale void / reversal: service and POS_VOID-protected route implemented and ready for user test.
  - SQA Comments / Improvement Notes: Verify inventory reversal, sale status='voided', retained original sale/payment rows, required void reason, duplicate-void rejection, and customer OS reversal for credit sales.
  - Evidence / Test Data:
Phase 3 status: READY FOR USER TEST — functional POS, cash-session linkage, receipt and void/reversal scope is implemented. Transaction-integrity hardening tests are postponed separately and are NOT a prerequisite for this functional test cycle.

### Phase 3 Hardening / Cash Control Checks — POSTPONED (DO NOT TEST YET)
- [ ] Cash session concurrency: two open attempts against the same register must not create two open sessions.
  - SQA Comments / Improvement Notes: Register-row locking is implemented. Verify duplicate-open prevention under rapid/concurrent requests and confirm only one session remains open.
  - Evidence / Test Data:
- [ ] Cash session close integrity: closing a session must lock the session state and calculate expected cash from the committed transaction set.
  - SQA Comments / Improvement Notes: Session-row locking and post-lock summary calculation are implemented. Verify a second close is rejected and cash sales cannot post after the session is closed.
  - Evidence / Test Data:
- [ ] Sale void cash reversal idempotency: a posted cash sale must produce one cash reversal only.
  - SQA Comments / Improvement Notes: Sale-row locking and duplicate reversal defense are implemented. Verify repeated void attempts are rejected and no duplicate cash-out reversal is created.
  - Evidence / Test Data:
- [ ] Inventory concurrency: concurrent sales consuming the same inventory key must not oversell stock.
  - SQA Comments / Improvement Notes: MySQL named inventory locks are acquired in deterministic key order for each posting/void. Verify two concurrent sales cannot both pass the same stock assertion when combined quantity exceeds available stock.
  - Evidence / Test Data:
- [ ] Customer credit concurrency: concurrent credit sales for the same customer must not bypass the credit limit.
  - SQA Comments / Improvement Notes: Customer row is locked and the credit limit is rechecked inside the posting transaction. Verify two concurrent credit sales cannot commit beyond the configured limit.
  - Evidence / Test Data:
- [ ] Cash session vs sale posting concurrency: a cash sale must not commit into a session after that session has been closed.
  - SQA Comments / Improvement Notes: Cash session row is locked during cash-sale posting, so close waits for the sale transaction and vice versa. Verify no cash transaction can be committed after the session state is closed.
  - Evidence / Test Data:


## Phase 4 — Purchases & Inventory (READY FOR USER TEST)
- [ ] Purchase entry: create a purchase with gas KG, filled cylinders and empty cylinders, calculate totals and post supplier/payment records.
  - SQA Comments / Improvement Notes: Verify line validation, discount calculation, supplier credit behavior, inventory IN and cash-session linkage for cash payments.
  - Evidence / Test Data:
- [ ] Inventory service / stock view: verify opening balances plus posted movements produce current stock.
  - SQA Comments / Improvement Notes: Compare displayed stock with SQL-calculated opening + IN - OUT for gas, filled and empty cylinders.
  - Evidence / Test Data:
- [ ] Stock adjustments: post authorized inventory IN/OUT adjustments with reason.
  - SQA Comments / Improvement Notes: Verify zero/negative quantities are rejected and OUT cannot reduce stock below zero.
  - Evidence / Test Data:
- [ ] Negative-stock protection: attempted sale/adjustment beyond available stock is rejected without partial posting.
  - SQA Comments / Improvement Notes: Verify both gas and cylinder stock boundaries.
  - Evidence / Test Data:
- [ ] Inventory reports: stock view is readable and location-scoped.
  - SQA Comments / Improvement Notes: Verify all active cylinder types and gas stock are visible.
  - Evidence / Test Data:
Phase 4 status: READY FOR USER TEST

## Phase 5 — Counter Cash & Expenses (READY FOR USER TEST)
- [ ] Cash register/session: open, summary and close a counter session.
  - SQA Comments / Improvement Notes: Verify opening float, expected cash and counted/difference values.
  - Evidence / Test Data:
- [ ] Cash IN/OUT: manual cash movements post to the active session.
  - SQA Comments / Improvement Notes: Verify direction, amount and reason appear in the cash total.
  - Evidence / Test Data:
- [ ] Expenses: post cash, cheque and online expenses.
  - SQA Comments / Improvement Notes: Verify cash expenses affect Counter Cash and non-cash expenses do not.
  - Evidence / Test Data:
- [ ] Customer receipts: post cash, cheque and online receipts.
  - SQA Comments / Improvement Notes: Verify customer balance decreases by posted receipts and cash receipts affect Counter Cash.
  - Evidence / Test Data:
- [ ] Supplier payments: post cash, cheque and online supplier payments.
  - SQA Comments / Improvement Notes: Verify cash payments affect Counter Cash and payment records remain linked to supplier.
  - Evidence / Test Data:
- [ ] Handover / cash movement foundation: manual cash movement is available for operational handover support.
  - SQA Comments / Improvement Notes: Confirm reason/notes are mandatory in operational practice; formal multi-register handover remains dependent on business decision.
  - Evidence / Test Data:
- [ ] Close/reconcile: close an open session and compare counted cash with expected cash.
  - SQA Comments / Improvement Notes: Verify difference is calculated and session cannot be treated as open afterward.
  - Evidence / Test Data:
Phase 5 status: READY FOR USER TEST

## Phase 6 — Reports & Audit (READY FOR USER TEST)
- [ ] Customer ledger/report: customer credit sales and receipts are traceable.
  - SQA Comments / Improvement Notes: Verify opening balance, credit sales and receipts reconcile to current OS.
  - Evidence / Test Data:
- [ ] Supplier ledger/report: supplier credit purchases and payments are traceable.
  - SQA Comments / Improvement Notes: Verify supplier outstanding calculation against posted purchase/payment data.
  - Evidence / Test Data:
- [ ] Daily transactions: daily sales/purchases summary is location-scoped and excludes voided transactions where appropriate.
  - SQA Comments / Improvement Notes: Compare summary totals to transaction rows.
  - Evidence / Test Data:
- [ ] Custom-rate report: custom-rate sales are identifiable from stored applied/standard rate flags.
  - SQA Comments / Improvement Notes: Verify higher and lower custom rates are retained for later reporting.
  - Evidence / Test Data:
- [ ] Stock report: current gas and cylinder stock is visible.
  - SQA Comments / Improvement Notes: Reconcile report values to inventory movement totals.
  - Evidence / Test Data:
- [ ] Counter cash reconciliation: daily expected vs counted cash is visible from session close.
  - SQA Comments / Improvement Notes: Verify opening + IN - OUT calculation.
  - Evidence / Test Data:
- [ ] Daily expenses: expense entries are recorded with category, amount and payment mode.
  - SQA Comments / Improvement Notes: Verify cash/non-cash treatment.
  - Evidence / Test Data:
- [ ] Outstanding balances: customer and supplier outstanding foundations can be reconciled to ledgers.
  - SQA Comments / Improvement Notes: Verify voided sales are excluded from customer OS.
  - Evidence / Test Data:
- [ ] Audit log: operational create actions are recorded with user, location, entity and timestamp.
  - SQA Comments / Improvement Notes: Verify audit entries do not expose passwords or sensitive secrets.
  - Evidence / Test Data:
Phase 6 status: READY FOR USER TEST

## Test Environment
- OS:
- PHP version:
- CodeIgniter version:
- MySQL version:
- Browser:
- Local URL: http://localhost:180/LPG2/LPG/public/

## Tester Notes
> Add screenshots, SQL errors, PHP errors or unexpected business behavior here.

## Developer Rule
A module is not complete until its tests are PASS. FAIL results become the next fix cycle; development does not silently skip failed tests.
