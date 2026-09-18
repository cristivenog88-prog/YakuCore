<?php

namespace App\Http\Requests\Pagos;

use App\Models\Socio;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMantenimientoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'socio_id' => [
                'required',
                Rule::exists('socios', 'id')->where('jass_id', app(ContextoJass::class)->id()),
            ],
            'vivienda_id' => [
                'required',
                Rule::exists('viviendas', 'id')
                    ->where('jass_id', app(ContextoJass::class)->id())
                    ->where('socio_id', $this->input('socio_id')),
            ],
            'periodo' => ['required', 'date_format:Y-m'],
            // Si la JASS usa POA, el monto lo calcula el servidor a partir de
            // la cuota del POA (CLAUDE.md #2) — el campo del request se
            // ignora en ese caso, así que aquí no se exige.
            'monto' => [Rule::requiredIf(! app(ContextoJass::class)->jass()?->usa_poa), 'numeric', 'min:0.01'],
            'fecha_pago' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'vivienda_id.exists' => 'La vivienda seleccionada no pertenece al socio elegido.',
        ];
    }

    public function socio(): Socio
    {
        return Socio::findOrFail($this->validated('socio_id'));
    }
}
