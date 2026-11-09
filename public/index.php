<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// PHP 8.5 deprecates the legacy PDO MySQL SSL constant used by older
// framework releases. Keep deprecations out of HTTP responses while they
// remain available to the configured application logger.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
