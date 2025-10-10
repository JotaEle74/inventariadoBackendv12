<?php
namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\CatalogoBienes\StoreCatalogoBienRequest;
use App\Http\Requests\CatalogoBienes\UpdateCatalogoBienRequest;
use App\Http\Resources\CatalogoBienesResource;
use App\Models\Inventariado\CatalogoBienes;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class CatalogoBienesController extends BaseController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = CatalogoBienes::query();

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('denominacion', 'like', '%' . $search . '%')
                      ->orWhere('codigo', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('sort_by')) {
                $sortDirection = $request->boolean('desc', false) ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->orderBy('id');
            }

            if($request->per_page === null){
                $catalogobienes = $query->get();
            }
            else {
                $perPage = $request->integer('per_page', 15);
                $catalogobienes = $query->paginate($perPage);
            }
            return CatalogoBienesResource::collection($catalogobienes);
        }
        catch (Exception $e) {
            Log::error('Error al listar Catalogo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function store(StoreCatalogoBienRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $catalogo = CatalogoBienes::create($request->validated());
            
            DB::commit();
            return $this->successResponse(
                new CatalogoBienesResource($catalogo), 'Catalogo creado exitosamente', 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear catalogo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function show(CatalogoBienes $catalogo): JsonResponse
    {
        try {
            $catalogo->load(['activos']);
            return $this->successResponse(
                new CatalogoBienesResource($catalogo),
                'Catalogo obtenida exitosamente'
            );
        } catch (Exception $e) {
            Log::error('Error al obtener catalogo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function update(UpdateCatalogoBienRequest $request, CatalogoBienes $catalogo): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            Log::info('Datos validados:', $validatedData);
            Log::info('ID de la catalogo:', ['id' => $catalogo->id]);
            
            $catalogo->fill($validatedData);
            $catalogo->save();
            
            DB::commit();
            return $this->successResponse(
                new CatalogoBienesResource($catalogo->fresh()),
                'Catalogo actualizada exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar catalogo: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e);
        }
    }

    public function destroy(CatalogoBienes $catalogo): JsonResponse
    {
        DB::beginTransaction();
        try {
            $catalogo->delete();
            
            DB::commit();
            return $this->successResponse(
                null,
                'catalogo eliminada exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar catalogo: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
}