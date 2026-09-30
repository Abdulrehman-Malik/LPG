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
- [ ] Customers / Parties: create, edit, active/inactive, required fields, duplicate-code handling, ledger link.
- [ ] Suppliers: create, edit, active/inactive, required fields, duplicate-code handling, ledger link.
- [ ] Cylinder Types: create, edit, capacity/tare validation, active/inactive, duplicate-code handling.
- [ ] LPG Rates: gas/kg and cylinder-package rates, effective date/time, rate history, old/new change log.
- [ ] Users / Roles / Permissions: create/update user, password hashing, role assignment, permission matrix, unauthorized access blocked.
- [ ] Opening Inventory: gas KG, filled cylinders and empty cylinders, date/type uniqueness, non-negative validation.
- [ ] Customer ledger: opening balance + posted credit sales - posted receipts.
- [ ] Supplier ledger: opening balance + posted credit purchases - posted supplier payments.
Phase 2 status: READY FOR TEST

## Phase 3 — POS Sales (DO NOT TEST YET)
- [ ] Filled cylinder sale
- [ ] KG refill
- [ ] Cylinder exchange
- [ ] Empty cylinder intake
- [ ] Empty cylinder sale
- [ ] Cash / cheque / online / credit
- [ ] Credit-limit enforcement
- [ ] Previous OS + Current Credit = New OS
- [ ] Custom-rate detection
- [ ] Atomic inventory + financial posting
- [ ] Receipt / print
- [ ] Sale void / reversal
Phase 3 status: NOT READY

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
