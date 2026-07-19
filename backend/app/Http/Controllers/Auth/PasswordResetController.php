<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Recuperación de contraseña.
 *
 * Estructura del flujo:
 *   1. GET  /forgot-password         → formulario para escribir el correo.
 *   2. POST /forgot-password         → si el correo existe, genera un token y
 *                                      lo envía por correo (avisa si NO existe,
 *                                      ver nota de seguridad en sendResetLink).
 *   3. GET  /reset-password/{token}  → formulario de nueva contraseña.
 *   4. POST /reset-password          → valida el token y guarda la contraseña.
 */
class PasswordResetController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // ⚠️ DECISIÓN EXPLÍCITA DEL ADMINISTRADOR (2026-07-13): este endpoint
        // SÍ revela si el correo existe o no ("correo inexistente" vs. "te
        // enviamos el enlace"). Esto es a propósito MENOS seguro que un
        // mensaje neutro: en general, decir si un correo existe permite a un
        // atacante enumerar cuentas válidas probando direcciones una por una.
        // Se acepta el riesgo porque este panel tiene muy pocas cuentas (solo
        // funcionarios del GAD, no registro público). Si el panel llegara a
        // abrirse a usuarios externos, esto debe revertirse a un mensaje
        // neutro (ver historial de este archivo / memoria del proyecto).

        // Buscamos por el correo principal o por el correo de recuperación.
        $user = User::where('email', $request->email)
            ->orWhere('recovery_email', $request->email)
            ->first();

        if (! $user) {
            return back()->with('error', 'No existe ninguna cuenta registrada con ese correo.');
        }

        // Generamos el token con el broker oficial (se guarda en
        // password_reset_tokens) y enviamos el enlace por correo. El enlace
        // en sí NUNCA se muestra en pantalla: el reset solo se hace con lo
        // que llega al correo.
        $token = Password::broker()->createToken($user);

        // El correo se ENTREGA en la dirección que el usuario escribió (puede
        // ser su correo de recuperación); antes se iba siempre al principal.
        // El enlace, en cambio, lleva el correo PRINCIPAL, porque el token del
        // broker se indexa por él y Password::reset() solo lo valida contra ese
        // (con el alternativo daría "usuario no encontrado").
        try {
            $user->sendPasswordResetNotification($token, $request->email);
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de recuperación de contraseña: ' . $e->getMessage());

            return back()->with('error', 'La cuenta existe, pero no pudimos enviar el correo. Intenta de nuevo en unos minutos o contacta al administrador.');
        }

        return back()->with('status', 'Te enviamos un enlace para restablecer tu contraseña. Revisa tu bandeja de entrada y la carpeta de spam.');
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Tu contraseña fue actualizada. Ya puedes iniciar sesión.')
            : back()->withInput()->with('error', __($status));
    }
}
