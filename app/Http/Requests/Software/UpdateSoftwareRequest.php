<?php

namespace App\Http\Requests\Software;

// Este request puede heredar del StoreSoftwareRequest si las reglas son idénticas
// o tener sus propias reglas si, por ejemplo, algunos campos no se pueden cambiar.

class UpdateSoftwareRequest extends StoreSoftwareRequest
{
    // Por ahora, las reglas de actualización son las mismas que las de creación.
    // Si necesitaras cambiar algo (ej. no permitir cambiar el 'tipo'),
    // podrías sobreescribir el método rules() aquí.
}
