<?php

namespace App\Http\Requests\Reuniones;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A diferencia de StoreReunionRequest, no exige `fecha >= hoy` (plan Fase 6
 * tarea 2: esa validación aplica solo al crear) — la reunión editable ya
 * pasó por esa validación al crearse.
 */
class UpdateReunionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string'],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'lugar' => ['required', 'string', 'max:180'],
            'puntos_tratar' => ['nullable', 'string'],
        ];
    }
}
