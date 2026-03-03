<?php

namespace App\Models\Inventariado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Acta extends Model
{
    use HasFactory;
    protected $table = 'actas';

    protected $fillable = [
        'numero_acta',
    ];

    /**
     * Generate the next sequential acta number with leading zeros (e.g. 001, 002).
     *
     * @return string
     */
    public static function nextNumero(): string
    {
        $last = self::orderBy('id', 'desc')->first();
        $next = $last ? intval($last->numero_acta) + 1 : 1;
        return str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Always return the acta number padded to three digits when accessing.
     */
    public function getNumeroActaAttribute($value): string
    {
        return str_pad(intval($value), 3, '0', STR_PAD_LEFT);
    }
}
