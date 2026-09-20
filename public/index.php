<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Auto-detect subdirectory deployment (e.g., /mow) behind rewrite rules
if (isset($_SERVER['SCRIPT_NAME']) && isset($_SERVER['REQUEST_URI'])) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    if (basename($scriptDir) === 'public' && strpos($_SERVER['REQUEST_URI'], '/public') === false) {
        $subDir = str_replace('\\', '/', dirname($scriptDir));
        if ($subDir !== '/' && $subDir !== '' && $subDir !== '.') {
            $_SERVER['SCRIPT_NAME'] = $subDir . '/index.php';
            $_SERVER['PHP_SELF'] = $subDir . '/index.php';
        }
    }
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
