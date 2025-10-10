<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class OficinaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'denominacion' => $this->denominacion,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}