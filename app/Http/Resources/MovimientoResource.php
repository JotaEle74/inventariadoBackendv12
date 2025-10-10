<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'usuario' => [
                'id' => $this->usuario->id,
                'nombre' => $this->usuario->name,
                'oficina' => $this->usuario->oficina ? $this->usuario->oficina->denominacion : 'N/A',
                'entidad' => $this->usuario->oficina && $this->usuario->oficina->entidad ? $this->usuario->oficina->entidad->denominacion : 'N/A'
            ],
            'receptor' => [
                'id' => $this->receptor->id,
                'nombre' => $this->receptor->name,
                'oficina' => $this->receptor->oficina ? $this->receptor->oficina->denominacion : 'N/A',
                'entidad' => $this->receptor->oficina && $this->receptor->oficina->entidad ? $this->receptor->oficina->entidad->denominacion : 'N/A'
            ],
            'fecha_movimiento' => $this->fecha_movimiento,
            'fecha_entrega' => $this->fecha_entrega,
            'fecha_recepcion' => $this->fecha_recepcion,
            'estado' => $this->estado,
            //'ubicacion_destino_id' => $this->ubicacion_destino_id,
            //'observaciones_entrega' => $this->observaciones_entrega,
            'observaciones_recepcion' => $this->observaciones_recepcion,
            'autorizado_por' => $this->whenLoaded('autorizadoPor', function () {
                return [
                    'id' => $this->autorizadoPor->id,
                    'nombre' => $this->autorizadoPor->name
                ];
            }),
            'activos' => $this->whenLoaded('movimientosActivos', function () {
                return $this->movimientosActivos->map(function ($movimientoActivo) {
                    return [
                        'id' => $movimientoActivo->activo->id,
                        'codigo' => $movimientoActivo->activo->codigo,
                        'nombre' => $movimientoActivo->activo->nombre,
                        'descripcion' => $movimientoActivo->activo->descripcion,
                        'marca' => $movimientoActivo->activo->marca,
                        'modelo' => $movimientoActivo->activo->modelo,
                        'numero_serie' => $movimientoActivo->activo->numero_serie,
                        'ubicacion_origen' => $movimientoActivo->ubicacionOrigen ? [
                            'id' => $movimientoActivo->ubicacionOrigen->id,
                            'nombre' => $movimientoActivo->ubicacionOrigen->edificio . ' - ' . $movimientoActivo->ubicacionOrigen->aula
                        ] : null,
                        'ubicacion_destino' => $movimientoActivo->ubicacionDestino ? [
                            'id' => $movimientoActivo->ubicacionDestino->id,
                            'nombre' => $movimientoActivo->ubicacionDestino->edificio . ' - ' . $movimientoActivo->ubicacionDestino->aula
                        ] : null,
                        'observaciones' => $movimientoActivo->observaciones,
                        'estado' => $movimientoActivo->estado
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}