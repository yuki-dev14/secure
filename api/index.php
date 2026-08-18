<?php

/**
 * Vercel Serverless Entry Point for Laravel
 */

// Display errors for debugging on serverless
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Force HTTPS scheme for Vercel edge proxy
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
putenv('HTTPS=on');
putenv('HTTP_X_FORWARDED_PROTO=https');

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

// Environment variable defaults for Vercel serverless
$envDefaults = [
    'APP_ENV'              => 'production',
    'APP_DEBUG'            => 'true',
    'APP_KEY'              => 'base64:FQhK4mYbGtUC1BRyAH+N67oYQvJC+vxwQbfKnxOqKs4=',
    'DB_CONNECTION'        => 'pgsql',
    'DB_HOST'              => 'aws-0-ap-southeast-2.pooler.supabase.com',
    'DB_PORT'              => '6543',
    'DB_DATABASE'          => 'postgres',
    'DB_USERNAME'          => 'postgres.rqhtxlffjoqhbukblrwd',
    'DB_PASSWORD'          => 'secure_euclid0314',
    'DB_SSLMODE'           => 'require',
    'SESSION_DRIVER'       => 'cookie',
    'CACHE_STORE'          => 'array',
    'FILESYSTEM_DISK'      => 'public',
    'QUEUE_CONNECTION'     => 'sync',
    'MAIL_MAILER'          => 'log',
    'LOG_CHANNEL'          => 'stderr',
    'BROADCAST_CONNECTION' => 'log',
];

foreach ($envDefaults as $key => $val) {
    if (empty(getenv($key)) && empty($_ENV[$key])) {
        putenv("{$key}={$val}");
        $_ENV[$key]    = $val;
        $_SERVER[$key] = $val;
    }
}

require $appDir . '/public/index.php';
