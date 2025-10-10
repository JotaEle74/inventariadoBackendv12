<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Declaracion\StoreDeclaracionRequest;
use App\Http\Requests\Declaracion\UpdateDeclaracionRequest;
use App\Http\Resources\DeclaracionResource;
use App\Models\Inventariado\Declaracion;
use App\Models\Inventariado\Activo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\Auth;
class DeclaracionController extends BaseController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = Declaracion::query()->with(['activos', 'user']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('fecha_declaracion', 'like', '%' . $search . '%')
                        ->orWhere('ito', 'like', '%'.$search.'%')
                        ->orWhere('codigo', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('usuario')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('name', $request->usuario)
                        ->orWhere('dni', $request->usuario);
                });
            }

            if ($request->has('sort_by')) {
                $sortDirection = $request->boolean('desc', false) ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->orderBy('id');
            }

            if ($request->has('per_page')) {
                $perPage = $request->integer('per_page', 15);
                $declaracion = $query->paginate($perPage);
            } else {
                $declaracion = $query->get();
            }
            return DeclaracionResource::collection($declaracion);
        } catch (Exception $e) {
            Log::error('Error al listar Declaraciones de uso: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function store(StoreDeclaracionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $activos = $data['activos'];
            unset($data['activos']);

            $data['numero_folios'] = 0;

            $data['codigo'] = 'TEMP-' . uniqid() . '-' . time();
            $declaracion = Declaracion::create($data);


            $year = $declaracion->created_at->format('Y');
            $declaracion->codigo = sprintf('DJ-%s-%04d', $year, $declaracion->id);
            $declaracion->save();
            $pivotData = [];
            foreach ($activos as $activoId) {
                $activo = Activo::find($activoId);
                $entidad = $activo->area && $activo->area->oficina && $activo->area->oficina->entidad ? $activo->area->oficina->entidad : null;
                $pivotData[$activoId] = [
                    'area_edificio' => $activo->area ? $activo->area->edificio : null,
                    'oficina_denominacion' => $activo->area && $activo->area->oficina ? $activo->area->oficina->denominacion : null,
                    'entidad_denominacion' => $entidad->denominacion ?? null,
                    'area_codigo' => $activo->area->codigo ?? null,
                    'estado' => $activo->estado,
                    'condicion' => $activo->condicion,
                    'ubicacion' => $activo->area ? ($activo->area->edificio . ' - ' . $activo->area->piso . ' - ' . $activo->area->aula) : null,
                    'descripcion' => $activo->descripcion,
                ];
            }
            $declaracion->activos()->sync($pivotData);

            // Actualizar el campo 'declaracion' en cada activo con el id del registro pivote
            foreach ($declaracion->activos as $activo) {
                $pivot = $activo->pivot;
                $activo->declaracion = $pivot->id; // id del registro en la tabla activo_declaracion
                $activo->save();
            }
            $declaraciones=$declaracion->load(['activos', 'user']);
            $folios=$this->calculateTotalPages($declaraciones);
            $declaraciones->numero_folios=$folios;
            $declaraciones->save();
            DB::commit();
            return $this->successResponse(
                new DeclaracionResource($declaraciones),
                'Declaración creada exitosamente',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear Declaración: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function update(UpdateDeclaracionRequest $request, Declaracion $declaracion): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if (isset($data['activos'])) {
                $activos = $data['activos'];

                // Recalculamos el número de folios si la lista de activos cambia
                $data['numero_folios'] = $this->calculateFolios(count($activos));

                $declaracion->activos()->sync($activos);
                unset($data['activos']); // Quitamos los activos para el update del modelo principal
            }

            $declaracion->update($data);

            DB::commit();
            return $this->successResponse(
                new DeclaracionResource($declaracion->load(['activos', 'user'])),
                'Declaración actualizada exitosamente'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar Declaración: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function endDeclaration ()
    {
        $userId = Auth::id();
        $ultimaDeclaracion = Declaracion::with(['activos', 'user'])
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        $pdf = PDF::loadView('pdf.declaracion-uso', [
            'declaracion' => $ultimaDeclaracion
        ]);
        // Configurar el PDF
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-right', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);
        // Devolver el PDF como stream
        return $pdf->stream('declaracion-uso-' . $ultimaDeclaracion->codigo . '.pdf');
    }

    public function generarPDF($id)
    {
        try {
            $declaracion = Declaracion::with(['activos', 'user'])->findOrFail($id);
            $pdf = PDF::loadView('pdf.declaracion-uso', [
                'declaracion' => $declaracion
            ]);
            $pdf->setPaper('a4', 'landscape');
            return $pdf->stream('declaracion-uso-' . $declaracion->codigo . '.pdf');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function reporteActivosconDeclaraciones(Request $request)
    {
        set_time_limit(300);
        $ito = $request->input('ito');

        $activos = DB::table('activos as a')
            ->join('catalogo_bienes as cb', 'a.catalogo_id', '=', 'cb.id')
            ->select('a.id', 'a.codigo', 'cb.denominacion as catalogo')
            ->get();

        $resultado = $activos->map(function($activo) use ($ito) { // Pasar $ito al closure
            // Primera activo_declaracion
            $primera = DB::table('activo_declaracion as ad')
                ->join('declaraciones as d', 'ad.declaracion_id', '=', 'd.id')
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->where('ad.activo_id', $activo->id)
                ->orderBy('ad.id', 'asc')
                ->select(
                    'ad.id as activodeclaracion_id',
                    'ad.ubicacion',
                    'ad.area_edificio',
                    'ad.oficina_denominacion',
                    'ad.entidad_denominacion',
                    'ad.estado',
                    'ad.condicion',
                    'ad.estado',
                    'ad.condicion',
                    'd.fecha_declaracion',
                    'd.codigo as declaracion_codigo',
                    'd.numero_folios as declaracion_folios',
                    'u.name',
                    'u.dni'
                )
                ->first();

            // Última activo_declaracion donde la declaración tenga el ito solicitado
            $especifica = DB::table('activo_declaracion as ad')
                ->join('declaraciones as d', 'ad.declaracion_id', '=', 'd.id')
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->where('ad.activo_id', $activo->id)
                ->where('d.ito', $ito)
                ->orderBy('ad.id', 'desc')
                ->select(
                    'ad.id as activodeclaracion_id',
                    'ad.ubicacion',
                    'ad.area_edificio',
                    'ad.oficina_denominacion',
                    'ad.entidad_denominacion',
                    'ad.estado',
                    'ad.condicion',
                    'd.fecha_declaracion',
                    'd.codigo as declaracion_codigo',
                    'd.numero_folios as declaracion_folios',
                    'u.name',
                    'u.dni'
                )
                ->first();

            return [
                'activo' => [
                    'id' => $activo->id,
                    'codigo' => $activo->codigo,
                    'catalogo' => $activo->catalogo,
                ],
                'primera_activodeclaracion' => $primera,
                'activodeclaracion_ito' => $especifica,
            ];
        });

        return response()->json($resultado);
    }
    private function calculateTotalPages(Declaracion $declaracion): int
    {
        try {
            $pdf = PDF::loadView('pdf.declaracion-uso', [
                'declaracion' => $declaracion,
                'forCounting' => true
            ]);

            $pdf->setPaper('a4', 'landscape');
            $pdf->render();
            return $pdf->getDomPDF()->get_canvas()->get_page_count();
        } catch (\Exception $e) {
            Log::error('Error al calcular páginas: '.$e->getMessage());
            return $this->calculateFolios(count($declaracion->activos));
        }
    }
}
