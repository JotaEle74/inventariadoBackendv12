<?php

namespace App\Http\Requests\oficina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOficinaRequest extends StoreOficinaRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codigo' => 'sometimes|string|max:50|unique:oficinas,codigo,' . $this->oficina->id,
            'denominacion' => 'sometimes|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'denominacion.string' => 'El nombre del oficina debe ser texto',
            'codigo.unique' => 'El código del oficina ya está registrado'
        ];
    }
} 