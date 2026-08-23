<?php

namespace App\Http\Requests\Reuniones;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReunionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'lugar' => ['required', 'string', 'max:180'],
            'puntos_tratar' => ['nullable', 'string'],
        ];
    }
}
