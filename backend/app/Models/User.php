<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'recovery_email',
        'password',
        'is_admin',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin'          => 'boolean',
    ];

    public function adminRoles()
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_user');
    }

    /**
     * Usa la notificación personalizada en español para el reset de contraseña.
     *
     * $destino permite entregar el correo en la dirección que el usuario
     * escribió (puede ser su 'recovery_email', no solo la principal). Si se
     * omite, Laravel lo manda a la principal como siempre.
     */
    public function sendPasswordResetNotification($token, ?string $destino = null): void
    {
        $this->notify(new ResetPasswordNotification($token, $destino));
    }

    /**
     * A qué dirección se entregan los correos de notificación.
     *
     * Por defecto Laravel usa SIEMPRE $this->email. Para el reset de contraseña
     * eso era un bug: si el usuario pedía el enlace desde su correo de
     * recuperación, el correo se iba igual al principal (posiblemente uno al
     * que ya no tiene acceso, que es justo el motivo de tener un alternativo).
     * Aquí respetamos la dirección que trae la notificación de reset.
     */
    public function routeNotificationForMail($notification = null): string
    {
        if ($notification instanceof ResetPasswordNotification && $notification->destino()) {
            return $notification->destino();
        }

        return $this->email;
    }

    /**
     * ¿Es administrador de sistema? (gestiona usuarios)
     */
    public function isAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    /**
     * ¿Es administrador de turismo? (gestiona contenido)
     */
    public function isAdminTurismo(): bool
    {
        return $this->rol === 'admin_turismo';
    }

    /**
     * ¿Tiene acceso al panel admin? Cualquiera de los dos roles lo tiene.
     * También acepta el flag legacy `is_admin`.
     */
    public function isAdmin(): bool
    {
        return in_array($this->rol, ['administrador', 'admin_turismo'])
            || (bool) $this->is_admin
            || $this->adminRoles()->where('name', 'admin')->exists();
    }
}
