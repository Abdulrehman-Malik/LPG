# Perfect LPG — Development Progress

Legend:
- [ ] Pending
- [/] In Progress
- [x] Done
- [!] Blocked / user business decision required

## Phase 0 — Architecture
- [x] Repository reviewed
- [x] Target normalized database architecture defined
- [x] Core services/business rules/API contract documented
- [x] Complete one-shot MySQL schema prepared
- [ ] Business decisions confirmed

## Phase 1 — Foundation / Local Test Gate
- [x] Complete database schema in database/schema.sql
- [x] Seed location, roles, permissions, admin, register, expense categories and cylinder types
- [x] Authentication updated for role table
- [x] Dashboard updated for target inventory/sales ledgers
- [x] TESTING.md created as mandatory test gate
- [x] User executes schema and tests foundation
- [x] User reports PASS/FAIL in TESTING.md

## Phase 2 — Master Data
- [x] Customers / Parties
- [x] Suppliers
- [x] Cylinder Types management
- [x] LPG rate management + rate history
- [x] Users / Roles / Permissions
- [x] Opening inventory
- [x] Customer/supplier ledgers

## Phase 3 — POS Sales
- [x] POS screen
- [x] Filled cylinder sale
- [x] KG refill
- [x] Cylinder exchange
- [x] Empty cylinder intake
- [x] Empty cylinder sale
- [x] Payment modes
- [x] Credit enforcement
- [x] OS balance block
- [x] Custom-rate highlighting
- [x] Atomic inventory/cash posting
- [x] Counter Cash session foundation (open/summary/close + POS cash linkage)
- [x] Receipt / print
- [x] Sale void/reversal
- [x] Phase 3 functional scope implemented and released for user testing

### Phase 3 — Transaction Integrity Hardening
- [x] Cash-session open concurrency protection
- [x] Cash-session close locking and committed-summary calculation
- [x] Cash-sale vs cash-session-close concurrency protection
- [x] Sale-row locking for void/reversal idempotency
- [x] Inventory-key concurrency locking for sales and reversals
- [x] Customer-row locking and in-transaction credit-limit recheck
- [ ] User concurrency/reversal tests recorded as PASS in TESTING.md
- [ ] HARDENING TESTS POSTPONED BY USER — remain outside the current functional test gate and will be resumed later

- [x] Configurable gas stock validation with explicit POS override confirmation
- [x] Gas wastage/leakage recording with physical-cylinder conversion to empty stock and filtered wastage report

## Phase 4 — Purchases & Inventory
- [x] Purchase entry
- [x] Inventory service
- [x] Physical cylinder unit inventory with actual gas weight per cylinder
- [x] Stock adjustments
- [x] Negative-stock protection
- [x] Inventory reports / stock view
- [x] Phase 4 functional scope implemented and released for user testing
- [x] Per-cylinder gas-weight inventory enhancement implemented and released for user retest

## Phase 5 — Counter Cash & Expenses
- [x] Cash register/session
- [x] Cash IN/OUT
- [x] Expenses
- [x] Customer receipts
- [x] Supplier payments
- [x] Handover / cash movement foundation
- [x] Close/reconcile
- [x] Phase 5 functional scope implemented and released for user testing

## Phase 6 — Reports & Audit
- [x] Customer ledger
- [x] Supplier ledger
- [x] Daily transactions / daily summary
- [x] Custom-rate reporting foundation
- [x] Stock report / stock view
- [x] Counter cash reconciliation
- [x] Expenses
- [x] Outstanding balance foundations
- [x] Audit log
- [x] Phase 6 functional scope implemented and released for user testing

## Workflow Gate
Functional development can be marked [x] when the requested module has been implemented and is packaged for user/SQA testing. User testing is recorded separately in TESTING.md and must not be represented as PASS until the user executes it.
Phase 3 transaction-integrity hardening tests are explicitly postponed by user request and remain pending.
