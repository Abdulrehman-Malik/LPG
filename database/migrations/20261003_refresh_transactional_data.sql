-- Perfect LPG - Controlled transactional/test-data refresh
--
-- PURPOSE
--   Reset transactional data on a dedicated test/staging database so SQA can
--   start from a clean stock, cash and ledger state while keeping master data
--   (locations, users, roles, permissions, cylinder types, customers,
--   suppliers, rates, shop settings, cash registers, policies) intact.
--
-- SAFETY
--   This script is MANUAL / OPT-IN.
--   It performs no destructive action unless the caller explicitly sets:
--
--     SET @lpg_refresh_transactional_data = 1;
--
--   This guard prevents an ordinary migration run from deleting data.
--
-- EXECUTION
--   In MySQL CLI:
--
--     USE perfect_lpg;
--     SET @lpg_refresh_transactional_data = 1;
--     SOURCE database/migrations/20261003_refresh_transactional_data.sql;
--
--   Or pass the file through mysql after setting the session variable in the
--   same connection. Do NOT use this against production unless a deliberate
--   full transactional reset is intended.
--
-- WHAT IS RESET
--   Sales:
--     sales, sale_items, sale_payments
--   Purchases:
--     purchases, purchase_items, purchase_payments
--   Customer / supplier ledger transactions:
--     customer_receipts, supplier_payments
--   Customer security / cylinder custody:
--     cylinder_custody, customer_security_deposits
--   Inventory / stock:
--     inventory_opening_balances, inventory_movements, inventory_wastage_logs,
--     cylinder_units
--   Counter cash:
--     cash_transactions, cash_sessions
--   Expenses:
--     expenses
--   Audit:
--     audit_logs
--   Ledger opening inputs:
--     customers.opening_balance, suppliers.opening_balance -> 0
--
-- WHAT IS PRESERVED
--   locations, shop_settings, roles, permissions, role_permissions, users
--   cylinder_types, customers, suppliers
--   rate_cards, rate_change_log
--   inventory_policies, cash_registers
--
-- The authoritative current application derives stock from opening balances
-- plus inventory movements/physical cylinder units, and derives ledgers from
-- customer/supplier opening balances plus their posted transactions.

USE perfect_lpg;

SET @lpg_refresh_transactional_data = COALESCE(@lpg_refresh_transactional_data, 0);

DROP PROCEDURE IF EXISTS sp_lpg_refresh_transactional_data;

DELIMITER $$

CREATE PROCEDURE sp_lpg_refresh_transactional_data()
BEGIN
    IF @lpg_refresh_transactional_data = 1 THEN

        SET FOREIGN_KEY_CHECKS = 0;

        TRUNCATE TABLE audit_logs;

        TRUNCATE TABLE customer_security_deposits;
        TRUNCATE TABLE cylinder_custody;

        TRUNCATE TABLE sale_payments;
        TRUNCATE TABLE sale_items;
        TRUNCATE TABLE sales;

        TRUNCATE TABLE purchase_payments;
        TRUNCATE TABLE purchase_items;
        TRUNCATE TABLE purchases;

        TRUNCATE TABLE customer_receipts;
        TRUNCATE TABLE supplier_payments;

        TRUNCATE TABLE inventory_wastage_logs;
        TRUNCATE TABLE inventory_movements;
        TRUNCATE TABLE inventory_opening_balances;
        TRUNCATE TABLE cylinder_units;

        TRUNCATE TABLE cash_transactions;
        TRUNCATE TABLE cash_sessions;

        TRUNCATE TABLE expenses;

        SET FOREIGN_KEY_CHECKS = 1;

        UPDATE customers
        SET opening_balance = 0;

        UPDATE suppliers
        SET opening_balance = 0;

        -- Older installations may still contain legacy daily snapshot tables.
        -- Clear them when present so stale daily stock/summary screens cannot
        -- survive the transactional reset.
        IF EXISTS (
            SELECT 1
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'daily_filled_stock'
        ) THEN
            SET @sql = 'TRUNCATE TABLE daily_filled_stock';
            PREPARE stmt_daily_filled FROM @sql;
            EXECUTE stmt_daily_filled;
            DEALLOCATE PREPARE stmt_daily_filled;
        END IF;

        IF EXISTS (
            SELECT 1
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'daily_empty_stock'
        ) THEN
            SET @sql = 'TRUNCATE TABLE daily_empty_stock';
            PREPARE stmt_daily_empty FROM @sql;
            EXECUTE stmt_daily_empty;
            DEALLOCATE PREPARE stmt_daily_empty;
        END IF;

        IF EXISTS (
            SELECT 1
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'daily_summaries'
        ) THEN
            SET @sql = 'TRUNCATE TABLE daily_summaries';
            PREPARE stmt_daily_summary FROM @sql;
            EXECUTE stmt_daily_summary;
            DEALLOCATE PREPARE stmt_daily_summary;
        END IF;

        SELECT
            'LPG transactional refresh completed' AS status,
            (SELECT COUNT(*) FROM sales) AS sales_rows,
            (SELECT COUNT(*) FROM purchases) AS purchase_rows,
            (SELECT COUNT(*) FROM inventory_movements) AS inventory_movement_rows,
            (SELECT COUNT(*) FROM cylinder_units) AS cylinder_unit_rows,
            (SELECT COUNT(*) FROM customer_receipts) AS customer_receipt_rows,
            (SELECT COUNT(*) FROM supplier_payments) AS supplier_payment_rows,
            (SELECT COUNT(*) FROM expenses) AS expense_rows,
            (SELECT COUNT(*) FROM cash_sessions) AS cash_session_rows,
            (SELECT COUNT(*) FROM customer_security_deposits) AS security_deposit_rows,
            (SELECT COUNT(*) FROM cylinder_custody) AS custody_rows,
            (SELECT COALESCE(SUM(opening_balance),0) FROM customers) AS customer_opening_balance,
            (SELECT COALESCE(SUM(opening_balance),0) FROM suppliers) AS supplier_opening_balance;

    ELSE
        SELECT
            'SKIPPED - set @lpg_refresh_transactional_data = 1 to execute' AS status;
    END IF;
END$$

DELIMITER ;

CALL sp_lpg_refresh_transactional_data();

DROP PROCEDURE IF EXISTS sp_lpg_refresh_transactional_data;
