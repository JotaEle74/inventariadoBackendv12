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
        Schema::create('activos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('denominacion');
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('color')->nullable();
            $table->string('numero_serie')->nullable();
            $table->string('dimension')->nullable();
            $table->string('aula')->nullable();
            $table->date('fecha_adquisicion');
            $table->decimal('valor_inicial', 12, 2)->nullable();
            $table->enum('estado', ['A', 'I'])->default('A');
            $table->enum('condicion', ['N', 'B', 'R', 'M'])->default('N'); // Nuevo, Bueno, Regular, Malo
            $table->text('descripcion')->nullable();
            $table->string('declaracion')->nullable();
            $table->string('piso')->nullable();
            //$table->foreignId('catalogo_id')->nullable()->constrained('catalogo_bienes');
            $table->foreignId('area_id')->nullable()->constrained('areas');
            $table->foreignId('responsable_id')->nullable()->constrained('users');
            $table->string('dniInventariador')->nullable();
            $table->string('nombreInventariador')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activos');
    }
};
