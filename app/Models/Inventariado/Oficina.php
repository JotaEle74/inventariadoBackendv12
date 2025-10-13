<?php

namespace App\Models\Inventariado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
class Oficina extends Model
{
    use HasFactory;
    protected $table = 'oficinas';

    protected $fillable = [
        'codigo',
        'denominacion',
        'escuela'
    ];

    public function area()
    {
        return $this->hasMany(Area::class);
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'oficina_user');
    }
}
