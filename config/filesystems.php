<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Aquí se especifica el disco que usará la aplicación para almacenar archivos.
    | Se ha cambiado el valor por defecto a "public" para que, en caso de no
    | especificar un disco al almacenar, se guarden los archivos en la carpeta pública.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar los discos disponibles para el almacenamiento.
    | El disco "public" está configurado para almacenar archivos en storage/app/public,
    | y se accede a ellos mediante la URL definida.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Estos enlaces simbólicos se crean cuando ejecutas el comando Artisan
    | "php artisan storage:link". Asegúrate de que la clave sea la ubicación del
    | enlace y el valor sea su destino.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
