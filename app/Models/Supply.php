<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Supply extends Model
{
    protected $table = 'supplies';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'imagen',
        'activo',
    ];

    protected $hidden = [
        'imagen'
    ];

    protected $appends = [
        'imagen_url'
    ];

    #region Attributes

    public function imagenUrl(): Attribute
    {
        return Attribute::get(
            fn() => Storage::url($this->imagen)
        );
    }

    #endregion
}
