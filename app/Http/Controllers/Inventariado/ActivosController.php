<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Activo\StoreActivoRequest;
use App\Http\Requests\Activo\UpdateActivoRequest;
use App\Http\Resources\ActivoResource;
use App\Http\Resources\MovimientoActivoResource;
use App\Models\Inventariado\Activo;
use App\Traits\ExportsAssets;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ActivosController extends BaseController
{
    use ExportsAssets;

    public function inventariador(Request $request)
    {
        try {
            $query=Activo::with(['catalogo:id,denominacion', 'responsable:id,name', 'area.oficina']);
            $user = $request->user();
            $oficinaIds = $user->oficinas->pluck('id');
            //$query->where('dniInventariador', $user->dni);
            $query->where('dniInventariador', null);
            $query->whereHas('area', function ($q) use ($oficinaIds) {
                $q->whereIn('oficina_id', $oficinaIds);
            });
            $query->select('id', 'codigo', 'numero_serie', 'color', 'catalogo_id', 'responsable_id');
            $perPage = $request->integer('per_page', 15);
            $activos = $query->paginate($perPage);
            return $activos;
        } catch (Exception $e) {
            Log::error('Error al listar activos: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
    public function index(Request $request)//: AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = Activo::with(['area.oficina', 'responsable']);
            $user = $request->user();
            if($request->has('codigo')){
                $query->where('codigo', 'like', $request->codigo);
                $activos = $query->get();
                return ActivoResource::collection($activos);
            }
            if ($user && ($user->hasRole('responsable_departamento') || $user->hasRole('usuario_consulta'))) {
                $query->where('responsable_id', $user->id);
            }

            if($request->has('search')){
                $search = $request->search;
                $query->where(function($q) use ($search){
                    $q->orWhere('codigo', 'like', "%{$search}%")
                      ->orWhere('numero_serie', 'like', "%{$search}%")
                      ->orWhereHas('catalogo', function($q2) use ($search) {
                          $q2->where('denominacion', 'like', "%{$search}%");
                      });
                });
            }

            if($request->has('area_id')){
                $query->where('area_id', $request->area_id);
            }

            if($request->has('oficina_id')){
                $query->whereHas('area', function($q) use ($request) {
                    $q->where('oficina_id', $request->oficina_id);
                });
            }

            if($request->has('entidad_id')){
                $query->whereHas('area.oficina', function($q) use ($request) {
                    $q->where('entidad_id', $request->entidad_id);
                });
            }

            if($request->has('estado')){
                $query->where('estado', $request->estado);
            }

            if($request->has('responsable_id')) {
                $query->where('responsable_id', $request->responsable_id);
            }

            if ($request->has('sort_by')) {
                $sortDirection = $request->boolean('desc', false) ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->orderBy('id');
            }

            if($request->per_page === null){
                $activos = $query->get();
            }
            else {
                $perPage = $request->integer('per_page', 15);
                $activos = $query->paginate($perPage);
            }
            return ActivoResource::collection($activos);
        } catch (Exception $e) {
            Log::error('Error al listar activos: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function dashboard(Request $request)//: JsonResponse
    {
        try {
            $user = $request->user();
            $isRestrictedUser = $user && ($user->hasRole('responsable_departamento') || $user->hasRole('usuario_consulta'));

            $activosQuery = DB::table('activos');
            $movimientosQuery = DB::table('movimientos');
            $oficinasQuery = DB::table('oficinas');
            $usersQuery = DB::table('users');

            if ($isRestrictedUser) {
                $activosQuery->where('responsable_id', $user->id);

                $movimientosQuery->where(function ($query) use ($user) {
                    $query->where('responsable_origen_id', $user->id)
                          ->orWhere('responsable_destino_id', $user->id);
                });

                $oficinaIds = $user->oficinas()->pluck('oficinas.id');
                $oficinasQuery->whereIn('id', $oficinaIds);

                $userIdsInSameOffice = DB::table('oficina_user')
                                        ->whereIn('oficina_id', $oficinaIds)
                                        ->pluck('user_id')
                                        ->unique();
                $usersQuery->whereIn('id', $userIdsInSameOffice);
            }

            $totalActivos = $activosQuery->count();

            $totalOficinas = $oficinasQuery->count();

            $totalUsuarios = $usersQuery->count();

            $activosPorEstado = (clone $activosQuery)
                ->select('estado', DB::raw('count(*) as total'))
                ->groupBy('estado')
                ->get()
                ->pluck('total', 'estado');

            $activosPorCategoria = (clone $activosQuery)
                ->join('catalogo_bienes', 'activos.catalogo_id', '=', 'catalogo_bienes.id')
                ->select('catalogo_bienes.denominacion', DB::raw('count(activos.id) as total'))
                ->groupBy('catalogo_bienes.denominacion')
                ->orderByDesc('total')
                ->limit(15)
                ->get()
                ->pluck('total', 'denominacion');

            $activosPorCondicion = (clone $activosQuery)
                ->select('condicion', DB::raw('count(*) as total'))
                ->groupBy('condicion')
                ->get()
                ->pluck('total', 'condicion');

            // Activos agregados recientemente (últimos 30 días)
            $activosRecientes = (clone $activosQuery)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            // Total de movimientos
            $totalMovimientos = $movimientosQuery->count();

            // Movimientos pendientes o en proceso
            $movimientosPendientes = (clone $movimientosQuery)
                ->whereIn('estado', ['pendiente', 'en_entrega'])
                ->count();

            $meses = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $meses[$date->locale('es')->translatedFormat('F Y')] = 0;
            }

            $movimientosData = (clone $movimientosQuery)
                ->select(
                    DB::raw("DATE_FORMAT(fecha_movimiento, '%Y-%m') as month_year"),
                    DB::raw('count(*) as total')
                )
                ->where('fecha_movimiento', '>=', now()->subMonths(5)->startOfMonth())
                ->groupBy('month_year')
                ->get();

            foreach ($movimientosData as $data) {
                $date = \Carbon\Carbon::createFromFormat('Y-m', $data->month_year)->locale('es');
                $key = $date->translatedFormat('F Y');
                $meses[$key] = (int) $data->total;
            }

            $dashboardData = [
                'total_activos' => $totalActivos,
                'total_oficinas' => $totalOficinas,
                'total_usuarios' => $totalUsuarios,
                'activos_por_estado' => [
                    'activos' => $activosPorEstado['A'] ?? 0,
                    'inactivos' => $activosPorEstado['I'] ?? 0,
                ],
                'activos_por_categoria' => $activosPorCategoria,
                'activos_por_condicion' => [
                    'nuevo' => $activosPorCondicion['N'] ?? 0,
                    'bueno' => $activosPorCondicion['B'] ?? 0,
                    'regular' => $activosPorCondicion['R'] ?? 0,
                    'malo' => $activosPorCondicion['M'] ?? 0,
                ],
                'activos_recientes' => $activosRecientes,
                'total_movimientos' => $totalMovimientos,
                'movimientos_pendientes' => $movimientosPendientes,
                'movimientos_ultimos_6_meses' => $meses,
            ];

            return $this->successResponse($dashboardData, 'Datos del dashboard obtenidos exitosamente.');
        } catch (Exception $e) {
            Log::error('Error al generar datos del dashboard: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function store(StoreActivoRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            Log::info('Datos antes de la inserción:', $data);
            $activo = Activo::create($data);
            DB::commit();
            return $this->successResponse(
                new ActivoResource($activo->fresh()),
                'Activo creado exitosamente',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear activo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function show(Activo $activo): JsonResponse
    {
        try {
            $activo->load(['catalogo', 'area.oficina.entidad', 'responsable', 'movimientos']);
            return $this->successResponse(
                new ActivoResource($activo),
                'Activo obtenido exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al obtener activo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function update(UpdateActivoRequest $request, Activo $activo): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            $activo->update($validatedData);
            DB::commit();
            return $this->successResponse(
                new ActivoResource($activo->fresh()),
                'Activo actualizado exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar activo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function destroy(Activo $activo)
    {
        DB::beginTransaction();
        try {
            if ( $activo->movimientos()->exists()) {
                throw new Exception(
                    'No se puede eliminar el activo porque tiene mantenimientos o movimientos asociados',
                    409
                );
            }
            $activo->delete();
            
            DB::commit();
            return $this->successResponse(
                null,
                'Activo eliminado exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar activo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function movimientos(Activo $activo): JsonResponse
    {
        try {
            $movimientos = $activo->movimientos()->with(['ubicacionOrigen', 'ubicacionDestino'])->get();
            return $this->successResponse(
                MovimientoActivoResource::collection($movimientos),
                'Movimientos del activo obtenidos exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al obtener movimientos del activo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
}
