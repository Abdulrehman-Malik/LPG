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

## How to Execute the Functional User Test

Follow the tests in the order below. Do not try to test every screen randomly. Complete the prerequisite step before moving to the next step. These are browser/user tests; do not mark a test PASS unless the result was actually observed locally.

### Step 0 — Login and Dashboard
1. Open **http://localhost:180/LPG2/LPG/public/**.
2. Login with **admin / admin123**.
3. Confirm the Dashboard opens and no PHP/SQL error appears.
4. If this fails, stop and record the complete error and reproduction steps.

### Step 1 — Open Counter Cash

**TEST Result:** PASS

**SQA Review / Improvement Notes:**
- Add a dedicated **Cash History** screen rather than putting a long history table directly on the opening/closing screen.
- History now supports date-range filtering and displays counter, transaction type, direction, amount, reference and notes. Counter/register filtering is prepared for multiple registers.

1. Open **Counter Cash**.
2. Enter Opening Cash: **Rs. 10,000**.
3. Enter Notes: **SQA test session**.
4. Click **Open**.
5. Confirm an open session for register **REG-01** is displayed.
6. Record the displayed Opening, Cash In, Cash Out and Expected values. If the opening float appears to be counted twice, record it as an SQA defect rather than changing the database manually.
TEST Result: PASS 
Suggestion: Show history report in a tab to view all history of cash counter or as seprate report. take the decision as professinal way to keep it user frindly also can be filterd searched for one day or for speccif date or for a month date range or for speifc counter cash history etc.

### Step 2 — Open POS and Post a Normal Cash Sale

**TEST Result:** PASS

**SQA Review / Improvement Notes:**
- Show available filled-cylinder quantity for the selected cylinder type and clearly distinguish it from overall location gas stock.
- Current implementation uses separate location-level `gas_kg` stock plus cylinder-type filled stock. It assumes a filled-cylinder sale consumes the cylinder's configured full capacity.
- Actual gas weight per individual cylinder is **not currently tracked**. A 20 kg cylinder is therefore treated as 20 kg for the filled-cylinder inventory movement. This does not yet support a 20 kg cylinder containing 18 kg and must be implemented as a dedicated inventory enhancement before this requirement can be marked PASS.
- Keep the active Cash Session status at the top-left of POS.
- Use user-friendly transaction names and show a short explanation of the selected transaction type.

1. Open **POS**.
2. Select a Phase 2 test customer.
3. Select **Filled Cylinder**.
4. Select a cylinder type with available opening stock.
5. Enter quantity **1** and use the displayed standard rate.
6. Set Discount to **0**.
7. Select **Cash** and enter the exact sale total.
8. Click **Post Sale**.
9. Confirm a success message and sale number appear.
10. Open the receipt and verify sale number, customer, cylinder, quantity, rate, total and payment.

TEST Result: PASS 
Suggestion:
1.when select filled cylinder it should show avaialble gas in kg for that cylinders.. so that user can see how much gas is avaialble in my stock.. in which cylidner. 
2.How system will manage GS stock. suppose a cylidner of 20kg filled cylidner exists in stock it mean we have 20kg in stock.
while adding stock if we select cylider 20kg and add 2 in qty it mean we have 2 filed cylidenr of type 20kg and gas avaialble 40kg. we can sale 40kg gas from that two cylidner types.  if same is already handling then ok other wise tell me how its implemented currently before changeing anything.
3. we can add cydlidner gas less than its capacity but not more than its capaicty. like we can have filled cylinder of type 20kg with actual filled gas as 18kg. so our gas stock will be 18kg for that specfic cylidner. this is must have feature.
4.move this at top left side, "Cash session: OPEN — REG-01"
5.Naming convention more user friendly and understndable to user for transaction type (like refill kg,empty intak, when user select the option show hint at the top what type of transction user is going to perform rule)
### Step 3 — Verify Inventory After the Sale
1. Open **Inventory**.
2. Find the cylinder type sold in Step 2.
3. Confirm filled-cylinder stock decreased by the expected quantity.
4. Confirm gas stock also changed according to the cylinder capacity.
5. Do not manually correct stock during the test.

### Step 4 — Test KG Refill
1. Return to **POS**.
2. Select **Refill KG**.
3. Enter **5 kg** and use the displayed gas/kg rate.
4. Select Cash and enter the exact total.
5. Post the sale.
6. Confirm the sale and receipt succeed.
7. Check Inventory and confirm gas stock decreased by **5 kg**.

### Step 5 — Test Credit Sale and Customer Ledger
1. Open **POS**.
2. Select the same test customer.
3. Create a small sale.
4. Select **Credit** and post it.
5. Open **Reports → Customer Ledger**.
6. Confirm the credit sale appears as a debit and the customer's outstanding balance increases accordingly.

### Step 6 — Test Customer Receipt
1. Open **Customer Receipts**.
2. Select the customer used in Step 5.
3. Enter **Rs. 500** or an amount not greater than the outstanding balance.
4. Select **Cash**.
5. Save the receipt.
6. Confirm the receipt succeeds and Counter Cash increases.
7. Re-open the customer ledger and confirm the receipt is shown as a credit and the outstanding balance decreases.

### Step 7 — Test Custom Rate
1. Open **POS** and create a sale.
2. Select a line with a displayed standard rate.
3. Change the rate manually to a different amount, for example standard **250** to applied **260**.
4. Confirm the line is visibly identified as a custom rate.
5. Post the sale.
6. Open the receipt and confirm the applied rate is retained.

### Step 8 — Test Cylinder Exchange
1. Open **POS**.
2. Select **Cylinder Exchange**.
3. Select a cylinder type and enter quantity **1**.
4. Post the transaction using the appropriate payment information.
5. Confirm the transaction succeeds.
6. Check Inventory for the expected filled-cylinder/gas OUT and empty-cylinder IN effects.

### Step 9 — Test Empty Cylinder Intake
1. Open **POS**.
2. Select **Empty Intake**.
3. Select a cylinder type and quantity **1**.
4. Post the transaction.
5. Confirm empty-cylinder stock increases by **1** and is not increased twice.

### Step 10 — Test Empty Cylinder Sale
1. Open **POS**.
2. Select **Empty Sale**.
3. Select a cylinder type with available empty stock.
4. Enter quantity **1** and a valid rate.
5. Post the transaction.
6. Confirm empty-cylinder stock decreases by **1**.
7. If the requested quantity is greater than available stock, confirm the application rejects the transaction without partial posting.

### Step 11 — Test Purchase
1. Open **Purchases**.
2. Select a Phase 2 test supplier.
3. Add a small purchase line, for example **20 kg gas** or **1 filled cylinder**.
4. Enter a valid rate and leave discount at **0**.
5. Select **Cash** and enter the exact total.
6. Post the purchase.
7. Confirm the purchase succeeds, inventory increases, and Counter Cash decreases by the cash payment.

### Step 12 — Test Inventory Adjustment
1. Open **Inventory**.
2. Create an **IN** adjustment for quantity **1** with reason **SQA test adjustment**.
3. Confirm stock increases by 1.
4. Attempt an **OUT** adjustment greater than available stock.
5. Confirm the application rejects the adjustment and does not create a negative balance or partial movement.

### Step 13 — Test Manual Cash IN and OUT
1. Open **Counter Cash** while the session is open.
2. Post Cash IN of **Rs. 100** with reason **SQA test**.
3. Confirm Cash In/Expected values change appropriately.
4. Post Cash OUT of **Rs. 50** with reason **SQA test**.
5. Confirm Cash Out/Expected values change appropriately.

### Step 14 — Test Expense
1. Open **Expenses**.
2. Select an expense category.
3. Enter **Rs. 100**.
4. Select **Cash**.
5. Enter description **SQA test expense**.
6. Save.
7. Confirm the expense appears in recent expenses and Counter Cash reflects the cash OUT.

### Step 15 — Test Supplier Payment
1. Open **Supplier Payments**.
2. Select the test supplier.
3. Enter a small payment.
4. Select **Cash**.
5. Save.
6. Confirm the supplier payment succeeds and Counter Cash reflects the cash OUT.

### Step 16 — Test Reports and Audit
1. Open **Reports** and verify today's sales, purchases, payment-mode summary and credit totals against the transactions you posted.
2. Open **Customer Ledger** and reconcile the test customer's opening balance, credit sales and receipts.
3. Open **Inventory** and reconcile gas, filled-cylinder and empty-cylinder stock to the transactions performed.
4. Open **Audit** and confirm operational create actions are recorded with user, location, entity and timestamp.

### Step 17 — Close Counter Cash
1. Return to **Counter Cash**.
2. Review the displayed Expected Cash.
3. Count the actual cash physically present.
4. Enter the counted amount.
5. Click **Close Session**.
6. Confirm the session closes and the displayed difference equals **Counted Cash - Expected Cash**.
7. Record the counted cash, expected cash and difference in the Evidence / Test Data section for the cash test.

### What to Send When a Test Fails
For every failure, record:
- Screen/function name.
- Exact values entered.
- Button/action performed.
- Complete PHP/SQL/browser error text, if any.
- Reproduction steps.
- Screenshot, where possible.

Example: **POS → Filled Cylinder → Customer Test Customer → C11_8 → Qty 1 → Rate 250 → Cash 250 → Post Sale → error: APPPATH\\Services\\CashService.php line 32**.

## SQA Test Results — Current Retest

| Test | Result | Notes |
|---|---|---|
| Login / Dashboard | PASS | User retest completed previously. |
| Counter Cash — open session | PASS | Session opened successfully; expected-cash double-count issue corrected in code. |
| POS — normal filled-cylinder cash sale | PASS | Sale posted and receipt flow passed. |
| POS — actual per-cylinder fill weight | NOT IMPLEMENTED | Current model does not track gas weight per individual cylinder. |
| Cash History | READY FOR RETEST | New screen added after SQA request; verify locally. |
| POS stock visibility / transaction hints | READY FOR RETEST | UI enhancement added; verify locally. |

**Important:** A PASS above records only the functions the user explicitly reported as passed. New code changes after that test cycle are marked READY FOR RETEST, not PASS.

## Phase 3 — POS Sales (READY FOR USER TEST)
- [x] POS screen: customer selection, line grid, totals, payments, validation.
  - SQA Comments / Improvement Notes: User retest PASS. Future improvement: verify mixed-line behavior, client/server total agreement, empty-state validation, and that walk-in mode prevents non-cash payment selection. Confirm displayed OS is clearly distinguished from the resulting New OS.
  - Evidence / Test Data:
- [x] Filled cylinder sale: capacity-based gas/cylinder inventory OUT and applied package rate.
  - SQA Comments / Improvement Notes: User retest PASS for the normal cash filled-cylinder sale and receipt. New SQA requirements recorded: show selected cylinder stock, clarify gas-stock handling, support actual per-cylinder fill weight (including partial fills up to capacity), and keep transaction terminology/hints user-friendly. Current implementation does not track actual gas weight per individual cylinder; gas_kg is a location-level balance and filled-cylinder quantity is tracked separately.
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
- [x] Cash register/session: open, summary and close a counter session.
  - SQA Comments / Improvement Notes: User retest PASS for opening the session. Expected-cash calculation was reviewed and corrected so the opening float is not double-counted. Cash History was added with date-range filtering and transaction details.
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
