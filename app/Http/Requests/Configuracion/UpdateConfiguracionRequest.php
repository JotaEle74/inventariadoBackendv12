<?php

namespace App\Http\Requests\Configuracion;

class UpdateConfiguracionRequest extends StoreConfiguracionRequest
{
    public function rules()
    {
        return [
            'clave' => 'sometimes|string|max:100|unique:configuraciones,clave,' .$this->configuracion->id,
            'mostrar_botones' => 'sometimes|boolean',
        ];
    }
}
