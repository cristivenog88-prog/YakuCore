<?php

namespace App\Http\Requests\Socios;

use App\Services\Tenancy\ContextoJass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSocioRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'dni' => [
                'required',
                'digits:8',
                Rule::unique('socios', 'dni')
                    ->where('jass_id', app(ContextoJass::class)->id())
                    ->ignore($this->route('socio')),
            ],
            'telefono' => ['nullable', 'string', 'max:15'],
            'direccion' => ['required', 'string', 'max:180'],
        ];
    }

    public function messages(): array
    {
        return [
            'dni.digits' => 'El DNI debe tener 8 dígitos.',
            'dni.unique' => 'Ya existe un socio con este DNI en esta JASS.',
        ];
    }
}
