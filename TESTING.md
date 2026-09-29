# Perfect LPG — TESTING.md

Use this file as the test gate between development phases.

## Application Starting URL

**Starting URL:** http://localhost:180/

The application base URL is configured in .env as:

    app.baseURL = 'http://localhost:180/'

After starting the application, open the URL above in your browser. The login page is available at:

    http://localhost:180/login

## How to use
1. Pull the latest main branch.
2. Install PHP 8.2+ with intl, mbstring and MySQLi enabled.
3. Run 'composer install' from the project root. This installs CodeIgniter into 'vendor/'; do not commit 'vendor/'.
4. Copy 'env' to '.env' if required and set your local MySQL credentials/database settings. The repository configuration uses 'perfect_lpg' as the database.
5. Execute 'database/schema.sql' once on a fresh MySQL server.
6. Start the application using the configured local web server and open **http://localhost:180/**.
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
- [ ] roles, permissions and role_permissions contain seed data.
Result: [ ] PASS / [ ] FAIL
Database error / notes:
> Write here.

## Phase 1 — Application Configuration
- [x] .env database connection points to perfect_lpg.
- [x] Application starts without PHP fatal error.
- [x] /login opens correctly.
- [ ] Bootstrap/CSS/JS load correctly.
- [x] Application starting URL is http://localhost:180/.
Result: [ ] PASS / [ ] FAIL
Error / notes:
> Write here.

## Phase 1 — Authentication
- [ ] Username: admin
- [ ] Password: admin123
- [ ] Login succeeds.
- [ ] Redirect to /dashboard works.
- [ ] Top-right user shows System Administrator.
- [ ] Role badge shows ADMIN.
- [ ] Invalid password is rejected.
- [ ] Logout returns to /login.
- [ ] Direct /dashboard while logged out redirects to /login.
Result: [ ] PASS / [ ] FAIL
Error / reproduction steps:
> Write here.

## Phase 1 — Dashboard
- [ ] Dashboard opens after login.
- [ ] Today's Sales shows Rs. 0.00 on fresh database.
- [ ] Cylinder Stock shows 0 on fresh database.
- [ ] Total Gas Stock shows 0.00 kg on fresh database.
- [ ] No SQL/PHP errors appear in page or server log.
Result: [ ] PASS / [ ] FAIL
Error / notes:
> Write here.

## Phase 2 — Master Data (DO NOT TEST YET)
- [ ] Customers / Parties
- [ ] Suppliers
- [ ] Cylinder Types
- [ ] LPG Rates
- [ ] Users / Roles
- [ ] Opening Inventory
Phase 2 status: NOT READY

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
- Local URL: http://localhost:180/

## Tester Notes
> Add screenshots, SQL errors, PHP errors or unexpected business behavior here.

## Developer Rule
A module is not complete until its tests are PASS. FAIL results become the next fix cycle; development does not silently skip failed tests.
