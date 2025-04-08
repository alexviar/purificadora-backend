<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $permissions = [
                'gestionar usuarios',
                'gestionar plantas purificadoras',
                'gestionar solicitudes de servicio',
                'gestionar compras de insumos',
                'gestionar catálogo de insumos',
                'gestionar videos de capacitación',
                'configurar pasarelas de pago',
                'programar alertas',
                'enviar notificaciones y correos',
                'ver estadísticas',
                'registrar técnicos',
                'asignar solicitudes de servicio',
                'actualizar estado de solicitudes',
                'gestionar pedidos de insumos',
                'actualizar catálogo de insumos',
                'crear plantas purificadoras',
                'ver solicitudes asignadas',
                'registrar mantenimiento con fotos y notas',
                'actualizar estado de mantenimiento',
                'ver historial de servicios',
                'ver plantas asignadas',
                'solicitar servicio de mantenimiento',
                'consultar historial de mantenimientos',
                'ver catálogo y comprar insumos',
                'ver videos de capacitación',
                'recibir alertas de mantenimiento',
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            $roles = [
                'superadmin' => $permissions,
                'admin' => [
                    'registrar técnicos',
                    'asignar solicitudes de servicio',
                    'actualizar estado de solicitudes',
                    'gestionar pedidos de insumos',
                    'actualizar catálogo de insumos',
                    'crear plantas purificadoras',
                ],
                'tecnico' => [
                    'ver solicitudes asignadas',
                    'registrar mantenimiento con fotos y notas',
                    'actualizar estado de mantenimiento',
                    'ver historial de servicios',
                ],
                'cliente' => [
                    'ver plantas asignadas',
                    'solicitar servicio de mantenimiento',
                    'consultar historial de mantenimientos',
                    'ver catálogo y comprar insumos',
                    'ver videos de capacitación',
                    'recibir alertas de mantenimiento',
                ],
            ];

            foreach ($roles as $roleName => $rolePermissions) {
                $role = Role::firstOrCreate(['name' => $roleName]);
                $role->syncPermissions($rolePermissions);
            }
        });
    }
}
