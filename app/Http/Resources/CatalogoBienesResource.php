<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CatalogoBienesResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'denominacion' => $this->denominacion
        ];
    }
}