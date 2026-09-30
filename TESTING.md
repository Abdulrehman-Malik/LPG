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

## Phase 3 — POS Sales (IN DEVELOPMENT — DO NOT TEST YET)
- [ ] POS screen: customer selection, line grid, totals, payments, validation.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Filled cylinder sale: capacity-based gas/cylinder inventory OUT and applied package rate.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] KG refill: KG-based gas inventory OUT and gas/kg rate.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Cylinder exchange: filled cylinder + gas OUT and empty cylinder IN.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Empty cylinder intake: empty-cylinder inventory IN.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Empty cylinder sale: empty-cylinder inventory OUT.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Cash / cheque / online / credit: payment validation and walk-in restrictions.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Credit-limit enforcement: Previous OS + Current Credit must not exceed Credit Limit.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Previous OS + Current Credit = New OS: customer balance calculation and display.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Custom-rate detection: applied rate differs from standard rate and is flagged.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Atomic inventory + financial posting: sale, items, payments and inventory movements commit or roll back together.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Receipt / print.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
- [ ] Sale void / reversal: posted transactions are reversed/voided without hard deletion.
  - SQA Comments / Improvement Notes:
  - Evidence / Test Data:
Phase 3 status: NOT READY FOR USER TEST

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
