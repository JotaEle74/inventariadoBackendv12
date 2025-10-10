<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\{Role, Permission, User};
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Http\Resources\RoleResource;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RolePermissionController extends BaseController
{
    use ApiResponse;

    public function getRoles(): AnonymousResourceCollection | JsonResponse
    {
        $roles = Role::with('permissions')->get();
        return RoleResource::collection($roles);
    }

    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string'
        ]);

        $role = Role::create($request->only('name', 'description'));
        return new RoleResource($role);
    }
    
    public function assignRoleToUser(Request $request, User $user)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        $user->update(['role_id' => $request->role_id]);
        return $this->successResponse(null, 'Rol asignado al usuario exitosamente');
    }

    public function getPermissions()
    {
        $permissions = Permission::all();
        return PermissionResource::collection($permissions);
    }

    public function createPermission(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions|max:255',
            'description' => 'nullable|string'
        ]);

        $permission = Permission::create($request->only(['name', 'description']));
        return new PermissionResource($permission);
    }

    public function assignPermissionToRole(Request $request, Role $role)
    {
        $request->validate([
            'permission_id' => 'required|exists:permissions,id'
        ]);

        $role->permissions()->syncWithoutDetaching($request->permission_id);
        return $this->successResponse(null, 'Permiso asignado al rol correctamente');
    }
}
