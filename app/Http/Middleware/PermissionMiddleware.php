<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle($request, Closure $next, $permission)
    {
        // Obtener usuario autenticado de ambos guards
        $user = $request->user();
        $usuario = auth('usuarios')->user();

        // Si no hay usuario autenticado en ninguno
        if (!$user && !$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
                'error' => 'Debe iniciar sesión para acceder a este recurso'
            ], 401);
        }

        $targetUserId = $request->route('id');

        // Permitir si está modificando su propio usuario (su propio id)
        if ($targetUserId && $user && $user->id == $targetUserId) {
            return $next($request);
        }
        if ($targetUserId && $usuario && $usuario->id == $targetUserId) {
            return $next($request);
        }
        // Verificar si el usuario tiene el permiso
        if ($user && !$user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
                'error' => 'No tienes el permiso requerido: ' . $permission
            ], 403);
        }
        if ($usuario && !$usuario->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
                'error' => 'No tienes el permiso requerido: ' . $permission
            ], 403);
        }

        return $next($request);
    }
} 