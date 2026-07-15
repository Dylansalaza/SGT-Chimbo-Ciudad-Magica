<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación de recuperación de contraseña personalizada para el SGT Chimbo.
 * El correo sale en español con el nombre y la identidad visual del sistema.
 *
 * Sobre $destino: el usuario puede pedir el restablecimiento desde su correo
 * principal O desde su correo de recuperación (ver PasswordResetController).
 * El correo debe llegar a la dirección que ESCRIBIÓ, no siempre a la principal,
 * así que se guarda aquí y User::routeNotificationForMail() la lee. Si es null
 * se cae al correo principal (comportamiento por defecto de Laravel).
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private string $token, private ?string $destino = null)
    {
    }

    /**
     * Dirección a la que debe ENTREGARSE este correo (null = la principal).
     * La usa User::routeNotificationForMail().
     */
    public function destino(): ?string
    {
        return $this->destino;
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
