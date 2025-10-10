<?php

namespace App\Models\Inventariado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class MovimientoActivo extends Model
{
    use HasFactory;

    protected $table = 'movimiento_activos';

    protected $fillable = [
        'movimiento_id',
        'activo_id',
        'ubicacion_origen_id',
        'ubicacion_destino_id',
        'observaciones',
        'estado'
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
        'fecha_entrega' => 'datetime',
        'fecha_recepcion' => 'datetime'
    ];

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    public function activo(): BelongsTo
    {
        return $this->belongsTo(Activo::class, 'activo_id');
    }

    public function ubicacionOrigen(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'ubicacion_destino_id');
    }

    public function puedeSerEntregado(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function puedeSerRecibido(): bool
    {
        return $this->estado === 'entregado';
    }

    public function puedeSerRechazado(): bool
    {
        return in_array($this->estado, ['pendiente', 'entregado']);
    }

    public function marcarComoEntregado(?string $observaciones = null): void
    {
        //if (!$this->puedeSerEntregado()) {
        //    throw new \Exception('El movimiento de activo no puede ser entregado en su estado actual here');
        //}

        $this->update([
            'estado' => 'entregado'
        ]);
    }

    public function marcarComoRecibido(?string $observaciones = null): void
    {
        //if (!$this->puedeSerRecibido()) {
        //    throw new \Exception('El movimiento de activo no puede ser recibido en su estado actual');
        //}

        $this->update([
            'estado' => 'recibido'
        ]);

        // Actualizar la ubicación del activo
        $this->activo->update([
            'area_id' => $this->ubicacion_destino_id
        ]);
    }

    public function rechazar(?string $observaciones = null): void
    {
        //if (!$this->puedeSerRechazado()) {
        //    throw new \Exception('El movimiento de activo no puede ser rechazado en su estado actual');
        //}

        $this->update([
            'estado' => 'rechazado'
        ]);
    }

    public function getEstadoAttribute(): string
    {
        return $this->movimiento->estado;
    }

    public function getFechaMovimientoAttribute()
    {
        return $this->movimiento->fecha_movimiento;
    }

    public function getFechaEntregaAttribute()
    {
        return $this->movimiento->fecha_entrega;
    }

    public function getFechaRecepcionAttribute()
    {
        return $this->movimiento->fecha_recepcion;
    }

    public function getUsuarioAttribute()
    {
        return $this->movimiento->usuario;
    }

    public function getReceptorAttribute()
    {
        return $this->movimiento->receptor;
    }

    public function getAutorizadoPorAttribute()
    {
        return $this->movimiento->autorizadoPor;
    }
} 