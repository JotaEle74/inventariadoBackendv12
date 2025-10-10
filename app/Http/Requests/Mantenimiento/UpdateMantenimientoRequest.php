<?php

namespace App\Http\Requests\Mantenimiento;

use Illuminate\Foundation\Http\FormRequest;

class UdateManteniminentoRequest extends StoreMantenimientoRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'activo_id' => 'sometimes|exists:activos,id',
            'tipo' => 'sometimes|in:preventivo,correctivo,predictivo',
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'costo' => 'sometimes|numeric|min:0',
            'proveedor_id' => 'sometimes|exists:proveedores,id',
            'descripcion' => 'sometimes|string',
            'tecnico' => 'sometimes|string|max:100',
            'estado' => 'sometimes|in:programado,en_proceso,completado,cancelado',
            'notas' => 'nullable|string',
        ];
    }
}