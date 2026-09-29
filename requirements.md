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