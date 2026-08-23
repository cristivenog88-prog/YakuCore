<?php

namespace App\Enums;

enum EstadoReunion: string
{
    case Programada = 'programada';
    case Cancelada = 'cancelada';
    case Finalizada = 'finalizada';
}
