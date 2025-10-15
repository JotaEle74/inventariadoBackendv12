<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'dni' => 'required|string|min:8|max:8|unique:users',
            'password' => 'required|string|min:8',
            'telefono' => 'required|string|min:7|max:15|regex:/^[0-9\s\-\+\(\)]+$/',
            'oficinas' => 'required|array',
            'oficinas.*' => 'exists:oficinas,id',
            'role_id' => 'nullable|exists:roles,id',
            'email_verified_at' => 'nullable',
            'grupo'=>'nullable|string'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El nombre del usuario es requerido',
            'email.required' => 'El email del usuario es requerido',
            'email.email' => 'El email debe ser una dirección válida',
            'email.unique' => 'Este email ya está registrado',
            'dni.required' => 'El DNI del usuario es requerido',
            'dni.min' => 'El DNI debe tener exactamente 8 caracteres',
            'dni.max' => 'El DNI debe tener exactamente 8 caracteres',
            'dni.unique' => 'Este DNI ya está registrado',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'La confirmación de contraseña no coincide',
            'password.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial',
            'telefono.required' => 'El teléfono es requerido',
            'telefono.regex' => 'El formato del teléfono no es válido',
            'oficinas.required' => 'Las oficinas son requeridas',
            'oficinas.array' => 'El campo oficinas debe ser un array',
            'oficinas.*.exists' => 'Una o más oficinas seleccionadas no existen',
            'role_id.exists' => 'El rol seleccionado no existe'
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