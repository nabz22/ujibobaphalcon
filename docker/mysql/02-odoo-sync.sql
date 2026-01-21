-- Migration: Create odoo_sync table for tracking synchronization

CREATE TABLE IF NOT EXISTS odoo_sync (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL COMMENT 'Type of entity (notes, users, etc)',
    entity_id INT NOT NULL COMMENT 'ID of local entity',
    odoo_id INT COMMENT 'ID of synced Odoo record',
    sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending' COMMENT 'Status of synchronization',
    sync_direction ENUM('push', 'pull', 'bidirectional') DEFAULT 'push' COMMENT 'Direction of sync',
    error_message TEXT COMMENT 'Error message if sync failed',
    synced_at TIMESTAMP NULL COMMENT 'When the sync was successful',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_sync (entity_type, entity_id),
    INDEX idx_status (sync_status),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_synced_at (synced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracking table for Odoo synchronization';

CREATE TABLE IF NOT EXISTS odoo_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    action VARCHAR(50) COMMENT 'create, update, delete, sync',
    source VARCHAR(20) COMMENT 'phalcon, odoo',
    status VARCHAR(20) COMMENT 'success, failed',
    message TEXT,
    response_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log for Odoo integration';
