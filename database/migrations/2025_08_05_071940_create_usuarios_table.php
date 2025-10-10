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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('dni')->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('telefono')->nullable();
            $table->foreignId('role_id')->nullable()->constrained('roles');
            $table->rememberToken();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE usuarios AUTO_INCREMENT = 15000');
    }
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
