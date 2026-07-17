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

// Leer puerto de argumentos CLI
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

$clients  = [];
$imeis    = [];
$buffers  = [];

/**
 * Calcula CRC16 para protocolo GT06
 */
function gt06_crc(string $data): int
{
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= (ord($data[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return $crc;
}

/**
 * Envía ACK al dispositivo
 */
function gt06_ack($socket, int $proto, int $serial): void
{
    $payload   = chr($proto) . chr($serial >> 8) . chr($serial & 0xFF);
    $crc       = gt06_crc($payload);
    $len       = strlen($payload) + 2; // payload + CRC
    $response  = "\x78\x78" . chr($len) . $payload . chr($crc >> 8) . chr($crc & 0xFF) . "\x0D\x0A";
    @fwrite($socket, $response);
}

while (true) {
    $read  = array_merge([$server], $clients);
    $write = null;
    $except = null;

    if (@stream_select($read, $write, $except, 1) === false) {
        continue;
    }

    // Nueva conexión
    if (in_array($server, $read)) {
        $client = @stream_socket_accept($server, 0);
        if ($client) {
            stream_set_blocking($client, false);
            $id              = (int) $client;
            $clients[$id]    = $client;
            $imeis[$id]      = '';
            $buffers[$id]    = '';
            echo "[GPS] Nueva conexión: {$id}" . PHP_EOL;
        }
        unset($read[array_search($server, $read)]);
    }

    // Datos de clientes
    foreach ($read as $client) {
        $id   = (int) $client;
        $data = @fread($client, 1024);

        if ($data === false || $data === '') {
            echo "[GPS] Desconexión: {$id}" . PHP_EOL;
            fclose($client);
            unset($clients[$id], $imeis[$id], $buffers[$id]);
            continue;
        }

        $buffers[$id] .= $data;

        // Procesar tramas completas
        while (strlen($buffers[$id]) >= 5) {
            $buf = $buffers[$id];

            if (substr($buf, 0, 2) !== "\x78\x78") {
                // Buscar inicio válido
                $pos = strpos($buf, "\x78\x78");
                if ($pos === false) { $buffers[$id] = ''; break; }
                $buffers[$id] = substr($buf, $pos);
                continue;
            }

            $pktLen  = ord($buf[2]);
            $total   = $pktLen + 5; // 2(start) + 1(len) + pktLen + 2(stop) — stop embebido en pktLen

            // GT06: total = 2(start) + 1(len) + len bytes + 0x0D0A
            // El len incluye proto + data + serial(2) + crc(2)
            // Trama completa = 2 + 1 + len + 2 = len+5
            if (strlen($buf) < $total) break;

            $proto  = ord($buf[3]);
            $serial = (ord($buf[$total - 4]) << 8) | ord($buf[$total - 3]);

            // Procesar según protocolo
            switch ($proto) {
                case 0x01: // Login — IMEI
                    $imei = '';
                    for ($i = 4; $i < 12; $i++) {
                        $byte = ord($buf[$i]);
                        $imei .= sprintf('%02d', $byte);
                    }
                    $imei = ltrim(substr($imei, 1), '0');  // GT06 codifica IMEI en BCD
                    $imeis[$id] = $imei;
                    gt06_ack($client, 0x01, $serial);
                    echo "[GPS] Login IMEI: {$imei} (cliente {$id})" . PHP_EOL;
                    Illuminate\Support\Facades\Log::info("GPS Login IMEI={$imei}");
                    break;

                case 0x12: // Ubicación GPS
                case 0x22: // Ubicación GPS variante
                    if (strlen($buf) < 4 + 12) break 2;

                    $offset = 4;
                    // Timestamp: 6 bytes (año,mes,día,hora,min,seg)
                    $yr  = ord($buf[$offset]);   $mo  = ord($buf[$offset+1]);
                    $dy  = ord($buf[$offset+2]); $hr  = ord($buf[$offset+3]);
                    $mn  = ord($buf[$offset+4]); $sc  = ord($buf[$offset+5]);
                    $offset += 6;

                    $sats_len = ord($buf[$offset++]);
                    $sats = $sats_len >> 4;

                    // Latitud (4 bytes, grados * 1,800,000)
                    $lat_raw = (ord($buf[$offset]) << 24) | (ord($buf[$offset+1]) << 16) |
                               (ord($buf[$offset+2]) << 8) | ord($buf[$offset+3]);
                    $offset += 4;

                    // Longitud (4 bytes)
                    $lon_raw = (ord($buf[$offset]) << 24) | (ord($buf[$offset+1]) << 16) |
                               (ord($buf[$offset+2]) << 8) | ord($buf[$offset+3]);
                    $offset += 4;

                    $lat = $lat_raw / 1800000.0;
                    $lon = $lon_raw / 1800000.0;

                    // Velocidad
                    $speed    = isset($buf[$offset]) ? ord($buf[$offset]) : 0;
                    $offset++;
                    $status   = isset($buf[$offset], $buf[$offset+1])
                        ? (ord($buf[$offset]) << 8) | ord($buf[$offset+1])
                        : 0;

                    // Bit de hemisferio
                    if ($status & 0x0004) $lat = -$lat;
                    if ($status & 0x0008) $lon = -$lon;

                    $imei    = $imeis[$id] ?: 'DESCONOCIDO';
                    $fechaStr = sprintf('20%02d-%02d-%02d %02d:%02d:%02d', $yr, $mo, $dy, $hr, $mn, $sc);

                    try {
                        $trama_hex = bin2hex(substr($buf, 0, $total));
                        App\Models\UbicacionPrueba::create([
                            'imei'      => $imei,
                            'ubicacion' => "{$lat},{$lon}",
                            'latitud'   => $lat,
                            'longitud'  => $lon,
                            'velocidad' => $speed,
                            'rumbo'     => null,
                            'evento'    => 'ubicacion',
                            'trama_raw' => $trama_hex,
                            'fecha_gps' => $fechaStr,
                        ]);
                        echo "[GPS] ✅ Guardado IMEI={$imei} lat={$lat} lon={$lon} vel={$speed}" . PHP_EOL;
                    } catch (\Throwable $e) {
                        echo "[GPS ERROR] DB: " . $e->getMessage() . PHP_EOL;
                    }

                    gt06_ack($client, $proto, $serial);
                    break;

                case 0x13: // Heartbeat
                    gt06_ack($client, 0x13, $serial);
                    echo "[GPS] Heartbeat de cliente {$id}" . PHP_EOL;
                    break;

                default:
                    echo "[GPS] Protocolo desconocido 0x" . sprintf('%02X', $proto) . PHP_EOL;
            }

            $buffers[$id] = substr($buf, $total);
        }
    }
}
