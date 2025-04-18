<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingVideo extends Model
{
    protected $table = 'training_videos';

    protected $fillable = [
        'titulo',
        'descripcion',
        'url'
    ];
    
    /**
     * Los usuarios a los que se ha asignado este video
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_training_videos')
                    ->withTimestamps();
    }
    
    /**
     * Relación con la tabla pivote
     */
    public function userVideos()
    {
        return $this->hasMany(UserTrainingVideo::class);
    }
}
