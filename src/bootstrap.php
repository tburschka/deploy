<?php

// vendor autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// phperform autoloader
spl_autoload_extensions('.php');
spl_autoload_register(function ($class) {
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        $class = str_replace('\\', '/', $class);
    }
    /** @noinspection PhpIncludeInspection */
    require_once $class . '.php';
});

$connect = new Deploy\Deploy('Deploy', '0.1-dev');
$connect->run();
