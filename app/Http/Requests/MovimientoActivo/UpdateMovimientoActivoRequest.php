<?php

namespace App\Http\Requests\MovimientoActivo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovimientoActivoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'activo_id' => 'sometimes|exists:activos,id',
            'ubicacion_origen_id' => 'sometimes|exists:areas,id',
            'ubicacion_destino_id' => 'sometimes|exists:areas,id',
            'responsable_origen_id' => 'sometimes|exists:users,id',
            'responsable_destino_id' => 'sometimes|exists:users,id',
            'fecha_movimiento' => 'sometimes|date',
            'motivo' => 'sometimes|string|max:255',
            'autorizado_por' => 'sometimes|exists:users,id'
        ];
    }
}