<?php

test('un error de validación llega en español', function () {
    $response = $this->post('/register', []);

    $response->assertSessionHasErrors('name');

    $errores = session('errors')->getBag('default');

    expect($errores->first('name'))->toContain('obligatorio');
});
