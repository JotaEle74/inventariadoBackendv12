<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class BaseController extends Controller
{
    use ApiResponse;

    protected function handleException(Throwable $e): JsonResponse
    {
        // Log detallado del error
        \Log::error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        $code = $this->getExceptionStatusCode($e);
        $message = $this->getExceptionMessage($e);

        // Manejar tipos específicos de excepciones
        if ($e instanceof ValidationException) {
            return $this->validationErrorResponse($e->errors(), $message);
        }

        if ($e instanceof QueryException) {
            return $this->errorResponse(
                'Error en la base de datos',
                ['error' => $message],
                500
            );
        }

        // Manejar códigos HTTP comunes
        switch ($code) {
            case 404:
                return $this->notFoundResponse($message);
            case 401:
                return $this->unauthorizedResponse($message);
            case 403:
                return $this->forbiddenResponse($message);
            case 422:
                return $this->validationErrorResponse([], $message);
            default:
                return $this->errorResponse(
                    'Error en el servidor',
                    ['error' => $message],
                    $code
                );
        }
    }

    private function getExceptionStatusCode(Throwable $e): int 
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }
        
        return (int) ($e->getCode() ?: 500);
    }

    private function getExceptionMessage(Throwable $e): string
    {
        return app()->environment('production') 
            ? 'Ha ocurrido un error inesperado.'
            : $e->getMessage();
    }
}