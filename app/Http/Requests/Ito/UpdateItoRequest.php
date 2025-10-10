<?php

namespace App\Http\Requests\Ito;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItoRequest extends StoreItoRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codigo' => 'sometimes|string|max:50|unique:itos,codigo,' . $this->ito->id,
            'estado' => 'sometimes|nullable|boolean'
        ];
    }

    public function messages()
    {
        return [
            'codigo.unique' => 'El código del Ito ya está registrado',
            'estado.boolean' => 'El esatdo del Ito debe ser verdadero o falso'
        ];
    }
} 