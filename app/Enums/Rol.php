<?php

namespace App\Enums;

enum Rol: string
{
    case Superadmin = 'superadmin';
    case Presidente = 'presidente';
    case Tesorero = 'tesorero';
    case Fiscal = 'fiscal';
    case Vocal = 'vocal';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Presidente => 'Presidente',
            self::Tesorero => 'Tesorero',
            self::Fiscal => 'Fiscal',
            self::Vocal => 'Vocal',
        };
    }
}
