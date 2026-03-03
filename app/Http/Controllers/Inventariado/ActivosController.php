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
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Inventariado\Acta;
class ActivosController extends BaseController
{
    use ExportsAssets;

    public function inventariador(Request $request)
    {
        try {
            $query = DB::table('activos as a')
                ->leftJoin('activo_user as au', 'a.id', '=', 'au.activo_id')
                ->join('areas as ar', 'a.area_id', '=', 'ar.id')
                ->whereNull('au.activo_id') // 👈 activos sin asignación
                ->whereNull('a.deleted_at')//add deleted_at
                ->whereNull('au.deleted_at')//add deleted_at
                //->where('ar.oficina_id', $request->oficina_id)
                //->select('a.codigo', 'a.numero_serie', 'a.denominacion', 'ar.aula')
                ->orderBy('ar.aula', 'desc')
                ->orderBy('a.id', 'desc');
            if($request->has('oficina_id')){
                $query->select('a.codigo', 'a.numero_serie', 'a.denominacion', 'ar.aula', 'ar.codigo as arcodigo', 'ar.oficina_id');
                $query->where('ar.oficina_id', $request->oficina_id);
                return $query->get();
            }
            $query->select('a.codigo', 'a.numero_serie', 'a.denominacion', 'ar.aula');
            if($request->has('area_id')){
                $query->where('a.area_id', $request->area_id);
            }
            return $query->paginate(15);
            //$query=Activo::with(['catalogo:id,denominacion', 'responsable:id,name', 'area.oficina']);
            //$query=Activo::with(['responsable:id,name', 'area.oficina']);
            //$user = $request->user();
            //$oficinaIds = $user->oficinas->pluck('id');
            //$query->where('dniInventariador', $user->dni);
            //$query->where('dniInventariador', null);
            //$query->whereHas('area', function ($q) use ($oficinaIds) {
            //    $q->whereIn('oficina_id', $oficinaIds);
            //});
            //$query->select('id', 'codigo', 'numero_serie', 'color', 'denominacion', 'responsable_id');
            //$query->where('area_id',$request->area_id);
            //$perPage = $request->integer('per_page', 15);
            //$activos = $query->paginate($perPage);
            //return $activos;
        } catch (Exception $e) {
            Log::error('Error al listar activos: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
    public function index(Request $request)//: AnonymousResourceCollection|JsonResponse
    {
        try {
            $query = Activo::with(['area.oficina', 'responsable', 'edificio', 'users'])->whereNull('deleted_at');
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
                //$query->has('users');
                $query->orderBy('id', 'desc');
                $activos = $query->get();
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
            ->whereNull('au.deleted_at')
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
            ->whereNull('au.deleted_at')
            ->whereNotNull('au.grupo') 
            ->groupBy(DB::raw('DATE(au.fecha)'), 'au.grupo')
            ->orderBy('au.grupo')
            ->get();
            $activosPorOficina = DB::table('activos as a')
            ->join('areas as ar', 'a.area_id', '=', 'ar.id')
            ->join('oficinas as of', 'ar.oficina_id', '=', 'of.id') // corregido
            ->leftJoin('activo_user as au', 'a.id', '=', 'au.activo_id') // left join para incluir los no registrados
            ->whereNull('a.deleted_at')//add deleted_at
            ->whereNull('au.deleted_at')//add deleted_at
            ->select(
                'of.denominacion',
                DB::raw('COUNT(a.id) as total_activos'),
                DB::raw('SUM(CASE WHEN au.report = 1 THEN 1 ELSE 0 END) as registrados'),
                DB::raw('SUM(CASE WHEN au.report IS NULL OR au.report = 0 THEN 1 ELSE 0 END) as faltantes')
            )
            ->whereRaw("a.codigo REGEXP '^[0-9]+$'")
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

    public function update(UpdateActivoRequest $request, Activo $activo)//: JsonResponse add deleted_at
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            $activo->update($validatedData);
            $user = $request->user();
            $activo->update_user = $user->id;
            if ($user->role_id == 5 || $user->role_id == 2) {
                $user->activos()->attach($activo->id, ['fecha'=> now(), 'grupo'=>$user->grupo, 'user_id_two'=>$request->user_id_two, 'update_user'=>$user->id]);
            }
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
            $activo->update_user = auth()->id();
            $activo->save();
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
            $user=User::find($request->user_id);
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
            ->where('au.grupo', $user->grupo)
            //->where('u.id', $request->user_id)
            ->where('ar.id', $request->area_id)
            ->whereNull('a.deleted_at')//add deleted_at
            ->whereNull('au.deleted_at')//add deleted_at
            ->groupBy('u.name', 'ar.aula', 'au.grupo', DB::raw('DATE(au.fecha)'))
            ->orderBy(DB::raw('DATE(au.fecha)'), 'desc');
            //->where();
            $user_two=null;
            if($request->user_id_two){
                $historial->where('a.responsable_id', $request->responsable_id)->where('au.user_id_two', $request->user_id_two);
                $user_two=User::find($request->user_id_two);
            }
            else{
                $historial->where('a.responsable_id', $request->responsable_id)->whereNull('au.user_id_two');
            }
            return $historial->get();
        } catch (e) {
            Log::error('Error al generar datos del dashboard: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
    public function reporteinventario(Request $request)
    {
        // crear registro de acta con número secuencial de 3 dígitos
        $numero_acta = Acta::create([
            'numero_acta' => Acta::nextNumero(),
        ]);

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
        ->where('au.grupo', $user->grupo)
        ->whereNull('a.deleted_at')//add deleted_at
        ->whereNull('au.deleted_at')//add deleted_at
        //->where('au.user_id', $user->id)
        ->whereIn('au.id', function ($query) {
            $query->select(DB::raw('MAX(id)'))
                  ->from('activo_user')
                  ->whereNull('deleted_at')
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
            'a.aula as ambiente',
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
        'au.fecha', 'au.report', 'au.item', 'a.aula')
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
        //->where('au.grupo', $user->grupo)
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
        $htmlBody = view('pdf.historial_body', compact('activos', 'total'))->render();
            $header   = view('pdf.historial_header', compact('activos', 'area', 'user', 'user_two', 'numero_acta'))->render();
            $footer   = view('pdf.historial_footer', compact('activos', 'user', 'user_two'))->render();
            $mpdf = new Mpdf([
                'format' => 'A4-L',
                'margin_top' => 45,
                'margin_bottom' => 50,
            ]);
            $mpdf->SetHTMLHeader($header);
            $mpdf->SetHTMLFooter($footer);
            $mpdf->WriteHTML($htmlBody);
            return response($mpdf->Output('', 'S'))->header('Content-Type', 'application/pdf');
    }
    public function historialPdf(Request $request)
    {
        try {
            $numero_acta = new Acta(['numero_acta' => '______']);
            $user=User::find($request->user_id);//user
            $activos=DB::table('activo_user as au')
            ->join('activos as a', 'au.activo_id', '=', 'a.id')
            ->join('areas as ar', 'a.area_id', '=', 'ar.id')
            ->join('users as r', 'a.responsable_id', '=', 'r.id')
            ->where('au.report', true)
            ->where('a.responsable_id', $request->responsable_id)
            ->where('a.area_id', $request->area_id)
            ->where('au.grupo', $user->grupo)
            ->whereDate('au.fecha', $request->fecha)
            //->where(DB::raw('DATE(au.fecha)'), $request->fecha)
            //->where('au.user_id', $user->id)
            ->whereNull('a.deleted_at')//add deleted_at
            ->whereNull('au.deleted_at')//add deleted_at
            ->whereIn('au.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                      ->from('activo_user')
                      ->whereNull('deleted_at')
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
                'a.aula as ambiente',
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
            'au.fecha', 'au.report', 'au.item', 'a.aula')
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
            //->where('au.grupo', $user->grupo)
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
            $htmlBody = view('pdf.historial_body', compact('activos', 'total'))->render();
            $header   = view('pdf.historial_header', compact('activos', 'area', 'user', 'user_two', 'numero_acta'))->render();
            $footer   = view('pdf.historial_footer', compact('activos', 'user', 'user_two'))->render();
            $mpdf = new Mpdf([
                'format' => 'A4-L',
                'margin_top' => 45,
                'margin_bottom' => 50,
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
    public function habilitar(Request $request)
    {
        DB::table('activo_user')
            ->where('activo_id', $request->id)
            ->update(['deleted_at' => now(), 'update_user' => auth()->id()]);

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
        ->where('au.grupo', $user->grupo)
        ->whereNull('a.deleted_at')//add deleted_at
        ->whereNull('au.deleted_at')//add deleted_at
        ->whereIn('au.id', function ($query) {
            $query->select(DB::raw('MAX(id)'))
                  ->from('activo_user')
                  ->whereNull('deleted_at')
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
            ->whereNull('activo_user.deleted_at')//add deleted_at
            ->whereNull('activos.deleted_at')//add deleted_at
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
        ->whereNull('a.deleted_at')//add deleted_at
        ->whereNull('au.deleted_at')//add deleted_at
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
        ->whereNull('activos.deleted_at')//add deleted_at
        ->whereNull('au.deleted_at')//add deleted_at
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
        $response = new StreamedResponse(function () {

        // Abrir salida por streaming
        echo "\xEF\xBB\xBF";
        flush();
        $handle = fopen('php://output', 'w');

        // ENCABEZADOS del CSV
        fputcsv($handle, [
            'Código', 'Denominación', 'Marca', 'Modelo', 'Número Serie', 'Piso', 'Aula',
            'Descripción', 'Teléfono', 'Inventariador', 'Estado', 'Sit',
            'DNI Usuario', 'Nombre Usuario', 'Código Área', 'Aula Área', 'Código Oficina',
            'Denominación Oficina', 'Código Edificio', 'Edificio', 'Grupo', 'Item', 'Fecha'
        ], '|');

        // CONSULTA optimizada para streaming
        $query = DB::table('activos as a')
            ->select(
                'a.codigo', 'a.denominacion', 'a.marca', 'a.modelo', 'a.numero_serie',
                'a.piso', 'a.aula', 'a.descripcion', 'a.telefono', 'a.nombreInventariador',
                DB::raw("CASE a.condicion 
                            WHEN 'N' THEN 'nuevo' 
                            WHEN 'B' THEN 'bueno' 
                            WHEN 'R' THEN 'regular' 
                            WHEN 'M' THEN 'malo' 
                         END as condicion_nombre"),
                DB::raw("CASE a.estado 
                            WHEN 'A' THEN 'U' 
                            ELSE 'D'
                         END as estado_ud"),
                'u.dni as usuario_dni',
                'u.name as usuario_nombre',
                'ar.codigo as area_codigo',
                'ar.aula as area_aula',
                'ofc.codigo as oficina_codigo',
                'ofc.denominacion as oficina_denominacion',
                'ed.codigo as edificio_codigo',
                'ed.denominacion as edificio_denominacion',
                'au.grupo',
                'au.item',
                'au.fecha'
            )
            ->leftjoin('users as u', 'a.responsable_id', '=', 'u.id')
            ->leftjoin('areas as ar', 'a.area_id', '=', 'ar.id')
            ->leftjoin('oficinas as ofc', 'ar.oficina_id', '=', 'ofc.id')
            ->leftjoin('edificios as ed', 'a.edificio_id', '=', 'ed.id')
            ->leftJoin('activo_user as au', 'a.id', '=', 'au.activo_id')
            ->whereNull('au.deleted_at')
            ->orderBy('a.id', 'asc')
            ->cursor(); // <–– esencial para NO cargar todo en RAM

        // Procesar fila por fila sin usar memoria
        foreach ($query as $row) {

            fputcsv($handle, [
                $row->codigo,
                $row->denominacion,
                $row->marca,
                $row->modelo,
                $row->numero_serie,
                $row->piso,
                $row->aula,
                $row->descripcion,
                $row->telefono,
                $row->nombreInventariador,
                $row->condicion_nombre,
                $row->estado_ud,
                $row->usuario_dni,
                $row->usuario_nombre,
                $row->area_codigo,
                $row->area_aula,
                $row->oficina_codigo,
                $row->oficina_denominacion,
                $row->edificio_codigo,
                $row->edificio_denominacion,
                $row->grupo,
                $row->item,
                $row->fecha
            ], '|');

            flush(); // imprescindible para liberar memoria
        }

        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="activos.csv"');

    return $response;
    }
    public function reporteSoftwareOTI(Request $request)
    {
        try {
            $rules = [
                'inventariador_id' => 'required|integer|exists:users,id',
                'responsable_id' => 'required|integer|exists:users,id',
                'area_id' => 'required|integer|exists:areas,id',
                'responsable_software_id' => 'required|integer|exists:users,id',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors'  => $validator->errors()->toArray()
                ], 422);
            }

            // Obtener usuarios y área para el header
            $inventariador = User::find($request->inventariador_id);
            $responsable = User::find($request->responsable_id);
            $area = Area::with('oficina')->find($request->area_id);
            $responsableSoftware = User::find($request->responsable_software_id);

            // CONSULTAS REALES A LA BASE DE DATOS - FILTRADAS POR ÁREA Y RESPONSABLE

            // Software de terceros (licencia_terceros)
            $softwareTerceros = Software::where('tipo', 'licencia_terceros')
                ->where('area_id', $request->area_id)
                //->where('responsable_id', $request->responsable_software_id)
                ->where('responsable_id', $request->responsable_id)
                ->select(
                    'codigoA as codigo',
                    'denominacion',
                    'nombre',
                    DB::raw('COALESCE(tipo_licencia, "-") as fuente'),
                    DB::raw('COALESCE(notas, "-") as observaciones')
                )
                ->orderBy('codigoA')
                ->get();
            // Software internos (desarrollo_interno)
            $softwareInternos = Software::where('tipo', 'desarrollo_interno')
                ->where('area_id', $request->area_id)
                //->where('responsable_id', $request->responsable_software_id)
                ->select(
                    'nombre',
                    'denominacion',
                    'estado',
                    DB::raw('COALESCE(tecnologias, "-") as tecnologias')
                )
                ->orderBy('nombre')
                ->get();

            // Redes sociales (red_social)
            $redesSociales = Software::where('tipo', 'red_social')
                ->where('area_id', $request->area_id)
                //->where('responsable_id', $request->responsable_software_id)
                ->select(
                    'nombre',
                    DB::raw('COALESCE(descripcion, "-") as descripcion'),
                    'estado',
                    DB::raw('COALESCE(plataforma, "-") as plataforma')
                )
                ->orderBy('nombre')
                ->get();

            // Renderizar vistas
            $htmlBody = view('pdf.software_oti_body', compact('softwareTerceros', 'softwareInternos', 'redesSociales'))->render();
            $header   = view('pdf.software_oti_header', compact('area', 'responsableSoftware', 'responsable'))->render();
            $footer   = view('pdf.software_oti_footer', compact('inventariador', 'responsable'))->render();

            // Configurar mPDF
            $mpdf = new Mpdf([
                'format' => 'A4-L',
                'margin_top' => 40,
                'margin_bottom' => 35,
                'margin_left' => 10,
                'margin_right' => 10,
            ]);

            $mpdf->SetHTMLHeader($header);
            $mpdf->SetHTMLFooter($footer);
            $mpdf->WriteHTML($htmlBody);

            return response($mpdf->Output('', 'S'))->header('Content-Type', 'application/pdf');

        } catch (\Exception $e) {
            Log::error('Error al generar reporte de software OTI: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
    public function faltaReporte(Request $request){
        try {
            $rules = [
                'inventariador_id' => 'required|integer|exists:users,id'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors'  => $validator->errors()->toArray()
                ], 422);
            }
            $user=User::find($request->inventariador_id);
            $subQuery = DB::table('activo_user')
                ->select(
                    'activo_id',
                    DB::raw('MAX(fecha) as ultima_fecha')
                )
                //->where('report', false)  // Filtro de report = 0
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')//add deleted_at
                ->groupBy('activo_id');
            $historial = DB::table('activo_user as au')
                ->joinSub($subQuery, 'ultimos', function ($join) {
                    $join->on('au.activo_id', '=', 'ultimos.activo_id')
                         ->on('au.fecha', '=', 'ultimos.ultima_fecha');
                })
                ->select(
                    'u.name',
                    'u.dni',
                    'ar.aula',
                    'au.grupo',
                    //'au.id',
                    DB::raw('DATE(au.fecha) as fecha'),
                    DB::raw('COUNT(au.activo_id) as total_activos')
                )
                ->join('activos as a', 'au.activo_id', '=', 'a.id')
                ->join('users as u', 'a.responsable_id', '=', 'u.id')
                ->join('areas as ar', 'a.area_id', '=', 'ar.id')
                ->whereNull('a.deleted_at')//add deleted_at
                ->whereNull('au.deleted_at')//add deleted_at
                ->groupBy(
                    'u.name',
                    'u.dni',
                    'ar.aula',
                    'au.grupo',
                    //'au.id',
                    DB::raw('DATE(au.fecha)')
                )
                ->where('au.report', false)
                ->orderBy(DB::raw('DATE(au.fecha)'), 'desc');
            return $historial->get();
        } catch (\Exception $e) {
            Log::error('Error al generar reporte de software OTI: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
    public function faltaReportePdf(Request $request){
        try {
            // crear número de acta secuencial
            $numero_acta = Acta::create([
                'numero_acta' => Acta::nextNumero(),
            ]);
            $rules = [
                'inventariador_id' => 'required|integer|exists:users,id',
                'fecha' => 'required|date',
                'responsable_dni' => 'required|string|exists:users,dni',
                'area_aula' => 'required|string|exists:areas,aula',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors'  => $validator->errors()->toArray()
                ], 422);
            }

            $user = User::find($request->inventariador_id);
            $responsable = User::where('dni', $request->responsable_dni)->first();
            $area = Area::where('aula', $request->area_aula)->first();

            // Construir la consulta para obtener los registros más recientes por activo en la fecha indicada
            $activosQuery = DB::table('activo_user as au')
                ->join('activos as a', 'au.activo_id', '=', 'a.id')
                ->join('areas as ar', 'a.area_id', '=', 'ar.id')
                ->join('users as r', 'a.responsable_id', '=', 'r.id')
                ->where('au.report', false)
                ->where('a.responsable_id', $responsable->id)
                ->where('a.area_id', $area->id)
                ->where('au.grupo', $user->grupo)
                ->whereDate('au.fecha', $request->fecha)
                ->whereNull('a.deleted_at')//add deleted_at
                ->whereNull('au.deleted_at')//add deleted_at
                ->whereIn('au.id', function ($query) {
                    $query->select(DB::raw('MAX(id)'))
                          ->from('activo_user')
                          ->whereNull('deleted_at')
                          ->groupBy('activo_id');
                });

            if ($request->user_id_two) {
                $activosQuery->where('au.user_id_two', $request->user_id_two);
                $user_two = User::find($request->user_id_two);
            } else {
                $activosQuery->whereNull('au.user_id_two');
                $user_two = null;
            }
            $activos = $activosQuery
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
                    'a.aula as ambiente',
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
                'au.fecha', 'au.report', 'au.item', 'a.aula')
                ->orderByRaw('ISNULL(au.item), au.item ASC')
                ->get();

            // Calcular total ya reportados en la oficina (para numeración de items)
            $total = DB::table('activo_user as au')
                ->join('activos', 'au.activo_id', '=', 'activos.id')
                ->join('areas', 'activos.area_id', '=', 'areas.id')
                ->where('areas.oficina_id', $area->oficina_id)
                ->where('au.report', true)
                ->where('au.grupo', $user->grupo)
                ->whereNull('activos.deleted_at')//add deleted_at
                ->whereNull('au.deleted_at')//add deleted_at
                ->count();

            $index = 1;
            foreach ($activos as $activo) {
                DB::table('activo_user')->where('id', $activo->aux_id)->update(['report' => true]);
                if (!$activo->item) {
                    DB::table('activo_user')->where('id', $activo->aux_id)->update(['item' => $total + $index]);
                    $index++;
                }
            }

            if ($activos->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => "Aun no tienes registros",
                    'data' => []
                ]);
            }

            $htmlBody = view('pdf.historial_body', compact('activos', 'total'))->render();
            $header   = view('pdf.historial_header', compact('activos', 'area', 'user', 'user_two', 'numero_acta'))->render();
            $footer   = view('pdf.historial_footer', compact('activos', 'user', 'user_two'))->render();
            $mpdf = new Mpdf([
                'format' => 'A4-L',
                'margin_top' => 45,
                'margin_bottom' => 50,
            ]);
            $mpdf->SetHTMLHeader($header);
            $mpdf->SetHTMLFooter($footer);
            $mpdf->WriteHTML($htmlBody);
            return response($mpdf->Output('', 'S'))->header('Content-Type', 'application/pdf');
        } catch (\Exception $e) {
            Log::error('Error al generar reporte de software OTI: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }
}