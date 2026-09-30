# Perfect LPG POS & ERP — Requirements

## Purpose
Production-ready POS + ERP for LPG retail operations.

## Stack
- PHP 8.x / CodeIgniter 4
- MySQL 8.0+
- Bootstrap 5.3
- HTML5/CSS3
- JavaScript/jQuery/AJAX
- DataTables
- InnoDB transactions and foreign keys

## Modules
1. Authentication and roles
2. Item/cylinder/rate setup
3. Customer and supplier directory
4. LPG POS
5. Purchases
6. Inventory
7. Customer/supplier ledgers
8. Counter Cash
9. Expenses
10. Reports
11. Audit logging

## Sales Use Cases
1. Filled cylinder sale
2. Customer-owned-cylinder refill by KG
3. Cylinder exchange
4. Empty cylinder intake
5. Empty cylinder sale
6. Customer cash receipt against outstanding

## Critical Rules
- Rate history stores effective rate, timestamp, old/new values and user.
- Every sale line stores the actual applied rate.
- Custom-rate lines are explicitly flagged.
- Walk-in customers are cash-only and cannot return cylinders or create credit.
- Credit limit is checked before posting a credit sale.
- Sales screen displays Previous OS + Current Credit = New OS.
- Payment modes: cash, cheque, online, credit.
- Counter Cash includes actual cash only.
- Posted transactions are reversed/voided rather than hard-deleted.
- Financial amounts use DECIMAL.
- Inventory and cash posting is atomic inside a DB transaction.

## Reports
- Customer ledger
- Supplier ledger
- Daily transactions
- Custom-rate sales
- Daily stock
- Counter Cash reconciliation
- Daily expenses
- Outstanding balances
- Payment-mode analysis

## SQA-Confirmed Inventory Control & Wastage Requirements
- Stock validation must be configurable at shop/location level, with optional cylinder-type overrides.
- When stock validation is ON, gas sales must not exceed available gas stock.
- When stock validation is OFF, a gas sale that exceeds available stock requires an explicit user confirmation and the override must be traceable.
- The system must provide a separate gas-wastage report with date-range and cylinder-type filtering.
- A filled physical cylinder can be converted to empty stock through an authorized inventory wastage/leakage adjustment. The gas lost is deducted from gas inventory and recorded with physical cylinder, user, date and reason.
- Wastage policy must support percentage or fixed-KG configuration, at shop level with cylinder-type override capability.
- Residual gas/wastage from a cylinder that is made empty must never exceed the cylinder's current actual gas weight.
- Inventory control and wastage actions must be atomic and auditable.

## Open Business Decisions
1. Can one sale contain multiple scenarios?
2. Are cylinder serial numbers required?
3. Can managers override credit limits?
4. Are cheque payments excluded from Counter Cash?
5. Single shop or multi-location?
6. Is GST/tax required?
7. Are discounts allowed?
8. Are sale returns/refunds required?
9. 80mm thermal receipt printer?
10. Offline POS required?
11. What exactly is included in cylinder package price?
12. Are multiple cash registers required?

## Completion Rule
A requirement is marked Done only after implementation, validation, transaction testing, UI testing, relevant reporting and tracker update.

## Master Data UI Quality Requirements
- Edit actions for master-data records must open the correct edit form and preload the selected record's current values.
- Saving an edit must persist the changed values and retain unchanged values.
- Editing a user must retain the existing password when the password field is left blank.
- Automated or manual regression testing should verify create/edit flows after UI changes to prevent modal or form JavaScript regressions.


## SQA-Confirmed POS / Inventory Requirements
- Filled-cylinder POS selection must show the currently available filled-cylinder quantity for the selected cylinder type and a clear gas-stock figure without misleading the user about whether gas is allocated to individual cylinders.
- The current inventory model treats gas KG as a location-level gas balance and filled cylinders as a cylinder-type quantity. It does **not** currently track the actual gas weight contained in each individual filled cylinder.
- The system must support partially filled cylinders: a cylinder type defines maximum capacity, while each filled cylinder may contain an actual gas weight from 0 up to that capacity. Inventory must prevent actual gas weight above cylinder capacity and account for actual weight when selling, refilling and exchanging cylinders.
- POS transaction types must use user-friendly names and display a short operational hint explaining what the selected transaction will do.
- The active Counter Cash session status should be prominent at the top-left of the POS screen.
- Counter Cash must provide a searchable/filterable history view by date range and counter/register, with transaction type, direction, amount, reference and notes visible.
- Counter Cash expected cash must not double-count the opening float: when an opening_float cash transaction exists, expected cash is calculated from cash-in minus cash-out for the session.
