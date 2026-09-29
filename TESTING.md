# Perfect LPG — TESTING.md

Use this file as the test gate between development phases.

## How to use
1. Pull the latest main branch.
2. Back up any local database you care about.
3. For the first installation, execute database/schema.sql once in MySQL.
4. Configure CodeIgniter .env for database perfect_lpg.
5. Test only items marked READY FOR TEST.
6. Change [ ] to [x] for PASS or [!] for FAIL.
7. For every FAIL, add the exact error/message and reproduction steps.
8. Commit/push your updated TESTING.md to GitHub.
9. I will read the results, fix failures, and only then start the next phase.

Status: [ ] Not tested · [x] Pass · [!] Fail · [-] N/A

## Phase 1 — Database Installation
- [ ] Execute database/schema.sql successfully on a fresh MySQL server.
- [ ] Database perfect_lpg is created.
- [ ] All required tables are created without SQL errors.
- [ ] users contains seeded admin account.
- [ ] cylinder_types contains 6 standard cylinder types.
- [ ] cash_registers contains REG-01.
- [ ] roles, permissions and role_permissions contain seed data.
Result: [ ] PASS / [ ] FAIL
Database error / notes:
> Write here.

## Phase 1 — Application Configuration
- [ ] .env database connection points to perfect_lpg.
- [ ] Application starts without PHP fatal error.
- [ ] /login opens correctly.
- [ ] Bootstrap/CSS/JS load correctly.
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
- Local URL:

## Tester Notes
> Add screenshots, SQL errors, PHP errors or unexpected business behavior here.

## Developer Rule
A module is not complete until its tests are PASS. FAIL results become the next fix cycle; development does not silently skip failed tests.
