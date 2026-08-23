<?php

namespace App\Enums;

enum TipoPago: string
{
    case Mantenimiento = 'mantenimiento';
    case ConsumoMedidor = 'consumo_medidor';
    case ConsumoDirecto = 'consumo_directo';
}
