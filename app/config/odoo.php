<?php

/**
 * Odoo Integration Configuration
 */

return [
    'enabled' => getenv('ODOO_ENABLED') ?? true,
    
    'connection' => [
        'url'      => getenv('ODOO_URL') ?? 'http://host.docker.internal:8069',
        'database' => getenv('ODOO_DB') ?? 'odoo',
        'username' => getenv('ODOO_USER') ?? 'admin',
        'password' => getenv('ODOO_PASSWORD') ?? 'admin',
        'timeout'  => 30
    ],

    // Model mapping antara Phalcon dan Odoo
    'models' => [
        'notes' => [
            'odoo_model' => 'mail.activity',  // Model Odoo yang sesuai
            'fields'     => [
                'judul'   => 'summary',
                'isi'     => 'note',
                'tanggal' => 'activity_date',
                'created_at' => 'create_date'
            ],
            'sync_enabled' => true
        ],
        'users' => [
            'odoo_model' => 'res.users',
            'fields'     => [
                'name'     => 'name',
                'email'    => 'email',
                'created_at' => 'create_date'
            ],
            'sync_enabled' => false
        ]
    ],

    // Sync settings
    'sync' => [
        'auto_sync'      => getenv('ODOO_AUTO_SYNC') ?? false,
        'sync_interval'  => 300,  // seconds
        'batch_size'     => 100,
        'log_enabled'    => true
    ],

    // API Endpoints Odoo
    'api' => [
        'timeout' => 30,
        'retry'   => 3,
        'retry_delay' => 2
    ]
];
