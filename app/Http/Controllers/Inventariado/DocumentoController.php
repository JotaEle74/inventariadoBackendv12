<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['success' => true]);
    }

    public function show($id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => null]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return response()->json(['success' => true]);
    }

    public function destroy($id): JsonResponse
    {
        return response()->json(['success' => true]);
    }
}
