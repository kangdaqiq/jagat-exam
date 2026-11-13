<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Auto-sync PWA Pengawas icon from root if needed
$pwaSource = dirname(__DIR__) . '/Ikon Ujian X Biru-Hijau.png';
$pwaDest = __DIR__ . '/images/logo/pwa-pengawas-icon.png';
if (file_exists($pwaSource) && (!file_exists($pwaDest) || filesize($pwaDest) !== filesize($pwaSource))) {
    @mkdir(dirname($pwaDest), 0777, true);
    @copy($pwaSource, $pwaDest);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
