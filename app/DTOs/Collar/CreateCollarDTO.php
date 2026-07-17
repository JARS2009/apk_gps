<?php

namespace App\DTOs\Collar;

use App\Enums\CollarEstado;

final readonly class CreateCollarDTO
{
    public function __construct(
        public string $serie,
        public string $modelo,
        public ?string $imei = null,
        public ?int $animal_id = null,
        public CollarEstado $estado = CollarEstado::Disponible,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            serie: $data['serie'],
            modelo: $data['modelo'],
            imei: $data['imei'] ?? null,
            animal_id: $data['animal_id'] ?? null,
            estado: isset($data['estado'])
                ? ($data['estado'] instanceof CollarEstado ? $data['estado'] : CollarEstado::from($data['estado']))
                : CollarEstado::Disponible,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
