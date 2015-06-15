<?php

// vendor autoloader
require_once __DIR__ . '/../vendor/autoload.php';

$connect = new Deploy\Deploy('Deploy', '0.1-dev');
$connect->run();
