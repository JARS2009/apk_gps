<?php

namespace App\DTOs\Compra;

final readonly class CreateCompraDTO
{
    public function __construct(
        public int $granja_id,
        public string $serie,
        public string $correlativo,
        public string $fecha,
        public ?string $proveedor = null,
        public ?string $observaciones = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            granja_id: $data['granja_id'],
            serie: $data['serie'],
            correlativo: $data['correlativo'],
            fecha: $data['fecha'],
            proveedor: $data['proveedor'] ?? null,
            observaciones: $data['observaciones'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
