<?php

$loader = new \Phalcon\Autoload\Loader();

/**
 * We're registering a set of directories for autoloading
 */
$loader->setDirectories(
    [
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
        APP_PATH . '/library/'
    ]
)->register();
