<?php

namespace App\Http\Requests\Software;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSoftwareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipo = $this->input('tipo');

        $rules = [
            'tipo' => 'required|in:desarrollo_interno,licencia_terceros,red_social',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'responsable_id' => 'required|exists:users,id',
            'area_id' => 'required|exists:areas,id',
            'estado' => 'required|string|max:50',
            'notas' => 'nullable|string',
        ];

        if ($tipo === 'desarrollo_interno') {
            $rules = array_merge($rules, [
                'url_acceso' => 'nullable|url|max:255',
                'tecnologias' => 'nullable|string|max:255',
                'ubicacion_servidor' => 'nullable|string|max:255',
            ]);
        } elseif ($tipo === 'licencia_terceros') {
            $rules = array_merge($rules, [
                'clave_licencia' => 'nullable|string',
                'tipo_licencia' => 'nullable|string|max:50',
                'cantidad_puestos' => 'nullable|integer|min:1',
                'fecha_compra' => 'nullable|date',
                'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_compra',
                'activos_asignados' => 'nullable|array',
                'activos_asignados.*' => 'exists:activos,id' // Validar que los IDs de activos existan
            ]);
        } elseif ($tipo === 'red_social') {
            $rules = array_merge($rules, [
                'plataforma' => 'required|string|max:100',
                'url_perfil' => 'nullable|url|max:255',
                'correo_institucional' => 'nullable|email|max:255',
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El valor seleccionado para :attribute no es válido.',
            'url' => 'El campo :attribute debe ser una URL válida.',
            'email' => 'El campo :attribute debe ser un correo electrónico válido.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $validator->errors()
        ], 422));
    }
}
