<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

/**
 * Autenticación de la aplicación. Cubre dos flujos con el MISMO endpoint /login:
 *   - Web (formulario Blade del panel): inicia sesión de servidor y redirige.
 *   - SPA (React público): emite un token Sanctum ('access_token').
 *
 * NOTA: no existe registro público de usuarios a propósito. Las cuentas de
 * funcionarios se crean únicamente desde el panel admin (Admin\UsuarioController),
 * donde se asigna el rol correspondiente. Cualquier alta sin rol sería un riesgo
 * de escalada de privilegios, por eso no se expone un register() aquí.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // La API de React consume JSON: nunca debemos devolver un redirect web.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Credenciales incorrectas',
                ], 401);
            }

            return back()->with('error', 'Credenciales incorrectas');
        }

        $isAdmin = $user->isAdmin();

        // Flujo web tradicional (formulario Blade): inicia sesión y redirige.
        if (!$request->expectsJson()) {
            Auth::login($user);

            return $isAdmin
                ? redirect()->route('admin.dashboard')
                : redirect()->intended('/');
        }

        // Flujo SPA (React): emite un token Sanctum.
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'is_admin' => $isAdmin,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        // Flujo SPA (React): si la petición trae un token Sanctum, lo revocamos.
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Sesión cerrada correctamente']);
        }

        // Flujo panel admin: cierre de la SESIÓN WEB. El panel se autentica por
        // sesión de servidor (Auth::login en el login Blade), NO por token; por
        // eso hay que cerrar el guard web e invalidar la sesión. Antes esto no
        // se hacía y el logout "no cerraba" realmente la sesión del panel.
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Formulario del panel (navegación normal): redirige al login.
            if (! $request->expectsJson()) {
                return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
            }
        }

        // Sin token ni sesión activa: respondemos OK para no trabar al cliente.
        return $request->expectsJson()
            ? response()->json(['message' => 'No había sesión activa.'], 200)
            : redirect()->route('login');
    }
}