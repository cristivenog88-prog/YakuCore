<?php

namespace App\Http\Requests\Viviendas;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreViviendaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'direccion' => ['required', 'string', 'max:180'],
            'activa' => ['boolean'],
        ];
    }
}
