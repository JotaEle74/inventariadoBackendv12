<?php

namespace App\Http\Requests\MovimientoActivo;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimientoActivoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'activo_id' => 'required|exists:activos,id',
            'ubicacion_origen_id' => 'required|exists:areas,id',
            'ubicacion_destino_id' => 'required|exists:areas,id',
            'responsable_origen_id' => 'required|exists:users,id',
            'responsable_destino_id' => 'required|exists:users,id',
            'fecha_movimiento' => 'required|date',
            'motivo' => 'required|string|max:255',
            'autorizado_por' => 'required|exists:users,id'
        ];
    }

    public function messages()
    {
        return [
            'ubicacion_destino_id.required' => 'La ubicación destino es requerida',
            'ubicacion_destino_id.exists' => 'La ubicación destino seleccionada no existe',
            'responsable_destino_id.required' => 'El responsable destino es requerido',
        ];
    }
}