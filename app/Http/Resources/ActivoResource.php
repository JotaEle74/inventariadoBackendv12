<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
class ActivoResource extends JsonResource
{  
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'denominacion' => $this->denominacion,
            'descripcion' => $this->descripcion,
            //'notas' => $this->notas,
            //'catalogo' => [
            //    'id' => $this->catalogo->id,
            //    'denominacion' => $this->catalogo->denominacion,
            //    'codigo' => $this->catalogo->codigo
            //],
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'numero_serie' => $this->numero_serie,
            'dimension'=>$this->dimension,
            'aula'=>$this->aula,
            'color' => $this->color,
            'fecha_adquisicion' => $this->fecha_adquisicion->format('Y-m-d'),
            'valor_inicial' => (float) $this->valor_inicial,
            'estado' => $this->estado,
            'estado_display' => $this->getEstadoDisplay(),
            'condicion' => $this->condicion,
            'condicion_display' => $this->getCondicionDisplay(),
            'area' => $this->area ? [
                'id' => $this->area->id,
                'codigo' => $this->codigo,
                'nombre_completo' => $this->area->edificio . ' - ' . 
                                    $this->area->aula,
                
                'oficina' => $this->area->oficina ? [
                    'id' => $this->area->oficina->id,
                    'denominacion' => $this->area->oficina->denominacion,
                    'codigo' => $this->area->oficina->codigo
                ] : null
            ] : null,
            'piso' => $this->piso,
            'responsable' => $this->responsable ? [
                'id' => $this->responsable->id,
                'name' => $this->responsable->name,
                'dni' => $this->responsable->dni
            ] : null,
            'movimientos' => MovimientoResource::collection($this->whenLoaded('movimientos')),
            //'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            //'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
    
    protected function getEstadoDisplay()
    {
        $estados = [
            'activo' => 'Activo',
            'inactivo' => 'Inactivo'
        ];
        
        return $estados[$this->estado] ?? $this->estado;
    }
    
    protected function getCondicionDisplay()
    {
        $condiciones = [
            'nuevo' => 'Nuevo',
            'bueno' => 'Bueno',
            'regular' => 'Regular',
            'malo' => 'Malo',
        ];
        
        return $condiciones[$this->condicion] ?? $this->condicion;
    }
}