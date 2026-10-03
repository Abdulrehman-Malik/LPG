ALTER TABLE shop_settings
  ADD COLUMN IF NOT EXISTS default_transaction_type ENUM('gas_sale','cylinder_sale','security_deposit','cylinder_return') NOT NULL DEFAULT 'gas_sale' AFTER default_sale_mode;

UPDATE shop_settings
SET default_transaction_type = CASE
  WHEN default_sale_mode IN ('sell_filled','replace_different','sell_empty') THEN 'cylinder_sale'
  ELSE 'gas_sale'
END
WHERE default_transaction_type = 'gas_sale';
