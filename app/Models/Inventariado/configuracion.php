<?php

namespace App\Models\Inventariado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Configuracion extends Model
{
    use HasFactory;
    protected $table = 'configuraciones';

    protected $fillable = [
        'clave',
        'mostrar_botones'
    ];

    protected $casts = [
        'mostrar_botones' => 'boolean',
    ];
}
