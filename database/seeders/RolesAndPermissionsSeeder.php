<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos
        $permissions = [
            // Permisos de activos
            ['name' => 'activos.view', 'description' => 'Ver activos'],
            ['name' => 'activos.create', 'description' => 'Crear activos'],
            ['name' => 'activos.edit', 'description' => 'Editar activos'],
            ['name' => 'activos.delete', 'description' => 'Eliminar activos'],
            
            // Permisos de movimientos
            ['name' => 'movimientos.view', 'description' => 'Ver movimientos'],
            ['name' => 'movimientos.create', 'description' => 'Crear movimientos'],
            ['name' => 'movimientos.edit', 'description' => 'Editar movimientos'],
            
            // Permisos de usuarios
            ['name' => 'usuarios.view', 'description' => 'Ver usuarios'],
            ['name' => 'usuarios.create', 'description' => 'Crear usuarios'],
            ['name' => 'usuarios.edit', 'description' => 'Editar usuarios'],
            ['name' => 'usuarios.delete', 'description' => 'Eliminar usuarios'],
            
            // Permisos de roles
            ['name' => 'roles.view', 'description' => 'Ver roles'],
            ['name' => 'roles.create', 'description' => 'Crear roles'],
            ['name' => 'roles.edit', 'description' => 'Editar roles'],
            ['name' => 'roles.delete', 'description' => 'Eliminar roles'],
            
            // Permisos de reportes
            ['name' => 'reportes.view', 'description' => 'Ver reportes'],
            ['name' => 'reportes.export', 'description' => 'Exportar reportes'],
            
            // Permisos de configuración
            ['name' => 'configuracion.view', 'description' => 'Ver configuración'],
            ['name' => 'configuracion.edit', 'description' => 'Editar configuración'],

            ['name' => 'software.view', 'description' => 'Ver software'],
            ['name' => 'software.create', 'description' => 'Crear software'],
            ['name' => 'software.edit', 'description' => 'Editar software'],
            ['name' => 'software.delete', 'description' => 'Eliminar software'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Crear roles
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Administrador del sistema con acceso total',
                'permissions' => [
                    'activos.view', 'activos.create', 'activos.edit', 'activos.delete',
                    'movimientos.view', 'movimientos.create', 'movimientos.edit',
                    'usuarios.view', 'usuarios.create', 'usuarios.edit', 'usuarios.delete',
                    'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                    'reportes.view', 'reportes.export',
                    'configuracion.view', 'configuracion.edit'
                ]
            ],
            [
                'name' => 'supervisor',
                'description' => 'Supervisor con acceso total al sistema',
                'permissions' => [
                    'activos.view', 'activos.create', 'activos.edit', 'activos.delete',
                    'movimientos.view', 'movimientos.create', 'movimientos.edit',
                    'usuarios.view', 'usuarios.create', 'usuarios.edit',
                    'roles.view',
                    'reportes.view', 'reportes.export',
                    'configuracion.view'
                ]
            ],
            [
                'name' => 'responsable_departamento',
                'description' => 'Responsable de departamento que gestiona su inventario',
                'permissions' => [
                    'activos.view', 'activos.create', 'activos.edit',
                    'movimientos.view', 'movimientos.create', 'movimientos.edit',
                    'reportes.view', 'reportes.export', 'configuracion.edit'
                ]
            ],
            [
                'name' => 'usuario_consulta',
                'description' => 'Usuario que solo puede consultar inventario asignado',
                'permissions' => [
                    'activos.view',
                    'movimientos.view',
                    'reportes.view'
                ]
            ],
            [
                'name' => 'inventarista',
                'description' => 'Inventarista con acceso a inventario',
                'permissions' => [
                    'activos.view',
                    'activos.create',
                    'activos.edit',
                    'usuarios.view',
                    'reportes.view',
                    'reportes.export'
                ]
            ],
            [
                'name' => 'software',
                'description' => 'Software con acceso a inventario',
                'permissions' => [
                    'activos.view',
                    'usuarios.view',
                    'reportes.view',
                    'reportes.export',
                    'software.view',
                    'roles.view',
                ]
            ],
            [
                'name' => 'installer',
                'description' => 'Instalar software en activos',
                'permissions' => [
                    'activos.view',
                    'usuarios.view',
                    'resportes.view',
                    'reportes.export',
                    'software.view'
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description']
                ]
            );

            // Asignar permisos al rol
            $permissionIds = Permission::whereIn('name', $roleData['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
} 