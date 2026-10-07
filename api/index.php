<?php

/*
 * Pintu masuk aplikasi di Vercel.
 * Di Vercel hanya folder /tmp yang bisa ditulis, jadi cache & view Laravel diarahkan ke sana.
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

// SEMENTARA: diagnosa error, buka /login?diag=golf
if (($_GET['diag'] ?? '') === 'golf') {
    header('Content-Type: text/plain; charset=utf-8');
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    echo "PHP " . PHP_VERSION . "\n";
    echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'ya' : 'TIDAK') . "\n";
    echo "/tmp writable: " . (is_writable($storage . '/framework/views') ? 'ya' : 'TIDAK') . "\n";
    echo "APP_KEY ada: " . (getenv('APP_KEY') ? 'ya' : 'TIDAK') . "\n\n";
    try {
        require __DIR__ . '/../vendor/autoload.php';
        $app = require __DIR__ . '/../bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $request = Illuminate\Http\Request::capture();
        $response = $kernel->handle($request);
        echo "Status: " . $response->getStatusCode() . "\n";
        $e = $response->exception ?? null;
        if ($e) {
            echo get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString();
            if ($e->getPrevious()) {
                $p = $e->getPrevious();
                echo "\n\nSEBELUMNYA: " . get_class($p) . ': ' . $p->getMessage() . "\n" . $p->getFile() . ':' . $p->getLine();
            }
        } else {
            echo "Panjang halaman: " . strlen((string) $response->getContent()) . " byte\n\n";
            echo "--- Tes terminate ---\n";
            try {
                $kernel->terminate($request, $response);
                echo "terminate OK\n";
            } catch (Throwable $t) {
                echo "TERMINATE ERROR: " . get_class($t) . ": " . $t->getMessage() . "\n" . $t->getFile() . ":" . $t->getLine() . "\n\n" . $t->getTraceAsString();
            }
            $last = error_get_last();
            if ($last) { echo "\n\nPHP error terakhir: " . $last["message"] . " di " . $last["file"] . ":" . $last["line"]; }
        }
    } catch (Throwable $e) {
        echo get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString();
    }
    exit;
}

require __DIR__ . '/../public/index.php';