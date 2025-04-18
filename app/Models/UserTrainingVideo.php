<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTrainingVideo extends Model
{
    protected $table = 'user_training_videos';

    protected $fillable = [
        'user_id',
        'training_video_id'
    ];

    /**
     * Obtener el usuario al que pertenece esta asignación
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el video de capacitación asociado a esta asignación
     */
    public function trainingVideo()
    {
        return $this->belongsTo(TrainingVideo::class);
    }
}
