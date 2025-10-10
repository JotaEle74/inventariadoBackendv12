<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('responsable_origen_id')->nullable()->constrained('users');
            $table->foreignId('responsable_destino_id')->constrained('users');
            $table->foreignId('ubicacion_origen_id')->nullable()->constrained('oficinas');
            $table->foreignId('ubicacion_destino_id')->constrained('oficinas');
            $table->dateTime('fecha_movimiento');
            $table->dateTime('fecha_entrega')->nullable();
            $table->dateTime('fecha_recepcion')->nullable();
            $table->text('motivo')->nullable();
            $table->text('observaciones_entrega')->nullable();
            $table->text('observaciones_recepcion')->nullable();
            $table->enum('estado', ['pendiente', 'en_entrega', 'entregado', 'rechazado', 'completado'])->default('pendiente');
            $table->foreignId('autorizado_por')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('movimiento_activos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->constrained('movimientos')->onDelete('cascade');
            $table->foreignId('activo_id')->constrained('activos');
            $table->foreignId('ubicacion_origen_id')->nullable()->constrained('areas');
            $table->foreignId('ubicacion_destino_id')->constrained('areas');
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['pendiente', 'entregado', 'recibido', 'rechazado'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_activos');
        Schema::dropIfExists('movimientos');
    }
};
