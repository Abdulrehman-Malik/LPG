# Perfect LPG Project Tracker

This file is a concise continuation point for development.

## Current State
- Target architecture is documented in ARCHITECTURE.md.
- The complete one-shot MySQL schema is database/schema.sql.
- The old schema_v2.sql is no longer the authoritative schema.
- TESTING.md is the mandatory user/developer test gate.
- Phase 1 foundation changes are pushed and ready for local testing.
- The POS Sales page has been hardened for transaction-type switching, stock-aware cylinder selectors, line management controls, and security-deposit entry.

## Phase 1 Ready for User Testing
1. Execute database/schema.sql on a fresh MySQL server.
2. Configure CodeIgniter .env for perfect_lpg.
3. Test login with admin / admin123.
4. Test dashboard.
5. Record PASS/FAIL and exact errors in TESTING.md.
6. Push the updated TESTING.md.

## Important Design Rules
- Transaction tables are source of truth.
- Inventory is represented by opening balances plus immutable inventory movements.
- Counter cash is represented by cash sessions plus cash transactions.
- Posted transactions are reversed/voided; they are not hard-deleted.
- Sales must preserve the actual applied rate on every line.
- Financial posting must be atomic inside a database transaction.
- Walk-in customers are cash-only and cannot create credit or cylinder-return balances.
- Credit sales must enforce the customer's credit limit.
- Controllers should not contain financial posting logic; use application services.

## Permission-Aware Navigation Notes

The shared sidebar now uses the authenticated user's granted permission codes. Reports requires REPORT_VIEW; POS Sales requires POS_SALE; Cash requires CASH_MANAGE; Purchases and Supplier Payments require PURCHASE_MANAGE; Inventory-related pages require INVENTORY_MANAGE; Customers/Suppliers/Rates/Users/Audit use their existing controller permission codes. Shop Settings follows the existing controller rule of USER_MANAGE or INVENTORY_MANAGE.

Unauthorized menu entries are hidden, while controller guards continue to enforce server-side access.

## POS Sale Page Regression Notes

After the latest Sales-page changes, verify:
1. Gas Sale / Refill selector shows filled count and gas KG.
2. Cylinder Sale selector updates between Filled and Empty stock counts.
3. Switching among Gas Sale, Cylinder Sale, Security Deposit, and Cylinder Return produces no JavaScript errors and clears incompatible line state.
4. Add Line creates independent rows and each row's remove button removes only that row.
5. Security Deposit Amount becomes editable only for Security Deposit / Issue Cylinder and participates in the payment total.

## Next Development Gate
After Phase 1 PASS, implement Phase 2 master data. If Phase 1 has FAIL entries, fix those first and update TESTING.md before continuing.
