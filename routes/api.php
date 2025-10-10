<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Inventariado\ActivosController;
use App\Http\Controllers\Inventariado\ProveedorController;
use App\Http\Controllers\Inventariado\AreaController;
use App\Http\Controllers\Inventariado\EntidadController;
use App\Http\Controllers\Inventariado\OficinaController;
use App\Http\Controllers\Inventariado\CatalogoBienesController;
use App\Http\Controllers\Inventariado\DocumentoController;
use App\Http\Controllers\Inventariado\MantenimientoController;
use App\Http\Controllers\Inventariado\MovimientoController;
use App\Http\Controllers\Inventariado\MovimientoActivoController;
use App\Http\Controllers\Inventariado\ItoController;
use App\Http\Controllers\Inventariado\DeclaracionController;
use App\Http\Controllers\Inventariado\SoftwareController;
use App\Http\Controllers\Api\UsuarioController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('auth.forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware('signed')->name('verification.verify');
    Route::get('/usuarios', [AuthController::class, 'usersAll'])->middleware(['auth:sanctum', 'verified']);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'userProfile'])->name('auth.user-profile');
        Route::get('/meusuario', [AuthController::class, 'usuarioProfile'])->name('auth.usuario-profile');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/profile', [AuthController::class, 'userProfile'])->name('auth.profile');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('auth.profile.update');
        Route::post('/email/verify/resend', [AuthController::class, 'resendVerification'])->name('verification.resend');
        
        Route::get('/sessions', [AuthController::class, 'activeSessions'])->name('auth.sessions');
        Route::delete('/sessions/{id}', [AuthController::class, 'revokeSession'])->name('auth.sessions.revoke');
        Route::delete('/sessions', [AuthController::class, 'revokeAllSessions'])->name('auth.sessions.revoke-all');
    });
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('auth/users')->group(function () {
        Route::get('/', [UsersController::class, 'index'])->middleware('permission:usuarios.view');
        Route::post('/', [UsersController::class, 'store'])->middleware('permission:usuarios.create');
        Route::get('/{id}', [UsersController::class, 'show'])->middleware('permission:usuarios.view');
        Route::put('/{id}', [UsersController::class, 'update'])->middleware('permission:usuarios.edit');
        Route::delete('/{id}', [UsersController::class, 'destroy'])->middleware('permission:usuarios.delete');
        Route::get('/{id}/roles', [UsersController::class, 'roles'])->middleware('permission:roles.view');
        Route::post('/{id}/assign-role', [UsersController::class, 'assignRole'])->middleware('permission:roles.edit');
    });

    // Roles y Permisos - Solo admin puede gestionar
    Route::prefix('auth/roles')->middleware('permission:roles.view')->group(function () {
        Route::get('/', [RolePermissionController::class, 'getRoles']);
        Route::post('/', [RolePermissionController::class, 'createRole']);
        Route::get('/permissions', [RolePermissionController::class, 'getPermissions']);
        Route::post('/permissions', [RolePermissionController::class, 'createPermission']);
        Route::post('/{role}/permissions', [RolePermissionController::class, 'assignPermissionToRole']);
    });
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('movimientos-activos')->group(function () {
        Route::get('/', [MovimientoActivoController::class, 'index'])->middleware('permission:movimientos.view');
        Route::post('/', [MovimientoActivoController::class, 'store'])->middleware('permission:movimientos.create');
        Route::post('/multiple', [MovimientoActivoController::class, 'storeMultiple'])->middleware('permission:movimientos.create');
        Route::get('/{movimientoActivo}', [MovimientoActivoController::class, 'show'])->middleware('permission:movimientos.view');
        Route::put('/{movimientoActivo}', [MovimientoActivoController::class, 'update'])->middleware('permission:movimientos.edit');
        Route::delete('/{movimientoActivo}', [MovimientoActivoController::class, 'destroy'])->middleware('permission:movimientos.delete');
        Route::get('/activo/{activo}', [MovimientoActivoController::class, 'movimientosPorActivo'])->middleware('permission:movimientos.view');
        Route::get('/pdf/{id}', [MovimientoActivoController::class, 'generarPDF'])->middleware('permission:reportes.export')->name('movimientos.pdf');
        
        // Proceso de entrega-recepción
        Route::post('/{movimientoActivo}/entregar', [MovimientoActivoController::class, 'entregar'])->middleware('permission:movimientos.edit');
        Route::post('/{movimientoActivo}/recibir', [MovimientoActivoController::class, 'recibir'])->middleware('permission:movimientos.edit');
        Route::post('/{movimientoActivo}/rechazar', [MovimientoActivoController::class, 'rechazar'])->middleware('permission:movimientos.edit');
    });

    Route::prefix('auth/movimientos')->group(function () {
        Route::get('/', [MovimientoController::class, 'index']);
        Route::post('/', [MovimientoController::class, 'store']);
        Route::get('/{movimiento}', [MovimientoController::class, 'show'])->middleware('permission:movimientos.view');
        Route::post('/{movimiento}/entregar', [MovimientoController::class, 'entregar']);
        Route::post('/{movimiento}/recibir', [MovimientoController::class, 'recibir']);
        Route::post('/{movimiento}/rechazar', [MovimientoController::class, 'rechazar']);
        Route::get('/{movimiento}/pdf', [MovimientoController::class, 'generarPDF']);
    });
});

//Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Catalogo
    Route::prefix('auth/activos')->group(function () {
        Route::get('/', [ActivosController::class, 'index']);//->middleware('permission:activos.view');
        Route::post('/', [ActivosController::class, 'store']);//->middleware('permission:activos.create');
        Route::get('/dashboard', [ActivosController::class, 'dashboard']);
        Route::get('/inventariador', [ActivosController::class, 'inventariador']);
        Route::get('/{activo}', [ActivosController::class, 'show']);//->middleware('permission:activos.view');
        Route::put('/{activo}', [ActivosController::class, 'update']);
        Route::delete('/{activo}', [ActivosController::class, 'destroy']);//->middleware('permission:activos.delete');
    });

    Route::prefix('auth/catalogobienes')->group(function () {
        Route::get('/', [CatalogoBienesController::class, 'index']);
        Route::post('/', [CatalogoBienesController::class, 'store']);//->middleware('permission:configuracion.edit');
        Route::get('/{catalogo}', [CatalogoBienesController::class, 'show']);
        Route::put('/{catalogo}', [CatalogoBienesController::class, 'update']);//->middleware('permission:configuracion.edit');
        Route::delete('/{catalogo}', [CatalogoBienesController::class, 'destroy']);//->middleware('permission:configuracion.edit');
    });

    // Ubicaciones
    Route::prefix('auth/areas')->group(function () {
        Route::get('/', [AreaController::class, 'index']);
        Route::post('/', [AreaController::class, 'store']);//->middleware('permission:configuracion.edit');
        Route::get('/{area}', [AreaController::class, 'show']);
        Route::put('/{area}', [AreaController::class, 'update']);//->middleware('permission:configuracion.edit');
        Route::delete('/{area}', [AreaController::class, 'destroy']);//->middleware('permission:configuracion.edit');
    });

    // Proveedores
    Route::prefix('auth/proveedores')->group(function () {
        Route::get('/', [ProveedorController::class, 'index']);
        Route::post('/', [ProveedorController::class, 'store'])->middleware('permission:configuracion.edit');
        Route::get('/{proveedor}', [ProveedorController::class, 'show']);
        Route::put('/{proveedor}', [ProveedorController::class, 'update'])->middleware('permission:configuracion.edit');
        Route::delete('/{proveedor}', [ProveedorController::class, 'destroy'])->middleware('permission:configuracion.edit');
    });

    // Departamentos//centro de costo
    Route::prefix('auth/oficinas')->group(function () {
        Route::get('/', [OficinaController::class, 'index']);
        Route::post('/', [OficinaController::class, 'store']);//;->middleware('permission:configuracion.edit');
        Route::get('/{oficina}', [OficinaController::class, 'show']);
        Route::put('/{oficina}', [OficinaController::class, 'update']);//->middleware('permission:configuracion.edit');
        Route::delete('/{oficina}', [OficinaController::class, 'destroy']);//->middleware('permission:configuracion.edit');
    });

    Route::prefix('auth/itos')->group(function () {
        Route::get('/', [ItoController::class, 'index']);
        Route::post('/', [ItoController::class, 'store']);
        Route::get('/{ito}', [ItoController::class, 'show']);
        Route::put('/{ito}', [ItoController::class, 'update']);
    });

    Route::prefix('auth/declaraciones')->group(function () {
        Route::get('/', [DeclaracionController::class, 'index']);
        Route::post('/', [DeclaracionController::class, 'store']);
        Route::get('/endpoint', [DeclaracionController::class, 'endDeclaration']);
        Route::get('reporte', [DeclaracionController::class, 'reporteActivosconDeclaraciones']);
        Route::put('/{declaracion}', [DeclaracionController::class, 'update']);
        Route::get('/{declaracion}', [DeclaracionController::class, 'show']);
        Route::delete('/{declaracion}', [DeclaracionController::class, 'destroy']);
        Route::get('/{declaracion}/pdf', [DeclaracionController::class, 'generarPDF']);
    });
//});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('mantenimientos')->group(function () {
        Route::get('/', [MantenimientoController::class, 'index'])->middleware('permission:activos.view');
        Route::post('/', [MantenimientoController::class, 'store'])->middleware('permission:activos.create');
        Route::get('/{mantenimiento}', [MantenimientoController::class, 'show'])->middleware('permission:activos.view');
        Route::put('/{mantenimiento}', [MantenimientoController::class, 'update'])->middleware('permission:activos.edit');
        Route::delete('/{mantenimiento}', [MantenimientoController::class, 'destroy'])->middleware('permission:activos.delete');
    });
});

Route::prefix('auth/software')->group(function () {
    Route::get('/', [SoftwareController::class, 'index']);
    Route::post('/', [SoftwareController::class, 'store']);
    Route::get('/activo/{activo}', [SoftwareController::class, 'installed']);
    Route::get('/{software}', [SoftwareController::class, 'show']);
    Route::put('/{software}', [SoftwareController::class, 'update']);
    Route::delete('/{software}', [SoftwareController::class, 'destroy']);
    Route::post('/instalar', [SoftwareController::class, 'install']);
});
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('documentos')->group(function () {
        Route::get('/', [DocumentoController::class, 'index'])->middleware('permission:activos.view');
        Route::post('/', [DocumentoController::class, 'store'])->middleware('permission:activos.create');
        Route::get('/{documento}', [DocumentoController::class, 'show'])->middleware('permission:activos.view');
        Route::put('/{documento}', [DocumentoController::class, 'update'])->middleware('permission:activos.edit');
        Route::delete('/{documento}', [DocumentoController::class, 'destroy'])->middleware('permission:activos.delete');
    });
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('auth/usuarios/software')->group(function () {
        Route::get('/', [UsuarioController::class, 'index'])->middleware('permission:software.view');
        Route::post('/', [UsuarioController::class, 'store'])->middleware('permission:software.view');
        Route::put('/{id}', [UsuarioController::class, 'update'])->middleware('permission:software.view');
        Route::delete('/{id}', [UsuarioController::class, 'destroy'])->middleware('permission:software.view');
    });
});

Route::middleware(['auth:sanctum', 'verified', 'permission:reportes.view'])->group(function () {
    // Aquí puedes agregar rutas específicas para reportes
    // Route::get('/reportes/activos', [ReporteController::class, 'activos']);
    // Route::get('/reportes/movimientos', [ReporteController::class, 'movimientos']);
    // Route::post('/reportes/export', [ReporteController::class, 'export'])->middleware('permission:reportes.export');
}); 