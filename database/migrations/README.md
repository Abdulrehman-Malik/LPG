# Perfect LPG — Database Migration Deployment Order

All manual SQL migrations in this directory use the format:

```
NNN_YYYYMMDD_feature_name.sql
```

The **NNN** prefix is the deployment sequence. The date remains in the filename so the feature/change date is still visible.

## Ordered migration list

| Seq | Migration | Purpose |
|---:|---|---|
| 001 | `001_20260930_shop_settings.sql` | Branch-level shop settings |
| 002 | `002_20260930_inventory_controls.sql` | Inventory validation and wastage controls |
| 003 | `003_20260930_per_cylinder_inventory.sql` | Physical cylinder units and per-cylinder inventory support |
| 004 | `004_20261002_credit_limit_validation.sql` | Customer/shop credit-limit configuration |
| 005 | `005_20261002_opening_inventory_comments.sql` | Opening-inventory comments |
| 006 | `006_20261002_security_deposits.sql` | Security deposits, cylinder custody and transaction fields |
| 007 | `007_20261003_pos_transaction_type.sql` | Header-level POS transaction type |
| 008 | `008_20261003_individual_cylinder_tracking.sql` | Individual-cylinder tracking setting |
| 009 | `009_20261003_permissions_sync.sql` | Synchronize standard permissions and role mappings |
| 010 | `010_20261004_purchase_inventory_integrity.sql` | Persist actual gas weight on purchase lines |
| 011 | `011_20261003_refresh_transactional_data.sql` | **Manual staging/test-data reset only** |
| 012 | `012_20261004_physical_cylinder_code.sql` | Global physical-cylinder code format |
| 013 | `013_20261004_customer_credit_sale_control.sql` | Per-customer credit-sale permission; disabled by default |

## Deployment rules

1. Run `database/schema.sql` only for a fresh database.
2. For an existing database, run only the migration scripts that have not already been applied.
3. Always execute pending migrations in ascending sequence order: **001 → 002 → ... → 013**.
4. Migration **011** is not a normal upgrade migration. It is a controlled, destructive transactional-data refresh intended for disposable staging/test databases.
5. Do not automatically run migration 011 during production deployment.
6. Do not rename or reorder an already released migration. Add a new sequence number for every future migration.
7. Keep migration files idempotent where practical so a partially completed deployment can be safely retried.

## Fresh database

The current `database/schema.sql` already contains the current application schema. For a fresh database, use the schema first and apply only migrations needed for compatibility/testing of an existing-installation path.

## Existing database

Before deployment, identify the latest sequence already applied and continue from the next pending sequence. The numeric prefix is the authoritative execution order; the date is informational.

## Staging refresh

To intentionally reset transactional/test data:

```sql
USE perfect_lpg;
SET @lpg_refresh_transactional_data = 1;
SOURCE database/migrations/011_20261003_refresh_transactional_data.sql;
```

Never execute the staging refresh against production unless a deliberate full transactional reset is explicitly intended.

## Future migration naming standard

Use:

```
012_YYYYMMDD_feature_name.sql
014_YYYYMMDD_feature_name.sql
015_YYYYMMDD_feature_name.sql
```

Never reuse an existing sequence number.
