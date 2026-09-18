<?php

namespace App\Enums;

enum TipoComprobante: string
{
    case Boleta = 'boleta';
    case Factura = 'factura';
    case DeclaracionJurada = 'declaracion_jurada';
    case Ticket = 'ticket';
}
