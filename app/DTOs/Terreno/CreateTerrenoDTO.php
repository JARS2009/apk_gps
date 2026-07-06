<?php

namespace App\DTOs\Terreno;

final readonly class CreateTerrenoDTO
{
    /**
     * @param  array<int, array<string, float>>  $coordenadas
     */
    public function __construct(
        public int $granja_id,
        public string $nombre,
        public array $coordenadas,
        public ?float $area = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            granja_id: $data['granja_id'],
            nombre: $data['nombre'],
            coordenadas: $data['coordenadas'],
            area: $data['area'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
