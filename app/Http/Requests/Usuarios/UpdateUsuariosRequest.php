<?php

namespace App\Http\Requests\Usuarios;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUsuariosRequest extends StoreUsuariosRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255', // 'sometimes' = opcional en actualización
            'email' => 'sometimes|string|email|max:255|unique:usuarios,email,' . $this->route('id'),
            'dni' => 'sometimes|string|min:8|max:8|unique:usuarios,dni,' . $this->route('id'),
            'password' => 'sometimes|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?.&]+$/',
            'telefono' => 'sometimes|string|min:7|max:15|regex:/^[0-9\s\-\+\(\)]+$/',
            'role_id' => 'sometimes|exists:roles,id',   
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El nombre del usuario es requerido',
            'email.required' => 'El email es requerido',
            'email.unique' => 'Este email ya está registrado por otro usuario',
            'dni.required' => 'El DNI es requerido',
            'dni.min' => 'El DNI debe tener 8 caracteres',
            'dni.max' => 'El DNI debe tener 8 caracteres',
            'dni.unique' => 'Este DNI ya está registrado por otro usuario',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.regex' => 'La contraseña debe incluir mayúsculas, minúsculas, números y símbolos (@$!%*?&)',
            'telefono.required' => 'El teléfono es requerido',
            'telefono.regex' => 'Formato de teléfono inválido',
            'role_id.exists' => 'El rol seleccionado no existe',
        ];
    }
}