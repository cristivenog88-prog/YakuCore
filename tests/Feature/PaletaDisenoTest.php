<?php

test('los tokens de la paleta institucional están definidos en app.css', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('--color-yaku-900: #021f4a')
        ->toContain('--color-yaku-800: #06396c')
        ->toContain('--color-yaku-600: #065b96')
        ->toContain('--color-yaku-400: #6496b1')
        ->toContain('--color-yaku-200: #b1ccdf')
        ->toContain('--color-yaku-exito:')
        ->toContain('--color-yaku-error:')
        ->toContain('--color-yaku-advertencia:');
});
