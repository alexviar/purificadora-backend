<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications'; // ya no es 'notificaciones'

    protected $fillable = [
        'user_id',
        'mensaje',
        'fecha_leido',
    ];

    protected $dates = [
        'fecha_leido',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
