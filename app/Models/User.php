<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Inventariado\Oficina;
use App\Models\Inventariado\Activo;
use App\Models\Inventariado\MovimientoActivo;
use App\Models\Role;
use App\Models\ActiveSession;
use App\Notifications\VerifyEmailNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'dni',
        'email',
        'password',
        'telefono',
        'oficina_id',
        'role_id',
        'grupo',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function oficinas()
    {
        return $this->belongsToMany(\App\Models\Inventariado\Oficina::class, 'oficina_user');
    }

    public function activosResponsable()
    {
        return $this->hasMany(Activo::class, 'responsable_id');
    }

    public function activeSessions(): HasMany
    {
        return $this->hasMany(ActiveSession::class);
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole($role)
    {
        return $this->role && $this->role->name === $role;
    }

    public function hasPermission($permission)
    {
        return $this->role && $this->role->permissions->contains('name', $permission);
    }

    public function movimientosAutorizados()
    {
        return $this->hasMany(MovimientoActivo::class, 'autorizado_por');
    }

    public function movimientosComoResponsableOrigen()
    {
        return $this->hasMany(MovimientoActivo::class, 'responsable_origen_id');
    }

    public function movimientosComoResponsableDestino()
    {
        return $this->hasMany(MovimientoActivo::class, 'responsable_destino_id');
    }

    public function declaracionesUso()
    {
        return $this->hasMany(\App\Models\DeclaracionUso::class);
    }
    public function activos()
    {
        return $this->belongsToMany(Activo::class)->withPivot('fecha', 'report', 'grupo');
    }
}