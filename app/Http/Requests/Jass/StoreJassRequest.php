<?php

namespace App\Http\Requests\Jass;

use App\Enums\Periodicidad;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJassRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120', 'unique:jass,nombre'],
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
            // Solo se pregunta cuando la JASS no tiene medidor: si elabora
            // su Plan Operativo Anual, la cuota de mantenimiento se calcula
            // desde el POA en vez de ingresarse a mano al cobrar.
            'usa_poa' => [
                Rule::requiredIf(fn () => ! $this->boolean('tiene_medidor')),
                'nullable',
                'boolean',
            ],
            'periodicidad_mantenimiento' => ['required', Rule::enum(Periodicidad::class)],
            'dia_vencimiento' => ['required', 'integer', 'between:1,28'],
        ];
    }

    public function messages(): array
    {
        return [
            'tarifa_base_m3.required' => 'La tarifa base por m³ es obligatoria cuando la JASS tiene medidor.',
            'usa_poa.required' => 'Indica si la JASS usará el Plan Operativo Anual (POA) para calcular su cuota.',
        ];
    }
}
