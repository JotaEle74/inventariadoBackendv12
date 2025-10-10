<?php

namespace App\Http\Requests\MovimientoActivo;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimientoMultipleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'activos' => 'required|array|min:1',
            'activos.*.id' => 'required|exists:activos,id',
            'receptor' => 'required|array',
            'receptor.id' => 'required|exists:users,id',
            'receptor.nombre' => 'required|string',
            'receptor.dni' => 'required|string',
            'receptor.oficina' => 'required|string',
            'receptor.entidad' => 'required|string',
            'usuario' => 'required|array',
            'usuario.id' => 'required|exists:users,id',
            'usuario.nombre' => 'required|string',
            'usuario.dni' => 'required|string',
            'usuario.oficina' => 'required|string',
            'usuario.entidad' => 'required|string',
            'observaciones' => 'nullable|string',
            'cambiarUbicacion' => 'required|boolean'
        ];
    }

    public function messages()
    {
        return [
            'activos.required' => 'Debe seleccionar al menos un activo',
            'activos.array' => 'El formato de activos no es válido',
            'activos.min' => 'Debe seleccionar al menos un activo',
            'activos.*.id.required' => 'El ID del activo es requerido',
            'activos.*.id.exists' => 'Uno o más activos seleccionados no existen',
            'receptor.required' => 'La información del receptor es requerida',
            'receptor.id.required' => 'El ID del receptor es requerido',
            'receptor.id.exists' => 'El receptor seleccionado no existe',
            'usuario.required' => 'La información del usuario es requerida',
            'usuario.id.required' => 'El ID del usuario es requerido',
            'usuario.id.exists' => 'El usuario seleccionado no existe',
            'cambiarUbicacion.required' => 'Debe especificar si se cambiará la ubicación'
        ];
    }
} 