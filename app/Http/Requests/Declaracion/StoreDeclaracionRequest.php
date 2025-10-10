<?php

namespace App\Http\Requests\Declaracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreDeclaracionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'fecha_declaracion' => 'nullable|date',
            'observaciones' => 'nullable|string',
            'ito' => 'nullable|string',
            'activos' => 'required|array|min:1',
            'activos.*' => 'exists:activos,id',
        ];
    }

    public function messages()
    {
        return [
            'user_id.required' => 'El usuario es requerido',
            'user_id.exists' => 'El usuario no existe',
            'tipo.required' => 'El tipo es requerido',
            'tipo.max' => 'El tipo no debe exceder 50 caracteres',
            'fecha_declaracion.date' => 'La fecha debe ser válida',
            'activos.required' => 'Debe asociar al menos un activo',
            'activos.array' => 'El campo activos debe ser un arreglo',
            'activos.*.exists' => 'Algún activo seleccionado no existe',
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