<?php

namespace App\Http\Requests\Jass;

use App\Enums\Periodicidad;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJassRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120', Rule::unique('jass', 'nombre')->ignore($this->route('jass'))],
            'comunidad' => ['required', 'string', 'max:120'],
            'distrito' => ['required', 'string', 'max:120'],
            'tiene_medidor' => ['required', 'boolean'],
            'tarifa_base_m3' => [
                Rule::requiredIf(fn () => $this->boolean('tiene_medidor')),
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'periodicidad_mantenimiento' => ['required', Rule::enum(Periodicidad::class)],
            'dia_vencimiento' => ['required', 'integer', 'between:1,28'],
        ];
    }

    public function messages(): array
    {
        return [
            'tarifa_base_m3.required' => 'La tarifa base por m³ es obligatoria cuando la JASS tiene medidor.',
        ];
    }
}
