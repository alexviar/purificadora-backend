<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $table = 'alerts';

    protected $fillable = [
        'titulo',
        'mensaje',
        'fecha_envio',
        'user_id',
        'status_id',
    ];

    protected $dates = [
        'fecha_envio',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->belongsTo(AlertStatus::class, 'status_id');
    }
}
