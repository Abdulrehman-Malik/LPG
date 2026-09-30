# Perfect LPG — SQA Functional Test Plan

## Purpose

This document is the execution checklist for SQA. Execute the application end-to-end in the order below, record the actual observed result for every use case, and update **Status** and **Comments**.

**Important:** Do not mark a case PASS unless it was actually executed and observed in the configured test environment.

### Status values

- **NOT EXECUTED** — not yet tested
- **PASS** — actual result matches expected result
- **FAIL** — actual result differs from expected result
- **BLOCKED** — cannot execute because a prerequisite/environment issue prevents testing
- **N/A** — formally not applicable

### Test environment

- Application: Perfect LPG POS & ERP
- URL: `http://localhost:180/LPG2/LPG/public/`
- Login: `admin / admin123` on a fresh development database
- Database: MySQL 8+
- PHP: 8.2+
- Browser: Chrome/Edge current stable
- Test database: use a disposable SQA database or a reset copy of `perfect_lpg`
- Use test names/codes prefixed with `SQA-` so test transactions are identifiable.

### Evidence rule

For financial/inventory cases, capture enough evidence to reconcile:
1. Before transaction stock/balance.
2. Input values.
3. Document/transaction number.
4. After transaction stock/balance.
5. Relevant receipt/report/audit entry.

---

## A. Installation, Security and Authentication

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| A01 | Fresh database installation | Run `database/schema.sql` on fresh MySQL | Database and all required tables are created without SQL errors | | | NOT EXECUTED |
| A02 | Seed verification | Inspect users, roles, permissions, register, cylinder types | Admin, roles/permissions, REG-01 and six standard cylinder types exist | | | NOT EXECUTED |
| A03 | Environment configuration | Copy `.env.example` to local `.env`; configure DB | Application connects to configured DB; local `.env` is not required in Git | | | NOT EXECUTED |
| A04 | Login success | admin / admin123 | User reaches Dashboard as System Administrator / ADMIN | | | NOT EXECUTED |
| A05 | Invalid login | admin / wrong password | Login rejected; no session created | | | NOT EXECUTED |
| A06 | Authentication protection | Open `/dashboard` while logged out | Redirect to login | | | NOT EXECUTED |
| A07 | Logout | Login then Logout | Session is destroyed and login page is shown | | | NOT EXECUTED |
| A08 | Permission enforcement | Use a restricted role/user and open an unauthorized module | HTTP 403 / access denied; no data mutation | | | NOT EXECUTED |
| A09 | CSRF protection | Submit a POST form without valid CSRF token | Request is rejected | | | NOT EXECUTED |

## B. Dashboard and Master Data

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| B01 | Dashboard fresh state | Open Dashboard on fresh DB | Sales, cylinder stock and gas stock show zero values without errors | | | NOT EXECUTED |
| B02 | Customer create | Create SQA customer with valid fields | Customer is created and appears in directory | | | NOT EXECUTED |
| B03 | Customer duplicate code | Reuse an existing customer code | Save is rejected with user-friendly error | | | NOT EXECUTED |
| B04 | Customer edit | Edit name/phone/credit limit | Correct record is loaded and changes persist | | | NOT EXECUTED |
| B05 | Customer inactive | Mark customer inactive | Customer cannot be selected for a new active transaction | | | NOT EXECUTED |
| B06 | Supplier create/edit | Create then edit SQA supplier | Record saves and edited values persist | | | NOT EXECUTED |
| B07 | Supplier duplicate code | Reuse supplier code | Save rejected without corrupting existing supplier | | | NOT EXECUTED |
| B08 | Cylinder type create/edit | Create 20 kg SQA type; edit capacity/tare | Valid values save; duplicate code/capacity and invalid capacity are rejected | | | NOT EXECUTED |
| B09 | Cylinder capacity validation | Try capacity 0 or negative | Save rejected | | | NOT EXECUTED |
| B10 | LPG gas rate | Create/update gas/kg rate with effective date | Current/effective rate is used by POS and history retains old/new values | | | NOT EXECUTED |
| B11 | Cylinder package rate | Set package rate for SQA cylinder | POS resolves correct package rate | | | NOT EXECUTED |
| B12 | User/role create/edit | Create SQA user, assign role, edit without password | Role changes persist and blank password does not overwrite existing password | | | NOT EXECUTED |

## C. Opening Inventory and Physical Cylinders

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| C01 | Gas opening balance | Gas KG = 100.00 | Inventory gas stock becomes 100.00 kg | | | NOT EXECUTED |
| C02 | Filled-cylinder opening | 2 × 20 kg, actual gas 20.000 kg | 2 physical filled units created; gas stock increases 40.000 kg | | | NOT EXECUTED |
| C03 | Partial filled-cylinder opening | 2 × 20 kg, actual gas 18.000 kg | 2 physical units each contain 18.000 kg; gas stock contribution = 36.000 kg | | | NOT EXECUTED |
| C04 | Over-capacity cylinder | Actual gas 20.001 kg for 20 kg cylinder | Save rejected; no units/movement created | | | NOT EXECUTED |
| C05 | Zero-gas filled cylinder | Actual gas 0 for filled cylinder | Save rejected | | | NOT EXECUTED |
| C06 | Empty-cylinder opening | 3 empty SQA cylinders | 3 physical empty units and empty stock +3 | | | NOT EXECUTED |
| C07 | Opening update | Change actual gas on an existing opening | Active opening units are reconciled; historical posted transactions are not deleted | | | NOT EXECUTED |
| C08 | Opening quantity reduction below active units | Existing active units > new quantity | Save rejected and existing stock remains unchanged | | | NOT EXECUTED |

## D. Counter Cash

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| D01 | Open cash session | Opening cash Rs. 10,000 | REG-01 opens; expected cash is Rs. 10,000 | | | NOT EXECUTED |
| D02 | Prevent second open session | Attempt opening another session on REG-01 | Second session rejected | | | NOT EXECUTED |
| D03 | Cash IN | Rs. 100 manual cash IN | Cash In and Expected increase by Rs. 100 | | | NOT EXECUTED |
| D04 | Cash OUT | Rs. 50 manual cash OUT | Cash Out increases by Rs. 50; Expected decreases by Rs. 50 | | | NOT EXECUTED |
| D05 | Cash history filter | From/to date + REG-01 | Matching transactions shown with date, counter, type, direction, amount, reference and notes | | | NOT EXECUTED |
| D06 | Opening float not double-counted | Open with Rs. 10,000 and inspect summary | Expected cash is Rs. 10,000, not Rs. 20,000 | | | NOT EXECUTED |
| D07 | Cash close | Counted cash = displayed expected | Session closes; difference = 0.00 | | | NOT EXECUTED |
| D08 | Cash close variance | Counted cash different from expected | Session closes and difference = counted - expected | | | NOT EXECUTED |
| D09 | Closed session protection | Try cash movement after close | Transaction rejected | | | NOT EXECUTED |

## E. POS — Filled Cylinder Sale

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| E01 | POS load | Open POS with cash session | Cash session is prominent; cylinder selector and transaction hints load without JS/PHP errors | | | NOT EXECUTED |
| E02 | Filled stock visibility | Select SQA 20 kg cylinder | Selector shows available filled quantity and physical unit/gas information | | | NOT EXECUTED |
| E03 | Partial-filled sale | Have 18 kg physical cylinder; sell quantity 1 | Sale deducts 18.000 kg and one filled physical unit | | | NOT EXECUTED |
| E04 | Multiple filled units | 2 units × 18 kg; sell one | One unit remains at 18 kg; gas decreases by 18 kg | | | NOT EXECUTED |
| E05 | Filled sale insufficient physical units | Request more cylinders than available | Sale rejected before posting; no partial movement | | | NOT EXECUTED |
| E06 | Standard package rate | Select filled cylinder with current rate | Standard rate auto-populates and is used | | | NOT EXECUTED |
| E07 | Custom rate | Change rate from standard to custom value | Line is flagged as custom; applied rate is retained in sale/receipt/report | | | NOT EXECUTED |
| E08 | Cash payment | Exact cash total | Sale posts and Counter Cash increases by cash amount | | | NOT EXECUTED |
| E09 | Cheque payment | Exact cheque total + reference | Sale posts; Counter Cash does not increase | | | NOT EXECUTED |
| E10 | Online payment | Exact online total + reference | Sale posts; Counter Cash does not increase | | | NOT EXECUTED |
| E11 | Payment mismatch | Payment total differs from sale total | Posting rejected | | | NOT EXECUTED |
| E12 | Discount | Valid discount below subtotal | Total decreases correctly; payment must equal discounted total | | | NOT EXECUTED |
| E13 | Walk-in restrictions | Walk-in + credit/cheque/online | Rejected; walk-in is cash only | | | NOT EXECUTED |
| E14 | Receipt | Open posted sale receipt | Sale number, customer, items, rates, totals and payments match transaction | | | NOT EXECUTED |

## F. POS — KG Refill, Exchange and Empty Cylinder Transactions

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| F01 | KG refill | Refill customer cylinder by 5.00 kg | 5.000 kg gas OUT; no filled/empty shell movement | | | NOT EXECUTED |
| F02 | KG refill insufficient stock, validation ON | Request more gas than available | Sale blocked with clear stock error | | | NOT EXECUTED |
| F03 | KG refill validation OFF | Turn validation OFF; request over available; confirm | Confirmation required; after confirmation sale posts and override is traceable | | | NOT EXECUTED |
| F04 | Cylinder exchange | Sell 1 filled + receive 1 empty | Filled unit OUT, its actual gas OUT, empty physical unit IN | | | NOT EXECUTED |
| F05 | Empty intake | Receive 1 empty cylinder | Empty stock +1 and one physical empty unit created | | | NOT EXECUTED |
| F06 | Empty sale | Sell 1 available empty cylinder | Empty unit becomes sold; empty stock -1 | | | NOT EXECUTED |
| F07 | Empty sale insufficient stock | Sell more empty cylinders than available | Rejected without partial posting | | | NOT EXECUTED |
| F08 | Mixed POS lines | Combine supported transaction types in one sale | System posts according to defined mixed transaction behavior without duplicate movements | | | NOT EXECUTED |

## G. Inventory Controls and Wastage

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| G01 | Default stock validation ON | Open Inventory Controls | Default policy shows ON | | | NOT EXECUTED |
| G02 | Cylinder-type validation override | Set SQA cylinder override OFF | POS uses the override for that cylinder type | | | NOT EXECUTED |
| G03 | Percentage wastage policy | Set 10% on 20 kg cylinder | Maximum permitted incident wastage is calculated from current gas | | | NOT EXECUTED |
| G04 | Fixed KG wastage policy | Set 1.000 kg fixed | Wastage above 1.000 kg is rejected | | | NOT EXECUTED |
| G05 | Partial wastage | 18 kg cylinder; record 1 kg wastage | Gas stock -1 kg; cylinder remains filled at 17 kg; wastage log records 1 kg | | | NOT EXECUTED |
| G06 | Make cylinder empty | 1 kg cylinder; record 1 kg wastage | Gas stock -1 kg; filled unit OUT; empty unit IN; cylinder status empty | | | NOT EXECUTED |
| G07 | Wastage greater than current gas | Current 5 kg; record 5.001 kg | Rejected; no stock change | | | NOT EXECUTED |
| G08 | Wastage above configured allowance | Configure 1 kg max; record 1.001 kg | Rejected; no partial movement | | | NOT EXECUTED |
| G09 | Wastage history | Filter by date/type | Correct physical unit, cylinder type, KG, reason, user and timestamp are shown | | | NOT EXECUTED |
| G10 | Atomic wastage failure | Force an invalid posting | No partial inventory movement or cylinder status change remains | | | NOT EXECUTED |
| G11 | Physical inventory adjustment IN | Filled cylinder IN with actual gas | Physical unit created and gas movement recorded | | | NOT EXECUTED |
| G12 | Physical inventory adjustment OUT | Filled cylinder OUT | Physical unit sold/removed and its actual gas deducted | | | NOT EXECUTED |
| G13 | Adjustment over capacity | Filled-cylinder IN above capacity | Rejected | | | NOT EXECUTED |
| G14 | Adjustment insufficient stock | Cylinder OUT above available | Rejected without partial movement | | | NOT EXECUTED |

## H. Purchases and Supplier Ledger

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| H01 | Gas purchase | Buy 20 kg gas at valid rate | Gas stock +20 kg; purchase and payment recorded | | | NOT EXECUTED |
| H02 | Filled-cylinder purchase | Buy 2 × 20 kg at 18 kg actual each | Two physical filled units created; gas stock +36 kg | | | NOT EXECUTED |
| H03 | Purchase over capacity | Actual gas above capacity | Purchase rejected | | | NOT EXECUTED |
| H04 | Empty-cylinder purchase | Buy empty cylinder | Empty physical unit and stock increase | | | NOT EXECUTED |
| H05 | Cash purchase | Exact cash payment | Purchase posts; Counter Cash decreases by cash amount | | | NOT EXECUTED |
| H06 | Credit purchase | Credit payment within supplier limit | Supplier outstanding increases by credit amount | | | NOT EXECUTED |
| H07 | Supplier credit limit | Existing outstanding + new credit > limit | Purchase rejected | | | NOT EXECUTED |
| H08 | Supplier payment | Pay amount <= outstanding | Supplier balance decreases; cash OUT for cash payment | | | NOT EXECUTED |
| H09 | Supplier overpayment | Payment > outstanding | Payment rejected | | | NOT EXECUTED |
| H10 | Supplier ledger | Open supplier ledger after transactions | Opening + credit purchases - posted payments reconciles | | | NOT EXECUTED |

## I. Customer Credit and Receipts

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| I01 | Credit sale within limit | Customer with sufficient available credit | Sale posts; outstanding increases by credit portion | | | NOT EXECUTED |
| I02 | Credit limit exceeded | Credit causing new balance above limit | Sale rejected | | | NOT EXECUTED |
| I03 | Customer receipt | Receipt <= outstanding | Customer balance decreases | | | NOT EXECUTED |
| I04 | Receipt over outstanding | Receipt > outstanding | Receipt rejected | | | NOT EXECUTED |
| I05 | Cash customer receipt | Valid cash receipt with open session | Customer balance decreases and Counter Cash increases | | | NOT EXECUTED |
| I06 | Cheque/online receipt | Valid non-cash receipt | Customer balance decreases; Counter Cash unchanged | | | NOT EXECUTED |
| I07 | Customer ledger | Open ledger after sale and receipt | Opening + credit sales - receipts reconciles | | | NOT EXECUTED |

## J. Expenses

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| J01 | Cash expense | Rs. 100 cash expense | Expense posts and Counter Cash decreases Rs. 100 | | | NOT EXECUTED |
| J02 | Non-cash expense | Cheque/online expense | Expense posts; Counter Cash unchanged | | | NOT EXECUTED |
| J03 | Invalid expense amount | Zero/negative | Rejected | | | NOT EXECUTED |
| J04 | Expense category | Inactive/invalid category | Save rejected | | | NOT EXECUTED |

## K. Voids, Reversals and Integrity

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| K01 | Sale void | Void a posted cash filled-cylinder sale with reason | Sale becomes voided; inventory/cash movements are reversed | | | NOT EXECUTED |
| K02 | Void reason required | Submit blank reason | Void rejected | | | NOT EXECUTED |
| K03 | Double void | Void same sale twice | Second void rejected; no duplicate reversal | | | NOT EXECUTED |
| K04 | Void credit sale | Void posted credit sale | Inventory reverses and customer outstanding returns to prior balance | | | NOT EXECUTED |
| K05 | Void exchange | Void exchange | Filled/empty physical states and gas inventory are restored correctly | | | NOT EXECUTED |
| K06 | Failed transaction atomicity | Cause a validation/cash failure during posting | No sale/purchase, partial inventory movement or partial cash movement remains | | | NOT EXECUTED |

## L. Reports and Audit

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| L01 | Daily sales report | Post known sales; open Reports | Sales count/total/credit match posted sales | | | NOT EXECUTED |
| L02 | Payment-mode report | Post cash/cheque/online/credit | Mode totals reconcile with sale payments | | | NOT EXECUTED |
| L03 | Inventory report | Reconcile known movements | Gas, filled and empty stock match movement ledger and physical units | | | NOT EXECUTED |
| L04 | Wastage report | Record known wastage | Date/type/unit/KG/reason/user filters return correct rows | | | NOT EXECUTED |
| L05 | Customer ledger report | Known credit sale + receipt | Ledger balances reconcile | | | NOT EXECUTED |
| L06 | Supplier ledger report | Known credit purchase + payment | Ledger balances reconcile | | | NOT EXECUTED |
| L07 | Audit log | Create/edit/post/void operational transactions | Audit contains user, location, action, entity and timestamp | | | NOT EXECUTED |
| L08 | Cross-module reconciliation | Compare POS, inventory, cash, customer/supplier ledgers | No unexplained difference exists | | | NOT EXECUTED |

## M. UI, Validation and Regression

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| M01 | POS numeric precision | Enter rates with decimal values | Rate fields accept/display 2 decimal places | | | NOT EXECUTED |
| M02 | Quantity precision | Enter gas/cylinder quantities | Gas quantities support 3 decimals; cylinder quantities remain whole where required | | | NOT EXECUTED |
| M03 | Browser refresh after validation error | Submit invalid transaction then refresh/back | No duplicate posting occurs | | | NOT EXECUTED |
| M04 | Empty line removal | Add/remove POS lines | Totals and hidden JSON remain correct | | | NOT EXECUTED |
| M05 | Multiple payments | Split one transaction across allowed modes | Payment total must equal transaction total and each payment is retained | | | NOT EXECUTED |
| M06 | Responsive POS | Test desktop and narrow/mobile viewport | Controls remain usable; no critical overlap or inaccessible action | | | NOT EXECUTED |
| M07 | JavaScript regression | Load POS, add lines, change transaction type/rate/qty | No console syntax/runtime errors; totals and selectors update correctly | | | NOT EXECUTED |
| M08 | Error handling | Trigger common validation failures | User sees readable error and no partial transaction | | | NOT EXECUTED |

## N. End-to-End Business Flow

| ID | Use case | Input / Steps | Expected output / result | Actual result | Comments | Status |
|---|---|---|---|---|---|---|
| N01 | Complete retail day | Open cash → opening stock → purchase → POS sale → receipt → expense → supplier payment → reports → close cash | All modules reconcile from start to end of day | | | NOT EXECUTED |
| N02 | Partial-fill lifecycle | Create 18 kg physical cylinder → sell → purchase/refill scenario → partial wastage → empty conversion | Physical unit status and gas inventory remain consistent at every stage | | | NOT EXECUTED |
| N03 | Credit lifecycle | Opening customer → credit sale → receipt → ledger reconciliation | Customer balance remains mathematically consistent | | | NOT EXECUTED |
| N04 | Supplier lifecycle | Opening supplier → credit purchase → supplier payment → ledger reconciliation | Supplier balance remains mathematically consistent | | | NOT EXECUTED |
| N05 | Reversal lifecycle | Post sale → verify stock/cash → void → verify all reversals | Final balances return to pre-sale state, except audit history | | | NOT EXECUTED |

---

## SQA Sign-off

**Tester:** ____________________  
**Test date:** ____________________  
**Build / commit:** ____________________  
**Browser:** ____________________  
**PHP version:** ____________________  
**MySQL version:** ____________________

### Defect summary

| Severity | Count | Notes |
|---|---:|---|
| Critical |  | |
| High |  | |
| Medium |  | |
| Low |  | |

### Final SQA result

- [ ] PASS — all required cases passed
- [ ] FAIL — one or more required cases failed
- [ ] BLOCKED — environment/prerequisite prevented completion

**SQA Comments:**  
> Record final observations, known limitations, and recommended follow-up here.
