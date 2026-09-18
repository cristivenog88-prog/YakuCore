<?php

namespace App\Enums;

/**
 * Las cinco categorías de actividad del Plan Operativo Anual (guía POA,
 * paso 1): cada actividad del plan pertenece a una sola.
 */
enum CategoriaActividadPoa: string
{
    case Operacion = 'operacion';
    case Mantenimiento = 'mantenimiento';
    case Administracion = 'administracion';
    case ReposicionEquipos = 'reposicion_equipos';
    case RehabilitacionesMenores = 'rehabilitaciones_menores';
}
