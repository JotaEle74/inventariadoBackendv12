<?php
namespace App\Http\Requests\Proveedor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UpdateProveedorRequest extends StoreProveedorRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        Log::info('UpdateProveedorRequest - Datos de la ruta:', [
            'route_parameters' => $this->route()->parameters(),
            'proveedor_parameter' => $this->route('proveedor')
        ]);

        $proveedor = $this->route('proveedor');
        
        if (!$proveedor) {
            Log::error('UpdateProveedorRequest - Proveedor no encontrado en la ruta');
            throw new \Exception('No se pudo obtener el proveedor de la ruta');
        }

        if (!$proveedor->id) {
            Log::error('UpdateProveedorRequest - ID del proveedor no encontrado', [
                'proveedor' => $proveedor
            ]);
            throw new \Exception('No se pudo obtener el ID del proveedor');
        }

        Log::info('UpdateProveedorRequest - ID del proveedor obtenido:', [
            'id' => $proveedor->id
        ]);

        return [
            'nombre' => 'sometimes|string|max:100',
            'ruc' => 'sometimes|string|max:20|unique:proveedores,ruc,'.$proveedor->id,
            'contacto_nombre' => 'sometimes|string|max:100',
            'contacto_telefono' => 'sometimes|string|max:20',
            'contacto_email' => 'sometimes|email|max:100',
            'direccion' => 'sometimes|string',
            'activo' => 'sometimes|boolean'
        ];
    }
}