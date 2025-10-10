<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'dni' => $this->dni,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'telefono' => $this->telefono,
            'role' => $this->whenLoaded('role', function () {
                return [
                    'id' => $this->role->id,
                    'name' => $this->role->name,
                    'description' => $this->role->description,
                ];
            }),
            'oficinas' => $this->whenLoaded('oficinas', function () {
                return $this->oficinas->map(function ($oficina) {
                    return [
                        'id' => $oficina->id,
                        'denominacion' => $oficina->denominacion,
                        'codigo' => $oficina->codigo,
                        'entidad' => $oficina->entidad ? [
                            'id' => $oficina->entidad->id,
                            'denominacion' => $oficina->entidad->denominacion,
                            'codigo' => $oficina->entidad->codigo,
                        ] : null,
                    ];
                });
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
} 