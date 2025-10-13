<?php

namespace App\Models\Inventariado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
class Activo extends Model
{
    use HasFactory;
    protected $table = 'activos';
    protected $fillable = [
        'codigo',
        'denominacion',
        'tipo',
        'marca',
        'modelo',
        'color',
        'numero_serie',
        'dimension',
        'aula',
        'fecha_adquisicion',
        'valor_inicial',
        'estado',
        'condicion',
        'descripcion',
        //'catalogo_id',
        'area_id',
        'piso',
        'responsable_id',
        //'notas',
        'edificio_id',
        'declaracion',
        'dniInventariador',
        'nombreInventariador',
    ];

    protected $casts = [
        'fecha_adquisicion' => 'date',
        'valor_inicial' => 'decimal:2',
        'estado' => 'string',
        'condicion' => 'string'
    ];

    public function setEstadoAttribute($value)
    {
        $estados = [
            'activo' => 'A',
            'inactivo' => 'I'
        ];
        $this->attributes['estado'] = $estados[strtolower($value)] ?? 'A';
    }

    public function getEstadoAttribute($value)
    {
        $estados = [
            'A' => 'activo',
            'I' => 'inactivo'
        ];
        return $estados[$value] ?? 'activo';
    }

    public function setCondicionAttribute($value)
    {
        $condiciones = [
            'nuevo' => 'N',
            'bueno' => 'B',
            'regular' => 'R',
            'malo' => 'M'
        ];
        $this->attributes['condicion'] = $condiciones[strtolower($value)] ?? 'N';
    }

    public function getCondicionAttribute($value)
    {
        $condiciones = [
            'N' => 'nuevo',
            'B' => 'bueno',
            'R' => 'regular',
            'M' => 'malo',
        ];
        return $condiciones[$value] ?? 'nuevo';
    }

    //public function catalogo()
    //{
    //    return $this->belongsTo(CatalogoBienes::class, 'catalogo_id');
    //}

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoActivo::class);
    }

    public function declaracionesUso()
    {
        return $this->belongsToMany(Declaracion::class, 'activo_declaracion', 'activo_id', 'declaracion_id');
    }
    public function edificio()
    {
        return $this->belongsTo(Edificio::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'activo_user')->withPivot('fecha', 'report');
    }
}
