<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

trait ApiResponse
{
    protected function getResponseStructure(bool $success, string $message, $data = null, array $errors = [], array $meta = []): array
    {
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => Carbon::now()->toIso8601String()
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return $response;
    }

    public function successResponse($data = null, string $message = 'Operación exitosa', int $code = 200, array $meta = []): JsonResponse
    {
        return response()->json(
            $this->getResponseStructure(true, $message, $data, [], $meta),
            $code
        );
    }

    public function errorResponse(string $message = 'Ocurrió un error', array $errors = [], int $code = 400, array $meta = []): JsonResponse
    {
        return response()->json(
            $this->getResponseStructure(false, $message, null, $errors, $meta),
            $code
        );
    }

    public function validationErrorResponse(array $errors = [], string $message = 'Errores de validación', array $meta = []): JsonResponse
    {
        return $this->errorResponse($message, $errors, 422, $meta);
    }

    public function notFoundResponse(string $message = 'Recurso no encontrado', array $meta = []): JsonResponse
    {
        return $this->errorResponse($message, [], 404, $meta);
    }

    public function unauthorizedResponse(string $message = 'No autorizado', array $meta = []): JsonResponse
    {
        return $this->errorResponse($message, [], 401, $meta);
    }

    public function forbiddenResponse(string $message = 'Acceso prohibido', array $meta = []): JsonResponse
    {
        return $this->errorResponse($message, [], 403, $meta);
    }

    public function conflictResponse(string $message = 'Conflicto detectado', array $meta = []): JsonResponse
    {
        return $this->errorResponse($message, [], 409, $meta);
    }

    public function serverErrorResponse(string $message = 'Error interno del servidor', array $meta = []): JsonResponse
    {
        return $this->errorResponse($message, [], 500, $meta);
    }
}