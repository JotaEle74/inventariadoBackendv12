<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolePermissionMiddleware
{
    public function handle($request, Closure $next, $role = null, $permission = null)
    {
        // Si no hay usuario autenticado
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
                'error' => 'Debe iniciar sesión para acceder a este recurso'
            ], 401);
        }

        // Si se especifica un rol
        if ($role && !$request->user()->hasRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
                'error' => 'No tienes el rol requerido: ' . $role
            ], 403);
        }

        // Si se especifica un permiso
        if ($permission && !$request->user()->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
                'error' => 'No tienes el permiso requerido: ' . $permission
            ], 403);
        }

        return $next($request);
    }
}
