<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Role;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Usuarios\StoreUsuariosRequest;
use App\Http\Requests\Usuarios\UpdateUsuariosRequest;
use App\Http\Resources\UsuarioResource;
class UsuarioController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Usuario::with(['role']);
        if($request->has('search')){
            $search=$request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $perPage = $request->integer('per_page', 15);
        $usuarios = $query->paginate($perPage);
        //return response()->json($usuarios);
        return UsuarioResource::Collection($usuarios);
    }

    public function store(StoreUsuariosRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = Usuario::create($validated);
        return response()->json("Usuario creado exitosamente");
    }

    // Mostrar un usuario
    public function show($id)
    {
        $user = Usuario::with(['role', 'oficinas.entidad'])->find($id);
        if (!$user) {
            return $this->notFoundResponse('Usuario no encontrado');
        }
        return $this->successResponse(new UserResource($user));
    }

    public function update(UpdateUsuariosRequest $request, $id)
    {
        $user = Usuario::find($id);
        if (!$user) {
            return $this->notFoundResponse('Usuario no encontrado');
        }
        $validated = $request->validated();
        if (isset($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }
        $user->update($validated);
        $user->load(['role']);
        return response()->json("Usuario actualizado exitosamente");
    }

    // Eliminar un usuario
    public function destroy($id)
    {
        $user = Usuario::find($id);
        if (!$user) {
            return $this->notFoundResponse('Usuario no encontrado');
        }
        $user->delete();
        return response()->json("Usuario eliminado exitosamente");
    }

    // Obtener todos los roles
    public function roles()
    {
        $roles = Role::all();
        return $this->successResponse(['roles' => $roles]);
    }

    // Asignar rol a un usuario
    public function assignRole(Request $request, $id)
    {
        $user = Usuario::find($id);
        if (!$user) {
            return $this->notFoundResponse('Usuario no encontrado');
        }
        
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);
        
        $user->update(['role_id' => $request->role_id]);
        $user->load('role');
        return response()->json('Rol asignado correctamente');
    }
}
