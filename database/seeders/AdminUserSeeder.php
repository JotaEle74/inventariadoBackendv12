<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@universidad.edu'],
            [
                'name' => 'Administrador del Sistema',
                'dni' => '12345678',
                'email' => 'admin@universidad.edu',
                'password' => Hash::make('Admin123!'),
                'telefono' => '123456789',
                'email_verified_at' => now(),
            ]
        );

        // Asignar rol de administrador
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminUser->update(['role_id' => $adminRole->id]);
        }

        $this->command->info('Usuario administrador creado exitosamente!');
        $this->command->info('Email: admin@universidad.edu');
        $this->command->info('Contraseña: Admin123!');
    }
} 