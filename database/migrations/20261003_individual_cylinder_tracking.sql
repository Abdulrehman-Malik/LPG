ALTER TABLE shop_settings
  ADD COLUMN IF NOT EXISTS individual_cylinder_tracking BOOLEAN NOT NULL DEFAULT 0 AFTER default_transaction_type;
