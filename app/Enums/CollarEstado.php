<?php

namespace App\Enums;

enum CollarEstado: string
{
    case Disponible = 'disponible';
    case Asignado = 'asignado';
    case Inactivo = 'inactivo';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Asignado => 'Asignado',
            self::Inactivo => 'Inactivo',
        };
    }
}
