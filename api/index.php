<?php

/**
 * Vercel Serverless Entry Point for Laravel
 */

// Forward requests to public/index.php
$appDir = dirname(__DIR__);

// Configure writable directories in /tmp for Vercel's read-only environment
if (getenv('VERCEL') || getenv('NOW_REGION')) {
    $storageDirs = [
        '/tmp/storage/framework/views',
        '/tmp/storage/framework/sessions',
        '/tmp/storage/framework/cache/data',
        '/tmp/storage/logs',
        '/tmp/bootstrap/cache',
    ];
    foreach ($storageDirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    putenv('APP_SERVICES_CACHE=/tmp/bootstrap/cache/services.php');
    putenv('APP_PACKAGES_CACHE=/tmp/bootstrap/cache/packages.php');
    putenv('APP_CONFIG_CACHE=/tmp/bootstrap/cache/config.php');
    putenv('APP_ROUTES_CACHE=/tmp/bootstrap/cache/routes.php');
    putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');

    $_ENV['APP_SERVICES_CACHE'] = '/tmp/bootstrap/cache/services.php';
    $_ENV['APP_PACKAGES_CACHE'] = '/tmp/bootstrap/cache/packages.php';
    $_ENV['APP_CONFIG_CACHE']   = '/tmp/bootstrap/cache/config.php';
    $_ENV['APP_ROUTES_CACHE']   = '/tmp/bootstrap/cache/routes.php';
    $_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
}

require $appDir . '/public/index.php';
