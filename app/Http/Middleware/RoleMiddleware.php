<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role)
    {
        // Si no hay usuario autenticado
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
                'error' => 'Debe iniciar sesión para acceder a este recurso'
            ], 401);
        }

        // Verificar si el usuario tiene el rol
        if (!$request->user()->hasRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
                'error' => 'No tienes el rol requerido: ' . $role
            ], 403);
        }

        return $next($request);
    }
} 