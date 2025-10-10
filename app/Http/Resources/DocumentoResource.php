<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'activo_id' => $this->activo_id,
            'tipo' => $this->tipo,
            'ruta' => $this->ruta,
            'fecha_subida' => $this->fecha_subida,
            'subido_por' => $this->subido_por,
            'notas' => $this->notas,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Relaciones
            'activo' => new ActivoResource($this->whenLoaded('activo')),
            'usuario' => new UserResource($this->whenLoaded('usuario')),
        ];
    }
}