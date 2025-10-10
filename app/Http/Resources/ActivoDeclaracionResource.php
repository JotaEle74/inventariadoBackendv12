<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivoDeclaracionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'activo_id' => $this->activo_id,
            'entidad_denominacion' => $this->entidad_denominacion,
            'area_edificio' => $this->area_edificio,
            'oficina_denominacion' => $this->oficina_denominacion,
            'area_codigo' => $this->area_codigo,
            'estado' => $this->estado,
            'condicion' => $this->condicion,
            'ubicacion' => $this->ubicacion,
            'descripcion' => $this->descripcion,
        ];
    }
} 