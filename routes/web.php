<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Inventariado\ActivosController;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/export-activos', [ActivosController::class, 'exportActivos']);
Route::get('/login', function () {
    return response()->json(['message' => 'Debe estar autenticado'], 401);
})->name('login');
