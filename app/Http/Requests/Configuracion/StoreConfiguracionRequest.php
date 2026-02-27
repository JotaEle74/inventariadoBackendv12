<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreConfiguracionRequest extends FormRequest
{
    public function authorize()
    {
        return true; // permission enforced by routes
    }

    public function rules()
    {
        return [
            'clave' => 'required|string|max:100|unique:configuraciones,clave',
            'mostrar_botones' => 'sometimes|boolean',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'unique' => 'El valor de :attribute ya está en uso.',
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
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
