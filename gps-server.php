<?php

/**
 * GPS Server Bootstrap
 * Arranca el servidor TCP GT06 directamente, sin pasar por artisan.
 * Uso: php gps-server.php [--port=5023]
 */

define('LARAVEL_START', microtime(true));

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Bootstrappear el kernel para tener DB, Log, config, etc.
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Leer puerto de argumentos
$port = 5023;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--port=')) {
        $port = (int) substr($arg, 7);
    }
}

$address = '0.0.0.0';

echo "[GPS] Iniciando servidor GT06 en {$address}:{$port}..." . PHP_EOL;
Illuminate\Support\Facades\Log::channel('single')->info("GPS Listener iniciado en {$address}:{$port}");

$server = @stream_socket_server(
    "tcp://{$address}:{$port}",
    $errno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
);

if (! $server) {
    echo "[GPS ERROR] No se pudo crear el servidor: [{$errno}] {$errstr}" . PHP_EOL;
    exit(1);
}

stream_set_blocking($server, false);
echo "[GPS] Servidor escuchando en puerto {$port}. Esperando rastreadores..." . PHP_EOL;

// ─── Instanciar el comando para reutilizar su lógica ─────
$command = $app->make(App\Console\Commands\GpsListenCommand::class);

// ─── Usar la lógica del handle() directamente via reflexión ──
$ref    = new ReflectionClass($command);
$handle = $ref->getMethod('handle');

// Crear input/output falsos para el comando
$input  = new Symfony\Component\Console\Input\ArrayInput([]);
$output = new Symfony\Component\Console\Output\ConsoleOutput();

// Inyectar input/output en el comando
$inputProp  = $ref->getParentClass()->getProperty('input');
$outputProp = $ref->getParentClass()->getProperty('output');
$inputProp->setAccessible(true);
$outputProp->setAccessible(true);
$inputProp->setValue($command, $input);
$outputProp->setValue($command, $output);

// Forzar la opción --port
$inputProp->setValue($command, new Symfony\Component\Console\Input\ArrayInput(['--port' => (string)$port]));

// Ejecutar el handle directamente
$handle->invoke($command);
