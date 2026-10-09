-- Configure the default entry method for Gas Sale / Refill on a per-shop basis.
-- Run once on existing installations; fresh installs include this field in the current schema.
ALTER TABLE shop_settings
    ADD COLUMN default_gas_entry_mode ENUM('quantity','amount','cylinders') NOT NULL DEFAULT 'quantity'
    AFTER default_transaction_type;
