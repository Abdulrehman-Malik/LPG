-- LPG POS: configurable source filled-cylinder selection.

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    "ALTER TABLE shop_settings ADD COLUMN allow_pos_source_cylinder_selection BOOLEAN NOT NULL DEFAULT 0 AFTER individual_cylinder_tracking",
    "SELECT 1"
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shop_settings'
    AND COLUMN_NAME = 'allow_pos_source_cylinder_selection'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
