<?php

namespace App\Http\Requests\Medidores;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReemplazarMedidorRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:500'],
        ];
    }
}
