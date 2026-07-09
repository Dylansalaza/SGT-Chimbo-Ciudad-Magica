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
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
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
