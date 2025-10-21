<?php

namespace App\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAreaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            //'edificio' => 'required|string|max:100',
            'codigo' => 'required|string|max:100',
            'aula' => 'required|string|max:50',
            'oficina_id' => 'nullable|exists:oficinas,id'
        ];
    }

    public function messages()
    {
        return [
            'edificio.required' => 'El edificio es requerido',
            'aula.required' => 'El aula es requerida',
            'oficina_id.required' => 'El Oficina es requerido',
            'oficina_id.exists' => 'El oficina seleccionado no existe',
            'codigo.required' => 'El codigo es requerido',
            //'codigo.unique' => 'El codigo ya existe'
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();

        $formattedErrors = [];
        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0];
        }

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $formattedErrors
            ], 422)
        );
    }
} 