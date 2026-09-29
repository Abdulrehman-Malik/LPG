-- =====================================================================
-- Perfect LPG (Pvt.) LTD — Distribution & Cylinder Inventory Management
-- MySQL Schema (InnoDB, utf8mb4)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- users : system operators (auth, roles)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(120)        NOT NULL,
    username        VARCHAR(60)         NOT NULL,
    email           VARCHAR(150)        NULL,
    password_hash   VARCHAR(255)        NOT NULL,
    role            ENUM('admin','manager','cashier') NOT NULL DEFAULT 'cashier',
    is_active       TINYINT(1)          NOT NULL DEFAULT 1,
    last_login_at   DATETIME            NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- cylinder_types : the fixed capacity catalog (6kg, 11.8kg, 15kg, ...)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cylinder_types (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label           VARCHAR(30)         NOT NULL,        -- e.g. "11.8 kg"
    capacity_kg     DECIMAL(6,2)        NOT NULL,        -- e.g. 11.80
    is_active       TINYINT(1)          NOT NULL DEFAULT 1,
    sort_order      SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cylinder_capacity (capacity_kg)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- gas_rates : per-cylinder-type rate AND a generic per-kg rate, both
-- versioned by effective_from so historical sales keep their own rate.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gas_rates (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cylinder_type_id    INT UNSIGNED    NULL,   -- NULL => generic per-kg rate row
    rate_per_cylinder   DECIMAL(10,2)   NULL,
    rate_per_kg         DECIMAL(10,4)   NULL,
    effective_from      DATE            NOT NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gasrate_cylinder
        FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(id)
        ON DELETE CASCADE,
    KEY idx_gasrate_effective (effective_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- customers : parties / vehicles buying gas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)        NOT NULL,
    phone           VARCHAR(30)         NULL,
    vehicle_no      VARCHAR(30)         NULL,
    address         VARCHAR(255)        NULL,
    opening_balance DECIMAL(12,2)       NOT NULL DEFAULT 0.00, -- +ve = customer owes
    is_active       TINYINT(1)          NOT NULL DEFAULT 1,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_customer_name (name),
    KEY idx_customer_vehicle (vehicle_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- sales : one row per sale transaction (header)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sales (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_date           DATE            NOT NULL,
    customer_id         INT UNSIGNED    NULL,       -- NULL => walk-in / cash sale
    vehicle_no          VARCHAR(30)     NULL,        -- snapshot at time of sale
    sale_mode           ENUM('cylinder','kg','mixed') NOT NULL DEFAULT 'cylinder',
    total_kg            DECIMAL(10,3)   NOT NULL DEFAULT 0.000,
    total_amount        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    cash_received       DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    online_received     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    credit_amount       DECIMAL(12,2)   NOT NULL DEFAULT 0.00, -- total - cash - online
    empty_recv_count    INT UNSIGNED    NOT NULL DEFAULT 0,
    payment_status      ENUM('cash','credit','online','mixed') NOT NULL DEFAULT 'cash',
    notes               VARCHAR(255)    NULL,
    created_by          INT UNSIGNED    NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_sales_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL,
    KEY idx_sales_date (sale_date),
    KEY idx_sales_customer (customer_id),
    KEY idx_sales_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- sale_items : line items — either cylinder-based or kg-based per line
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sale_items (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id             INT UNSIGNED    NOT NULL,
    cylinder_type_id    INT UNSIGNED    NULL,        -- NULL when pure kg-based line
    line_type           ENUM('cylinder','kg') NOT NULL DEFAULT 'cylinder',
    cylinder_count      INT UNSIGNED    NOT NULL DEFAULT 0,
    gas_weight_kg       DECIMAL(10,3)   NOT NULL DEFAULT 0.000,
    rate                DECIMAL(10,4)   NOT NULL DEFAULT 0.0000, -- per-cylinder or per-kg
    line_total          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_saleitem_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_saleitem_cylinder
        FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(id)
        ON DELETE RESTRICT,
    KEY idx_saleitem_sale (sale_id),
    KEY idx_saleitem_cylinder (cylinder_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- customer_payments : direct payment receiving (independent of a sale)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customer_payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED        NOT NULL,
    payment_date    DATE                NOT NULL,
    amount          DECIMAL(12,2)       NOT NULL,
    payment_mode    ENUM('cash','online','cheque','bank') NOT NULL DEFAULT 'cash',
    notes           VARCHAR(255)        NULL,
    created_by      INT UNSIGNED        NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_custpay_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_custpay_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL,
    KEY idx_custpay_date (payment_date),
    KEY idx_custpay_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- daily_filled_stock : per cylinder-type, per day filled-gas stock ledger
-- Closing = Opening + Received - Sold + Adjustment  (enforced in app layer,
-- mirrored here as a generated column for reporting integrity)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS daily_filled_stock (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_date          DATE            NOT NULL,
    cylinder_type_id    INT UNSIGNED    NOT NULL,
    opening_stock       INT             NOT NULL DEFAULT 0,
    received_stock      INT             NOT NULL DEFAULT 0,
    sold_stock          INT             NOT NULL DEFAULT 0,
    adjustment          INT             NOT NULL DEFAULT 0,  -- can be negative
    closing_stock       INT GENERATED ALWAYS AS
                            (opening_stock + received_stock - sold_stock + adjustment)
                            STORED,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_filledstock_cylinder
        FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_filledstock_date_type (stock_date, cylinder_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- daily_empty_stock : per cylinder-type, per day empty-cylinder ledger
-- Closing = Opening + Recv(from customers) - Sent(for refill) + Adjustment
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS daily_empty_stock (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_date          DATE            NOT NULL,
    cylinder_type_id    INT UNSIGNED    NOT NULL,
    opening_stock       INT             NOT NULL DEFAULT 0,
    received_from_cust  INT             NOT NULL DEFAULT 0,
    sent_for_refill     INT             NOT NULL DEFAULT 0,
    adjustment          INT             NOT NULL DEFAULT 0,
    closing_stock       INT GENERATED ALWAYS AS
                            (opening_stock + received_from_cust - sent_for_refill + adjustment)
                            STORED,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_emptystock_cylinder
        FOREIGN KEY (cylinder_type_id) REFERENCES cylinder_types(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_emptystock_date_type (stock_date, cylinder_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- expenses : bowser/salary/misc expenses feeding the daily cash report
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS expenses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_date    DATE                NOT NULL,
    category        VARCHAR(60)         NOT NULL, -- e.g. 'bowser', 'salary', 'misc'
    description     VARCHAR(255)        NULL,
    amount          DECIMAL(12,2)       NOT NULL,
    created_by      INT UNSIGNED        NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expense_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL,
    KEY idx_expense_date (expense_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- daily_summaries : one row per business day — the Daily Cash/Sale Report
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS daily_summaries (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    summary_date        DATE            NOT NULL,
    opening_cash        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    cash_sale           DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    credit_sale         DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    online_sale         DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    credit_received     DECIMAL(12,2)   NOT NULL DEFAULT 0.00, -- from customer_payments
    total_sale          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    bowser_expense      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    other_expense       DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    hand_over_cash      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    balance             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    closed_by           INT UNSIGNED    NULL,
    is_closed           TINYINT(1)      NOT NULL DEFAULT 0,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_summary_user
        FOREIGN KEY (closed_by) REFERENCES users(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_summary_date (summary_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Seed: standard cylinder capacities
-- =====================================================================
INSERT INTO cylinder_types (label, capacity_kg, sort_order) VALUES
    ('6 kg',    6.00,  1),
    ('11.8 kg', 11.80, 2),
    ('15 kg',   15.00, 3),
    ('35 kg',   35.00, 4),
    ('45 kg',   45.00, 5),
    ('45.2 kg', 45.20, 6)
ON DUPLICATE KEY UPDATE label = VALUES(label);
