-- Customer credit-sale control.
-- Credit is disabled by default. Enable it per actual customer from Customers / Parties.

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    "ALTER TABLE customers ADD COLUMN allow_credit_sale BOOLEAN NOT NULL DEFAULT 0 AFTER credit_limit",
    "SELECT 1"
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'customers'
    AND COLUMN_NAME = 'allow_credit_sale'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
