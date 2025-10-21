<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Oficina\StoreOficinaRequest;
use App\Http\Requests\Oficina\UpdateOficinaRequest;
use App\Http\Resources\OficinaResource;
use App\Http\Resources\UbicacionResource;
use App\Http\Resources\UserResource;
use App\Models\Inventariado\Oficina;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class OficinaController extends BaseController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = Oficina::query()->with(['area', 'users']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('denominacion', 'like', "%$search%")
                        ->orWhere('codigo', 'like', "%$search%");
                });
            }
            if ($request->has('sort_by')) {
                $sortDirection = $request->boolean('desc', false) ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->orderBy('codigo');
            }

            if ($request->has('per_page')) {
                $perPage = $request->integer('per_page', 15);
                $oficina = $query->paginate($perPage);
            } else {
                $oficina =$query->get();
            }

            return OficinaResource::collection($oficina);
        } catch (Exception $e) {
            Log::error('Error al listar oficina: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function store(StoreOficinaRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $oficina = Oficina::create($request->validated());
            DB::commit();
            return $this->successResponse(
                new OficinaResource($oficina),
                'oficina creado exitosamente',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear oficina: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function show(Oficina $oficina): JsonResponse
    {
        try {
            $oficina->load(['area', 'users']);
            return $this->successResponse(
                new Oficina($oficina),
                'Centro de costo encontrado exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al obtener centro de costo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function update(UpdateOficinaRequest $request, Oficina $oficina): JsonResponse
    {
        DB::beginTransaction();
        try {
            $oficina->update($request->validated());
            DB::commit();
            return $this->successResponse(
                new OficinaResource($oficina->fresh()),
                'Oficina actualizado exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar oficina: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function destroy(Oficina $oficina): JsonResponse
    {
        DB::beginTransaction();
        try {
            if ($oficina->area()->exists() || $oficina->users()->exists()) {
                throw new Exception('No se puede eliminar el oficina porque tiene areas o usuarios asignados');
            }
            
            $oficina->delete();
            DB::commit();
            return $this->successResponse(null, 'oficina eliminado exitosamente');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar oficina: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
}