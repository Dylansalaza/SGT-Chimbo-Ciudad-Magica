<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación de recuperación de contraseña personalizada para el SGT Chimbo.
 * El correo sale en español con el nombre y la identidad visual del sistema.
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // La URL absoluta al formulario de nueva contraseña (con token + correo).
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        // Minutos de validez del token (config oficial de Laravel), para que el
        // texto del correo coincida siempre con la caducidad real.
        $broker = config('auth.defaults.passwords', 'users');
        $expira = (int) config("auth.passwords.{$broker}.expire", 60);

        // Correo con plantilla HTML de marca (resources/views/emails/reset-password).
        return (new MailMessage)
            ->subject('Restablece tu contraseña — SGT San José de Chimbo')
            ->view('emails.reset-password', [
                'nombre' => $notifiable->name,
                'url'    => $url,
                'expira' => $expira,
            ]);
    }
}
