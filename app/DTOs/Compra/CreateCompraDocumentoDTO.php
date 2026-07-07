<?php

namespace App\DTOs\Compra;

final readonly class CreateCompraDocumentoDTO
{
    public function __construct(
        public string $tipo_documento,
        public string $serie_documento,
        public string $correlativo_documento,
        public ?string $fecha_documento = null,
        public ?string $observaciones = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            tipo_documento: $data['tipo_documento'],
            serie_documento: $data['serie_documento'],
            correlativo_documento: $data['correlativo_documento'],
            fecha_documento: $data['fecha_documento'] ?? null,
            observaciones: $data['observaciones'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
