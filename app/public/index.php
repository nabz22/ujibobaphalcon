<?php

use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Autoload\Loader;

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH);

/**
 * Autoload
 */
$loader = new Loader();
$loader->setDirectories([
    APP_PATH . '/controllers/',
    APP_PATH . '/models/',
]);
$loader->register();

/**
 * Dependency Injection
 */
$di = new FactoryDefault();

/**
 * Router
 * Definisikan route seperti di aplikasi utama
 */
$router = $di->getRouter();

// Halaman landing "/"
$router->addGet(
    '/',
    [
        'controller' => 'index',
        'action'     => 'index',
    ]
);

// Halaman catatan (CRUD)
$router->addGet(
    '/notes',
    [
        'controller' => 'notes',
        'action'     => 'index',
    ]
);

// Simpan catatan baru
$router->addPost(
    '/notes/create',
    [
        'controller' => 'notes',
        'action'     => 'create',
    ]
);

// Edit catatan
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

// Logout -> kembali ke halaman utama
$router->addGet(
    '/logout',
    [
        'controller' => 'index',
        'action'     => 'logout',
    ]
);

/**
 * Database
 */
$di->setShared('db', function () {
    return new \Phalcon\Db\Adapter\Pdo\Mysql([
        // Host harus pakai NAMA SERVICE di docker-compose, yaitu "db"
        'host'     => 'db',
        'username' => 'root',
        'password' => 'root',
        'dbname'   => 'catatanharian',
        'charset'  => 'utf8mb4',
    ]);
});

/**
 * View
 */
$di->setShared('view', function () {
    $view = new View();
    $view->setViewsDir(APP_PATH . '/views/');
    return $view;
});

$app = new Application($di);

try {
    echo $app->handle($_SERVER['REQUEST_URI'])->getContent();
} catch (Throwable $e) {
    echo '<pre>' . $e->getMessage() . '</pre>';
}
