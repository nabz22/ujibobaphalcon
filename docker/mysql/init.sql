CREATE DATABASE IF NOT EXISTS catatanharian;
USE catatanharian;

CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    isi TEXT NOT NULL,
    kategori VARCHAR(100) DEFAULT 'Umum',
    prioritas VARCHAR(50) DEFAULT 'Normal',
    status VARCHAR(50) DEFAULT 'Aktif',
    tanggal DATE NOT NULL,
    odoo_model_id INT DEFAULT NULL,
    odoo_model_type VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_kategori` (`kategori`),
    KEY `idx_status` (`status`),
    KEY `idx_odoo_model` (`odoo_model_type`, `odoo_model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Sales Orders
CREATE TABLE IF NOT EXISTS `sales_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `odoo_order_id` INT UNIQUE,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `customer_name` VARCHAR(255) NOT NULL,
  `order_date` DATE NOT NULL,
  `total_amount` DECIMAL(15, 2) DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'draft',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sales Order Items
CREATE TABLE IF NOT EXISTS `sales_order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sales_order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` DECIMAL(10, 2) NOT NULL,
  `unit_price` DECIMAL(15, 2) NOT NULL,
  `subtotal` DECIMAL(15, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Orders
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `odoo_order_id` INT UNIQUE,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `supplier_name` VARCHAR(255) NOT NULL,
  `order_date` DATE NOT NULL,
  `total_amount` DECIMAL(15, 2) DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'draft',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Order Items
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `purchase_order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` DECIMAL(10, 2) NOT NULL,
  `unit_price` DECIMAL(15, 2) NOT NULL,
  `subtotal` DECIMAL(15, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `odoo_invoice_id` INT UNIQUE,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `sales_order_id` INT,
  `purchase_order_id` INT,
  `invoice_date` DATE NOT NULL,
  `total_amount` DECIMAL(15, 2) NOT NULL,
  `tax_amount` DECIMAL(15, 2) DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'confirmed',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_invoice_number` (`invoice_number`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders`(`id`),
  FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory movements
CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `movement_type` VARCHAR(50),
  `reference_type` VARCHAR(50),
  `reference_id` INT,
  `quantity_before` DECIMAL(10, 2),
  `quantity_after` DECIMAL(10, 2),
  `quantity_moved` DECIMAL(10, 2),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
  KEY `idx_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
