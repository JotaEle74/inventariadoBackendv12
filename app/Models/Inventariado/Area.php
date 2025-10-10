<?php

namespace App\Models\Inventariado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Area extends Model
{
    use HasFactory;
    protected $table = 'areas';

    protected $fillable = [
        'edificio',
        'codigo',
        'aula',
        'oficina_id'
    ];
    
    public function oficina()
    {
        return $this->belongsTo(Oficina::class);
    }

    public function activos()
    {
        return $this->hasMany(Activo::class, 'area_id');
    }

    public function movimientosActivos()
    {
        return $this->hasMany(MovimientosActivos::class, 'oficina_id');
    }
}
