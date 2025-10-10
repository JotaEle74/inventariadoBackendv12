<?php

namespace App\Http\Requests\Movimiento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreMovimientoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'activos' => 'required|array|min:1',
            'activos.*.id' => 'required|exists:activos,id',
            'activos.*.observaciones' => 'nullable|string',
            'activos.*.area.id' => 'required|exists:areas,id',
            'receptor' => 'required|array',
            'receptor.id' => 'required|exists:users,id',
            'receptor.nombre' => 'required|string',
            'receptor.dni' => 'required|string',
            'receptor.oficina' => 'required|string',
            'receptor.entidad' => 'required|string',
            'usuario' => 'required|array',
            'usuario.id' => 'required|exists:users,id',
            'usuario.nombre' => 'required|string',
            'usuario.dni' => 'required|string',
            'usuario.oficina' => 'required|string',
            'usuario.entidad' => 'required|string',
            'observaciones' => 'nullable|string',
            'cambiarUbicacion' => 'required|boolean',
            'ubicacion' => 'required_if:cambiarUbicacion,true',
            'ubicacion.value' => 'required_if:cambiarUbicacion,true|exists:areas,id',
            'ubicacion.label' => 'required_if:cambiarUbicacion,true|string',
            'ubicacion_origen_id' => 'required|exists:oficinas,id',
            'ubicacion_destino_id' => 'required|exists:oficinas,id'
        ];
    }

    public function messages()
    {
        return [
            'activos.required' => 'Debe seleccionar al menos un activo',
            'activos.array' => 'El formato de activos no es válido',
            'activos.min' => 'Debe seleccionar al menos un activo',
            'activos.*.id.required' => 'El ID del activo es requerido',
            'activos.*.id.exists' => 'Uno o más activos seleccionados no existen',
            'receptor.required' => 'La información del receptor es requerida',
            'receptor.id.required' => 'El ID del receptor es requerido',
            'receptor.id.exists' => 'El receptor seleccionado no existe',
            'usuario.required' => 'La información del usuario es requerida',
            'usuario.id.required' => 'El ID del usuario es requerido',
            'usuario.id.exists' => 'El usuario seleccionado no existe',
            'activos.*.ubicacion.id.required' => 'La ubicación del activo es requerida',
            'activos.*.ubicacion.id.exists' => 'La ubicación del activo no existe',
            'cambiarUbicacion.required' => 'Debe especificar si se cambiará la ubicación',
            'cambiarUbicacion.boolean' => 'El valor de cambiarUbicacion debe ser verdadero o falso',
            'ubicacion.required_if' => 'La ubicación es requerida cuando se indica cambio de ubicación',
            'ubicacion.array' => 'El formato de ubicación no es válido',
            'ubicacion.value.required_if' => 'El ID de la ubicación es requerido cuando se indica cambio de ubicación',
            'ubicacion.value.exists' => 'La ubicación seleccionada no existe',
            'ubicacion.label.required_if' => 'La etiqueta de la ubicación es requerida cuando se indica cambio de ubicación',
            'ubicacion_origen_id.required' => 'La ubicación origen es requerida',
            'ubicacion_origen_id.exists' => 'La ubicación origen seleccionada no existe',
            'ubicacion_destino_id.required' => 'La ubicación destino es requerida',
            'ubicacion_destino_id.exists' => 'La ubicación destino seleccionada no existe'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();

        $formattedErrors = [];
        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0];
        }

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $formattedErrors
            ], 422)
        );
    }

    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->input('activos', []) as $i => $activo) {
                $movimientoActivo = \App\Models\Inventariado\MovimientoActivo::where('activo_id', $activo['id'])
                    ->orderByDesc('created_at')
                    ->first();

                if ($movimientoActivo && in_array($movimientoActivo->estado, ['pendiente', 'en_entrega'])) {
                    $validator->errors()->add("activos.$i", "El activo ya tiene un movimiento pendiente o entregado que debe ser recibido o rechazado antes de crear uno nuevo.");
                }
            }
        });
    }
} 

