<?php

namespace App\Http\Requests\Activo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreActivoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'codigo' => 'required|string|max:50|unique:activos,codigo',
            'denominacion' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            //'catalogo_id' => 'required|exists:catalogo_bienes,id',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'aula'=>'nullable|string|max:10',
            'numero_serie' => 'nullable|string|max:100',
            'dimension'=>'nullable|string|max:100',
            'aula'=>'nullable|string|max:8',
            'fecha_adquisicion' => 'required|date',
            'valor_inicial' => 'nullable|numeric|min:0',
            'estado' => 'required|string|in:activo,inactivo',
            'condicion' => 'required|string|in:nuevo,bueno,regular,malo',
            'declaracion_id' => 'nullable|string',
            'area_id' => 'required|exists:areas,id',
            'piso' => 'nullable|string',
            'responsable_id' => 'required|exists:users,id',
            'dniInventariador'=> 'nullable|string',
            'nombreInventariador'=> 'nullable|string',
            'tipo'=>'nullable|string|in:AF,ND,AU',
            'edificio_id'=>'nullable|exists:edificios,id',
            'telefono'=>'nullable|string'
        ];
    }

    protected function passedValidation()
    {
        $data = $this->validated();
        
        if (isset($data['estado'])) {
            $data['estado'] = $this->convertEstado($data['estado']);
        }
        
        // Convertir condición
        if (isset($data['condicion'])) {
            $data['condicion'] = $this->convertCondicion($data['condicion']);
        }
        
        $this->merge($data);
    }

    private function convertEstado($estado)
    {
        $estados = [
            'activo' => 'A',
            'inactivo' => 'I'
        ];
        $convertido = $estados[strtolower($estado)] ?? 'A';
        return $convertido;
    }

    private function convertCondicion($condicion)
    {
        $condiciones = [
            'nuevo' => 'N',
            'bueno' => 'B',
            'regular' => 'R',
            'malo' => 'M',
        ];
        $convertido = $condiciones[strtolower($condicion)] ?? 'N';
        return $convertido;
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
