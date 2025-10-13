<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
class UsersController extends Controller
{
    use ApiResponse;

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $query = User::with(['role', 'oficinas']);
        if($request->has('search')){
            $search=$request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $perPage = $request->integer('per_page', 15);
        $users = $query->paginate($perPage);
        return UserResource::Collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $oficinas = $validated['oficinas'] ?? [];
        unset($validated['oficinas']);
        $user = User::create($validated);
        $user->oficinas()->sync($oficinas);
        $user->load(['role', 'oficinas']);
        return $this->successResponse(new UserResource($user), 'Usuario creado correctamente', 201);
    }

    // Mostrar un usuario
    public function show($id)
    {
        $user = User::with(['role', 'oficinas.entidad'])->find($id);
        if (!$user) {
            return $this->notFoundResponse('Usuario no encontrado');
        }
        return $this->successResponse(new UserResource($user));
    }

    // Actualizar un usuario
    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->notFoundResponse('Usuario no encontrado');
        }
        $validated = $request->validated();
        if (isset($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }
        $oficinas = $validated['oficinas'] ?? null;
        unset($validated['oficinas']);
        $user->update($validated);
        if ($oficinas !== null) {
            $user->oficinas()->sync($oficinas);
        }
        $user->load(['role', 'oficinas']);
        return $this->successResponse(new UserResource($user), 'Usuario actualizado correctamente');
    }

    // Eliminar un usuario
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->notFoundResponse('Usuario no encontrado');
        }
        $user->delete();
        return $this->successResponse(null, 'Usuario eliminado correctamente');
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
        $user = User::find($id);
        if (!$user) {
            return $this->notFoundResponse('Usuario no encontrado');
        }
        
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);
        
        $user->update(['role_id' => $request->role_id]);
        $user->load('role');
        return $this->successResponse(new UserResource($user), 'Rol asignado correctamente');
    }
}
