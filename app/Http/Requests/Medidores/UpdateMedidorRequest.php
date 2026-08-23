<?php

namespace App\Http\Requests\Medidores;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * El correlativo (y por lo tanto el código) no se edita: se fija al crear
 * el medidor. Cambios de lectura base van por el flujo de reemplazo (8.3).
 */
class UpdateMedidorRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lectura_inicial' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['boolean'],
            'fecha_instalacion' => ['nullable', 'date'],
        ];
    }
}
