<?php

namespace App\Http\Requests\Conciliaciones;

use App\Models\Conciliacion;
use Illuminate\Foundation\Http\FormRequest;

class PrepararConciliacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('preparar', Conciliacion::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'periodo' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'efectivo_verificado' => ['required', 'numeric', 'min:0'],
            'banco_verificado' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
