<?php

// Ambil router dari DI
/** @var \Phalcon\Mvc\Router $router */
$router = $di->getRouter();

// ROUTE HALAMAN WEB (FRONTEND)

// Halaman landing ("/") -> IndexController::indexAction
$router->addGet(
    '/',
    [
        'controller' => 'index',
        'action'     => 'index',
    ]
);

// Logout -> kembali ke halaman utama
$router->addGet(
    '/logout',
    [
        'controller' => 'index',
        'action'     => 'logout',
    ]
);

// Halaman daftar + tambah catatan -> NotesController::indexAction
$router->addGet(
    '/notes',
    [
        'controller' => 'notes',
        'action'     => 'index',
    ]
);

// Aksi simpan catatan baru
$router->addPost(
    '/notes/create',
    [
        'controller' => 'notes',
        'action'     => 'create',
    ]
);

// Form edit catatan
$router->addGet(
    '/notes/edit/{id}',
    [
        'controller' => 'notes',
        'action'     => 'edit',
    ]
);

// Update catatan
$router->addPost(
    '/notes/update/{id}',
    [
        'controller' => 'notes',
        'action'     => 'update',
    ]
);

// Hapus catatan
$router->addGet(
    '/notes/delete/{id}',
    [
        'controller' => 'notes',
        'action'     => 'delete',
    ]
);


// ROUTE API - NOTES & GENERAL
$router->addGet('/api/notes', [
    'controller' => 'api',
    'action'     => 'notes',
]);

$router->addGet('/api/odoo-notes', [
    'controller' => 'api',
    'action'     => 'odooNotes',
]);

$router->addPost('/api/sync-to-odoo', [
    'controller' => 'api',
    'action'     => 'syncToOdoo',
]);

$router->addGet('/api/health', [
    'controller' => 'api',
    'action'     => 'health',
]);

// ROUTE API - ODOO INTEGRATION
$router->addGet('/api/odoo/health', [
    'controller' => 'odoo',
    'action'     => 'health',
]);

$router->addGet('/api/odoo/read', [
    'controller' => 'odoo',
    'action'     => 'read',
]);

$router->addPost('/api/odoo/create', [
    'controller' => 'odoo',
    'action'     => 'create',
]);

$router->addPost('/api/odoo/sync-notes', [
    'controller' => 'odoo',
    'action'     => 'syncNotes',
]);

$router->addGet('/api/odoo/sync-status', [
    'controller' => 'odoo',
    'action'     => 'syncStatus',
]);

$router->addGet('/api/odoo/failed-syncs', [
    'controller' => 'odoo',
    'action'     => 'failedSyncs',
]);

// Kembalikan instance router ke DI
return $router;
