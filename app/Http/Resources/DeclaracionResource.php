<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeclaracionResource extends JsonResource
{
    public function toArray($request)
    {
        \Log::info('Valor de $this->activos en DeclaracionResource:', ['activos' => $this->activos]);
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'fecha_declaracion' => $this->fecha_declaracion,
            'observaciones' => $this->observaciones,
            'ito' => $this->ito,
            'user' => new UserResource($this->whenLoaded('user')),
            'activos_count' => $this->whenLoaded('activos', function() { return $this->activos->count(); }),
            'activos' => ActivoResource::collection($this->whenLoaded('activos')),
            'activos_declaracion' => $this->activos->map(function($activo) {
                return new ActivoDeclaracionResource($activo->pivot);
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
} 