<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeclaracionUso extends Model
{
    use HasFactory;

    protected $table = 'declaraciones';

    protected $fillable = [
        'codigo',
        'user_id',
        'activo_id',
        'aceptado_en',
        'metodo_aceptacion',
        'observaciones',
        'numero_folios'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activo()
    {
        return $this->belongsTo(\App\Models\Inventariado\Activo::class);
    }
} 