-- Perfect LPG migration: per-cylinder gas inventory
-- Run once against an existing perfect_lpg database before using per-cylinder gas features.
USE perfect_lpg;
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE IF NOT EXISTS cylinder_units(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 location_id BIGINT UNSIGNED NOT NULL,
 cylinder_type_id BIGINT UNSIGNED NOT NULL,
 unit_code VARCHAR(60) NOT NULL,
 status ENUM('filled','empty','sold') NOT NULL DEFAULT 'empty',
 gas_weight_kg DECIMAL(14,3) NOT NULL DEFAULT 0,
 source_type VARCHAR(40),
 source_id BIGINT UNSIGNED,
 created_by BIGINT UNSIGNED,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_cylinder_unit_code(location_id,unit_code),
 KEY idx_cylinder_units_stock(location_id,cylinder_type_id,status),
 FOREIGN KEY(location_id) REFERENCES locations(id) ON DELETE RESTRICT,
 FOREIGN KEY(cylinder_type_id) REFERENCES cylinder_types(id) ON DELETE RESTRICT,
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
 CHECK(gas_weight_kg>=0)
) ENGINE=InnoDB;
ALTER TABLE inventory_movements ADD COLUMN cylinder_unit_id BIGINT UNSIGNED NULL AFTER source_line_id;
ALTER TABLE inventory_movements ADD KEY idx_inventory_unit(cylinder_unit_id);
ALTER TABLE inventory_movements ADD CONSTRAINT fk_inventory_unit FOREIGN KEY (cylinder_unit_id) REFERENCES cylinder_units(id) ON DELETE SET NULL;
SET FOREIGN_KEY_CHECKS=1;

-- Backfill existing filled/empty cylinder stock as full-capacity units.
INSERT INTO cylinder_units(location_id,cylinder_type_id,unit_code,status,gas_weight_kg,source_type,source_id)
SELECT i.location_id,i.cylinder_type_id,
       CONCAT('MIG-',i.location_id,'-',i.cylinder_type_id,'-',i.inventory_date,'-',n.n),
       CASE WHEN i.inventory_type='filled_cylinder' THEN 'filled' ELSE 'empty' END,
       CASE WHEN i.inventory_type='filled_cylinder' THEN ct.capacity_kg ELSE 0 END,
       'opening_migration',i.id
FROM inventory_opening_balances i
JOIN cylinder_types ct ON ct.id=i.cylinder_type_id
JOIN (
 SELECT ones.n + tens.n*10 + hundreds.n*100 + 1 n
 FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ones
 CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) tens
 CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) hundreds
) n ON n.n <= FLOOR(i.quantity)
WHERE i.inventory_type IN ('filled_cylinder','empty_cylinder')
AND i.quantity > 0
AND NOT EXISTS (
 SELECT 1 FROM cylinder_units u WHERE u.location_id=i.location_id AND u.source_type='opening_migration' AND u.source_id=i.id
);

-- Reconcile current cylinder stock created before this feature.
-- Historical cylinder transactions did not store per-cylinder gas weight, so migrated filled units use full rated capacity.
INSERT INTO cylinder_units(location_id,cylinder_type_id,unit_code,status,gas_weight_kg,source_type,source_id)
SELECT x.location_id,x.cylinder_type_id,
       CONCAT('MIG-CUR-',x.location_id,'-',x.cylinder_type_id,'-',x.status,'-',n.n),
       x.status,
       CASE WHEN x.status='filled' THEN x.capacity_kg ELSE 0 END,
       'migration_reconcile',0
FROM (
 SELECT ct.id cylinder_type_id,ct.capacity_kg,loc.id location_id,'filled' status,
        GREATEST(0,
          COALESCE((SELECT SUM(i.quantity) FROM inventory_opening_balances i WHERE i.location_id=loc.id AND i.inventory_type='filled_cylinder' AND i.cylinder_type_id=ct.id),0)
          +COALESCE((SELECT SUM(m.quantity) FROM inventory_movements m WHERE m.location_id=loc.id AND m.inventory_type='filled_cylinder' AND m.cylinder_type_id=ct.id AND m.direction='in'),0)
          -COALESCE((SELECT SUM(m.quantity) FROM inventory_movements m WHERE m.location_id=loc.id AND m.inventory_type='filled_cylinder' AND m.cylinder_type_id=ct.id AND m.direction='out'),0)
        ) stock_qty
 FROM locations loc CROSS JOIN cylinder_types ct
 UNION ALL
 SELECT ct.id,ct.capacity_kg,loc.id,'empty',
        GREATEST(0,
          COALESCE((SELECT SUM(i.quantity) FROM inventory_opening_balances i WHERE i.location_id=loc.id AND i.inventory_type='empty_cylinder' AND i.cylinder_type_id=ct.id),0)
          +COALESCE((SELECT SUM(m.quantity) FROM inventory_movements m WHERE m.location_id=loc.id AND m.inventory_type='empty_cylinder' AND m.cylinder_type_id=ct.id AND m.direction='in'),0)
          -COALESCE((SELECT SUM(m.quantity) FROM inventory_movements m WHERE m.location_id=loc.id AND m.inventory_type='empty_cylinder' AND m.cylinder_type_id=ct.id AND m.direction='out'),0)
        )
 FROM locations loc CROSS JOIN cylinder_types ct
) x
JOIN (
 SELECT ones.n + tens.n*10 + hundreds.n*100 + 1 n
 FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ones
 CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) tens
 CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) hundreds
) n ON n.n <= FLOOR(x.stock_qty-COALESCE((SELECT COUNT(*) FROM cylinder_units u WHERE u.location_id=x.location_id AND u.cylinder_type_id=x.cylinder_type_id AND u.status=x.status),0))
WHERE x.stock_qty>COALESCE((SELECT COUNT(*) FROM cylinder_units u WHERE u.location_id=x.location_id AND u.cylinder_type_id=x.cylinder_type_id AND u.status=x.status),0)
AND NOT EXISTS (SELECT 1 FROM cylinder_units u WHERE u.location_id=x.location_id AND u.cylinder_type_id=x.cylinder_type_id AND u.status=x.status AND u.source_type='migration_reconcile' AND u.source_id=0);