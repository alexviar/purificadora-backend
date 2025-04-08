<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'planta_id',
        'technician_id',
        'tipo_servicio',
        'fecha_programada',
        'status_id',
        'observaciones_cliente',
        'observaciones_tecnico',
    ];

    public function planta()
    {
        return $this->belongsTo(Plant::class, 'planta_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function status()
    {
        return $this->belongsTo(ServiceRequestStatus::class, 'status_id');
    }
}
