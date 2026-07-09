<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;  // <--- Agrega esta línea

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Usuario creado'], 201);
    }

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
        // 🛡️ BLINDAJE: Verificamos que el usuario esté autenticado y tenga un token activo
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Sesión cerrada correctamente']);
        }

        // Si llegó aquí sin token o la sesión ya expiró, respondemos con éxito para no trabar a React
        return response()->json(['message' => 'No había token activo o la sesión ya expiró'], 200);
    }
}