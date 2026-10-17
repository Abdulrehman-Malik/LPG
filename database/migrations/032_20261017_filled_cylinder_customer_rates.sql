-- Add fixed selling prices for filled cylinders and optional customer-specific overrides.
-- Existing cylinder_package rates remain the empty-cylinder price for backward compatibility.
ALTER TABLE rate_cards DROP CHECK rate_cards_chk_2;

ALTER TABLE rate_cards
    MODIFY COLUMN rate_type ENUM('gas_per_kg','cylinder_package','filled_cylinder_price') NOT NULL,
    ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER cylinder_type_id,
    ADD KEY idx_rate_customer_lookup(location_id,customer_id,rate_type,cylinder_type_id,effective_from),
    ADD CONSTRAINT fk_rate_card_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE RESTRICT;

-- Customer overrides are only valid for filled-cylinder prices.
ALTER TABLE rate_cards
    ADD CONSTRAINT chk_rate_customer_type CHECK (customer_id IS NULL OR rate_type='filled_cylinder_price'),
    ADD CONSTRAINT chk_rate_card_type_shape CHECK ((rate_type='gas_per_kg' AND cylinder_type_id IS NULL AND customer_id IS NULL) OR (rate_type='cylinder_package' AND cylinder_type_id IS NOT NULL AND customer_id IS NULL) OR (rate_type='filled_cylinder_price' AND cylinder_type_id IS NOT NULL));

ALTER TABLE rate_change_log
    MODIFY COLUMN rate_type ENUM('gas_per_kg','cylinder_package','filled_cylinder_price') NOT NULL,
    ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER cylinder_type_id,
    ADD KEY idx_rate_change_customer(location_id,customer_id,changed_at),
    ADD CONSTRAINT fk_rate_change_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE RESTRICT;
