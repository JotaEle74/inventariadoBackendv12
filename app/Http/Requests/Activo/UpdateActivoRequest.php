<?php

namespace App\Http\Requests\Activo;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class UpdateActivoRequest extends StoreActivoRequest
{
    public function rules()
    {
        return [
            'codigo' => 'sometimes|string|max:50|unique:activos,codigo,'.$this->activo->id,
            'denominacion' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            //'catalogo_id' => 'sometimes|exists:catalogo_bienes,id',
            'marca' => 'sometimes|string|max:100',
            'modelo' => 'sometimes|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'dimension' => 'nullable|string|max:100',
            'aula'=>'nullable|string|max:10',
            'color' => 'nullable|string|max:100',
            'fecha_adquisicion' => 'sometimes|date',
            'valor_inicial' => 'sometimes|numeric|min:0',
            'estado' => 'sometimes|string|in:activo,inactivo',
            'condicion' => 'sometimes|string|in:nuevo,bueno,regular,malo',
            'area_id' => 'nullable|exists:areas,id',
            'piso' => 'nullable|string',
            'responsable_id' => 'nullable|exists:users,id',
            'notas' => 'nullable|string',
            'dniInventariador'=> 'nullable|string',
            'nombreInventariador'=> 'nullable|string',
            'tipo'=>'nullable|string|in:AF,ND,AU',
            'edificio_id'=>'nullable|exists:edificios,id'
        ];
    }
}
