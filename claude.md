# Perfect LPG Project Tracker

This file is a concise continuation point for development.

## Current State
- Target architecture is documented in ARCHITECTURE.md.
- The complete one-shot MySQL schema is database/schema.sql.
- The old schema_v2.sql is no longer the authoritative schema.
- TESTING.md is the mandatory user/developer test gate.
- Phase 1 foundation changes are pushed and ready for local testing.

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

## Next Development Gate
After Phase 1 PASS, implement Phase 2 master data. If Phase 1 has FAIL entries, fix those first and update TESTING.md before continuing.
