-- Tabel untuk menyimpan produk dari Odoo
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `odoo_product_id` INT UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(100),
  `category` VARCHAR(100),
  `description` LONGTEXT,
  `list_price` DECIMAL(10, 2) DEFAULT 0,
  `cost_price` DECIMAL(10, 2) DEFAULT 0,
  `quantity_on_hand` DECIMAL(10, 2) DEFAULT 0,
  `uom` VARCHAR(50),
  `status` VARCHAR(20) DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_odoo_id` (`odoo_product_id`),
  KEY `idx_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
