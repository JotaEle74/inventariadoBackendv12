<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Configuracion\StoreConfiguracionRequest;
use App\Http\Requests\Configuracion\UpdateConfiguracionRequest;
use App\Http\Resources\ConfiguracionResource;
use App\Models\Inventariado\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfiguracionController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $configs = Configuracion::query();

            if ($request->has('search')) {
                $search = $request->search;
                $configs->where('clave', 'like', "%{$search}%");
            }

            if ($request->has('sort_by')) {
                $direction = $request->boolean('desc', false) ? 'desc' : 'asc';
                $configs->orderBy($request->sort_by, $direction);
            }

            if ($request->has('per_page')) {
                $perPage = $request->integer('per_page', 15);
                $result = ConfiguracionResource::collection($configs->paginate($perPage));
            } else {
                $result = ConfiguracionResource::collection($configs->get());
            }

            return $result;
        } catch (Exception $e) {
            Log::error('Error al listar configuraciones: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function store(StoreConfiguracionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $config = Configuracion::create($request->validated());
            DB::commit();
            return $this->successResponse(
                new ConfiguracionResource($config),
                'Configuración creada exitosamente',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear configuración: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function show(Configuracion $configuracion): JsonResponse
    {
        try {
            return $this->successResponse(
                new ConfiguracionResource($configuracion),
                'Configuración encontrada exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al obtener configuración: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function update(UpdateConfiguracionRequest $request, Configuracion $configuracion): JsonResponse
    {
        DB::beginTransaction();
        try {
            $configuracion->update($request->validated());
            DB::commit();
            return $this->successResponse(
                new ConfiguracionResource($configuracion->fresh()),
                'Configuración actualizada exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar configuración: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function destroy(Configuracion $configuracion): JsonResponse
    {
        DB::beginTransaction();
        try {
            $configuracion->delete();
            DB::commit();
            return $this->successResponse(null, 'Configuración eliminada exitosamente');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar configuración: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
}