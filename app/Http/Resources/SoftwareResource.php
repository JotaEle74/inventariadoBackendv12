<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SoftwareResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'tipo' => $this->tipo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'responsable' => new UserResource($this->whenLoaded('responsable')),
            'area' => new AreaResource($this->whenLoaded('area')),
            'estado' => $this->estado,
            'notas' => $this->notas,

            // Campos condicionales según el tipo
            'url_acceso' => $this->when($this->tipo === 'desarrollo_interno', $this->url_acceso),
            'tecnologias' => $this->when($this->tipo === 'desarrollo_interno', $this->tecnologias),
            'ubicacion_servidor' => $this->when($this->tipo === 'desarrollo_interno', $this->ubicacion_servidor),

            'clave_licencia' => $this->when($this->tipo === 'licencia_terceros', $this->clave_licencia),
            'tipo_licencia' => $this->when($this->tipo === 'licencia_terceros', $this->tipo_licencia),
            'cantidad_puestos' => $this->when($this->tipo === 'licencia_terceros', $this->cantidad_puestos),
            'fecha_compra' => $this->when($this->tipo === 'licencia_terceros', $this->fecha_compra?->format('Y-m-d')),
            'fecha_vencimiento' => $this->when($this->tipo === 'licencia_terceros', $this->fecha_vencimiento?->format('Y-m-d')),
            'activos_asignados' => ActivoResource::collection($this->whenLoaded('activosAsignados')),
            'plataforma' => $this->when($this->tipo === 'red_social', $this->plataforma),
            'url_perfil' => $this->when($this->tipo === 'red_social', $this->url_perfil),
            'correo_institucional' => $this->when($this->tipo === 'red_social', $this->correo_institucional),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
