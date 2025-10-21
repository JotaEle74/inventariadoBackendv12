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
        Schema::create('activo_user', function (Blueprint $table) {
            $table->id();
            $table->boolean('report')->default(false);
            $table->string('grupo')->nullable();
            //$table->string('counters')->nullable();
            $table->date('fecha');
            $table->integer('item')->nullable();
            $table->foreignId('activo_id')->constrained('activos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activo_user');
    }
};
