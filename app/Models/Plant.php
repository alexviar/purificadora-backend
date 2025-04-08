<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    use HasFactory;

    protected $table = 'plants';

    protected $fillable = [
        'user_id',
        'nombre',
        'direccion_instalacion',
        'paquete_instalado',
        'fecha_instalacion',
    ];

    public function servicios()
    {
        return $this->hasMany(Service::class, 'planta_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
