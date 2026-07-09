<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Recuperación de contraseña.
 *
 * Estructura del flujo:
 *   1. GET  /forgot-password         → formulario para escribir el correo.
 *   2. POST /forgot-password         → genera un token, intenta enviar el correo
 *                                      y SIEMPRE muestra el enlace en pantalla
 *                                      (así funciona aunque no haya SMTP configurado).
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

        // Buscamos por el correo principal o por el correo de recuperación.
        $user = User::where('email', $request->email)
            ->orWhere('recovery_email', $request->email)
            ->first();

        // Por seguridad no revelamos si el correo existe o no, salvo para mostrar
        // el enlace local (entorno de pruebas / municipal sin servidor de correo).
        if (! $user) {
            return back()->withInput()->with('error', 'No existe ninguna cuenta con ese correo.');
        }

        // Generamos el token con el broker oficial (se guarda en password_reset_tokens).
        $token = Password::broker()->createToken($user);
        $url   = route('password.reset', ['token' => $token, 'email' => $user->email]);

        // Intento de envío por correo (si MAIL está configurado). No bloquea el flujo.
        try {
            $user->sendPasswordResetNotification($token);
            $enviado = true;
        } catch (\Throwable $e) {
            $enviado = false;
        }

        return back()
            ->with('reset_link', $url)
            ->with('reset_email', $user->email)
            ->with('reset_sent', $enviado);
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
