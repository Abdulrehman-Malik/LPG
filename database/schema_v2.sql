-- Perfect LPG POS / ERP - Target Schema v2
-- MySQL 8.0+ / InnoDB / utf8mb4
-- Transaction tables are the source of truth.
-- Inventory and cash are maintained through immutable movement ledgers.

CREATE DATABASE IF NOT EXISTS perfect_lpg
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perfect_lpg;

CREATE TABLE locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(120) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(80),
    phone VARCHAR(30),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_locations_code (code)
) ENGINE=InnoDB;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,
    description VARCHAR(255),
    UNIQUE KEY uq_roles_code (code)
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    UNIQUE KEY uq_permissions_code (code)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED,
    role_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL,
    email VARCHAR(150),
    password_hash VARCHAR(255) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_location (location_id),
    KEY idx_users_role (role_id),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30),
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30),
    city VARCHAR(80),
    address VARCHAR(255),
    credit_limit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_customers_code (code),
    KEY idx_customers_name (name),
    KEY idx_customers_phone (phone),
    KEY idx_customers_city (city),
    CONSTRAINT chk_customer_credit_limit CHECK (credit_limit >= 0)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30),
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30),
    city VARCHAR(80),
    address VARCHAR(255),
    credit_limit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_suppliers_code (code),
    KEY idx_suppliers_name (name),
    KEY idx_suppliers_phone (phone),
    CONSTRAINT chk_supplier_credit_limit CHECK (credit_limit >= 0)
) ENGINE=InnoDB;

CREATE TABLE cylinder_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(60) NOT NULL,
    capacity_kg DECIMAL(8,3) NOT NULL,
    tare_weight_kg DECIMAL(8,3),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cylinder_code (code),
    UNIQUE KEY uq_cylinder_capacity (capacity_kg),
    KEY idx_cylinder_active (is_active),
    CHECK (capacity_kg > 0)
) ENGINE=InnoDB;

-- Effective-dated master rates.
CREATE TABLE rate_cards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED,
    rate_type ENUM('gas_per_kg','cylinder_package') NOT NULL,
    cylinder_type_id BIGINT UNSIGNED,
    rate_value DECIMAL(14,4) NOT NULL,
    effective_from DATETIME NOT NULL,
    effective_to DATETIME,
    created_by BIGINT UNSIGNED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_rates_lookup (location_id, rate_type, cylinder_type_id, effective_from),
    KEY idx_rates_effective (effective_from),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (rate_value >= 0),
    CHECK (
        (rate_type = 'gas_per_kg' AND cylinder_type_id IS NULL)
        OR
        (rate_type = 'cylinder_package' AND cylinder_type_id IS NOT NULL)
    )
) ENGINE=InnoDB;

-- Explicit old/new rate audit history.
CREATE TABLE rate_change_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rate_card_id BIGINT UNSIGNED,
    location_id BIGINT UNSIGNED,
    rate_type ENUM('gas_per_kg','cylinder_package') NOT NULL,
    cylinder_type_id BIGINT UNSIGNED,
    old_rate DECIMAL(14,4),
    new_rate DECIMAL(14,4) NOT NULL,
    changed_by BIGINT UNSIGNED,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(255),
    KEY idx_rate_log_time (changed_at),
    KEY idx_rate_log_lookup (location_id, rate_type, cylinder_type_id, changed_at),
    FOREIGN KEY (rate_card_id) REFERENCES rate_cards(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(id) ON DELETE SET NULL,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_no VARCHAR(40) NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED,
    transaction_type ENUM(
        'filled_cylinder','refill_service','cylinder_exchange',
        'empty_intake','empty_sale','mixed'
    ) NOT NULL,
    transaction_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_kg DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    credit_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    custom_rate_flag BOOLEAN NOT NULL DEFAULT FALSE,
    notes VARCHAR(500),
    created_by BIGINT UNSIGNED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sales_no_location (location_id, sale_no),
    KEY idx_sales_datetime (transaction_at),
    KEY idx_sales_customer_date (customer_id, transaction_at),
    KEY idx_sales_location_date (location_id, transaction_at),
    KEY idx_sales_custom_rate (custom_rate_flag, transaction_at),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (subtotal >= 0 AND discount_amount >= 0 AND total_amount >= 0 AND credit_amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    line_no SMALLINT UNSIGNED NOT NULL,
    line_type ENUM('filled_cylinder','refill_kg','empty_cylinder') NOT NULL,
    cylinder_type_id BIGINT UNSIGNED,
    quantity DECIMAL(14,3) NOT NULL,
    gas_weight_kg DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    applied_rate DECIMAL(14,4) NOT NULL,
    standard_rate DECIMAL(14,4),
    custom_rate_flag BOOLEAN NOT NULL DEFAULT FALSE,
    empty_cylinder_received DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    line_discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(14,2) NOT NULL,
    notes VARCHAR(255),
    UNIQUE KEY uq_sale_line (sale_id, line_no),
    KEY idx_sale_items_sale (sale_id),
    KEY idx_sale_items_cylinder (cylinder_type_id),
    KEY idx_sale_items_custom (custom_rate_flag),
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(id) ON DELETE RESTRICT,
    CHECK (quantity > 0),
    CHECK (gas_weight_kg >= 0),
    CHECK (applied_rate >= 0),
    CHECK (empty_cylinder_received >= 0)
) ENGINE=InnoDB;

CREATE TABLE sale_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    payment_mode ENUM('cash','cheque','online','credit') NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    reference_no VARCHAR(100),
    payment_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_by BIGINT UNSIGNED,
    notes VARCHAR(255),
    KEY idx_sale_payment_sale (sale_id),
    KEY idx_sale_payment_date_mode (payment_at, payment_mode),
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_no VARCHAR(40) NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    transaction_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    credit_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    notes VARCHAR(500),
    created_by BIGINT UNSIGNED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_purchase_no_location (location_id, purchase_no),
    KEY idx_purchase_supplier_date (supplier_id, transaction_at),
    KEY idx_purchase_location_date (location_id, transaction_at),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT UNSIGNED NOT NULL,
    line_no SMALLINT UNSIGNED NOT NULL,
    line_type ENUM('gas_kg','filled_cylinder','empty_cylinder') NOT NULL,
    cylinder_type_id BIGINT UNSIGNED,
    quantity DECIMAL(14,3) NOT NULL,
    unit_rate DECIMAL(14,4) NOT NULL,
    line_total DECIMAL(14,2) NOT NULL,
    UNIQUE KEY uq_purchase_line (purchase_id, line_no),
    KEY idx_purchase_items_purchase (purchase_id),
    KEY idx_purchase_items_cylinder (cylinder_type_id),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(id) ON DELETE RESTRICT,
    CHECK (quantity > 0),
    CHECK (unit_rate >= 0)
) ENGINE=InnoDB;

CREATE TABLE purchase_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT UNSIGNED NOT NULL,
    payment_mode ENUM('cash','cheque','online','credit') NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    reference_no VARCHAR(100),
    payment_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_by BIGINT UNSIGNED,
    KEY idx_purchase_payment_purchase (purchase_id),
    KEY idx_purchase_payment_date_mode (payment_at, payment_mode),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE customer_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(40) NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    payment_mode ENUM('cash','cheque','online') NOT NULL,
    receipt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reference_no VARCHAR(100),
    notes VARCHAR(255),
    created_by BIGINT UNSIGNED,
    UNIQUE KEY uq_customer_receipt_no (location_id, receipt_no),
    KEY idx_customer_receipt_customer_date (customer_id, receipt_at),
    KEY idx_customer_receipt_date_mode (receipt_at, payment_mode),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE supplier_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_no VARCHAR(40) NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    payment_mode ENUM('cash','cheque','online') NOT NULL,
    payment_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reference_no VARCHAR(100),
    notes VARCHAR(255),
    created_by BIGINT UNSIGNED,
    UNIQUE KEY uq_supplier_payment_no (location_id, payment_no),
    KEY idx_supplier_payment_supplier_date (supplier_id, payment_at),
    KEY idx_supplier_payment_date_mode (payment_at, payment_mode),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE inventory_opening_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    inventory_date DATE NOT NULL,
    inventory_type ENUM('gas_kg','filled_cylinder','empty_cylinder') NOT NULL,
    cylinder_type_id BIGINT UNSIGNED,
    quantity DECIMAL(14,3) NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory_opening (
        location_id, inventory_date, inventory_type, cylinder_type_id
    ),
    KEY idx_inventory_opening_date (inventory_date),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (quantity >= 0)
) ENGINE=InnoDB;

CREATE TABLE inventory_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    inventory_type ENUM('gas_kg','filled_cylinder','empty_cylinder') NOT NULL,
    cylinder_type_id BIGINT UNSIGNED,
    quantity DECIMAL(14,3) NOT NULL,
    direction ENUM('in','out','adjustment') NOT NULL,
    movement_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    source_type VARCHAR(40) NOT NULL,
    source_id BIGINT UNSIGNED NOT NULL,
    source_line_id BIGINT UNSIGNED,
    created_by BIGINT UNSIGNED,
    notes VARCHAR(255),
    KEY idx_inventory_lookup (
        location_id, inventory_type, cylinder_type_id, movement_at
    ),
    KEY idx_inventory_source (source_type, source_id),
    KEY idx_inventory_date (movement_at),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE cash_registers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(80) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE KEY uq_register_location_code (location_id, code),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE cash_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    register_id BIGINT UNSIGNED NOT NULL,
    opened_by BIGINT UNSIGNED NOT NULL,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    opening_cash DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    closed_by BIGINT UNSIGNED,
    closed_at DATETIME,
    counted_cash DECIMAL(14,2),
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    notes VARCHAR(255),
    KEY idx_cash_session_status (register_id, status),
    KEY idx_cash_session_opened (opened_at),
    FOREIGN KEY (register_id) REFERENCES cash_registers(id) ON DELETE RESTRICT,
    FOREIGN KEY (opened_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (opening_cash >= 0),
    CHECK (counted_cash IS NULL OR counted_cash >= 0)
) ENGINE=InnoDB;

CREATE TABLE cash_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cash_session_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM(
        'opening_float','sale_cash','customer_receipt','purchase_cash',
        'supplier_payment','expense','manual_in','manual_out','cash_handover'
    ) NOT NULL,
    direction ENUM('in','out') NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    transaction_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reference_type VARCHAR(40),
    reference_id BIGINT UNSIGNED,
    created_by BIGINT UNSIGNED,
    notes VARCHAR(255),
    KEY idx_cash_tx_session_date (cash_session_id, transaction_at),
    KEY idx_cash_tx_type_date (transaction_type, transaction_at),
    KEY idx_cash_tx_reference (reference_type, reference_id),
    FOREIGN KEY (cash_session_id) REFERENCES cash_sessions(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE expense_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(80) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE KEY uq_expense_category_code (code)
) ENGINE=InnoDB;

CREATE TABLE expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_no VARCHAR(40) NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    payment_mode ENUM('cash','cheque','online') NOT NULL,
    expense_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reference_no VARCHAR(100),
    description VARCHAR(255),
    created_by BIGINT UNSIGNED,
    UNIQUE KEY uq_expense_no_location (location_id, expense_no),
    KEY idx_expense_date_category (expense_at, category_id),
    KEY idx_expense_date_mode (expense_at, payment_mode),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    location_id BIGINT UNSIGNED,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_entity (entity_type, entity_id, created_at),
    KEY idx_audit_user_date (user_id, created_at),
    KEY idx_audit_action_date (action, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Initial cylinder master data.
INSERT INTO cylinder_types (code, name, capacity_kg, sort_order) VALUES
('C6', '6 kg', 6.000, 1),
('C11_8', '11.8 kg', 11.800, 2),
('C15', '15 kg', 15.000, 3),
('C35', '35 kg', 35.000, 4),
('C45', '45 kg', 45.000, 5),
('C45_2', '45.2 kg', 45.200, 6)
ON DUPLICATE KEY UPDATE name = VALUES(name);