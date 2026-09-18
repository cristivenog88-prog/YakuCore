<?php

namespace App\Http\Requests\Rendiciones;

use App\Models\Rendicion;
use Illuminate\Foundation\Http\FormRequest;

class GenerarRendicionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', Rendicion::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'trimestre' => ['required', 'integer', 'min:1', 'max:4'],
        ];
    }
}
