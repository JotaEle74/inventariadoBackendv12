<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Http\Resources\AreaResource;
use App\Models\Inventariado\Area;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AreaController extends BaseController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = Area::query()->with(['oficina']);
            if($request->has('oficinas')){
                $query->where('oficina_id', $request->oficinas);
                $areas = $query->get();
                return AreaResource::collection($areas);
            }
            $user = $request->user();
            if ($user && ($user->hasRole('responsable_departamento') || $user->hasRole('usuario_consulta'))) {
                $oficinasIds = $user->oficinas()->pluck('oficinas.id')->toArray(); // IDs de las oficinas del usuario
                $query->whereIn('oficina_id', $oficinasIds);
            }
            
            if ($request->has('edificio')) {
                $query->where('edificio', 'like', '%' . $request->edificio . '%');
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('codigo', 'like', '%' . $search . '%')
                      ->orWhere('edificio', 'like', '%' . $search . '%')
                      ->orWhereHas('oficina', function ($q2) use ($search) {
                          $q2->where('denominacion', 'like', '%' . $search . '%');
                      });
                });
            }
            if ($request->has('oficina')) {
                $oficina = $request->oficina;
                $query->where('oficina_id', $oficina);
            }

            if ($request->has('aula')) {
                $query->where('aula', 'like', '%' . $request->aula . '%');
            }

            if ($request->has('sort_by')) {
                $sortDirection = $request->boolean('desc', false) ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->orderBy('edificio')->orderBy('piso')->orderBy('aula');
            }

            if ($request->has('per_page')) {
                $perPage = $request->integer('per_page', 15);
                $areas = $query->paginate($perPage);
            } else {
                $areas = $query->get();
            }
            return AreaResource::collection($areas);
        } catch (Exception $e) {
            Log::error('Error al listar areas: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function store(StoreAreaRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $area = Area::create($request->validated());
            DB::commit();
            return $this->successResponse(
                new AreaResource($area->load(['oficina'])),
                'Area creada exitosamente',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear area: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function show(Area $area): JsonResponse
    {
        try {
            $area->load(['oficina.entidad', 'activos']);
            return $this->successResponse(
                new AreaResource($area),
                'Area encontrada exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al obtener area: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function update(UpdateAreaRequest $request, Area $area)//: JsonResponse
    {
        DB::beginTransaction();
        try {
            $area->update($request->validated());
            DB::commit();
            return $this->successResponse(
                new AreaResource($area->fresh()->load(['oficina'])),
                'Area actualizada exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar area: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function destroy(Area $area): JsonResponse
    {
        DB::beginTransaction();
        try {
            if ($area->activos()->exists()) {
                throw new Exception('No se puede eliminar el Area porque tiene activos asignados');
            }
            
            $area->delete();
            DB::commit();
            return $this->successResponse(null, 'Area eliminada exitosamente');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar Area: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
}
