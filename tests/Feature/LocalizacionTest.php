<?php

test('un error de validación llega en español', function () {
    $response = $this->post('/login', []);

    $response->assertSessionHasErrors('email');

    $errores = session('errors')->getBag('default');

    expect($errores->first('email'))->toContain('obligatorio');
});
