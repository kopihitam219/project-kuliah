<?php

ob_start();

/*
 * Pintu masuk aplikasi di Vercel.
 * Di Vercel hanya folder /tmp yang bisa ditulis, jadi cache & view Laravel diarahkan ke sana.
 * ob_start(): tampung output supaya header Laravel tetap bisa dikirim.
 */

$storage = '/tmp/storage';

foreach (['framework/views', 'framework/cache/data', 'framework/sessions', 'logs', 'app/public', 'app/private'] as $dir) {
    if (! is_dir($storage . '/' . $dir)) {
        @mkdir($storage . '/' . $dir, 0777, true);
    }
}

$defaults = [
    'LARAVEL_STORAGE_PATH' => $storage,
    'APP_ENV'              => 'production',
    'APP_DEBUG'            => 'false',
    'APP_CONFIG_CACHE'     => '/tmp/config.php',
    'APP_EVENTS_CACHE'     => '/tmp/events.php',
    'APP_PACKAGES_CACHE'   => '/tmp/packages.php',
    'APP_ROUTES_CACHE'     => '/tmp/routes.php',
    'APP_SERVICES_CACHE'   => '/tmp/services.php',
    'VIEW_COMPILED_PATH'   => $storage . '/framework/views',
    'LOG_CHANNEL'          => 'stderr',
    'SESSION_DRIVER'       => 'database',
    'CACHE_STORE'          => 'database',
    'QUEUE_CONNECTION'     => 'sync',
    'SUPABASE_STORAGE'     => 'true',
];

foreach ($defaults as $key => $value) {
    if (getenv($key) === false && ! isset($_ENV[$key]) && ! isset($_SERVER[$key])) {
        putenv($key . '=' . $value);
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

require __DIR__ . '/../public/index.php';