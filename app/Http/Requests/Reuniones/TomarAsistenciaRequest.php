<?php

namespace App\Http\Requests\Reuniones;

use App\Enums\EstadoAsistencia;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TomarAsistenciaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asistencias' => ['required', 'array', 'min:1'],
            'asistencias.*.socio_id' => [
                'required',
                'integer',
                Rule::exists('socios', 'id')->where('jass_id', app(ContextoJass::class)->id()),
                'distinct',
            ],
            'asistencias.*.estado' => ['required', Rule::enum(EstadoAsistencia::class)],
        ];
    }
}
