<?php
namespace App\Http\Requests\Mantenimiento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreMantenimientoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'activo_id' => 'required|exists:activos,id',
            'tipo' => 'required|in:preventivo,correctivo,predictivo',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'costo' => 'required|numeric|min:0',
            'proveedor_id' => 'required|exists:proveedores,id',
            'descripcion' => 'required|string',
            'tecnico' => 'required|string|max:100',
            'estado' => 'required|in:programado,en_proceso,completado,cancelado',
            'notas' => 'nullable|string'
        ];
    }

    public function messages()
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El valor seleccionado para :attribute no es válido.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'min' => 'El campo :attribute debe ser al menos :min.',
            'in' => 'El campo :attribute debe ser uno de: :values.',
            'max' => 'El campo :attribute no debe exceder :max caracteres.',
            'unique' => 'El valor del campo :attribute ya está en uso.',
            'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a la fecha de adquisición.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();
        
        $formattedErrors = [];
        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0]; // Tomamos solo el primer mensaje por campo
        }

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $formattedErrors
            ], 422)
        );
    }
}