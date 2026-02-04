<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Mvc\View;

// If $di not provided, create new one
if (!isset($di)) {
    $di = new FactoryDefault();
}

/**
 * Database
 */
$di->setShared('db', function () {
    return new \Phalcon\Db\Adapter\Pdo\Mysql([
        'host'     => 'db',               // WAJIB 'db'
        'username' => 'root',
        'password' => 'root',
        'dbname'   => 'catatanharian',
        'charset'  => 'utf8mb4',
    ]);
});


/**
 * View
 */
$di->set('view', function () {
    $view = new View();
    $view->setViewsDir('../app/views/');
    $view->setLayoutsDir('layouts/');
$view->setLayout('main');

    return $view;
});

return $di;

// REMOVED: explicit router service - let Phalcon use default
// This was causing conflicts
