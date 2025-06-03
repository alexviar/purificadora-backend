<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\LocalFilesystemAdapter;
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

    protected $appends = [
        'imagen_url',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'float',
            'stock' => 'integer',
            'activo' => 'boolean',
        ];
    }

    #region Attributes

    public function imagenUrl(): Attribute
    {
        /** @var LocalFilesystemAdapter $disk */
        $disk = Storage::disk('public');
        return Attribute::get(fn() => $this->imagen ? $disk->url($this->imagen) : null);
    }

    #endregion
}
