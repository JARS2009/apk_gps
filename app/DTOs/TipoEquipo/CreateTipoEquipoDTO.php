<?php

namespace App\DTOs\TipoEquipo;

final readonly class CreateTipoEquipoDTO
{
    public function __construct(
        public int $granja_id,
        public string $nombre,
        public string $prefijo_codigo,
        public bool $estado = true,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            granja_id: $data['granja_id'],
            nombre: $data['nombre'],
            prefijo_codigo: $data['prefijo_codigo'],
            estado: $data['estado'] ?? true,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
