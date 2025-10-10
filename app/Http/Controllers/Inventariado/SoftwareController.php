<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Software\StoreSoftwareRequest;
use App\Http\Requests\Software\UpdateSoftwareRequest; // Crearemos este archivo a continuación
use App\Http\Resources\SoftwareResource;
use App\Models\Inventariado\Software;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SoftwareController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Software::with(['responsable', 'area', 'activosAsignados']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%")
                      ->orWhere('tipo', 'like', "%{$search}%");
                });
            }

            if ($request->has('sort_by')) {
                $sortDirection = $request->boolean('desc', false) ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->orderBy('id');
            }

            if ($request->has('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            $perPage = $request->integer('per_page', 15);
            $software = $query->paginate($perPage);

            return $this->successResponse(
                SoftwareResource::collection($software)->response()->getData(true),
                'Activos de software listados exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al listar software: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    /**
     * Almacena un nuevo activo de software.
     */
    public function store(StoreSoftwareRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();

            $software = Software::create($validatedData);

            // Si es una licencia y se asignaron activos físicos
            if ($software->tipo === 'licencia_terceros' && isset($validatedData['activos_asignados'])) {
                $asignaciones = [];
                foreach ($validatedData['activos_asignados'] as $activoId) {
                    $asignaciones[$activoId] = ['fecha_asignacion' => now()];
                }
                $software->activosAsignados()->sync($asignaciones);
            }

            DB::commit();

            return $this->successResponse(
                new SoftwareResource($software->load(['responsable', 'area', 'activosAsignados'])),
                'Activo de software creado exitosamente',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear software: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    /**
     * Muestra un activo de software específico.
     */
    public function show(Software $software): JsonResponse
    {
        try {
            return $this->successResponse(
                new SoftwareResource($software->load(['responsable', 'area', 'activosAsignados'])),
                'Activo de software obtenido exitosamente'
            );
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Actualiza un activo de software específico.
     */
    public function update(UpdateSoftwareRequest $request, Software $software): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();

            $software->update($validatedData);

            // Si es una licencia, sincronizamos los activos físicos asignados
            if ($software->tipo === 'licencia_terceros') {
                if (isset($validatedData['activos_asignados'])) {
                    $asignaciones = [];
                    foreach ($validatedData['activos_asignados'] as $activoId) {
                        $asignaciones[$activoId] = ['fecha_asignacion' => now()];
                    }
                    $software->activosAsignados()->sync($asignaciones);
                } else {
                    // Si no se envía el array, se desvinculan todos
                    $software->activosAsignados()->sync([]);
                }
            }

            DB::commit();

            return $this->successResponse(
                new SoftwareResource($software->load(['responsable', 'area', 'activosAsignados'])),
                'Activo de software actualizado exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar software: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
    public function destroy(Software $software): JsonResponse
    {
        try {
            $software->delete();
            return $this->successResponse(
                null,
                'Activo de software eliminado exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al eliminar software: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
    public function installed($activoId){
        $softwares = DB::table('software')
            ->join('activo_software', 'software.id', '=', 'activo_software.software_id')
            ->where('activo_software.activo_id', $activoId)
            ->whereNull('software.deleted_at')
            ->select([
                'software.id',
                'software.codigo',
                'software.tipo',
                'software.nombre',
                'software.descripcion',
                'software.estado',
                'software.clave_licencia',
                'software.tipo_licencia',
                'software.cantidad_puestos',
                'software.fecha_compra',
                'software.fecha_vencimiento',
                'activo_software.fecha_asignacion'
            ])
            ->get();
        
        $data = $softwares->map(function($software) {
            return [
                'id' => $software->id,
                'codigo' => $software->codigo,
                'tipo' => $software->tipo,
                'nombre' => $software->nombre,
                'descripcion' => $software->descripcion,
                'estado' => $software->estado,
                
                'clave_licencia' => $software->tipo === 'licencia_terceros' ? $software->clave_licencia : null,
                'tipo_licencia' => $software->tipo === 'licencia_terceros' ? $software->tipo_licencia : null,
                'cantidad_puestos' => $software->tipo === 'licencia_terceros' ? $software->cantidad_puestos : null,
                'fecha_compra' => $software->tipo === 'licencia_terceros' ? $software->fecha_compra : null,
                'fecha_vencimiento' => $software->tipo === 'licencia_terceros' ? $software->fecha_vencimiento : null,
                
                'fecha_asignacion' => $software->fecha_asignacion ? date('Y-m-d H:i:s', strtotime($software->fecha_asignacion)) : null,
            ];
        });
        
        return $this->successResponse(
            $data,
            'Software instalados en el activo obtenidos exitosamente'
        );
    }

    public function install(Request $request)
    {
        DB::beginTransaction();
        try {
            $softwares=Software::with('activosAsignados')->whereIn('id', $request->software_id)->get();
            $AsigDelete=DB::table('activo_software')->where('activo_id', $request->activo_id)->pluck('software_id')->toArray();
            $softwareIdsAEliminar = array_diff($AsigDelete, $request->software_id);
            if($request->activo_id){
                //$asignaciones[$request->activo_id]=['fecha_asignacion' => now()];
                foreach ($softwares as $software) {
                    if(!($software->activosAsignados->contains('id', $request->activo_id)))
                        DB::table('activo_software')->insert([
                        'software_id' => $software->id,
                        'activo_id' => $request->activo_id,
                        'fecha_asignacion' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),]);
                }
            }
            if (!empty($softwareIdsAEliminar)) {
                DB::table('activo_software')
                    ->where('activo_id', $request->activo_id)
                    ->whereIn('software_id', $softwareIdsAEliminar)
                    ->delete();
            }
            DB::commit();

            return response()->json([
                'message' => 'Asignación de software al activo realizada exitosamente.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar software: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
}
