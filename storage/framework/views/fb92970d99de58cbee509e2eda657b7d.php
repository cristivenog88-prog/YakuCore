<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo e($informe->codigo); ?></title>
    <style>
        @page { margin: 90px 60px 80px 60px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; line-height: 1.4; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 4px 0; }
        h2 { font-size: 12px; margin: 20px 0 8px 0; border-bottom: 1px solid #999; padding-bottom: 4px; }
        p { margin: 6px 0; text-align: justify; }
        .subtitle { text-align: center; margin-bottom: 4px; font-weight: bold; }
        .meta { text-align: center; font-size: 10px; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 10px; }
        th, td { border: 1px solid #bbb; padding: 5px 8px; text-align: left; }
        th { background: #f2f4f7; }
        td.num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
        .total { font-weight: bold; background: #f8f8f8; }
        .header-block { margin-bottom: 12px; }
        .header-block td { border: none; padding: 3px 0; }
        .header-block td:first-child { width: 120px; font-weight: bold; }
        .firma { margin-top: 40px; }
        .firma td { border: none; text-align: center; padding-top: 40px; border-top: 1px solid #000; width: 33%; }
        .norma { font-size: 9px; color: #666; }
        .aviso { background: #fff8e6; border: 1px solid #e6c66e; padding: 8px; margin: 10px 0; font-size: 10px; }
    </style>
</head>
<body>

<?php
    $trimestreRoman = ['', 'I', 'II', 'III', 'IV'][$rendicion->trimestre] ?? '';
    $ejecucion = $rendicion->ejecucion_por_categoria ?? [];
    $etiquetasCategoria = [
        'operacion' => 'Operación',
        'mantenimiento' => 'Mantenimiento',
        'administracion' => 'Administración',
        'reposicion_equipos' => 'Reposición de equipos',
        'rehabilitaciones_menores' => 'Rehabilitaciones menores',
        'sin_categoria' => 'Sin categoría',
    ];
?>

<h1><?php echo e($informe->codigo); ?></h1>
<div class="subtitle">INFORME DE GESTIÓN FINANCIERA</div>
<div class="meta">
    <?php echo e($jass->nombre); ?> · Trimestre <?php echo e($trimestreRoman); ?> de <?php echo e($rendicion->anio); ?>

    <?php if($rendicion->fecha_asamblea): ?> · Asamblea del <?php echo e($rendicion->fecha_asamblea->format('d/m/Y')); ?> <?php endif; ?>
</div>

<table class="header-block">
    <tr><td>A</td><td>Asamblea General de Asociados de la JASS</td></tr>
    <tr><td>DE</td><td>Consejo Directivo de la JASS</td></tr>
    <tr><td>ASUNTO</td><td>Rendición de la gestión financiera del trimestre</td></tr>
    <tr><td>FECHA</td><td><?php echo e($informe->generado_at->format('d/m/Y')); ?></td></tr>
</table>

<h2>Resumen ejecutivo</h2>
<p>
    En el trimestre <?php echo e($trimestreRoman); ?> de <?php echo e($rendicion->anio); ?>, la JASS
    <strong><?php echo e($jass->nombre); ?></strong> registró ingresos por pagos de
    <strong>S/ <?php echo e(number_format($rendicion->ingresos_pagos, 2)); ?></strong>
    y otros ingresos por <strong>S/ <?php echo e(number_format($rendicion->otros_ingresos, 2)); ?></strong>.
    Los egresos ejecutados suman <strong>S/ <?php echo e(number_format($rendicion->egresos_total, 2)); ?></strong>,
    resultando en un saldo final de <strong>S/ <?php echo e(number_format($rendicion->saldo_final, 2)); ?></strong>.
</p>

<h2>1. Objeto del informe</h2>
<p>
    El presente informe tiene por objeto comunicar a la Asamblea General la
    recaudación, ejecución y control de los recursos de la JASS del trimestre,
    conforme a las obligaciones de rendición periódica de las organizaciones
    comunales prestadoras del servicio de saneamiento.
</p>

<h2>2. Base normativa</h2>
<table>
    <thead>
        <tr><th>Norma</th><th>Aplicación en la gestión financiera</th></tr>
    </thead>
    <tbody>
        <tr><td>RCD N.° 028-2018-SUNASS-CD</td><td>Metodología para determinar la cuota familiar.</td></tr>
        <tr><td>R.M. N.° 205-2010-VIVIENDA</td><td>Modelo de Estatuto: cobranza, registros y rendición de cuentas.</td></tr>
        <tr><td>D.S. N.° 009-2024-VIVIENDA</td><td>Reglamento aplicable a la prestación de los servicios de agua y saneamiento.</td></tr>
        <tr><td>D.S. N.° 001-2025-VIVIENDA</td><td>Texto Único Ordenado de la Ley del Servicio Universal.</td></tr>
    </tbody>
</table>

<h2>3. Balance del trimestre</h2>
<table>
    <thead>
        <tr><th>Concepto</th><th class="num">Importe (S/)</th></tr>
    </thead>
    <tbody>
        <tr><td>Saldo inicial</td><td class="num"><?php echo e(number_format($rendicion->saldo_inicial, 2)); ?></td></tr>
        <tr><td>Ingresos por pagos (cuota + consumo)</td><td class="num"><?php echo e(number_format($rendicion->ingresos_pagos, 2)); ?></td></tr>
        <tr><td>Otros ingresos</td><td class="num"><?php echo e(number_format($rendicion->otros_ingresos, 2)); ?></td></tr>
        <tr><td>Egresos ejecutados</td><td class="num">(<?php echo e(number_format($rendicion->egresos_total, 2)); ?>)</td></tr>
        <tr class="total"><td>Saldo final del trimestre</td><td class="num"><?php echo e(number_format($rendicion->saldo_final, 2)); ?></td></tr>
    </tbody>
</table>

<h2>4. Ejecución por categoría de POA</h2>
<table>
    <thead>
        <tr><th>Categoría</th><th class="num">Egreso ejecutado (S/)</th></tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $etiquetasCategoria; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(array_key_exists($key, $ejecucion)): ?>
                <tr>
                    <td><?php echo e($label); ?></td>
                    <td class="num"><?php echo e(number_format((float) $ejecucion[$key], 2)); ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <tr class="total">
            <td>TOTAL</td>
            <td class="num"><?php echo e(number_format($rendicion->egresos_total, 2)); ?></td>
        </tr>
    </tbody>
</table>

<h2>5. Control documental</h2>
<?php $chk = $rendicion->checklist ?? []; ?>
<table>
    <thead>
        <tr><th>Elemento</th><th style="width: 60px;">Cumple</th></tr>
    </thead>
    <tbody>
        <tr><td>Libros contables al día</td><td><?php echo e(!empty($chk['libros_al_dia']) ? 'Sí' : 'No'); ?></td></tr>
        <tr><td>Recibos y comprobantes archivados</td><td><?php echo e(!empty($chk['recibos_archivados']) ? 'Sí' : 'No'); ?></td></tr>
        <tr><td>Saldo bancario conciliado</td><td><?php echo e(!empty($chk['saldo_bancario_conciliado']) ? 'Sí' : 'No'); ?></td></tr>
        <tr><td>Inventario actualizado</td><td><?php echo e(!empty($chk['inventario_actualizado']) ? 'Sí' : 'No'); ?></td></tr>
        <tr><td>Informe del fiscal</td><td><?php echo e(!empty($chk['informe_fiscal']) ? 'Sí' : 'No'); ?></td></tr>
    </tbody>
</table>

<h2>6. Conclusiones y recomendaciones</h2>
<p>
    El Consejo Directivo presenta el presente informe para conocimiento,
    evaluación y aprobación de la Asamblea General. Se recomienda: continuar
    la emisión correlativa de recibos, registrar oportunamente ingresos y
    egresos con documento sustentatorio, realizar la conciliación mensual con
    doble firma (Tesorero + Fiscal) y actualizar trimestralmente la ejecución
    del POA.
</p>

<?php if($rendicion->numero_acta): ?>
    <div class="aviso">
        Aprobación registrada en Libro de Actas — <strong>Acta <?php echo e($rendicion->numero_acta); ?></strong>
        <?php if($rendicion->aprobada_at): ?> del <?php echo e($rendicion->aprobada_at->format('d/m/Y')); ?> <?php endif; ?>.
    </div>
<?php endif; ?>

<table class="firma">
    <tr>
        <td>Presidente/a</td>
        <td>Tesorero/a</td>
        <td>Fiscal</td>
    </tr>
</table>

<p class="norma">
    Documento generado automáticamente por YakuCore el <?php echo e($informe->generado_at->format('d/m/Y H:i')); ?>.
    Base: RCD N.° 028-2018-SUNASS-CD, R.M. N.° 205-2010-VIVIENDA, D.S. N.° 009-2024-VIVIENDA.
</p>

</body>
</html>
<?php /**PATH C:\xampp\htdocs\YakuCore\resources\views/informes/gestion-financiera.blade.php ENDPATH**/ ?>