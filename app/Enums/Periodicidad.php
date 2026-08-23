<?php

namespace App\Enums;

enum Periodicidad: string
{
    case Mensual = 'mensual';
    case Trimestral = 'trimestral';
    case Semestral = 'semestral';
    case Anual = 'anual';
}
