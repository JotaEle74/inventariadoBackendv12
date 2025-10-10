<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Ito\StoreItoRequest;
use App\Http\Requests\Ito\UpdateItoRequest;
use App\Http\Resources\ItoResource;
use App\Models\Inventariado\Ito;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ItoController extends BaseController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = Ito::query();

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('codigo', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('estado') && $request->estado) {
                $query->where('estado', true);
            }

            if ($request->has('sort_by')) {
                $sortDirection = $request->boolean('desc', false) ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->orderBy('id');
            }

            if ($request->has('per_page')) {
                $perPage = $request->integer('per_page', 15);
                $ito = $query->paginate($perPage);
            } else {
                $ito = $query->get();
            }
            return ItoResource::collection($ito);
        } catch (Exception $e) {
            Log::error('Error al listar Itos: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function store(StoreItoRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $area = Ito::create($request->validated());
            DB::commit();
            return $this->successResponse(
                new ItoResource($area),
                'Ito creada exitosamente',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear Ito: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function show(Ito $ito): JsonResponse
    {
        try {
            return $this->successResponse(
                new ItoResource($ito),
                'Ito encontrada exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al obtener Ito: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function update(UpdateItoRequest $request, Ito $ito): JsonResponse
    {
        DB::beginTransaction();
        try {
            $ito->update($request->validated());
            DB::commit();
            return $this->successResponse(
                new ItoResource($ito->fresh()),
                'Ito actualizada exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar Ito: ' . $e->getMessage());
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
