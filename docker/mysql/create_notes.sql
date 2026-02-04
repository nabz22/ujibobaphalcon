-- Create notes table if not exists
CREATE TABLE IF NOT EXISTS `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `isi` longtext NOT NULL,
  `kategori` varchar(100) DEFAULT 'Umum',
  `prioritas` varchar(50) DEFAULT 'Normal',
  `status` varchar(50) DEFAULT 'Aktif',
  `tanggal` date NOT NULL,
  `odoo_model_id` int(11) DEFAULT NULL,
  `odoo_model_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_status` (`status`),
  KEY `idx_odoo_model` (`odoo_model_type`, `odoo_model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;