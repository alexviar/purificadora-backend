<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->setupRolesAndPermissions();
        $this->setupSuperadmin();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    protected function setupRolesAndPermissions()
    {
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
    }

    protected function setupSuperadmin()
    {
        /** @var User $admin */
        $admin = User::create([
            'name' => 'Admin',
            'email' => config('app.superadmin_credentials.email'),
            'password' => config('app.superadmin_credentials.password')
        ]);
        $admin->syncRoles('superadmin');
    }
};
