<?php
namespace App\Http\Requests\Proveedor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProveedorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:100',
            'ruc' => 'required|numeric|digits_between:1,20|unique:proveedores,ruc',
            'contacto_nombre' => 'required|string|max:100',
            'contacto_telefono' => 'required|string|max:20',
            'contacto_email' => 'required|email|max:100',
            'direccion' => 'required|string',
            'activo' => 'sometimes|boolean'
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