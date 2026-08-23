<?php

/**
 * Guarda el DoD de la Fase 1.5 (docs/design-system.md): ninguna vista usa
 * colores por defecto de Tailwind fuera de los tokens del tema. Excluye
 * components/ui (primitivas shadcn sin colores propios) y welcome.tsx
 * (contenido genérico del starter kit, se reemplaza por completo en la
 * Fase 8 — spec §6.13).
 */
test('ninguna página o componente propio usa colores por defecto de Tailwind', function () {
    $patron = '/-(red|green|blue|yellow|indigo|purple|pink|orange|teal|cyan|amber|lime|emerald|rose|violet|fuchsia|sky|neutral|gray|slate|zinc|stone)-[0-9]{2,3}/';

    $directorios = [
        resource_path('js/pages'),
        resource_path('js/components'),
        resource_path('js/layouts'),
    ];

    $excluidos = [
        resource_path('js/pages/welcome.tsx'),
    ];

    $infracciones = [];

    foreach ($directorios as $directorio) {
        $archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio));

        foreach ($archivos as $archivo) {
            if (! $archivo->isFile() || $archivo->getExtension() !== 'tsx') {
                continue;
            }

            if (str_contains($archivo->getPathname(), DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'ui'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            if (in_array(str_replace('\\', '/', $archivo->getPathname()), array_map(fn ($p) => str_replace('\\', '/', $p), $excluidos), true)) {
                continue;
            }

            $contenido = file_get_contents($archivo->getPathname());

            if (preg_match($patron, $contenido)) {
                $infracciones[] = $archivo->getPathname();
            }
        }
    }

    expect($infracciones)->toBe([]);
});

test('ninguna página implementa su propia tabla — todas usan TablaResponsiva', function () {
    $directorio = resource_path('js/pages');
    $archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio));

    $infracciones = [];

    foreach ($archivos as $archivo) {
        if (! $archivo->isFile() || $archivo->getExtension() !== 'tsx') {
            continue;
        }

        $contenido = file_get_contents($archivo->getPathname());

        if (str_contains($contenido, '<table')) {
            $infracciones[] = $archivo->getPathname();
        }
    }

    expect($infracciones)->toBe([]);
});
