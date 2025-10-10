<?php

namespace App\Models\Inventariado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Ito extends Model
{
    use HasFactory;
    protected $table = 'itos';

    protected $fillable = [
        'codigo',
        'estado'
    ];
}
