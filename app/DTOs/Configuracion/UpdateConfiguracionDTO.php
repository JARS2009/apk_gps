<?php

namespace App\DTOs\Configuracion;

final readonly class UpdateConfiguracionDTO
{
    public function __construct(
        public ?string $telefono_policia = null,
        public ?string $telefono_emergencia = null,
        public ?string $mensaje_alerta = null,
        public bool $alertas_activas = false,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            telefono_policia: $data['telefono_policia'] ?? null,
            telefono_emergencia: $data['telefono_emergencia'] ?? null,
            mensaje_alerta: $data['mensaje_alerta'] ?? null,
            alertas_activas: $data['alertas_activas'] ?? false,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
