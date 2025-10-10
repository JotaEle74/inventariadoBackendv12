<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $dni = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        User::factory()->create([
            'name' => 'Test User',
            'dni' => $dni,
            'email' => 'test@example.com',
            'password' => bcrypt($dni)
        ]);

        // Ejecutar seeder de roles y permisos
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
