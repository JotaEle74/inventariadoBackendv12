<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            //'edificio' => $this->edificio,
            'codigo' => $this->codigo,
            'aula' => $this->aula,
            'oficina' => new OficinaResource($this->whenLoaded('oficina')),
            //'activos_count' => $this->whenCounted('activos'),
            //'activos' => ActivoResource::collection($this->whenLoaded('activos')),
            //'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            //'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}