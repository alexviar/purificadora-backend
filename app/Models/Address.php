<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'street',
        'number',
        'colony',
        'city',
        'state',
        'postal_code',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para filtrar direcciones predeterminadas
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Obtener la dirección completa formateada
     */
    public function getFullAddressAttribute()
    {
        $parts = [
            $this->street . ' ' . $this->number,
            $this->colony,
            $this->city . ', ' . $this->state,
            $this->postal_code
        ];
        
        return implode(', ', array_filter($parts));
    }

    /**
     * Obtener la dirección en formato corto
     */
    public function getShortAddressAttribute()
    {
        return $this->street . ' ' . $this->number . ', ' . $this->city;
    }

    /**
     * Establecer esta dirección como predeterminada y quitar
     * ese estado de las demás direcciones del mismo usuario
     */
    public function setAsDefault()
    {
        if (!$this->is_default) {
            // Primero desactivamos todas las direcciones predeterminadas del usuario
            $this->user->addresses()->where('id', '!=', $this->id)
                ->update(['is_default' => false]);
            
            // Establecemos esta como predeterminada
            $this->is_default = true;
            $this->save();
        }
        
        return $this;
    }
}