<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoActivoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'movimiento_id' => $this->movimiento_id,
            'activo' => $this->whenLoaded('activo', function () {
                return [
                    'id' => $this->activo->id,
                    'codigo' => $this->activo->codigo,
                    'denominacion' => $this->activo->nombre,
                    'descripcion' => $this->activo->descripcion,
                    'marca' => $this->activo->marca,
                    'modelo' => $this->activo->modelo,
                    'numero_serie' => $this->activo->numero_serie
                ];
            }),
            'ubicacion_origen' => $this->whenLoaded('ubicacionOrigen', function () {
                return [
                    'id' => $this->ubicacionOrigen->id,
                    'nombre' => $this->ubicacionOrigen->edificio . ' - ' . $this->ubicacionOrigen->aula
                ];
            }),
            'ubicacion_destino' => $this->whenLoaded('ubicacionDestino', function () {
                return [
                    'id' => $this->ubicacionDestino->id,
                    'nombre' => $this->ubicacionDestino->edificio . ' - ' . $this->ubicacionDestino->aula
                ];
            }),
            'movimiento' => $this->whenLoaded('movimiento', function () {
                return [
                    'id' => $this->movimiento->id,
                    'codigo' => $this->movimiento->codigo,
                    'estado' => $this->movimiento->estado,
                    'fecha_movimiento' => $this->movimiento->fecha_movimiento,
                    'usuario' => $this->whenLoaded('movimiento.usuario', function () {
                        return [
                            'id' => $this->movimiento->usuario->id,
                            'nombre' => $this->movimiento->usuario->nombre,
                            'dni' => $this->movimiento->usuario->dni
                        ];
                    }),
                    'receptor' => $this->whenLoaded('movimiento.receptor', function () {
                        return [
                            'id' => $this->movimiento->receptor->id,
                            'nombre' => $this->movimiento->receptor->nombre,
                            'dni' => $this->movimiento->receptor->dni
                        ];
                    }),
                    'autorizado_por' => $this->whenLoaded('movimiento.autorizadoPor', function () {
                        return [
                            'id' => $this->movimiento->autorizadoPor->id,
                            'nombre' => $this->movimiento->autorizadoPor->nombre
                        ];
                    })
                ];
            }),
            'observaciones' => $this->observaciones,
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
} 