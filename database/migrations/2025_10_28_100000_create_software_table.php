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
        Schema::create('software', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->enum('tipo', ['desarrollo_interno', 'licencia_terceros', 'red_social']);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            
            $table->foreignId('responsable_id')->nullable()->constrained('users');
            $table->foreignId('area_id')->constrained('areas');
            
            $table->string('estado', 50); // activo, inactivo, en_desarrollo, vencido
            $table->text('notas')->nullable();

            // Campos para 'desarrollo_interno'
            $table->string('url_acceso')->nullable();
            $table->string('tecnologias')->nullable();
            $table->string('ubicacion_servidor')->nullable();

            // Campos para 'licencia_terceros'
            $table->text('clave_licencia')->nullable();
            $table->string('tipo_licencia', 50)->nullable(); // perpetua, suscripcion, volumen
            $table->integer('cantidad_puestos')->nullable();
            $table->date('fecha_compra')->nullable();
            $table->date('fecha_vencimiento')->nullable();

            // Campos para 'red_social'
            $table->string('plataforma', 100)->nullable();
            $table->string('url_perfil')->nullable();
            $table->string('correo_institucional')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software');
    }
};
