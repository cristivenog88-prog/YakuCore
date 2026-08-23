<?php

namespace App\Http\Requests\Publico;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConsultaDeudaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dni' => ['required', 'digits:8'],
            'correlativo_medidor' => ['nullable', 'string', 'max:20'],
        ];
    }
}
