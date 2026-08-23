<?php

namespace App\Http\Requests\Gastos;

use App\Enums\TipoComprobante;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreGastoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'descripcion' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'tipo_comprobante' => ['required', Rule::enum(TipoComprobante::class)],
            'foto' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(5 * 1024)],
        ];
    }

    public function attributes(): array
    {
        return [
            'foto' => 'foto del comprobante',
        ];
    }
}
