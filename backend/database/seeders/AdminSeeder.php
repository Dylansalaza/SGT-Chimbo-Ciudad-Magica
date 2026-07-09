<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AdminRole;
use Illuminate\Support\Facades\Hash;

/**
 * Crea los dos usuarios administradores del sistema SGT Chimbo.
 *
 * ┌─────────────────────────────┬─────────────────────────┬──────────────────────┐
 * │ Rol                         │ Email                   │ Contraseña           │
 * ├─────────────────────────────┼─────────────────────────┼──────────────────────┤
 * │ administrador               │ admin@chimbo.gob.ec     │ Admin#2026           │
 * │ admin_turismo               │ turismo@chimbo.gob.ec   │ Turismo#2026         │
 * └─────────────────────────────┴─────────────────────────┴──────────────────────┘
 *
 * El administrador solo puede ver el Dashboard y gestionar Usuarios.
 * El admin de turismo puede gestionar todo el contenido turístico.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Rol legacy (tabla pivot) — se conserva por compatibilidad
        $adminRole = AdminRole::firstOrCreate(['name' => 'admin']);

        // ── 1. Administrador del sistema (gestiona usuarios) ──────────────
        $administrador = User::updateOrCreate(
            ['email' => 'administrador.sgt@gmail.com'],
            [
                'name'     => 'Administrador SGT',
                'password' => Hash::make('Admin#2026'),
                'is_admin' => true,
                'rol'      => 'administrador',
            ]
        );
        $administrador->adminRoles()->syncWithoutDetaching([$adminRole->id]);

        // ── 2. Admin de turismo (gestiona todo el contenido) ──────────────
        $turismo = User::updateOrCreate(
            ['email' => 'turismo.sgt@gmail.com'],
            [
                'name'     => 'Admin Turismo SGT',
                'password' => Hash::make('Turismo#2026'),
                'is_admin' => true,
                'rol'      => 'admin_turismo',
            ]
        );
        $turismo->adminRoles()->syncWithoutDetaching([$adminRole->id]);

        $this->command->info('✅ Usuarios creados:');
        $this->command->info('   ADMINISTRADOR → administrador.sgt@gmail.com / Admin#2026');
        $this->command->info('   ADMIN TURISMO → turismo.sgt@gmail.com      / Turismo#2026');
    }
}
