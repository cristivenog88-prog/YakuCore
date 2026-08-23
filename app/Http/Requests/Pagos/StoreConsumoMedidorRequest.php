<?php

namespace App\Http\Requests\Pagos;

use App\Models\Medidor;
use App\Models\Socio;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsumoMedidorRequest extends FormRequest
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
            'medidor_id' => [
                'required',
                Rule::exists('medidores', 'id')->where('jass_id', app(ContextoJass::class)->id()),
                function ($attribute, $value, $fail) {
                    $perteneceAlSocio = Medidor::whereKey($value)
                        ->whereHas('vivienda', fn ($q) => $q->where('socio_id', $this->input('socio_id')))
                        ->exists();

                    if (! $perteneceAlSocio) {
                        $fail('El medidor seleccionado no pertenece al socio elegido.');
                    }
                },
            ],
            'periodo' => ['required', 'date_format:Y-m'],
            'lectura_actual' => ['required', 'numeric', 'min:0'],
            'fecha_pago' => ['required', 'date'],
        ];
    }

    public function socio(): Socio
    {
        return Socio::findOrFail($this->validated('socio_id'));
    }
}
