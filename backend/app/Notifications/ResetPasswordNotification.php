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
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Restablecer contraseña — SGT San José de Chimbo')
            ->greeting('Hola, ' . $notifiable->name . '.')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en el **Sistema de Gestión Turística de San José de Chimbo**.')
            ->action('Restablecer mi contraseña', $url)
            ->line('Este enlace expirará en **60 minutos**.')
            ->line('Si no solicitaste restablecer tu contraseña, puedes ignorar este correo — tu cuenta sigue segura.')
            ->salutation('SGT Chimbo — Municipio de San José de Chimbo');
    }
}
