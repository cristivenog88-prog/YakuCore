<?php

namespace App\Enums;

enum Rol: string
{
    case Superadmin = 'superadmin';
    case Presidente = 'presidente';
    case Tesorero = 'tesorero';
    case Vocal = 'vocal';
}
