<?php

namespace App\Models\Inventariado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class Movimiento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'movimientos';

    protected $fillable = [
        'codigo',
        'responsable_origen_id',
        'responsable_destino_id',
        'ubicacion_origen_id',
        'ubicacion_destino_id',
        'fecha_movimiento',
        'fecha_entrega',
        'fecha_recepcion',
        'estado',
        'observaciones_entrega',
        'observaciones_recepcion',
        'autorizado_por'
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
        'fecha_entrega' => 'datetime',
        'fecha_recepcion' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($movimiento) {
            $movimiento->codigo = 'MOV-' . strtoupper(Str::random(8));
        });
    }

    public function ubicacionOrigen(): BelongsTo
    {
        return $this->belongsTo(Oficina::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino(): BelongsTo
    {
        return $this->belongsTo(Oficina::class, 'ubicacion_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_origen_id');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_destino_id');
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function movimientosActivos(): HasMany
    {
        return $this->hasMany(MovimientoActivo::class, 'movimiento_id');
    }

    public function puedeSerEntregado(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function puedeSerRecibido(): bool
    {
        return $this->estado === 'en_entrega';
    }

    public function puedeSerRechazado(): bool
    {
        return in_array($this->estado, ['pendiente', 'en_entrega', 'entregado']);
    }

    public function marcarComoEntregado(?string $observaciones = null): void
    {
        Log::info('Entrando a marcarComoEntregado', [
            'id' => $this->id,
            'estado_actual' => $this->estado,
            'observaciones' => $observaciones
        ]);
        if (!$this->puedeSerEntregado()) {
            Log::warning('No puede ser entregado', ['id' => $this->id, 'estado' => $this->estado]);
            throw new \Exception('El movimiento no puede ser entregado en su estado actual');
        }

        $this->update([
            'estado' => 'en_entrega',
            'fecha_entrega' => now(),
            'observaciones_entrega' => $observaciones
        ]);
        Log::info('Update ejecutado en marcarComoEntregado', [
            'id' => $this->id,
            'nuevo_estado' => $this->estado
        ]);

        // Cambiar estado de los movimientos activos relacionados
        foreach ($this->movimientosActivos as $movimientoActivo) {
            $movimientoActivo->marcarComoEntregado($observaciones);
        }
    }

    public function marcarComoRecibido(?string $observaciones = null): void
    {
        if (!$this->puedeSerRecibido()) {
            throw new \Exception('El movimiento no puede ser recibido en su estado actual');
        }

        $this->update([
            'estado' => 'entregado',
            'fecha_recepcion' => now(),
            'observaciones_recepcion' => $observaciones
        ]);

        // Actualizar ubicación de todos los activos
        foreach ($this->movimientosActivos as $movimientoActivo) {
            $movimientoActivo->activo->update([
                'area_id' => $this->ubicacion_destino_id,
                'responsable_id' => $this->responsable_destino_id
            ]);
        }
        foreach ($this->movimientosActivos as $movimientoActivo) {
            $movimientoActivo->marcarComoRecibido($observaciones);
        }
    }

    public function rechazar(?string $observaciones = null): void
    {
        if (!$this->puedeSerRechazado()) {
            throw new \Exception('El movimiento no puede ser rechazado en su estado actual');
        }

        $this->update([
            'estado' => 'rechazado',
            'observaciones_recepcion' => $observaciones
        ]);

        foreach ($this->movimientosActivos as $movimientoActivo) {
            $movimientoActivo->rechazar($observaciones);
        }
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeEntregados($query)
    {
        return $query->where('estado', 'entregado');
    }

    public function scopeRecibidos($query)
    {
        return $query->where('estado', 'recibido');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado', 'rechazado');
    }
} 