<?php

namespace App\Models\Inventariado;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Software extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'software';

    protected $fillable = [
        'codigo', 'tipo', 'nombre', 'descripcion', 'responsable_id', 'area_id',
        'estado', 'notas', 'url_acceso', 'tecnologias', 'ubicacion_servidor',
        'clave_licencia', 'tipo_licencia', 'cantidad_puestos', 'fecha_compra',
        'fecha_vencimiento', 'plataforma', 'url_perfil', 'correo_institucional'
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($software) {
            $prefix = match ($software->tipo) {
                'desarrollo_interno' => 'SIS-',
                'licencia_terceros' => 'LIC-',
                'red_social' => 'RED-',
                default => 'SOFT-'
            };
            $software->codigo = $prefix . strtoupper(Str::random(8));
        });
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Los activos físicos (PCs) a los que esta licencia de software está asignada.
     */
    public function activosAsignados(): BelongsToMany
    {
        return $this->belongsToMany(Activo::class, 'activo_software', 'software_id', 'activo_id')
            ->withPivot('fecha_asignacion', 'notas')
            ->withTimestamps();
    }
}
