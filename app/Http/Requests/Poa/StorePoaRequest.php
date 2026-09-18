<?php

namespace App\Http\Requests\Poa;

use App\Enums\CategoriaActividadPoa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePoaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'otros_ingresos' => ['nullable', 'numeric', 'min:0'],
            'actividades' => ['required', 'array', 'min:1'],
            'actividades.*.categoria' => ['required', Rule::enum(CategoriaActividadPoa::class)],
            'actividades.*.descripcion' => ['required', 'string', 'max:180'],
            'actividades.*.frecuencia_anual' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'actividades.*.costo_unitario' => ['required', 'numeric', 'min:0'],
            'actividades.*.mes' => ['nullable', 'string', 'max:60'],
            'actividades.*.responsable' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'otros_ingresos' => 'otros ingresos',
            'actividades.*.categoria' => 'categoría',
            'actividades.*.descripcion' => 'descripción',
            'actividades.*.frecuencia_anual' => 'frecuencia anual',
            'actividades.*.costo_unitario' => 'costo unitario',
        ];
    }
}
