# Perfect LPG POS & ERP

Production-oriented LPG retail POS and ERP system.

## Technology
PHP 8.x, CodeIgniter 4, MySQL 8.0+, Bootstrap 5.3, jQuery/AJAX, HTML5/CSS3.

## Core Modules
- Login and roles
- Customers and suppliers
- LPG rates and cylinder types
- POS sales
- Purchases
- Inventory
- Customer/supplier ledgers
- Counter Cash
- Expenses
- Reports
- Audit

## Important Architecture Decision
The target architecture uses transaction tables plus immutable inventory/cash movement ledgers as the source of truth. Daily summary tables are reporting conveniences, not financial truth.

## Project Documentation
- `requirements.md` — functional and business requirements
- `ARCHITECTURE.md` — services, rules, APIs and UX
- `DEVELOPMENT_PLAN.md` — phase-by-phase progress
- `claude.md` — existing session/task tracker
- `database/schema_v2.sql` — target normalized database model

## Development Approach
Implement one phase at a time. Mark each requirement `[x]` only after implementation and testing. Never silently rewrite completed functionality.

## Current Repository
The repository already contains an initial CodeIgniter 4 implementation. The v2 database model should be introduced through migrations so the current application remains recoverable during the transition.