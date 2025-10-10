<?php

namespace App\Models\Inventariado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CatalogoBienes extends Model
{
    use HasFactory;

    protected $table = 'catalogo_bienes';

    protected $fillable = [
        'codigo',
        'denominacion'
    ];
}
