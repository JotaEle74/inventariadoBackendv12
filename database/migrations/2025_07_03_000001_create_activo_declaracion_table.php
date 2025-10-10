<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activo_declaracion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_id')->constrained('activos')->onDelete('cascade');
            $table->foreignId('declaracion_id')->constrained('declaraciones')->onDelete('cascade');
            $table->string('area_edificio')->nullable();
            $table->string('area_codigo')->nullable();
            $table->string('oficina_denominacion')->nullable();
            $table->string('entidad_denominacion')->nullable();
            $table->string('estado')->nullable();
            $table->string('condicion')->nullable();
            $table->string('ubicacion')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('activo_declaracion');
    }
}; 