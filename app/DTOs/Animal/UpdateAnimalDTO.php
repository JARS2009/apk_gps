<?php

namespace App\DTOs\Animal;

final readonly class UpdateAnimalDTO
{
    /**
     * @param  array<int, int>  $terreno_ids
     */
    public function __construct(
        public int $granja_id,
        public string $nombre,
        public string $codigo,
        public ?string $tipo = null,
        public ?string $raza = null,
        public array $terreno_ids = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            granja_id: $data['granja_id'],
            nombre: $data['nombre'],
            codigo: $data['codigo'],
            tipo: $data['tipo'] ?? null,
            raza: $data['raza'] ?? null,
            terreno_ids: $data['terreno_ids'] ?? [],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
