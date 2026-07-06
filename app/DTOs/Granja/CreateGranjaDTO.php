<?php

namespace App\DTOs\Granja;

final readonly class CreateGranjaDTO
{
    public function __construct(
        public string $nombre,
        public ?string $descripcion = null,
        public bool $activa = true,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            activa: $data['activa'] ?? true,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
