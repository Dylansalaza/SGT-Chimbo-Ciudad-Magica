<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Verifica que el envío de correos funciona.
 *   php artisan mail:test correo-destino@ejemplo.com
 */
class TestMail extends Command
{
    protected $signature = 'mail:test {destino}';
    protected $description = 'Envía un correo de prueba para verificar la configuración SMTP';

    public function handle(): int
    {
        $destino = $this->argument('destino');
        $this->info("Enviando correo de prueba a: {$destino} ...");
        $this->line('Mailer: ' . config('mail.default') . ' · Host: ' . config('mail.mailers.smtp.host'));

        try {
            Mail::raw(
                "✅ ¡Funciona! Este es un correo de prueba del Sistema de Gestión Turística de San José de Chimbo.\n\nSi recibes esto, la recuperación de contraseñas también enviará correos correctamente.",
                function ($m) use ($destino) {
                    $m->to($destino)->subject('Prueba de correo — SGT Chimbo');
                }
            );
            $this->info('✅ Correo enviado sin errores. Revisa la bandeja de entrada (y SPAM) de ' . $destino);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Falló el envío: ' . $e->getMessage());
            $this->warn('Revisa MAIL_USERNAME / MAIL_PASSWORD (contraseña de aplicación) en el archivo .env.');
            return self::FAILURE;
        }
    }
}
