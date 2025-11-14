<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Activo\StoreActivoRequest;
use App\Http\Requests\Activo\UpdateActivoRequest;
use App\Http\Resources\ActivoResource;
use App\Http\Resources\MovimientoActivoResource;
use App\Models\Inventariado\Activo;
use App\Models\Inventariado\Software;
use App\Traits\ExportsAssets;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Inventariado\Area;
use PDF;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Validator;
use App\Exports\ActivosExport;
use Maatwebsite\Excel\Facades\Excel;
class ActivosController extends BaseController
{
    use ExportsAssets;

    public function inventariador(Request $request)
    {
        try {
            //$query=Activo::with(['catalogo:id,denominacion', 'responsable:id,name', 'area.oficina']);
            $query=Activo::with(['responsable:id,name', 'area.oficina']);
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
            $query = Activo::with(['area.oficina', 'responsable', 'edificio', 'users']);
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
                      ->orWhere('numero_serie', 'like', "%{$search}%");
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
                $query->has('users');
                $query->orderBy('id', 'desc');
                $activos = $query->limit(1000)->get();
                //$activos = $query->skip(10)->get();
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

            $activosPorDia = DB::table('activo_user as au')
            ->select(DB::raw('DATE(au.fecha) as fecha'), DB::raw('COUNT(DISTINCT au.activo_id) as total'))
            ->groupBy(DB::raw('DATE(au.fecha)'))
            ->orderBy(DB::raw('DATE(au.fecha)'), 'asc')
            ->get();
            $activosPorGrupoDia = DB::table('activo_user as au')
            ->select(
                DB::raw('DATE(au.fecha) as fecha'),
                'au.grupo',
                DB::raw('COUNT(DISTINCT au.activo_id) as total')
            )
            ->whereDate('au.fecha', today())
            ->whereNotNull('au.grupo') 
            ->groupBy(DB::raw('DATE(au.fecha)'), 'au.grupo')
            ->orderBy('au.grupo')
            ->get();
            $activosPorOficina = DB::table('activos as a')
            ->join('areas as ar', 'a.area_id', '=', 'ar.id')
            ->join('oficinas as of', 'ar.oficina_id', '=', 'of.id') // corregido
            ->leftJoin('activo_user as au', 'a.id', '=', 'au.activo_id') // left join para incluir los no registrados
            ->select(
                'of.denominacion',
                DB::raw('COUNT(a.id) as total_activos'),
                DB::raw('SUM(CASE WHEN au.report = 1 THEN 1 ELSE 0 END) as registrados'),
                DB::raw('SUM(CASE WHEN au.report IS NULL OR au.report = 0 THEN 1 ELSE 0 END) as faltantes')
            )
            ->groupBy('of.denominacion')
            ->orderBy('of.denominacion')
            ->get();
            $dashboardData = [
                'activosPorDia'=>$activosPorDia,
                'activosPorGrupoDia'=>$activosPorGrupoDia,
                'activosPorOficina'=>$activosPorOficina
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
            $user = $request->user();
            $user->activos()->attach($activo->id, ['fecha'=> now(), 'grupo'=>$user->grupo, 'user_id_two'=>$request->user_id_two]);
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

    public function update(UpdateActivoRequest $request, Activo $activo)//: JsonResponse
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            $activo->update($validatedData);
            $user = $request->user();
            $user->activos()->attach($activo->id, ['fecha'=> now(), 'grupo'=>$user->grupo, 'user_id_two'=>$request->user_id_two]);
            $activo->save();
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
    public function historial(Request $request)
    {
        try {
            $user=User::find($request->id);
            $historial = DB::table('activo_user as au')
            ->select(
                'u.name',
                'ar.aula',
                'au.grupo',
                DB::raw('DATE(au.fecha) as fecha'),
                DB::raw('COUNT(au.activo_id) as total_activos')
            )
            ->join('activos as a', 'au.activo_id', '=', 'a.id')
            ->join('users as u', 'a.responsable_id', '=', 'u.id')
            ->join('areas as ar', 'a.area_id', '=', 'ar.id')
            ->where('au.report', true)
            ->where('u.id', $request->user_id)
            ->where('ar.id', $request->area_id)
            ->groupBy('u.name', 'ar.aula', 'au.grupo', DB::raw('DATE(au.fecha)'))
            ->orderBy(DB::raw('DATE(au.fecha)'), 'desc');
            //->where();
            return $historial->get();
        } catch (e) {
            Log::error('Error al generar datos del dashboard: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
    public function reporteinventario(Request $request)
    {
        $rules = [
            'responsable_id' => 'required|integer|exists:users,id',
            'area_id'        => 'required|integer|exists:areas,id',
            'id'        => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $validator->errors()->toArray()
            ], 422);
        }
        $user=User::find($request->id);
        $today=date('Y-m-d');
        $activos=DB::table('activo_user as au')
        ->join('activos as a', 'au.activo_id', '=', 'a.id')
        ->join('areas as ar', 'a.area_id', '=', 'ar.id')
        ->join('users as r', 'a.responsable_id', '=', 'r.id')
        ->where('au.report', false)
        ->where('a.responsable_id', $request->responsable_id)
        ->where('a.area_id', $request->area_id)
        ->where('au.user_id', $user->id)
        ->whereIn('au.id', function ($query) {
            $query->select(DB::raw('MAX(id)'))
                  ->from('activo_user')
                  ->groupBy('activo_id');
        })
        ->select(
            DB::raw('MAX(au.id) as aux_id'),
            'au.activo_id as au_a_id',
            'au.fecha as fecha_registro',
            'au.report',
            'au.user_id_two',
            'au.grupo as grupo',
            'a.id as a_id',
            'a.codigo',
            'a.denominacion',
            'a.marca',
            'a.modelo',
            'a.tipo',
            'a.numero_serie',
            'a.dimension',
            'a.estado',
            'a.condicion',
            'a.descripcion',
            'ar.id as ar_id',
            'ar.aula',
            'ar.oficina_id',
            'r.id as r_id',
            'r.dni as r_dni',
            'r.name as r_name',
            'au.item'
        )
        ->groupBy('au.activo_id', 'a.id', 'au.grupo', 'r.id', 'ar.id', 'a.codigo', 'a.denominacion',
        'a.marca', 'a.modelo', 'a.tipo', 'au.user_id_two',
        'a.numero_serie', 'a.dimension', 'a.estado',
        'a.condicion', 'a.descripcion', 'ar.aula',
        'ar.oficina_id', 'r.dni', 'r.name',
        'au.fecha', 'au.report', 'au.item')
        ->orderByRaw('ISNULL(au.item), au.item ASC');
        //->orderBy('au.item');
        //->orderBy('ar.id')
        $user_two=null;
        if($request->user_id_two){
            $activos->where('au.user_id_two', $request->user_id_two);
            $user_two=User::find($request->user_id_two);
        }
        else{
            $activos->whereNull('au.user_id_two');
        }
        $activos=$activos->get();
        $area=Area::find($request->area_id);
        $total = DB::table('activo_user as au')
        ->join('activos', 'au.activo_id', '=', 'activos.id')
        ->join('areas', 'activos.area_id', '=', 'areas.id')
        ->where('areas.oficina_id', $area->oficina_id)
        ->where('au.report', true)
        ->where('au.fecha', '!=', date('Y-m-d'))
        ->where('au.grupo', $user->grupo)
        ->count();
        $index=1;
        foreach($activos as $activo){
            DB::table('activo_user')->where('id', $activo->aux_id)->update(['report' => true]);
            //if(!$activo->item){
                DB::table('activo_user')->where('id', $activo->aux_id)->update(['item'=>$total+$index]);
                $index++;
            //}
        }
        if($activos->isEmpty()){
            return response()->json([
                'status'=>false,
                'message'=>"Aun no tienes registros",
                'data'=>[]
            ]);
        }
        $htmlBody = view('pdf.historial_body', compact('activos'))->render();
            $header   = view('pdf.historial_header', compact('activos', 'area', 'user', 'user_two'))->render();
            $footer   = view('pdf.historial_footer', compact('activos', 'user', 'user_two'))->render();
            $mpdf = new Mpdf([
                'format' => 'A4-L',
                'margin_top' => 45,
                'margin_bottom' => 45,
            ]);
            $mpdf->SetHTMLHeader($header);
            $mpdf->SetHTMLFooter($footer);
            $mpdf->WriteHTML($htmlBody);
            return response($mpdf->Output('', 'S'))->header('Content-Type', 'application/pdf');
    }
    public function historialPdf(Request $request)
    {
        try {
            $user=User::find($request->id);
            $activos=DB::table('activo_user as au')
            ->join('activos as a', 'au.activo_id', '=', 'a.id')
            ->join('areas as ar', 'a.area_id', '=', 'ar.id')
            ->join('users as r', 'a.responsable_id', '=', 'r.id')
            ->where('au.report', true)
            ->where('a.responsable_id', $request->responsable_id)
            ->where('a.area_id', $request->area_id)
            ->whereDate('au.fecha', $request->fecha)
            //->where(DB::raw('DATE(au.fecha)'), $request->fecha)
            ->where('au.user_id', $user->id)
            ->whereIn('au.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                      ->from('activo_user')
                      ->groupBy('activo_id');
            })
            ->select(
                DB::raw('MAX(au.id) as aux_id'),
                'au.activo_id as au_a_id',
                'au.fecha as fecha_registro',
                'au.report',
                'au.user_id_two',
                'au.grupo as grupo',
                'a.id as a_id',
                'a.codigo',
                'a.denominacion',
                'a.marca',
                'a.modelo',
                'a.tipo',
                'a.numero_serie',
                'a.dimension',
                'a.estado',
                'a.condicion',
                'a.descripcion',
                'ar.id as ar_id',
                'ar.aula',
                'ar.oficina_id',
                'r.id as r_id',
                'r.dni as r_dni',
                'r.name as r_name',
                'au.item'
            )
            ->groupBy('au.activo_id', 'a.id', 'au.grupo', 'r.id', 'ar.id', 'a.codigo', 'a.denominacion',
            'a.marca', 'a.modelo', 'a.tipo', 'au.user_id_two',
            'a.numero_serie', 'a.dimension', 'a.estado',
            'a.condicion', 'a.descripcion', 'ar.aula',
            'ar.oficina_id', 'r.dni', 'r.name',
            'au.fecha', 'au.report', 'au.item')
            ->orderByRaw('ISNULL(au.item), au.item ASC');
            $user_two=null;
            if($request->user_id_two){
                $activos->where('au.user_id_two', $request->user_id_two);
                $user_two=User::find($request->user_id_two);
            }
            else{
                $activos->whereNull('au.user_id_two');
            }
            $activos=$activos->get();
            $area=Area::find($request->area_id);
            $total = DB::table('activo_user as au')
            ->join('activos', 'au.activo_id', '=', 'activos.id')
            ->join('areas', 'activos.area_id', '=', 'areas.id')
            ->where('areas.oficina_id', $area->oficina_id)
            ->where('au.report', true)
            ->where('au.fecha', '!=', date('Y-m-d'))
            ->where('au.grupo', $user->grupo)
            ->count();
            $index=1;
            foreach($activos as $activo){
                DB::table('activo_user')->where('id', $activo->aux_id)->update(['report' => true]);
                if(!$activo->item){
                    DB::table('activo_user')->where('id', $activo->aux_id)->update(['item'=>$total+$index]);
                    $index++;
                }
            }
            if($activos->isEmpty()){
                return response()->json([
                    'status'=>false,
                    'message'=>"Aun no tienes registros",
                    'data'=>[]
                ]);
            }
            $htmlBody = view('pdf.historial_body', compact('activos'))->render();
            $header   = view('pdf.historial_header', compact('activos', 'area', 'user', 'user_two'))->render();
            $footer   = view('pdf.historial_footer', compact('activos', 'user', 'user_two'))->render();
            $mpdf = new Mpdf([
                'format' => 'A4-L',
                'margin_top' => 45,
                'margin_bottom' => 45,
            ]);
            $mpdf->SetHTMLHeader($header);
            $mpdf->SetHTMLFooter($footer);
            $mpdf->WriteHTML($htmlBody);
            return response($mpdf->Output('', 'S'))->header('Content-Type', 'application/pdf');
        } catch (\Exception $e) {
            Log::error('Error al generar datos del dashboard: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
    public function historialPdfs(Request $request)
    {
        try {
            $user=User::find($request->id);
            $activos=DB::table('activo_user as au')
            ->join('activos as a', 'au.activo_id', '=', 'a.id')
            ->join('areas as ar', 'a.area_id', '=', 'ar.id')
            ->join('users as r', 'a.responsable_id', '=', 'r.id')
            ->where('au.report', true)
            ->where('a.responsable_id', $request->responsable_id)
            ->where('a.area_id', $request->area_id)
            ->whereDate('au.fecha', $request->fecha)
            //->where(DB::raw('DATE(au.fecha)'), $request->fecha)
            ->where('au.user_id', $user->id)
            ->whereIn('au.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                      ->from('activo_user')
                      ->groupBy('activo_id');
            })
            ->select(
                DB::raw('MAX(au.id) as aux_id'),
                'au.activo_id as au_a_id',
                'au.fecha as fecha_registro',
                'au.report',
                'au.user_id_two',
                'au.grupo as grupo',
                'a.id as a_id',
                'a.codigo',
                'a.denominacion',
                'a.marca',
                'a.modelo',
                'a.tipo',
                'a.numero_serie',
                'a.dimension',
                'a.estado',
                'a.condicion',
                'a.descripcion',
                'ar.id as ar_id',
                'ar.aula',
                'ar.oficina_id',
                'r.id as r_id',
                'r.dni as r_dni',
                'r.name as r_name',
                'au.item'
            )
            ->groupBy('au.activo_id', 'a.id', 'au.grupo', 'r.id', 'ar.id', 'a.codigo', 'a.denominacion',
            'a.marca', 'a.modelo', 'a.tipo', 'au.user_id_two',
            'a.numero_serie', 'a.dimension', 'a.estado',
            'a.condicion', 'a.descripcion', 'ar.aula',
            'ar.oficina_id', 'r.dni', 'r.name',
            'au.fecha', 'au.report', 'au.item')
            ->orderByRaw('ISNULL(au.item), au.item ASC');
            $user_two=null;
            if($request->user_id_two){
                $activos->where('au.user_id_two', $request->user_id_two);
                $user_two=User::find($request->user_id_two);
            }
            else{
                $activos->whereNull('au.user_id_two');
            }
            $activos=$activos->get();
            $area=Area::find($request->area_id);
            $total = DB::table('activo_user as au')
            ->join('activos', 'au.activo_id', '=', 'activos.id')
            ->join('areas', 'activos.area_id', '=', 'areas.id')
            ->where('areas.oficina_id', $area->oficina_id)
            ->where('au.report', true)
            ->where('au.fecha', '!=', date('Y-m-d'))
            ->where('au.grupo', $user->grupo)
            ->count();
            $index=1;
            foreach($activos as $activo){
                DB::table('activo_user')->where('id', $activo->aux_id)->update(['report' => true]);
                if(!$activo->item){
                    DB::table('activo_user')->where('id', $activo->aux_id)->update(['item'=>$total+$index]);
                    $index++;
                }
            }
            if($activos->isEmpty()){
                return response()->json([
                    'status'=>false,
                    'message'=>"Aun no tienes registros",
                    'data'=>[]
                ]);
            }
            $pdf = PDF::loadView('pdf.reportev2', [
                'activos' => $activos,
                'area'=>$area,
                'inventariador'=>$user,
                'total'=>$total,
                'user_two'=>$user_two
            ]);
            $pdf->setPaper('a4', 'landscape');
            return $pdf->stream('reporete-' . '.pdf');
        } catch (e) {
            Log::error('Error al generar datos del dashboard: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
    public function habilitar(Request $request)
    {
        DB::table('activo_user')
            ->where('activo_id', $request->id)
            //->update(['report' => 0]);
            ->delete();

        return response()->json([
            'status'=>true,
            'message'=>'Registros habilitados correctamente',
            'data'=>[]
        ]);
    }
    public function reportepdf(Request $request)
    {
        $rules = [
            'responsable_id' => 'required|integer|exists:users,id',
            'area_id'        => 'required|integer|exists:areas,id',
            'id'        => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $validator->errors()->toArray()
            ], 422);
        }
        $user=User::find($request->id);
        $activos=DB::table('activo_user as au')
        ->join('activos as a', 'au.activo_id', '=', 'a.id')
        ->join('areas as ar', 'a.area_id', '=', 'ar.id')
        ->join('users as r', 'a.responsable_id', '=', 'r.id')
        ->where('au.report', false)
        ->where('a.responsable_id', $request->responsable_id)
        ->where('a.area_id', $request->area_id)
        //->where('au.fecha', date('Y-m-d'))
        ->where('au.user_id', $user->id)
        ->whereIn('au.id', function ($query) {
            $query->select(DB::raw('MAX(id)'))
                  ->from('activo_user')
                  ->groupBy('activo_id');
        })
        //->where(function($query) use ($today) {
        //    $query->where(function($q) use ($today) {
        //        $q->where('au.report', false); // caso report = false, siempre incluir
        //    })
        //    ->orWhere(function($q) use ($today) {
        //        $q->where('au.report', true)
        //          ->whereDate('au.fecha', $today); // report = true solo si es hoy
        //    });
        //})
        ->select(
            DB::raw('MAX(au.id) as aux_id'),
            'au.activo_id as au_a_id',
            'au.fecha as fecha_registro',
            'au.user_id_two',
            'a.id as a_id',
            'a.codigo',
            'a.denominacion',
            'a.marca',
            'a.modelo',
            'a.tipo',
            'a.numero_serie',
            'a.dimension',
            'a.estado',
            'a.condicion',
            'a.descripcion',
            'r.id as r_id',
            'r.dni as r_dni',
            'r.name as r_name'
        )
        ->groupBy('au.activo_id', 'a.id', 'au.grupo', 'r.id', 'ar.id', 'a.codigo', 'a.denominacion',
        'a.marca', 'a.modelo', 'a.tipo', 'au.user_id_two',
        'a.numero_serie', 'a.dimension', 'a.estado',
        'a.condicion', 'a.descripcion', 'ar.aula',
        'ar.oficina_id', 'r.dni', 'r.name',
        'au.fecha', 'au.report', 'au.item')
        ->orderByRaw('ISNULL(au.item), au.item ASC');
        //->orderBy('au.item');
        //->orderBy('ar.id');
        if($request->user_id_two){
            $activos->where('au.user_id_two', $request->user_id_two);
        }
        else{
            $activos->whereNull('au.user_id_two');
        }
        return $activos->get();
        $sub = DB::table('activo_user')
            ->join('activos', 'activo_user.activo_id', '=', 'activos.id')
            ->join('areas', 'activos.area_id', '=', 'areas.id')
            ->select(DB::raw('MAX(activo_user.id) as last_id'), 'activos.denominacion', 'areas.oficina_id')
            ->groupBy('activo_user.activo_id', 'activos.denominacion', 'areas.oficina_id');
        $activos = DB::table('activo_user as au')
        ->select(
            'a.*',
            'ar.oficina_id',
            'au.fecha as fecha_registro',
            'au.report',
            'au.id as aux_id',
            'au.grupo as grupo',
            'r.id as r_id',
            'r.dni as r_dni',
            'r.name as r_name',
        )
        ->joinSub($sub, 'sub', fn($join) => $join->on('au.id', '=', 'sub.last_id'))
        ->join('activos as a', 'a.id', '=', 'au.activo_id')
        ->leftJoin('areas as ar', 'a.area_id', '=', 'ar.id')
        ->leftJoin('users as r', 'a.responsable_id', '=', 'r.id')
        //->where('au.user_id', $user->id)
        ->where('au.grupo', $user->grupo)
        ->where('au.report', false)
        ->get();
        $end=$activos->last();
        $activosFilter = $activos->filter(function($activo) use ($end) {
            return $activo->area_id == $end->area_id;
        });
        $activos=$activosFilter->values();
        $total = DB::table('activo_user as au')
        ->join('activos', 'au.activo_id', '=', 'activos.id')
        ->join('areas', 'activos.area_id', '=', 'areas.id')
        ->where('areas.oficina_id', $end->oficina_id)
        ->where('au.report', true)
        ->where('au.grupo', $user->grupo)
        ->count();
        $index=1;
        foreach($activos as $activo){
            DB::table('activo_user')->where('id', $activo->aux_id)->update(['report' => true]);
            DB::table('activo_user')->where('id', $activo->aux_id)->update(['item'=>$total+$index]);
            $index++;
        }
        if($activos->isEmpty()){
            return "Aun no tienes registros";
        }
        $area=Area::find($activos[0]->area_id);
        $pdf = PDF::loadView('pdf.reporte', [
            'activos' => $activos,
            'area'=>$area,
            'inventariador'=>$user,
            'total'=>$total
        ]);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->stream('reporete-' . '.pdf');
    }

    public function exportActivos()
    {
        return Excel::download(new ActivosExport, 'activos.xlsx');
    }
}