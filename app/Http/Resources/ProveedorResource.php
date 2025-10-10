<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'ruc' => $this->ruc,
            'contacto_nombre' => $this->contacto_nombre,
            'contacto_telefono' => $this->contacto_telefono,
            'contacto_email' => $this->contacto_email,
            'direccion' => $this->direccion,
            'activo' => (bool) $this->activo,
            'activos_count' => $this->whenCounted('activos'),
            'mantenimientos_count' => $this->whenCounted('mantenimientos'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}