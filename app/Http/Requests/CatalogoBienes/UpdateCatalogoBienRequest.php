<?php
namespace App\Http\Requests\CatalogoBienes;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCatalogoBienRequest extends StoreCatalogoBienRequest
{
    public function rules()
    {
        return [
            'codigo' => 'sometimes|string|max:50|unique:catalogo_bienes,codigo,' . $this->route('catalogo')->id,
            'denominacion' => 'sometimes|string|max:255|string'
        ];
    }
}