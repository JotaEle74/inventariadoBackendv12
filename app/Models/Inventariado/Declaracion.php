<?php

namespace App\Models\Inventariado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Declaracion extends Model
{
    use HasFactory;
    protected $table = "declaraciones";
    protected $fillable = [
        'user_id',
        // 'tipo', // Este campo no existe en la migración, podría causar errores.
        'fecha_declaracion',
        'observaciones',
        'ito',
        'codigo',
        'numero_folios'
    ];

    protected $casts=[
        'fecha_declaracion' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function activos()
    {
        return $this->belongsToMany(Activo::class, 'activo_declaracion', 'declaracion_id', 'activo_id')
            ->withPivot(['id', 'area_edificio', 'oficina_denominacion', 'entidad_denominacion', 'area_codigo', 'estado', 'condicion', 'ubicacion', 'descripcion']);
    }
}
