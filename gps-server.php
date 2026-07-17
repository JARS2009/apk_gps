<?php

/**
 * GPS Server Bootstrap — Dual Protocol
 * Soporta:
 *   - Protocolo GT06 binario (SinoTrack, Concox, etc.)  → inicio: 0x7878
 *   - Protocolo HQ ASCII (algunos clones chinos)          → inicio: *HQ,
 *
 * Uso: php gps-server.php [--port=5023]
 */

define('LARAVEL_START', microtime(true));

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

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

echo "[GPS] Iniciando servidor dual GT06+HQ en {$address}:{$port}..." . PHP_EOL;
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

$clients = [];
$imeis   = [];
$buffers = [];

// ─── Helpers GT06 ──────────────────────────────────────────

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

function gt06_ack($socket, int $proto, int $serial): void
{
    $payload  = chr($proto) . chr($serial >> 8) . chr($serial & 0xFF);
    $crc      = gt06_crc($payload);
    $len      = strlen($payload) + 2;
    $response = "\x78\x78" . chr($len) . $payload . chr($crc >> 8) . chr($crc & 0xFF) . "\x0D\x0A";
    @fwrite($socket, $response);
}

// ─── Helper guardar en BD ──────────────────────────────────

function guardar_ubicacion(string $imei, float $lat, float $lon, float $speed, string $fecha, string $trama_hex, string $evento = 'ubicacion'): void
{
    try {
        App\Models\UbicacionPrueba::create([
            'imei'      => $imei,
            'ubicacion' => "{$lat},{$lon}",
            'latitud'   => $lat,
            'longitud'  => $lon,
            'velocidad' => $speed,
            'rumbo'     => null,
            'evento'    => $evento,
            'trama_raw' => $trama_hex,
            'fecha_gps' => $fecha,
        ]);
        echo "[GPS] ✅ Guardado IMEI={$imei} lat={$lat} lon={$lon} vel={$speed} ({$evento})" . PHP_EOL;
    } catch (\Throwable $e) {
        echo "[GPS ERROR] BD: " . $e->getMessage() . PHP_EOL;
    }
}

// ─── Parser HQ ASCII ───────────────────────────────────────

/**
 * Convierte NMEA lat/lon a grados decimales
 * Ejemplo: "0709.1968","S" → -7.1533
 */
function nmea_to_decimal(string $nmea, string $dir): float
{
    // Formato NMEA: DDDMM.MMMM
    if (strlen($nmea) < 5) return 0.0;

    // Separar grados y minutos
    $dot     = strpos($nmea, '.');
    $minLen  = 2; // minutos siempre 2 dígitos antes del punto
    $degStr  = substr($nmea, 0, $dot - $minLen);
    $minStr  = substr($nmea, $dot - $minLen);

    $deg = (float) $degStr;
    $min = (float) $minStr;
    $dec = $deg + ($min / 60.0);

    if ($dir === 'S' || $dir === 'W') {
        $dec = -$dec;
    }
    return round($dec, 7);
}

function parse_hq(string $line, int $socket_id, array &$imeis, $socket): void
{
    // Formato: *HQ,<IMEI>,<TYPE>,<fields...>#
    $line = trim($line, "*#\r\n ");
    $parts = explode(',', $line);

    if (count($parts) < 4) return;
    if ($parts[0] !== 'HQ') return;

    $imei = $parts[1];
    $type = $parts[2];

    $imeis[$socket_id] = $imei;

    echo "[HQ] Trama tipo={$type} IMEI={$imei}" . PHP_EOL;

    switch ($type) {
        case 'V8':  // Ubicación GPS
        case 'V1':
        case 'V3':
            if (count($parts) < 12) return;

            $timeStr  = $parts[3];           // HHMMSS
            $validity = $parts[4];           // A=válido, V=sin fix
            $latNmea  = $parts[5];           // DDMM.MMMM
            $latDir   = $parts[6];           // N/S
            $lonNmea  = $parts[7];           // DDDMM.MMMM
            $lonDir   = $parts[8];           // E/W
            $speed    = (float) $parts[9];   // nudos
            $heading  = isset($parts[10]) ? (float) $parts[10] : 0.0;
            $dateStr  = $parts[11];          // DDMMYY

            // Convertir velocidad: nudos → km/h
            $speedKmh = round($speed * 1.852, 2);

            // Convertir fecha/hora
            $dd = substr($dateStr, 0, 2);
            $mm = substr($dateStr, 2, 2);
            $yy = substr($dateStr, 4, 2);
            $hh = substr($timeStr, 0, 2);
            $ii = substr($timeStr, 2, 2);
            $ss = substr($timeStr, 4, 2);
            $fecha = "20{$yy}-{$mm}-{$dd} {$hh}:{$ii}:{$ss}";

            $lat = nmea_to_decimal($latNmea, $latDir);
            $lon = nmea_to_decimal($lonNmea, $lonDir);

            // Si tiene coordenadas reales (no 0,0), forzar 'ubicacion' aunque venga marcado como V
            $evento = ($validity === 'A' || (abs($lat) > 0.001 && abs($lon) > 0.001)) ? 'ubicacion' : 'sin_fix';

            guardar_ubicacion(
                $imei,
                $lat,
                $lon,
                $speedKmh,
                $fecha,
                bin2hex("*HQ,{$line}#"),
                $evento
            );

            // Respuesta HQ (opcional — algunos dispositivos no la necesitan)
            // @fwrite($socket, "*HQ,{$imei},V8,OK#");
            break;

        case 'LINK':  // Heartbeat
            echo "[HQ] Heartbeat IMEI={$imei}" . PHP_EOL;
            // Respuesta: *HQ,<IMEI>,LINK,OK#
            @fwrite($socket, "*HQ,{$imei},LINK,OK#");
            break;

        default:
            echo "[HQ] Tipo desconocido: {$type}" . PHP_EOL;
    }
}

// ─── Parser GT06 Binario ───────────────────────────────────

function parse_gt06(string &$buffer, int $id, array &$imeis, $client): void
{
    while (strlen($buffer) >= 5) {
        if (substr($buffer, 0, 2) !== "\x78\x78") {
            $pos = strpos($buffer, "\x78\x78");
            if ($pos === false) { $buffer = ''; break; }
            $buffer = substr($buffer, $pos);
            continue;
        }

        $pktLen = ord($buffer[2]);
        $total  = $pktLen + 5; // 2(start) + 1(len) + pktLen + 2(stop 0D0A)

        if (strlen($buffer) < $total) break;

        $proto  = ord($buffer[3]);
        // GT06 frame: [...data...][serial 2B][crc 2B][0D 0A]
        $serial = (ord($buffer[$total - 6]) << 8) | ord($buffer[$total - 5]);

        switch ($proto) {
            case 0x01: // Login — IMEI en BCD
                $imei = '';
                for ($i = 4; $i < 12; $i++) {
                    $b = ord($buffer[$i]);
                    $imei .= sprintf('%x', ($b >> 4)) . sprintf('%x', ($b & 0x0F));
                }
                $imei = ltrim($imei, '0');
                $imeis[$id] = $imei;
                gt06_ack($client, 0x01, $serial);
                echo "[GT06] Login IMEI={$imei}" . PHP_EOL;
                Illuminate\Support\Facades\Log::info("GPS GT06 Login IMEI={$imei}");
                break;

            case 0x12: // Ubicación GPS
            case 0x22:
                if (strlen($buffer) < 4 + 14) break 2;

                $off = 4;
                $yr  = ord($buffer[$off]);   $mo  = ord($buffer[$off+1]);
                $dy  = ord($buffer[$off+2]); $hr  = ord($buffer[$off+3]);
                $mn  = ord($buffer[$off+4]); $sc  = ord($buffer[$off+5]);
                $off += 6;

                $sats_len = ord($buffer[$off++]);

                $lat_raw  = (ord($buffer[$off]) << 24) | (ord($buffer[$off+1]) << 16) |
                            (ord($buffer[$off+2]) << 8) | ord($buffer[$off+3]);
                $off += 4;

                $lon_raw  = (ord($buffer[$off]) << 24) | (ord($buffer[$off+1]) << 16) |
                            (ord($buffer[$off+2]) << 8) | ord($buffer[$off+3]);
                $off += 4;

                $lat   = round($lat_raw / 1800000.0, 7);
                $lon   = round($lon_raw / 1800000.0, 7);
                $speed = isset($buffer[$off]) ? ord($buffer[$off]) : 0;
                $off++;

                $status = isset($buffer[$off], $buffer[$off+1])
                    ? (ord($buffer[$off]) << 8) | ord($buffer[$off+1])
                    : 0;

                if ($status & 0x0004) $lat = -$lat;
                if ($status & 0x0008) $lon = -$lon;

                $imei    = $imeis[$id] ?: 'GT06-' . $id;
                $fecha   = sprintf('20%02d-%02d-%02d %02d:%02d:%02d', $yr, $mo, $dy, $hr, $mn, $sc);
                $trama   = bin2hex(substr($buffer, 0, $total));

                guardar_ubicacion($imei, $lat, $lon, $speed, $fecha, $trama);
                gt06_ack($client, $proto, $serial);
                break;

            case 0x13: // Heartbeat
                gt06_ack($client, 0x13, $serial);
                echo "[GT06] Heartbeat cliente {$id}" . PHP_EOL;
                break;

            default:
                echo "[GT06] Protocolo desconocido 0x" . sprintf('%02X', $proto) . PHP_EOL;
        }

        $buffer = substr($buffer, $total);
    }
}

// ─── Loop principal ────────────────────────────────────────

while (true) {
    $read   = array_merge([$server], $clients);
    $write  = null;
    $except = null;

    if (@stream_select($read, $write, $except, 1) === false) continue;

    // Nueva conexión
    if (in_array($server, $read)) {
        $client = @stream_socket_accept($server, 0);
        if ($client) {
            stream_set_blocking($client, false);
            $id             = (int) $client;
            $clients[$id]   = $client;
            $imeis[$id]     = '';
            $buffers[$id]   = '';
            $peer           = stream_socket_get_name($client, true);
            echo "[GPS] Nueva conexión #{$id} desde {$peer}" . PHP_EOL;
        }
        unset($read[array_search($server, $read)]);
    }

    // Datos de clientes
    foreach ($read as $client) {
        $id   = (int) $client;
        $data = @fread($client, 4096);

        if ($data === false || $data === '') {
            echo "[GPS] Desconexión #{$id}" . PHP_EOL;
            fclose($client);
            unset($clients[$id], $imeis[$id], $buffers[$id]);
            continue;
        }

        $buffers[$id] .= $data;

        // Detectar protocolo por el inicio del buffer
        $buf = $buffers[$id];

        if (str_starts_with($buf, "\x78\x78")) {
            // ── GT06 Binario ──
            parse_gt06($buffers[$id], $id, $imeis, $client);

        } elseif (str_starts_with($buf, '*')) {
            // ── HQ ASCII — puede llegar varias tramas juntas
            // Separar por '#'
            while (($end = strpos($buffers[$id], '#')) !== false) {
                $line          = substr($buffers[$id], 0, $end + 1);
                $buffers[$id]  = substr($buffers[$id], $end + 1);
                parse_hq($line, $id, $imeis, $client);
            }
            // Si queda buffer sin '#' y es muy largo, limpiar
            if (strlen($buffers[$id]) > 2048) {
                $buffers[$id] = '';
            }

        } else {
            // Protocolo desconocido — loguear en hex y limpiar
            if (strlen($buf) > 5) {
                echo "[GPS] Protocolo desconocido desde #{$id}: " . bin2hex(substr($buf, 0, 20)) . PHP_EOL;
                $buffers[$id] = '';
            }
        }
    }
}
