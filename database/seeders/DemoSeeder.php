<?php

namespace Database\Seeders;

use App\Enums\CollarEstado;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Seeder de demostración con datos realistas de granjas ganaderas en Perú (región Cajamarca).
 * Ejecuta: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    // Coordenadas base en Cajamarca, Perú (~-7.16, -78.50)
    // Cada granja tiene sus propias coordenadas dentro de la región

    private array $granjas = [
        [
            'nombre'      => 'Granja Santa Rosa de Chugur',
            'descripcion' => 'Ganadería de leche y carne en las alturas de Chugur. Especializada en ganado Brown Swiss.',
            'lat_base'    => -6.6890,
            'lng_base'    => -78.7421,
            'policia'     => '076-362200',
            'emergencia'  => '116',
            'mensaje'     => 'Animal detectado fuera del perímetro autorizado. Contactar al capataz: 976-123456',
        ],
        [
            'nombre'      => 'Estancia El Páramo – La Encañada',
            'descripcion' => 'Crianza extensiva de vacunos en la zona de La Encañada. Pastos naturales de altura.',
            'lat_base'    => -7.0315,
            'lng_base'    => -78.3862,
            'policia'     => '076-363100',
            'emergencia'  => '105',
            'mensaje'     => 'Alerta: animal fuera de rango. Notifique al responsable de turno de inmediato.',
        ],
        [
            'nombre'      => 'Rancho Los Alisos – Bambamarca',
            'descripcion' => 'Producción mixta de leche y carne en la cuenca del Llaucano. Herd de 80+ cabezas.',
            'lat_base'    => -6.6739,
            'lng_base'    => -78.5188,
            'policia'     => '076-353000',
            'emergencia'  => '116',
            'mensaje'     => 'Se detectó movimiento de ganado fuera del área asignada. Verificar con administración.',
        ],
    ];

    private array $razasVacuno = [
        'Brown Swiss', 'Holstein Friesian', 'Simmental', 'Hereford',
        'Angus', 'Charolais', 'Criollo Andino', 'Fleckvieh',
    ];

    private array $tiposAnimal = ['Vacuno', 'Bovino'];

    private array $modelsCollar = ['GPS-Track Pro 3', 'AgroCollar V2', 'SkyPasture LT', 'RanchEye 4G'];


    private array $tiposEquipo = [
        ['nombre' => 'Collar GPS',          'prefijo' => 'COL'],
        ['nombre' => 'Sensor de temperatura','prefijo' => 'TEMP'],
        ['nombre' => 'Gateway LoRa',         'prefijo' => 'GW'],
        ['nombre' => 'Panel Solar',          'prefijo' => 'PS'],
    ];

    private array $nombresVacas = [
        'Bella', 'Luna', 'Estrella', 'Rosita', 'Paloma', 'Negra',
        'Canela', 'Manchada', 'Blanca', 'Mora', 'Clavel', 'Pinta',
        'Lucero', 'Azucena', 'Mariposa', 'Chola', 'Serrana', 'Andina',
        'Primavera', 'Flor', 'Violeta', 'Carmela', 'Petunia', 'Dalia',
        'Esperanza', 'Consuelo', 'Graciela', 'Milagros', 'Soledad', 'Aurora',
    ];

    private array $nombresUsuarios = [
        ['name' => 'Carlos Quispe Huamán',     'email' => 'c.quispe@gmail.com',    'doc' => '41523678'],
        ['name' => 'María López Vargas',        'email' => 'm.lopez@gmail.com',     'doc' => '52347891'],
        ['name' => 'José Díaz Herrera',         'email' => 'j.diaz@gmail.com',      'doc' => '63412590'],
        ['name' => 'Ana Flores Ríos',           'email' => 'a.flores@gmail.com',    'doc' => '74523016'],
        ['name' => 'Pedro Llanos Cueva',        'email' => 'p.llanos@gmail.com',    'doc' => '85634127'],
        ['name' => 'Rosa Sánchez Aliaga',       'email' => 'r.sanchez@gmail.com',   'doc' => '10234567'],
    ];

    public function run(): void
    {
        $this->command->info('Creando usuarios de demostración...');
        $users = $this->crearUsuarios();

        $this->command->info('Creando granjas...');
        $granjas = $this->crearGranjas($users);

        $this->command->info('Asignando usuarios a granjas...');
        $this->asignarUsuariosGranjas($granjas, $users);

        $this->command->info('Creando configuraciones de granja...');
        $this->crearConfiguraciones($granjas);

        $this->command->info('Creando tipos de equipo...');
        $this->crearTiposEquipo($granjas);

        $this->command->info('Creando terrenos...');
        $terrenos = $this->crearTerrenos($granjas);

        $this->command->info('Creando animales...');
        $animales = $this->crearAnimales($granjas);

        $this->command->info('Asignando animales a terrenos...');
        $this->asignarAnimalesTerrenos($animales, $terrenos);

        $this->command->info('Creando collares GPS...');
        $collares = $this->crearCollares($animales);

        $this->command->info('Registrando ubicaciones GPS (últimos 7 días)...');
        $this->crearUbicaciones($collares, $granjas);

        $this->command->info('Generando alertas...');
        $this->crearAlertas($granjas, $collares, $animales, $terrenos);


        $this->command->info('✅ Seeder completado exitosamente.');
    }

    // ─────────────────────────────────────────────
    // Usuarios
    // ─────────────────────────────────────────────
    private function crearUsuarios(): array
    {
        $users = [];
        foreach ($this->nombresUsuarios as $i => $datos) {
            $role = $i === 0 ? UserRole::Admin : UserRole::Admin;
            $users[] = User::firstOrCreate(
                ['email' => $datos['email']],
                [
                    'name'              => $datos['name'],
                    'num_doc'           => $datos['doc'],
                    'password'          => Hash::make('password'),
                    'role'              => $role,
                    'email_verified_at' => now()->subDays(rand(10, 120)),
                ]
            );
        }
        return $users;
    }

    // ─────────────────────────────────────────────
    // Granjas
    // ─────────────────────────────────────────────
    private function crearGranjas(array $users): array
    {
        $creadas = [];
        foreach ($this->granjas as $i => $data) {
            $creador = $users[$i % count($users)];
            $id = DB::table('farms')->insertGetId([
                'id_usuario_creador' => $creador->id,
                'nombre'             => $data['nombre'],
                'descripcion'        => $data['descripcion'],
                'activa'             => true,
                'created_at'         => now()->subMonths(rand(6, 24)),
                'updated_at'         => now()->subDays(rand(1, 30)),
            ]);
            $creadas[] = array_merge($data, ['id' => $id]);
        }
        return $creadas;
    }

    // ─────────────────────────────────────────────
    // Relación granja ↔ usuario
    // ─────────────────────────────────────────────
    private function asignarUsuariosGranjas(array $granjas, array $users): void
    {
        $pares = [];
        foreach ($granjas as $i => $granja) {
            // Dueño principal
            $pares[] = ['granja_id' => $granja['id'], 'user_id' => $users[$i % count($users)]->id, 'created_at' => now(), 'updated_at' => now()];
            // Segundo usuario con acceso
            $segundo = $users[($i + 1) % count($users)]->id;
            $pares[] = ['granja_id' => $granja['id'], 'user_id' => $segundo, 'created_at' => now(), 'updated_at' => now()];
        }
        // Evitar duplicados antes de insertar
        $unicos = [];
        $vistos = [];
        foreach ($pares as $par) {
            $key = "{$par['granja_id']}-{$par['user_id']}";
            if (!isset($vistos[$key])) {
                $unicos[] = $par;
                $vistos[$key] = true;
            }
        }
        DB::table('farm_user')->insertOrIgnore($unicos);
    }

    // ─────────────────────────────────────────────
    // Configuración de granja
    // ─────────────────────────────────────────────
    private function crearConfiguraciones(array $granjas): void
    {
        foreach ($granjas as $granja) {
            DB::table('farm_settings')->insertOrIgnore([
                'granja_id'          => $granja['id'],
                'telefono_policia'   => $granja['policia'],
                'telefono_emergencia'=> $granja['emergencia'],
                'mensaje_alerta'     => $granja['mensaje'],
                'alertas_activas'    => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    // ─────────────────────────────────────────────
    // Tipos de equipo
    // ─────────────────────────────────────────────
    private function crearTiposEquipo(array $granjas): void
    {
        foreach ($granjas as $granja) {
            foreach ($this->tiposEquipo as $tipo) {
                DB::table('equipment_types')->insertOrIgnore([
                    'granja_id'     => $granja['id'],
                    'nombre'        => $tipo['nombre'],
                    'prefijo_codigo'=> $tipo['prefijo'],
                    'estado'        => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────
    // Terrenos (polígonos GPS reales en Cajamarca)
    // ─────────────────────────────────────────────
    private function crearTerrenos(array $granjas): array
    {
        $todos = [];
        // Cada granja tiene 3 terrenos de diferente tamaño
        $parcelas = [
            ['nombre' => 'Potrero Norte',  'offset_lat' =>  0.003, 'offset_lng' =>  0.002, 'tam' => 0.004, 'area' => 12.50],
            ['nombre' => 'Pradera Central','offset_lat' =>  0.000, 'offset_lng' => -0.003, 'tam' => 0.006, 'area' => 21.80],
            ['nombre' => 'Pastizal Sur',   'offset_lat' => -0.005, 'offset_lng' =>  0.001, 'tam' => 0.003, 'area' =>  8.75],
        ];

        foreach ($granjas as $granja) {
            foreach ($parcelas as $parcela) {
                $lat = $granja['lat_base'] + $parcela['offset_lat'];
                $lng = $granja['lng_base'] + $parcela['offset_lng'];
                $t   = $parcela['tam'];

                // Polígono rectangular (5 puntos cerrando el anillo)
                $coordenadas = [
                    [$lng,       $lat      ],
                    [$lng + $t,  $lat      ],
                    [$lng + $t,  $lat + $t ],
                    [$lng,       $lat + $t ],
                    [$lng,       $lat      ], // cierre
                ];

                $id = DB::table('lands')->insertGetId([
                    'granja_id'   => $granja['id'],
                    'nombre'      => $parcela['nombre'],
                    'coordenadas' => json_encode($coordenadas),
                    'area'        => $parcela['area'],
                    'created_at'  => now()->subMonths(rand(3, 18)),
                    'updated_at'  => now()->subDays(rand(1, 15)),
                ]);
                $todos[] = ['id' => $id, 'granja_id' => $granja['id'], 'lat' => $lat + $t / 2, 'lng' => $lng + $t / 2];
            }
        }
        return $todos;
    }

    // ─────────────────────────────────────────────
    // Animales
    // ─────────────────────────────────────────────
    private function crearAnimales(array $granjas): array
    {
        $todos = [];
        $nombreIdx = 0;
        $animalCounter = 1;

        foreach ($granjas as $granja) {
            $cantidad = rand(8, 12);
            for ($i = 0; $i < $cantidad; $i++) {
                $nombre = $this->nombresVacas[$nombreIdx % count($this->nombresVacas)];
                // Evitar nombre repetido en misma granja
                if ($i >= count($this->nombresVacas)) {
                    $nombre .= ' ' . ($i + 1);
                }
                $nombreIdx++;

                $raza         = $this->razasVacuno[array_rand($this->razasVacuno)];
                $tipo         = $this->tiposAnimal[array_rand($this->tiposAnimal)];
                $prefixFarm   = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $granja['nombre']), 0, 3));
                $codigo       = $prefixFarm . '-' . str_pad($animalCounter, 4, '0', STR_PAD_LEFT);
                $animalCounter++;

                $nacimiento = Carbon::now()
                    ->subYears(rand(1, 8))
                    ->subMonths(rand(0, 11))
                    ->format('Y-m-d');

                $id = DB::table('animals')->insertGetId([
                    'granja_id'       => $granja['id'],
                    'nombre'          => $nombre,
                    'codigo'          => $codigo,
                    'tipo'            => $tipo,
                    'raza'            => $raza,
                    'fecha_nacimiento'=> $nacimiento,
                    'created_at'      => now()->subMonths(rand(1, 24)),
                    'updated_at'      => now()->subDays(rand(0, 30)),
                ]);

                $todos[] = ['id' => $id, 'granja_id' => $granja['id']];
            }
        }
        return $todos;
    }

    // ─────────────────────────────────────────────
    // Relación animal ↔ terreno
    // ─────────────────────────────────────────────
    private function asignarAnimalesTerrenos(array $animales, array $terrenos): void
    {
        $pares = [];
        $vistos = [];

        foreach ($animales as $animal) {
            // Terrenos de la misma granja
            $terrenosGranja = array_values(array_filter($terrenos, fn($t) => $t['granja_id'] === $animal['granja_id']));
            if (empty($terrenosGranja)) {
                continue;
            }
            $terreno = $terrenosGranja[array_rand($terrenosGranja)];
            $key = "{$animal['id']}-{$terreno['id']}";
            if (!isset($vistos[$key])) {
                $pares[] = ['animal_id' => $animal['id'], 'terreno_id' => $terreno['id'], 'created_at' => now(), 'updated_at' => now()];
                $vistos[$key] = true;
            }
        }
        DB::table('animal_land')->insertOrIgnore($pares);
    }

    // ─────────────────────────────────────────────
    // Collares GPS
    // ─────────────────────────────────────────────
    private function crearCollares(array $animales): array
    {
        $collares = [];
        $serieBase = 10000;

        // 85% de los animales tienen collar; algunos collares sin asignar extra
        $animalesConCollar = array_filter($animales, fn() => rand(1, 100) <= 85);

        foreach ($animalesConCollar as $animal) {
            $serie  = 'COL-' . ($serieBase++);
            $modelo = $this->modelsCollar[array_rand($this->modelsCollar)];

            $id = DB::table('collars')->insertGetId([
                'animal_id'  => $animal['id'],
                'serie'      => $serie,
                'modelo'     => $modelo,
                'estado'     => CollarEstado::Asignado->value,
                'created_at' => now()->subMonths(rand(1, 12)),
                'updated_at' => now()->subDays(rand(0, 10)),
            ]);
            $collares[] = ['id' => $id, 'animal_id' => $animal['id'], 'granja_id' => $animal['granja_id']];
        }

        // 5 collares disponibles (sin animal)
        for ($i = 0; $i < 5; $i++) {
            $serie  = 'COL-' . ($serieBase++);
            $modelo = $this->modelsCollar[array_rand($this->modelsCollar)];
            $estado = rand(0, 1) ? CollarEstado::Disponible->value : CollarEstado::Inactivo->value;

            $id = DB::table('collars')->insertGetId([
                'animal_id'  => null,
                'serie'      => $serie,
                'modelo'     => $modelo,
                'estado'     => $estado,
                'created_at' => now()->subMonths(rand(1, 6)),
                'updated_at' => now()->subDays(rand(0, 30)),
            ]);
            $collares[] = ['id' => $id, 'animal_id' => null, 'granja_id' => null];
        }

        return $collares;
    }

    // ─────────────────────────────────────────────
    // Ubicaciones GPS (últimos 7 días, cada 30 min)
    // ─────────────────────────────────────────────
    private function crearUbicaciones(array $collares, array $granjas): void
    {
        $granjaMap = [];
        foreach ($granjas as $g) {
            $granjaMap[$g['id']] = $g;
        }

        $batch = [];
        $now   = Carbon::now();

        foreach ($collares as $collar) {
            if ($collar['granja_id'] === null) {
                continue;
            }
            $granja   = $granjaMap[$collar['granja_id']];
            $lat      = $granja['lat_base'] + (rand(-30, 30) / 10000);
            $lng      = $granja['lng_base'] + (rand(-30, 30) / 10000);

            // 7 días × 48 lecturas/día = 336 registros por collar (máx)
            $lecturas = rand(200, 336);
            $intervalo = (7 * 24 * 60) / $lecturas; // minutos entre lecturas

            for ($j = 0; $j < $lecturas; $j++) {
                // Movimiento aleatorio tipo Brownian motion
                $lat += (rand(-5, 5) / 10000);
                $lng += (rand(-5, 5) / 10000);

                $ts = (clone $now)->subMinutes((int)($j * $intervalo));

                $batch[] = [
                    'collar_id'   => $collar['id'],
                    'latitud'     => round($lat, 7),
                    'longitud'    => round($lng, 7),
                    'recibido_en' => $ts->toDateTimeString(),
                    'created_at'  => $ts->toDateTimeString(),
                    'updated_at'  => $ts->toDateTimeString(),
                ];

                if (count($batch) >= 500) {
                    DB::table('collar_locations')->insert($batch);
                    $batch = [];
                }
            }
        }

        if (!empty($batch)) {
            DB::table('collar_locations')->insert($batch);
        }
    }

    // ─────────────────────────────────────────────
    // Alertas
    // ─────────────────────────────────────────────
    private function crearAlertas(array $granjas, array $collares, array $animales, array $terrenos): void
    {
        $granjaMap   = [];
        foreach ($granjas as $g) {
            $granjaMap[$g['id']] = $g;
        }

        $collarPorGranja = [];
        foreach ($collares as $c) {
            if ($c['granja_id']) {
                $collarPorGranja[$c['granja_id']][] = $c;
            }
        }

        $animalPorGranja = [];
        foreach ($animales as $a) {
            $animalPorGranja[$a['granja_id']][] = $a;
        }

        $terrenoPorGranja = [];
        foreach ($terrenos as $t) {
            $terrenoPorGranja[$t['granja_id']][] = $t;
        }

        $alertaRows = [];
        $tipos = ['fuera_de_rango', 'sin_señal'];
        $mensajes = [
            'fuera_de_rango' => 'Animal detectado fuera del perímetro del terreno asignado.',
            'sin_señal'      => 'El collar no ha reportado señal GPS en los últimos 30 minutos.',
        ];

        foreach ($granjas as $granja) {
            $gId = $granja['id'];
            if (empty($collarPorGranja[$gId])) {
                continue;
            }

            $cantAlertas = rand(5, 12);
            for ($i = 0; $i < $cantAlertas; $i++) {
                $collar  = $collarPorGranja[$gId][array_rand($collarPorGranja[$gId])];
                $animal  = !empty($animalPorGranja[$gId]) ? $animalPorGranja[$gId][array_rand($animalPorGranja[$gId])] : null;
                $terreno = !empty($terrenoPorGranja[$gId]) ? $terrenoPorGranja[$gId][array_rand($terrenoPorGranja[$gId])] : null;
                $tipo    = $tipos[array_rand($tipos)];

                $lat = $granja['lat_base'] + (rand(-100, 100) / 10000);
                $lng = $granja['lng_base'] + (rand(-100, 100) / 10000);
                $ts  = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));

                $alertaRows[] = [
                    'granja_id'  => $gId,
                    'collar_id'  => $collar['id'],
                    'animal_id'  => $animal ? $animal['id'] : null,
                    'terreno_id' => $terreno ? $terreno['id'] : null,
                    'tipo'       => $tipo,
                    'latitud'    => round($lat, 7),
                    'longitud'   => round($lng, 7),
                    'mensaje'    => $mensajes[$tipo],
                    'leida'      => rand(0, 1) === 1,
                    'created_at' => $ts->toDateTimeString(),
                    'updated_at' => $ts->toDateTimeString(),
                ];
            }
        }

        DB::table('alertas')->insert($alertaRows);
    }


}
