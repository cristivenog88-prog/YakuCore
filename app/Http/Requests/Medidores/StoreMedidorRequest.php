<?php

namespace App\Http\Requests\Medidores;

use App\Services\Tenancy\ContextoJass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedidorRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'correlativo' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('medidores', 'correlativo')->where('jass_id', app(ContextoJass::class)->id()),
            ],
            'lectura_inicial' => ['nullable', 'numeric', 'min:0'],
            'fecha_instalacion' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'correlativo.unique' => 'Ya existe un medidor con este correlativo en esta JASS.',
        ];
    }
}
