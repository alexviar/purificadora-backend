<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $table = 'service_requests';

    protected $fillable = [
        'planta_id',
        'cliente_id',
        'tecnico_id',
        'status_id',
        'tipo_servicio',
        'fecha_programada',
        'comentarios',
        'comentarios_tecnico',
        'fotos_antes',
        'fotos_despues'
    ];

    protected $casts = [
        'fecha_programada' => 'datetime',
        'fotos_antes' => 'array',
        'fotos_despues' => 'array'
    ];

    public function planta()
    {
        return $this->belongsTo(Plant::class, 'planta_id');
    }

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function status()
    {
        return $this->belongsTo(ServiceRequestStatus::class, 'status_id');
    }
}
