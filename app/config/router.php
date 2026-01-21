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


// ROUTE API
$router->addGet('/api/notes', [
    'controller' => 'api',
    'action'     => 'notes',
]);

$router->addGet('/api/health', [
    'controller' => 'api',
    'action'     => 'health',
]);

// Kembalikan instance router ke DI
return $router;
