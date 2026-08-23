<?php

test('las cabeceras de seguridad están presentes en la respuesta', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Content-Security-Policy');

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'none'");
});

test('no envía Strict-Transport-Security fuera de HTTPS', function () {
    $response = $this->get('/');

    $response->assertHeaderMissing('Strict-Transport-Security');
});

test('sí envía Strict-Transport-Security en HTTPS', function () {
    $response = $this->get('https://localhost/');

    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
