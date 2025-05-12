<?php

return [
    /*
      |--------------------------------------------------------------------------
      | Default Filesystem Disk
      |--------------------------------------------------------------------------
      |
      | Here you may specify the default filesystem disk that should be used
      | by the framework. The "local" disk, as well as a variety of cloud
      | based disks are available to your application. Just store away!
      |
     */

    'default' => env('FILESYSTEM_DRIVER', 'local'),
    /*
      |--------------------------------------------------------------------------
      | Filesystem Disks
      |--------------------------------------------------------------------------
      |
      | Here you may configure as many filesystem "disks" as you wish, and you
      | may even configure multiple disks of the same driver. Defaults have
      | been setup for each driver as an example of the required options.
      |
      | Supported Drivers: "local", "ftp", "sftp", "s3"
      |
     */
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
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
        ],
        'descargas' => [
            'driver' => 'local',
            'root' => storage_path('app/descargas'), // Ruta a la carpeta privada
        ],
        'guias' => [
            'driver' => 'local',
            'root' => storage_path('app/guias'), // Ruta a la carpeta privada
        ],
        'guias_app' => [
            'driver' => 'local',
            //'root' => '/var/www/test.acfarma/guiafabricaciondigital_app/public/pdf'
            'root' => '/var/www/test.acfarma/guiafabricaciondigital_app/storage/app/pdf'
        ],
        'sftp_portal_prov_comex_factura' => [
            'driver' => 'local',
            'root' => '/var/www/Pago_Proveedores_COMEX/FACTURA_LOCAL',
        ],
        'sftp_portal_prov_comex_invoice' => [
            'driver' => 'local',
            'root' => '/var/www/Pago_Proveedores_COMEX/INVOICE',
        ],
        'sftp_imagen_master' => [
            'driver' => 'local',
            'root' => '/var/www/Master/ArchivosImagen',
        ],        
        'sftp_plantilla_master' => [
            'driver' => 'local',
            'root' => '/var/www/Master',
        ],
        'sftp_carta_fianza' => [
            'driver' => 'local',
            'root' => '/var/www/Carta_Fianza',
        ],
        'sftp_contrato' => [
            'driver' => 'local',
            'root' => '/var/www/Contrato_Proceso',
        ],
        'sftp_contrato_sagitario' => [
            'driver' => 'local',
            'root' => '/var/www/Sagitario_Contrato_Proceso',
        ],
        
        'sftp_protocolo' => [
            'driver' => 'local',
            'root' => '/var/www/Sw_Protocolo',
        ],
        'sftp_especificacion' => [
            'driver' => 'local',
            'root' => '/var/www/Sw_Especificacion',
        ],
        'sftp_contrato_oc' => [
            'driver' => 'local',
            'root' => '/var/www/Sw_Contrato_OC',
        ],    
    ],
    /*
      |--------------------------------------------------------------------------
      | Symbolic Links
      |--------------------------------------------------------------------------
      |
      | Here you may configure the symbolic links that will be created when the
      | `storage:link` Artisan command is executed. The array keys should be
      | the locations of the links and the values should be their targets.
      |
     */
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
