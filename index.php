<?php

define('ROOT', __DIR__);
require __DIR__.'/vendor/autoload.php';

$kernel = new kernel();
$kernel->run($argv);