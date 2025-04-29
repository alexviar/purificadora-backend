<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Cashier\Billable;
/**
 * @method bool hasRole(string|array $roles, string|null $guard = null)
 * @method bool hasAnyRole(string|array $roles, string|null $guard = null)
 * @method bool hasAllRoles(string|array $roles, string|null $guard = null)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Billable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    /**
     * Los videos de capacitación asignados a este usuario
     */
    public function trainingVideos()
    {
        return $this->belongsToMany(TrainingVideo::class, 'user_training_videos')
                    ->withTimestamps();
    }
    
    /**
     * Relación con la tabla pivote
     */
    public function userTrainingVideos()
    {
        return $this->hasMany(UserTrainingVideo::class);
    }
    
    /**
     * Las direcciones asociadas a este usuario
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
    
    /**
     * Obtener la dirección predeterminada del usuario
     */
    public function defaultAddress()
    {
        return $this->addresses()->where('is_default', true)->first();
    }
}