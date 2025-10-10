<?php

namespace App\Http\Requests\Oficina;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOficinaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codigo' => 'required|string|max:50|unique:oficinas,codigo',
            'denominacion' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'denominacion.required' => 'El nombre de la oficina es requerido',
            'codigo.required' => 'El código de la oficina es requerido',
            'codigo.unique' => 'El código de la oficina ya está registrado',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();
        
        $formattedErrors = [];
        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0]; // Tomamos solo el primer mensaje por campo
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