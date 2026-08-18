<?php

/**
 * Vercel Serverless Entry Point for Laravel
 */

// Display errors for debugging on serverless
ini_set('display_errors', '1');
error_reporting(E_ALL);

$appDir = dirname(__DIR__);

// Ensure writable directories in /tmp for Vercel's read-only environment
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

// Override cache directories and log channel to /tmp and stderr
putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = 'stderr';
$_SERVER['LOG_CHANNEL'] = 'stderr';

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

// Fallback APP_KEY
if (!getenv('APP_KEY') && empty($_ENV['APP_KEY'])) {
    $key = 'base64:FQhK4mYbGtUC1BRyAH+N67oYQvJC+vxwQbfKnxOqKs4=';
    putenv("APP_KEY={$key}");
    $_ENV['APP_KEY'] = $key;
    $_SERVER['APP_KEY'] = $key;
}

// Fallback Supabase Database parameters if missing in environment
if (!getenv('DB_HOST') && empty($_ENV['DB_HOST'])) {
    putenv('DB_CONNECTION=pgsql');
    putenv('DB_HOST=aws-0-ap-southeast-2.pooler.supabase.com');
    putenv('DB_PORT=6543');
    putenv('DB_DATABASE=postgres');
    putenv('DB_USERNAME=postgres.rqhtxlffjoqhbukblrwd');
    putenv('DB_PASSWORD=secure_euclid0314');
    putenv('DB_SSLMODE=require');

    $_ENV['DB_CONNECTION'] = 'pgsql';
    $_ENV['DB_HOST']       = 'aws-0-ap-southeast-2.pooler.supabase.com';
    $_ENV['DB_PORT']       = '6543';
    $_ENV['DB_DATABASE']   = 'postgres';
    $_ENV['DB_USERNAME']   = 'postgres.rqhtxlffjoqhbukblrwd';
    $_ENV['DB_PASSWORD']   = 'secure_euclid0314';
    $_ENV['DB_SSLMODE']    = 'require';
}

if (!getenv('SESSION_DRIVER')) {
    putenv('SESSION_DRIVER=cookie');
    $_ENV['SESSION_DRIVER'] = 'cookie';
}

require $appDir . '/public/index.php';
