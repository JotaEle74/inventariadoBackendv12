<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Movimiento\StoreMovimientoRequest;
use App\Http\Resources\MovimientoResource;
use App\Models\Inventariado\Movimiento;
use App\Models\Inventariado\MovimientoActivo;
use Exception;
use Illuminate\Http\JsonResponse;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;

class MovimientoController extends BaseController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = Movimiento::query()->with([
                'usuario.oficinas.entidad',
                'receptor.oficinas.entidad',
                'autorizadoPor',
                'movimientosActivos.activo',
                'movimientosActivos.ubicacionOrigen',
                'movimientosActivos.ubicacionDestino'
            ]);

            $user = $request->user();
            if ($user && ($user->hasRole('responsable_departamento') || $user->hasRole('usuario_consulta'))) {
                $query->where(function($q) use ($user) {
                    $q->where('responsable_origen_id', $user->id)
                      ->orWhere(function($q2) use ($user) {
                          $q2->where('responsable_destino_id', $user->id)
                             // Muestra los movimientos que están por recibir o ya gestionados
                             ->whereIn('estado', ['en_entrega', 'entregado', 'rechazado']); 
                      });
                });
            }

            if($request->has('search')){
                $search = $request->search;
                $query->where(function($q) use ($search){
                    $q->where('codigo', 'like', "%{$search}%")
                        ->orWhereHas('usuario', function($q2) use ($search) {
                            $q2->where('name', 'like', '%'.$search.'%');
                        });
                });
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('codigo')) {
                $query->where('codigo', 'like', '%' . $request->codigo . '%');
            }

            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $query->whereBetween('fecha_movimiento', [
                    $request->fecha_desde,
                    $request->fecha_hasta
                ]);
            }

            // Ordenación
            if ($request->has('sort_by')) {
                $sortDirection = $request->boolean('desc', false) ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->latest('fecha_movimiento');
            }

            // Paginación
            $perPage = $request->integer('per_page', 15);
            $movimientos = $query->paginate($perPage);

            return MovimientoResource::collection($movimientos);
        } catch (Exception $e) {
            Log::error('Error al listar movimientos: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function store(StoreMovimientoRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            if (!Auth::check()) {
                throw new Exception('Usuario no autenticado');
            }

            $data = $request->validated();
            
            // Crear el movimiento
            $movimiento = Movimiento::create([
                'responsable_origen_id' => $data['usuario']['id'],
                'responsable_destino_id' => $data['receptor']['id'],
                'ubicacion_origen_id' => $data['ubicacion_origen_id'],
                'ubicacion_destino_id' => $data['ubicacion_destino_id'],
                'fecha_movimiento' => now(),
                'estado' => 'pendiente',
                'observaciones_entrega' => $data['observaciones'] ?? null,
                'autorizado_por' => Auth::id()
            ]);

            // Crear los movimientos de activos
            foreach ($data['activos'] as $activoData) {
                MovimientoActivo::create([
                    'movimiento_id' => $movimiento->id,
                    'activo_id' => $activoData['id'],
                    'ubicacion_origen_id' => $activoData['area']['id'],
                    'ubicacion_destino_id' => $data['cambiarUbicacion'] ? $data['ubicacion']['value']:$activoData['area']['id'],
                    'observaciones' => $activoData['observaciones'] ?? null
                ]);
            }
            // Cargar relaciones para la respuesta
            $movimiento->load([
                'usuario.oficinas.entidad',
                'receptor.oficinas.entidad',
                'autorizadoPor',
                'movimientosActivos.activo',
                'movimientosActivos.ubicacionOrigen',
                'movimientosActivos.ubicacionDestino'
            ]);

            DB::commit();

            return $this->successResponse(
                new MovimientoResource($movimiento),
                'Movimiento registrado exitosamente',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar movimiento: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function show(Movimiento $movimiento): JsonResponse
    {
        try {
            $movimiento->load([
                'usuario.oficinas.entidad',
                'receptor.oficinas.entidad',
                'autorizadoPor',
                'movimientosActivos.activo',
                'movimientosActivos.ubicacionOrigen',
                'movimientosActivos.ubicacionDestino'
            ]);

            return $this->successResponse(
                new MovimientoResource($movimiento),
                'Movimiento obtenido exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al obtener movimiento: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function entregar(Movimiento $movimiento, Request $request): JsonResponse
    {
        try {
            $movimiento->marcarComoEntregado($request->observaciones);

            return $this->successResponse(
                new MovimientoResource($movimiento->fresh()->load([
                    'usuario.oficinas.entidad',
                    'receptor.oficinas.entidad',
                    'autorizadoPor',
                    'movimientosActivos.activo',
                    'movimientosActivos.ubicacionOrigen',
                    'movimientosActivos.ubicacionDestino'
                ])),
                'Movimiento marcado como entregado exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al marcar movimiento como entregado: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function recibir(Movimiento $movimiento, Request $request): JsonResponse
    {
        try {
            $movimiento->marcarComoRecibido($request->observaciones);

            return $this->successResponse(
                new MovimientoResource($movimiento->fresh()->load([
                    'usuario.oficinas.entidad',
                    'receptor.oficinas.entidad',
                    'autorizadoPor',
                    'movimientosActivos.activo',
                    'movimientosActivos.ubicacionOrigen',
                    'movimientosActivos.ubicacionDestino'
                ])),
                'Movimiento marcado como recibido exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al marcar movimiento como recibido: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function rechazar(Movimiento $movimiento, Request $request): JsonResponse
    {
        try {
            $movimiento->rechazar($request->observaciones);

            return $this->successResponse(
                new MovimientoResource($movimiento->fresh()->load([
                    'usuario.oficinas.entidad',
                    'receptor.oficinas.entidad',
                    'autorizadoPor',
                    'movimientosActivos.activo',
                    'movimientosActivos.ubicacionOrigen',
                    'movimientosActivos.ubicacionDestino'
                ])),
                'Movimiento rechazado exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al rechazar movimiento: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function generarPDF(Movimiento $movimiento)
    {
        try {
            $movimiento->load([
                'usuario.oficinas.entidad',
                'receptor.oficinas.entidad',
                'autorizadoPor',
                'ubicacionOrigen.entidad',
                'ubicacionDestino.entidad',
                'movimientosActivos.ubicacionOrigen',
                'movimientosActivos.ubicacionDestino'
            ]);
            if (!$movimiento->usuario || !$movimiento->receptor || !$movimiento->autorizadoPor) {
                throw new Exception('El movimiento no tiene todos los datos necesarios para generar el PDF');
            }
            $pdf = PDF::loadView('pdf.movimiento', [
                'movimiento' => $movimiento
            ]);

            // Configurar el PDF
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('margin-top', 10);
            $pdf->setOption('margin-right', 10);
            $pdf->setOption('margin-bottom', 10);
            $pdf->setOption('margin-left', 10);

            // Devolver el PDF como stream
            return $pdf->stream('movimiento-' . $movimiento->codigo . '.pdf');

        } catch (Exception $e) {
            Log::error('Error al generar PDF del movimiento: ' . $e->getMessage());
            return $this->errorResponse('Error al generar el PDF: ' . $e->getMessage(), 500);
        }
    }
} 