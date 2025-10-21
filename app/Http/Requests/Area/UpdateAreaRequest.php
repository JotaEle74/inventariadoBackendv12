<?php

namespace App\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAreaRequest extends StoreAreaRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            //'edificio' => 'sometimes|string|max:100',
            'codigo' => 'sometimes|string|max:50',
            'aula' => 'sometimes|string|max:50',
            'oficina_id' => 'nullable|exists:oficinas,id'
        ];
    }

    public function messages()
    {
        return [
            'edificio.string' => 'El edificio debe ser texto',
            'codigo.exists' => 'El codigo seleccionado ya existe',
            'codigo.max' => 'El codigo debe ser menor a 50 caracteres',
            'codigo.string' => 'El codigo debe ser texto',
            //'codigo.unique' => 'El codigo seleccionado ya existe',
            'aula.string' => 'El aula debe ser texto',
            'oficina_id.exists' => 'La oficina seleccionada no existe'
        ];
    }
} 