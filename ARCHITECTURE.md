# Perfect LPG — Core Business Logic Architecture

## Layering

Browser / Mobile Browser
-> Bootstrap + jQuery
-> AJAX/API
-> CodeIgniter Controllers
-> Application Services
-> Models/Repositories/Validators
-> MySQL

Controllers should not contain financial posting logic.

## Core Services

### SalesService
- Validate sale scenario and lines.
- Resolve current rates.
- Calculate KG and totals.
- Detect custom rates.
- Validate walk-in restrictions.
- Lock/read customer balance for credit validation.
- Post sale, payments, inventory and cash atomically.
- Write audit log.

### InventoryService
Source of truth:
- opening balances
- immutable inventory movements

Track:
- gas KG
- filled cylinders by type
- empty cylinders by type

Use reversal movements instead of deleting posted movements.

### CashService
Source of truth for Counter Cash:
- opening float
- cash sales
- customer cash receipts
- cash purchases
- supplier cash payments
- cash expenses
- manual cash in/out
- handover
- closing reconciliation

Cheque and online payments create no Counter Cash movement.

### RateService
- Current gas/KG rate.
- Current cylinder package rate.
- Effective-dated rate history.
- Old/new change log.
- Rate override authorization.

### PurchaseService
- Supplier purchase.
- Purchase lines.
- Purchase payments.
- Inventory IN.
- Supplier balance.

### ReportService
Reports are derived from posted transaction tables and ledgers, not mutable dashboard totals.

## Credit Rule

previous_balance = customer outstanding before sale
current_credit = credit part of current sale
new_balance = previous_balance + current_credit

Reject when:
new_balance > credit_limit

unless a manager-approval feature is explicitly added.

## Walk-in Rule

customer_id = NULL means:
- CASH only
- no credit
- no cheque
- no online
- no cylinder return/intake
- no customer outstanding

## Six Scenarios

| Scenario | Gas | Filled cylinder | Empty cylinder |
|---|---:|---:|---:|
| Filled cylinder sale | OUT by actual physical-unit gas weight | OUT | Optional IN |
| Refill service | OUT by KG | No change | No shell change |
| Cylinder exchange | OUT by actual physical-unit gas weight | OUT | IN |
| Empty intake | No change | No change | IN |
| Empty sale | No gas | No change | OUT |
| Customer receipt | No change | No change | No change |

## POS UX

Desktop:
- Customer/transaction panel
- Line-entry grid
- Totals/payment panel
- Large Save/Print action

Mobile:
- Stacked cards
- Searchable cylinder selector
- Large numeric fields
- Sticky total/payment footer
- Collapsible balance panel

Credit block:
Previous OS Balance
+ Current Credit
= New OS Balance
Credit Limit
Available Credit

Custom-rate lines must be visibly highlighted.

## API Contract

Authentication:
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me

Sales:
GET  /api/sales/rates/current
GET  /api/sales/customer/{id}/balance
POST /api/sales/quote
POST /api/sales
GET  /api/sales
GET  /api/sales/{id}
POST /api/sales/{id}/void

Customer receipts:
POST /api/customers/{id}/receipts
GET  /api/customers/{id}/ledger

Purchases:
POST /api/purchases
GET  /api/purchases
GET  /api/purchases/{id}
POST /api/purchases/{id}/void

Inventory:
GET  /api/inventory/stock
GET  /api/inventory/movements
POST /api/inventory/adjustments

Cash:
POST /api/cash/sessions/open
GET  /api/cash/sessions/current
POST /api/cash/transactions
POST /api/cash/sessions/{id}/handover
POST /api/cash/sessions/{id}/close
GET  /api/cash/reconciliation

Reports:
GET /api/reports/daily-transactions
GET /api/reports/stock
GET /api/reports/customer-ledger
GET /api/reports/supplier-ledger
GET /api/reports/counter-cash
GET /api/reports/rate-overrides

## Physical Cylinder Inventory

Each filled physical cylinder is represented by a `cylinder_units` record with a unique unit code, cylinder type, status and actual gas weight. Actual gas may be below rated capacity but cannot exceed capacity. Filled-cylinder sales, purchases, stock adjustments and wastage update both the physical unit and the immutable inventory movement ledger.

## Wastage Behavior

Partial wastage deducts only the recorded gas loss and leaves the physical cylinder filled with its remaining gas. If the recorded wastage equals all remaining gas, the physical cylinder is moved to empty stock. Wastage is limited by the configured shop/type percentage or fixed-KG allowance and is recorded with physical unit, user, reason and timestamp.
