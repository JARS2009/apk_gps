<?php

namespace App\Console\Commands;

use App\Models\UbicacionPrueba;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Servidor TCP que escucha tramas del protocolo GT06 (SinoTrack ST-901 y compatibles).
 *
 * Protocolo GT06:
 *   Inicio: 0x78 0x78
 *   [longitud] [protocolo] [datos...] [serial 2B] [crc 2B] [0x0D 0x0A]
 *
 *   Protocolos soportados:
 *     0x01 = Login (envía IMEI)
 *     0x12 = Ubicación GPS
 *     0x13 = Heartbeat (status)
 *     0x16 = Alarma GPS
 *     0x22 = Ubicación GPS (variante)
 */
class GpsListenCommand extends Command
{
    protected $signature = 'gps:listen {--port=5023 : Puerto TCP de escucha}';

    protected $description = 'Escucha conexiones TCP de rastreadores SinoTrack (protocolo GT06) y guarda en ubicacion_prueba';

    /** @var array<int, resource> */
    private array $clients = [];

    /** @var array<int, string> IMEI asociado a cada socket */
    private array $imeis = [];

    /** @var array<int, string> Buffer de datos parciales por socket */
    private array $buffers = [];

    public function handle(): int
    {
        $port = (int) $this->option('port');
        $address = '0.0.0.0';

        $this->info("Iniciando servidor GPS GT06 en {$address}:{$port}...");
        Log::channel('single')->info("GPS Listener iniciado en {$address}:{$port}");

        $server = @stream_socket_server("tcp://{$address}:{$port}", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);

        if (! $server) {
            $this->error("No se pudo crear el servidor: [{$errno}] {$errstr}");

            return self::FAILURE;
        }

        stream_set_blocking($server, false);
        $this->info("Servidor escuchando. Esperando conexiones de rastreadores...");

        while (true) {
            $read = array_merge([$server], $this->clients);
            $write = null;
            $except = null;

            if (@stream_select($read, $write, $except, 5) === false) {
                continue;
            }

            // Nueva conexión
            if (in_array($server, $read)) {
                $client = @stream_socket_accept($server, 0);
                if ($client) {
                    $id = (int) $client;
                    $this->clients[$id] = $client;
                    $this->buffers[$id] = '';
                    $peer = stream_socket_get_name($client, true);
                    $this->info("Conexión desde: {$peer}");
                    Log::channel('single')->info("GPS: nueva conexión desde {$peer}");
                    stream_set_blocking($client, false);
                }
                $read = array_diff($read, [$server]);
            }

            // Datos de clientes existentes
            foreach ($read as $client) {
                $id = (int) $client;
                $data = @fread($client, 1024);

                if ($data === false || $data === '') {
                    $this->removeClient($id);

                    continue;
                }

                $this->buffers[$id] .= $data;
                $this->processBuffer($id, $client);
            }
        }

        return self::SUCCESS; // @phpstan-ignore-line
    }

    /**
     * Procesa el buffer buscando tramas GT06 completas.
     */
    private function processBuffer(int $id, mixed $client): void
    {
        $buffer = &$this->buffers[$id];

        while (strlen($buffer) >= 10) {
            // Buscar inicio de trama 0x78 0x78
            $startPos = strpos($buffer, "\x78\x78");
            if ($startPos === false) {
                // No hay inicio válido, limpiar buffer
                $buffer = '';

                return;
            }

            // Descartar basura antes del inicio
            if ($startPos > 0) {
                $buffer = substr($buffer, $startPos);
            }

            // Verificar que tenemos suficientes bytes para leer la longitud
            if (strlen($buffer) < 3) {
                return;
            }

            $packetLength = ord($buffer[2]);
            $totalLength = $packetLength + 5; // 2 start + 1 length + data + 2 stop

            // Esperar más datos si la trama está incompleta
            if (strlen($buffer) < $totalLength) {
                return;
            }

            // Extraer trama completa
            $packet = substr($buffer, 0, $totalLength);
            $buffer = substr($buffer, $totalLength);

            // Verificar terminación 0x0D 0x0A
            if (substr($packet, -2) !== "\x0D\x0A") {
                $this->warn("Trama con terminación inválida, descartando.");

                continue;
            }

            $this->processPacket($id, $client, $packet);
        }
    }

    /**
     * Procesa una trama GT06 completa.
     */
    private function processPacket(int $id, mixed $client, string $packet): void
    {
        $hex = strtoupper(bin2hex($packet));
        $protocol = ord($packet[3]);

        $this->line("  Trama [{$hex}] Protocolo: 0x".dechex($protocol));

        match ($protocol) {
            0x01 => $this->handleLogin($id, $client, $packet, $hex),
            0x12, 0x22 => $this->handleLocation($id, $packet, $hex, 'ubicacion'),
            0x16 => $this->handleLocation($id, $packet, $hex, 'alarma'),
            0x13, 0x23 => $this->handleHeartbeat($id, $client, $packet, $hex),
            default => $this->line("  Protocolo no manejado: 0x".dechex($protocol)),
        };
    }

    /**
     * Login: el dispositivo envía su IMEI.
     * Responder con ACK para que siga transmitiendo.
     */
    private function handleLogin(int $id, mixed $client, string $packet, string $hex): void
    {
        // IMEI está en bytes 4-11 (8 bytes BCD)
        $imeiBytes = substr($packet, 4, 8);
        $imei = '';
        for ($i = 0; $i < 8; $i++) {
            $imei .= sprintf('%02d', ord($imeiBytes[$i]));
        }
        $imei = ltrim($imei, '0');
        if (strlen($imei) > 15) {
            $imei = substr($imei, 0, 15);
        }

        $this->imeis[$id] = $imei;
        $this->info("  Login IMEI: {$imei}");
        Log::channel('single')->info("GPS Login: IMEI={$imei}");

        // Guardar evento de login
        UbicacionPrueba::create([
            'imei' => $imei,
            'ubicacion' => '0,0',
            'latitud' => 0,
            'longitud' => 0,
            'evento' => 'login',
            'trama_raw' => $hex,
        ]);

        // Responder ACK (obligatorio para GT06)
        $this->sendResponse($client, $packet, 0x01);
    }

    /**
     * Ubicación GPS: parsear lat/lng del paquete GT06.
     */
    private function handleLocation(int $id, string $packet, string $hex, string $evento): void
    {
        $imei = $this->imeis[$id] ?? 'desconocido';

        // Datos de ubicación empiezan en byte 4
        $data = substr($packet, 4);

        if (strlen($data) < 12) {
            $this->warn("  Paquete de ubicación demasiado corto");

            return;
        }

        // Fecha/hora (bytes 0-5): YY MM DD HH MM SS
        $year = 2000 + ord($data[0]);
        $month = ord($data[1]);
        $day = ord($data[2]);
        $hour = ord($data[3]);
        $minute = ord($data[4]);
        $second = ord($data[5]);

        $fechaGps = null;
        try {
            $fechaGps = Carbon::create($year, $month, $day, $hour, $minute, $second, 'UTC');
        } catch (\Exception $e) {
            $this->warn("  Fecha GPS inválida: {$year}-{$month}-{$day} {$hour}:{$minute}:{$second}");
        }

        // Byte 6: longitud info GPS (nibble alto) + cantidad satélites (nibble bajo)
        $gpsInfoLength = (ord($data[6]) >> 4) & 0x0F;
        $satellites = ord($data[6]) & 0x0F;

        // Latitud (bytes 7-10): valor entero / 30000 / 60 = grados decimales
        $latRaw = (ord($data[7]) << 24) | (ord($data[8]) << 16) | (ord($data[9]) << 8) | ord($data[10]);
        $latitud = $latRaw / 30000.0 / 60.0;

        // Longitud (bytes 11-14)
        $lngRaw = (ord($data[11]) << 24) | (ord($data[12]) << 16) | (ord($data[13]) << 8) | ord($data[14]);
        $longitud = $lngRaw / 30000.0 / 60.0;

        // Velocidad (byte 15) en km/h
        $velocidad = ord($data[15]);

        // Rumbo y flags (bytes 16-17)
        $courseStatus = (ord($data[16]) << 8) | ord($data[17]);
        $rumbo = $courseStatus & 0x03FF; // bits 0-9 = rumbo

        // Bit 10: 0=Este, 1=Oeste
        $isWest = ($courseStatus >> 10) & 0x01;
        // Bit 11: 0=Norte, 1=Sur
        $isSouth = ($courseStatus >> 11) & 0x01;

        if ($isSouth) {
            $latitud = -$latitud;
        }
        if ($isWest) {
            $longitud = -$longitud;
        }

        // Bit 12: 1=GPS posicionado
        $gpsFixed = ($courseStatus >> 12) & 0x01;

        $ubicacion = "{$latitud},{$longitud}";

        $this->info("  Ubicación: {$ubicacion} | Vel: {$velocidad} km/h | Rumbo: {$rumbo}° | Satélites: {$satellites} | Fix: {$gpsFixed}");

        UbicacionPrueba::create([
            'imei' => $imei,
            'ubicacion' => $ubicacion,
            'latitud' => $latitud,
            'longitud' => $longitud,
            'velocidad' => $velocidad,
            'rumbo' => $rumbo,
            'evento' => $evento,
            'trama_raw' => $hex,
            'fecha_gps' => $fechaGps,
        ]);

        Log::channel('single')->info("GPS Ubicación: IMEI={$imei} Lat={$latitud} Lng={$longitud} Vel={$velocidad}");
    }

    /**
     * Heartbeat: responder ACK para mantener la conexión viva.
     */
    private function handleHeartbeat(int $id, mixed $client, string $packet, string $hex): void
    {
        $imei = $this->imeis[$id] ?? 'desconocido';
        $this->line("  Heartbeat de IMEI: {$imei}");

        $this->sendResponse($client, $packet, ord($packet[3]));
    }

    /**
     * Envía respuesta ACK al dispositivo (formato GT06).
     */
    private function sendResponse(mixed $client, string $packet, int $protocol): void
    {
        // Extraer serial number (2 bytes antes del CRC)
        $len = strlen($packet);
        $serialHigh = ord($packet[$len - 6]);
        $serialLow = ord($packet[$len - 5]);

        // Respuesta: 78 78 05 [protocolo] [serial 2B] [crc 2B] 0D 0A
        $response = "\x78\x78\x05";
        $response .= chr($protocol);
        $response .= chr($serialHigh).chr($serialLow);

        // CRC-ITU sobre longitud + protocolo + serial
        $crcData = "\x05".chr($protocol).chr($serialHigh).chr($serialLow);
        $crc = $this->crcItu($crcData);
        $response .= pack('n', $crc);
        $response .= "\x0D\x0A";

        @fwrite($client, $response);
    }

    /**
     * CRC-ITU (CRC-CCITT con tabla) usado por GT06.
     */
    private function crcItu(string $data): int
    {
        $crc = 0xFFFF;

        for ($i = 0; $i < strlen($data); $i++) {
            $byte = ord($data[$i]);
            $crc = ($crc >> 8) ^ $this->crcTable()[($crc ^ $byte) & 0xFF];
        }

        return $crc;
    }

    /**
     * Tabla CRC-CCITT precalculada.
     *
     * @return int[]
     */
    private function crcTable(): array
    {
        static $table = null;

        if ($table === null) {
            $table = [];
            for ($i = 0; $i < 256; $i++) {
                $crc = $i;
                for ($j = 0; $j < 8; $j++) {
                    if ($crc & 1) {
                        $crc = ($crc >> 1) ^ 0x8408;
                    } else {
                        $crc >>= 1;
                    }
                }
                $table[] = $crc & 0xFFFF;
            }
        }

        return $table;
    }

    /**
     * Limpia un cliente desconectado.
     */
    private function removeClient(int $id): void
    {
        $imei = $this->imeis[$id] ?? 'desconocido';
        $this->warn("  Desconexión de IMEI: {$imei}");
        Log::channel('single')->info("GPS Desconexión: IMEI={$imei}");

        if (isset($this->clients[$id])) {
            @fclose($this->clients[$id]);
            unset($this->clients[$id]);
        }
        unset($this->imeis[$id], $this->buffers[$id]);
    }
}
