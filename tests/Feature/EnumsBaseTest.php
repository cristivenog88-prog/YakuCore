<?php

use App\Enums\EstadoAnuncio;
use App\Enums\EstadoAsistencia;
use App\Enums\EstadoReunion;
use App\Enums\Periodicidad;
use App\Enums\Rol;
use App\Enums\TipoComprobante;
use App\Enums\TipoPago;

test('Rol tiene los cinco roles del sistema (incluye Fiscal)', function () {
    expect(array_column(Rol::cases(), 'value'))
        ->toBe(['superadmin', 'presidente', 'tesorero', 'fiscal', 'vocal']);
});

test('TipoPago tiene los tres tipos de cobro', function () {
    expect(array_column(TipoPago::cases(), 'value'))
        ->toBe(['mantenimiento', 'consumo_medidor', 'consumo_directo']);
});

test('EstadoReunion tiene los tres estados', function () {
    expect(array_column(EstadoReunion::cases(), 'value'))
        ->toBe(['programada', 'cancelada', 'finalizada']);
});

test('EstadoAnuncio tiene los dos estados', function () {
    expect(array_column(EstadoAnuncio::cases(), 'value'))
        ->toBe(['visible', 'inactivo']);
});

test('TipoComprobante tiene los cuatro tipos', function () {
    expect(array_column(TipoComprobante::cases(), 'value'))
        ->toBe(['boleta', 'factura', 'declaracion_jurada', 'ticket']);
});

test('Periodicidad tiene las cuatro periodicidades', function () {
    expect(array_column(Periodicidad::cases(), 'value'))
        ->toBe(['mensual', 'trimestral', 'semestral', 'anual']);
});

test('EstadoAsistencia tiene los dos estados', function () {
    expect(array_column(EstadoAsistencia::cases(), 'value'))
        ->toBe(['presente', 'ausente']);
});
