<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Proveedor\StoreProveedorRequest;
use App\Http\Requests\Proveedor\UpdateProveedorRequest;
use App\Http\Resources\ProveedorResource;
use App\Models\Inventariado\Proveedor;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProveedorController extends BaseController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = Proveedor::query()->withCount(['activos', 'mantenimientos']);
            if ($request->has('nombre')) {
                $query->where('nombre', 'like', '%'.$request->nombre.'%');
            }
            if ($request->has('ruc')) {
                $query->where('ruc', 'like', '%'.$request->ruc.'%');
            }

            if ($request->has('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            // Ordenación
            if ($request->has('sort_by')) {
                $sortDirection = $request->boolean('desc', false) ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->orderBy('nombre');
            }

            if($request->per_page === null){
                $proveedores = $query->get();
            }
            else {
                $perPage = $request->integer('per_page', 2);
                $proveedorres = $query->paginate($perPage);
            }

            // Paginación
            return ProveedorResource::collection($proveedores);
        } catch (Exception $e) {
            Log::error('Error al listar proveedores: '.$e->getMessage());
            return $this->handleException($e);
        }
    }

    public function store(StoreProveedorRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $proveedor = Proveedor::create($request->validated());
            DB::commit();
            return $this->successResponse(new ProveedorResource($proveedor), 'Proveedor creado exitosamente', 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear Proveedor: '.$e->getMessage());
            return $this->handleException($e);
        }
    }

    public function show(Proveedor $proveedor): JsonResponse
    {
        try {
            $proveedor->loadCount(['activos', 'mantenimientos']);
            return $this->successResponse(new ProveedorResource($proveedor), 'Proveedor encontrado');
        } catch (\Throwable $th) {
            Log::error('Error al obtener proveedor: '.$e->getMessage());
            return $this->handleException($e);
        }
    }

    public function update(UpdateProveedorRequest $request, Proveedor $proveedor)
    {
        try {
            if (!$proveedor) {
                Log::error('Proveedor no encontrado');
                return $this->errorResponse('Proveedor no encontrado', 404);
            }
            DB::beginTransaction();
            $datos = $request->validated();

            $actualizado = $proveedor->update($datos);
            if (!$actualizado) {
                throw new Exception('No se pudo actualizar el proveedor');
            }

            DB::commit();

            // Obtener el proveedor actualizado
            $proveedorActualizado = $proveedor->fresh();
            if (!$proveedorActualizado) {
                throw new Exception('No se pudo obtener el proveedor actualizado');
            }

            return $this->successResponse(
                new ProveedorResource($proveedorActualizado),
                'Proveedor actualizado exitosamente'
            );
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar proveedor: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return $this->errorResponse(
                'Error al actualizar el proveedor: ' . $e->getMessage(),
                500
            );
        }
    }

    public function destroy(Proveedor $proveedor): JsonResponse
    {
        DB::beginTransaction();
        try {
            if($proveedor->activos()->exists() || $proveedor->mantenimientos()->exists())
            {
                throw new Exception ('No se puede eliminar el proveedor, tiene activos o mantenimientos asignados', 409);
            }
            $proveedor->delete();
            DB::commit();
            return $this->successResponse(null, 'Proveedor eliminado exitosamente');
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar proveedor: '. $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function activos(Proveedor $proveedor): JsonResponse
    {
        try {
            $activos = $proveedor->activos()->with(['categoria', 'ubicacion', 'responsable'])->get();
            return $this->successResponse(ActivoResource::collection($activos), 'Activos del proveedor obtenidos exitosamente');
        } catch (Exception $e) {
            Log::error('Error al obtener activos del proveedor: '.$e->getMessage());
            return $this->handleException($e);
        }
    }

    public function mantenimientos(Proveedor $proveedor): JsonResponse
    {
        try {
            $mantenimientos = $proveedor->mantenimientos()->with(['activo', 'creadoPor'])->get();

            return $this->succeessResponse(MantenimientosResource::collection($mantenimientos) ,'Mantenimientos del proveedor obtenidos exitosamente');
        } catch (Exception $e) {
            Log::error('Error al obtener mantenimientos del proveedor: '.$e->getMessage());
            return $this->handleException($e);
        }
    }
}
