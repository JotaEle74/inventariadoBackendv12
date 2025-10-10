<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\MovimientoActivo\StoreMovimientoActivoRequest;
use App\Http\Requests\MovimientoActivo\StoreMovimientoMultipleRequest;
use App\Http\Requests\MovimientoActivo\UpdateMovimientoActivoRequest;
use App\Http\Resources\MovimientoActivoResource;
use App\Models\Inventariado\Activo;
use App\Models\Inventariado\MovimientoActivo;
use App\Models\Inventariado\Movimiento;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use PDF;

class MovimientoActivoController extends BaseController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = MovimientoActivo::query()->with([
                'activo',
                'ubicacionOrigen',
                'ubicacionDestino',
                'movimiento.usuario',
                'movimiento.receptor',
                'movimiento.autorizadoPor'
            ]);

            // Filtrar por responsable si el usuario es responsable_departamento o usuario_consulta
            $user = $request->user();
            if ($user && ($user->hasRole('responsable_departamento') || $user->hasRole('usuario_consulta'))) {
                $query->whereHas('movimiento', function($q) use ($user) {
                    $q->where('responsable_origen_id', $user->id)
                      ->orWhere('responsable_destino_id', $user->id);
                });
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('activo_id')) {
                $query->where('activo_id', $request->activo_id);
            }

            if ($request->has('ubicacion_origen_id')) {
                $query->where('ubicacion_origen_id', $request->ubicacion_origen_id);
            }

            if ($request->has('ubicacion_destino_id')) {
                $query->where('ubicacion_destino_id', $request->ubicacion_destino_id);
            }

            // Ordenación
            if ($request->has('sort_by')) {
                $sortDirection = $request->boolean('desc', false) ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->latest('created_at');
            }

            // Paginación
            $perPage = $request->integer('per_page', 15);
            $movimientos = $query->paginate($perPage);

            return MovimientoActivoResource::collection($movimientos);
        } catch (Exception $e) {
            Log::error('Error al listar movimientos de activos: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function store(StoreMovimientoActivoRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $movimiento = MovimientoActivo::create($data);

            DB::commit();
            return $this->successResponse(
                new MovimientoActivoResource($movimiento->load([
                    'activo', 
                    'ubicacionOrigen', 
                    'ubicacionDestino', 
                    'movimiento.usuario',
                    'movimiento.receptor',
                    'movimiento.autorizadoPor'
                ])), 
                'Movimiento de activo registrado exitosamente', 
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar movimiento de activo: '.$e->getMessage());
            return $this->handleException($e);
        }
    }

    public function show(MovimientoActivo $movimientoActivo): JsonResponse
    {
        try {
            $movimientoActivo->load([
                'activo', 
                'ubicacionOrigen', 
                'ubicacionDestino', 
                'movimiento.usuario',
                'movimiento.receptor',
                'movimiento.autorizadoPor'
            ]);
            return $this->successResponse(
                new MovimientoActivoResource($movimientoActivo), 
                'Movimiento de activo obtenido exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al obtener movimiento de activo: '. $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function update(UpdateMovimientoActivoRequest $request, MovimientoActivo $movimientoActivo): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            
            // Actualizar el movimiento
            $movimientoActivo->update($data);
            
            DB::commit();
            return $this->successResponse(
                new MovimientoActivoResource($movimientoActivo->fresh()->load([
                    'activo',
                    'ubicacionOrigen',
                    'ubicacionDestino',
                    'movimiento.usuario',
                    'movimiento.receptor',
                    'movimiento.autorizadoPor'
                ])),
                'Movimiento de activo actualizado exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar movimiento de activo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function destroy(MovimientoActivo $movimientoActivo): JsonResponse
    {
        DB::beginTransaction();
        try {
            $movimientoActivo->delete();
            
            DB::commit();
            return $this->successResponse(
                null,
                'Movimiento de activo eliminado exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar movimiento de activo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function movimientosPorActivo(Activo $activo): JsonResponse
    {
        try {
            $movimientos = MovimientoActivo::where('activo_id', $activo->id)
                ->with([
                    'ubicacionOrigen',
                    'ubicacionDestino',
                    'movimiento',
                    'movimiento.usuario',
                    'movimiento.receptor',
                    'movimiento.autorizadoPor'
                ])
                ->latest('created_at')
                ->get();
                
            return $this->successResponse(
                MovimientoActivoResource::collection($movimientos),
                'Historial de movimientos del activo obtenido exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al obtener movimientos del activo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function storeMultiple(StoreMovimientoMultipleRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            
            // Crear el movimiento principal
            $movimiento = Movimiento::create([
                'responsable_origen_id' => $data['usuario']['id'],
                'responsable_destino_id' => $data['receptor']['id'],
                'fecha_movimiento' => now(),
                'estado' => 'pendiente',
                'observaciones_entrega' => $data['observaciones'] ?? 'Movimiento múltiple de activos',
                'autorizado_por' => Auth::id()
            ]);

            $movimientosActivos = [];

            foreach ($data['activos'] as $activoData) {
                $activo = Activo::findOrFail($activoData['id']);
                
                $movimientoActivo = MovimientoActivo::create([
                    'movimiento_id' => $movimiento->id,
                    'activo_id' => $activo->id,
                    'ubicacion_origen_id' => $activo->ubicacion_id,
                    'ubicacion_destino_id' => $data['cambiarUbicacion'] ? $data['ubicacion']['value'] : $activo->ubicacion_id,
                    'observaciones' => $activoData['observaciones'] ?? null,
                    'estado' => 'pendiente'
                ]);

                $movimientosActivos[] = $movimientoActivo;
            }

            // Cargar relaciones para la respuesta
            $movimiento->load([
                'usuario',
                'receptor',
                'autorizadoPor',
                'movimientosActivos.activo',
                'movimientosActivos.ubicacionOrigen',
                'movimientosActivos.ubicacionDestino'
            ]);

            DB::commit();

            return $this->successResponse([
                'movimiento' => new MovimientoResource($movimiento),
                'movimientos_activos' => MovimientoActivoResource::collection(collect($movimientosActivos)->load([
                    'activo',
                    'ubicacionOrigen',
                    'ubicacionDestino',
                    'movimiento.usuario',
                    'movimiento.receptor',
                    'movimiento.autorizadoPor'
                ]))
            ], 'Movimientos múltiples registrados exitosamente', 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar movimientos múltiples: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function entregar(MovimientoActivo $movimientoActivo, Request $request): JsonResponse
    {
        try {
            if (!$movimientoActivo->puedeSerEntregado()) {
                return $this->errorResponse('El movimiento no puede ser entregado en su estado actual', 400);
            }

            $movimientoActivo->marcarComoEntregado($request->observaciones);

            return $this->successResponse(
                new MovimientoActivoResource($movimientoActivo->fresh()->load([
                    'activo',
                    'ubicacionOrigen',
                    'ubicacionDestino',
                    'movimiento.usuario',
                    'movimiento.receptor',
                    'movimiento.autorizadoPor'
                ])),
                'Movimiento marcado como entregado exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al marcar movimiento como entregado: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function recibir(MovimientoActivo $movimientoActivo, Request $request): JsonResponse
    {
        try {
            if (!$movimientoActivo->puedeSerRecibido()) {
                return $this->errorResponse('El movimiento no puede ser recibido en su estado actual', 400);
            }

            $movimientoActivo->marcarComoRecibido($request->observaciones);

            return $this->successResponse(
                new MovimientoActivoResource($movimientoActivo->fresh()->load([
                    'activo',
                    'ubicacionOrigen',
                    'ubicacionDestino',
                    'movimiento.usuario',
                    'movimiento.receptor',
                    'movimiento.autorizadoPor'
                ])),
                'Movimiento marcado como recibido exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al marcar movimiento como recibido: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function rechazar(MovimientoActivo $movimientoActivo, Request $request): JsonResponse
    {
        try {
            if (!$movimientoActivo->puedeSerRechazado()) {
                return $this->errorResponse('El movimiento no puede ser rechazado en su estado actual', 400);
            }

            $movimientoActivo->rechazar($request->observaciones);

            return $this->successResponse(
                new MovimientoActivoResource($movimientoActivo->fresh()->load([
                    'activo',
                    'ubicacionOrigen',
                    'ubicacionDestino',
                    'movimiento.usuario',
                    'movimiento.receptor',
                    'movimiento.autorizadoPor'
                ])),
                'Movimiento rechazado exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al rechazar movimiento: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function generarPDF($id)
    {
        try {
            $movimientoActivo = MovimientoActivo::with([
                'activo',
                'ubicacionOrigen',
                'ubicacionDestino',
                'movimiento.usuario',
                'movimiento.receptor',
                'movimiento.autorizadoPor'
            ])->findOrFail($id);

            $pdf = PDF::loadView('pdf.movimiento-multiple', [
                'movimientos' => [$movimientoActivo],
                'usuario' => [
                    'nombre' => $movimientoActivo->movimiento->usuario->nombre,
                    'dni' => $movimientoActivo->movimiento->usuario->dni,
                    'departamento' => $movimientoActivo->movimiento->usuario->departamento,
                    'facultad' => $movimientoActivo->movimiento->usuario->facultad
                ],
                'receptor' => [
                    'nombre' => $movimientoActivo->movimiento->receptor->nombre,
                    'dni' => $movimientoActivo->movimiento->receptor->dni,
                    'departamento' => $movimientoActivo->movimiento->receptor->departamento,
                    'facultad' => $movimientoActivo->movimiento->receptor->facultad
                ],
                'fecha' => $movimientoActivo->movimiento->fecha_movimiento,
                'observaciones' => $movimientoActivo->observaciones
            ]);

            return $pdf->stream('movimiento-activo-' . $id . '.pdf');
        } catch (Exception $e) {
            Log::error('Error al generar PDF del movimiento: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
}
