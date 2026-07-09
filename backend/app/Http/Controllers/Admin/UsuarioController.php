<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Gestión de usuarios del panel administrativo:
 * crear cuentas, asignar/retirar el rol de administrador y eliminar usuarios.
 */
class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::orderBy('name')->get();
        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('admin.usuarios.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'recovery_email' => 'nullable|email',
            'password'       => 'required|string|min:8|confirmed',
            'rol'            => 'required|in:administrador,admin_turismo',
        ]);

        User::create([
            'name'           => $datos['name'],
            'email'          => $datos['email'],
            'recovery_email' => $datos['recovery_email'] ?? null,
            'password'       => Hash::make($datos['password']),
            'is_admin'       => true,
            'rol'            => $datos['rol'],
        ]);

        return redirect()->route('admin.usuarios.index')
                         ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Agrega o actualiza el correo de recuperación de un usuario.
     */
    public function updateRecovery(Request $request, $id)
    {
        $datos = $request->validate([
            'recovery_email' => 'nullable|email',
        ]);

        $usuario = User::findOrFail($id);
        $usuario->recovery_email = $datos['recovery_email'] ?? null;
        $usuario->save();

        return back()->with('success', 'Correo de recuperación actualizado.');
    }

    /**
     * Activa o desactiva el rol de administrador de un usuario.
     */
    public function toggleAdmin(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        // Evitamos que un admin se quite sus propios permisos por accidente.
        if ($request->user() && $request->user()->id === $usuario->id) {
            return back()->with('error', 'No puedes cambiar tu propio rol de administrador.');
        }

        $usuario->is_admin = ! $usuario->is_admin;
        $usuario->save();

        return back()->with('success', 'Rol de administrador actualizado.');
    }

    public function destroy(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        if ($request->user() && $request->user()->id === $usuario->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete();

        return redirect()->route('admin.usuarios.index')
                         ->with('success', 'Usuario eliminado.');
    }
}
