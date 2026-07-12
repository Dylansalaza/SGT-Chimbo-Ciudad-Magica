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

        // MENSAJE NEUTRO SIEMPRE: no revelamos si el correo está registrado o no.
        // Esto evita la enumeración de cuentas (un atacante no puede usar este
        // formulario para averiguar qué correos de funcionarios existen). El
        // token nunca se muestra en pantalla: el reset solo se hace con el
        // enlace que llega al correo.
        $mensajeNeutro = 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña. Revisa tu bandeja de entrada y la carpeta de spam.';

        // Buscamos por el correo principal o por el correo de recuperación.
        $user = User::where('email', $request->email)
            ->orWhere('recovery_email', $request->email)
            ->first();

        // Si no existe, respondemos IGUAL que si existiera (sin delatar la ausencia).
        if (! $user) {
            return back()->with('status', $mensajeNeutro);
        }

        // Generamos el token con el broker oficial (se guarda en
        // password_reset_tokens) y enviamos el enlace por correo.
        $token = Password::broker()->createToken($user);

        try {
            $user->sendPasswordResetNotification($token);
        } catch (\Throwable $e) {
            // El fallo de SMTP se registra para diagnóstico del administrador,
            // pero al cliente le damos el MISMO mensaje neutro para no filtrar
            // que la cuenta existe.
            Log::error('No se pudo enviar el correo de recuperación de contraseña: ' . $e->getMessage());
        }

        return back()->with('status', $mensajeNeutro);
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
