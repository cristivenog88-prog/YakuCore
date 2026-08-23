<?php

namespace App\Http\Requests\Anuncios;

use App\Enums\EstadoAnuncio;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnuncioRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:180'],
            'contenido' => ['required', 'string'],
            'fecha_publicacion' => ['required', 'date'],
            'estado' => ['required', Rule::enum(EstadoAnuncio::class)],
        ];
    }
}
