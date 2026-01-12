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
 * Database
 */
$di->setShared('db', function () {
    return new \Phalcon\Db\Adapter\Pdo\Mysql([
        'host'     => 'ujicoba_db', // ⬅️ PENTING
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
